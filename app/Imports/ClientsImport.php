<?php

namespace App\Imports;

use App\Models\Client;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ClientsImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{
    public $errors = [];
    public $imported = 0;
    public $duplicates_found = 0;

    protected $requiredHeaders = ['company_name', 'email', 'phone_number'];

    // Keeps track of seen signatures across chunks
    protected $seenPrimaryId = []; // signature → id of first record

    protected $cacheKey;

    public function __construct($cacheKey)
    {
        // Unique cache key for this import
        $this->cacheKey = $cacheKey;
        Cache::put($this->cacheKey, [], now()->addHours(2)); // initialize empty errors array
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->storeError(['header' => 'File (or chunk) is empty']);
            return;
        }

        // Check headers
        $headers = array_keys($rows->first()->toArray());
        $missing = array_diff($this->requiredHeaders, $headers);
        if (!empty($missing)) {
            $this->storeError(['header' => 'Missing required headers: ' . implode(', ', $missing)]);
            return;
        }

        $now = Carbon::now();

        // 1. Build counts of signatures in this chunk
        $chunkSignatures = [];
        $rowsData = [];

        foreach ($rows as $idx => $row) {
            $company = strtolower(trim($row['company_name'] ?? ''));
            $email = strtolower(trim($row['email'] ?? ''));
            $phone = preg_replace('/\D+/', '', trim($row['phone_number'] ?? ''));
            $sig = "{$company}||{$email}||{$phone}";

            $chunkSignatures[$sig] = ($chunkSignatures[$sig] ?? 0) + 1;

            $rowsData[] = [
                'line' => $idx + 2,
                'company_name' => $company,
                'email' => $email,
                'phone_number' => $phone,
                'signature' => $sig,
            ];
        }

        // 2. Prefetch existing clients by email or phone
        $emails = $rows->pluck('email')->filter()->map(fn($v) => strtolower(trim($v)))->unique();
        $phones = $rows->pluck('phone_number')->filter()->map(fn($v) => preg_replace('/\D+/', '', trim($v)))->unique();

        $existingClients = Client::whereIn('email', $emails)
            ->orWhereIn('phone_number', $phones)
            ->get(['id', 'company_name', 'email', 'phone_number'])
            ->mapWithKeys(fn($c) => [
                strtolower(trim($c->company_name))
                    . '||' . strtolower(trim($c->email))
                    . '||' . preg_replace('/\D+/', '', trim($c->phone_number))
                => $c->id
            ]);

        $batchInsert = [];
        $duplicatesToUpdate = [];

        foreach ($rowsData as $row) {
            $validator = Validator::make([
                'company_name' => $row['company_name'],
                'email' => $row['email'],
                'phone_number' => $row['phone_number'],
            ], [
                'company_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone_number' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                $this->storeError([
                    'line' => $idx + 2,
                    'row' => $row,
                    'errors' => $validator->errors()->all(),
                ]);
                continue; // skip invalid row
            }

            $sig = $row['signature'];
            $isDuplicate = false;
            $duplicateOfId = null;

            // Check if duplicate in DB
            if (isset($existingClients[$sig])) {
                $isDuplicate = true;
                $duplicateOfId = $existingClients[$sig];
                $this->seenPrimaryId[$sig] = $existingClients[$sig];
            }
            // Check if duplicate in this chunk
            elseif ($chunkSignatures[$sig] > 1) {
                $isDuplicate = true;

                // If we already have a primary ID in this chunk, use it
                if (!isset($this->seenPrimaryId[$sig])) {
                    $this->seenPrimaryId[$sig] = null; // will be set after insertion
                }

                $duplicateOfId = $this->seenPrimaryId[$sig];
            }

            $batchInsert[] = [
                'company_name' => $row['company_name'],
                'email' => $row['email'],
                'phone_number' => $row['phone_number'],
                'is_duplicate' => $isDuplicate,
                'duplicate_of_id' => $duplicateOfId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Insert in chunks of 1000
            if (count($batchInsert) >= 1000) {
                $this->insertBatchWithDuplicates($batchInsert, $sig);
                $batchInsert = [];
            }
        }

        if (!empty($batchInsert)) {
            $this->insertBatchWithDuplicates($batchInsert, $sig);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Inserts batch and fixes duplicate_of_id for chunk duplicates
     */
    protected function insertBatchWithDuplicates(array $batch)
    {
        // Insert all rows and get their IDs
        foreach ($batch as &$row) {
            $client = Client::create([
                'company_name' => $row['company_name'],
                'email' => $row['email'],
                'phone_number' => $row['phone_number'],
                'is_duplicate' => $row['is_duplicate'],
                'duplicate_of_id' => null, // temporarily null
            ]);
            $row['id'] = $client->id;

            $sig = "{$row['company_name']}||{$row['email']}||{$row['phone_number']}";

            // If this is first occurrence in chunk and seenPrimaryId is null, set as primary
            if ($row['is_duplicate'] && ($this->seenPrimaryId[$sig] ?? null) === null) {
                $this->seenPrimaryId[$sig] = $client->id;
            }

            // If duplicate and duplicate_of_id is null, set to primary
            if ($row['is_duplicate'] && $row['duplicate_of_id'] === null) {
                $client->update(['duplicate_of_id' => $this->seenPrimaryId[$sig]]);
            }
        }

        $this->imported += count($batch);
    }

    protected function storeError($error)
    {
        // cache errors to show later
        $current = Cache::get($this->cacheKey, []);
        $current[] = $error;
        Cache::put($this->cacheKey, $current, now()->addHours(2));
    }
}
