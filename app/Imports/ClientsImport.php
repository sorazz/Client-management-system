<?php
namespace App\Imports;

use App\Models\Client;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldQueue;
use Illuminate\Support\Facades\Validator;

class ClientsImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{
    public $errors = [];
    public $imported = 0;
    public $duplicates_found = 0;

    public function collection(Collection $rows)
    {
        $batch = [];
        $seenInFile = [];

        foreach ($rows as $index => $row) {
            $data = [
                'company_name'=>trim($row['company_name'] ?? ''),
                'email'=>trim($row['email'] ?? ''),
                'phone_number'=>trim($row['phone_number'] ?? ''),
            ];

            $validator = Validator::make($data, [
                'company_name'=>'required|string|max:255',
                'email'=>'required|email',
                'phone_number'=>'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                $this->errors[]=['line'=>$index+2,'errors'=>$validator->errors()->all()];
                continue;
            }

            $signature = strtolower($data['company_name']).'||'.strtolower($data['email']).'||'.preg_replace('/\D+/','',$data['phone_number'] ?? '');
            $duplicateId = null;
            $isDuplicate = false;

            // check in-file duplicates
            if(isset($seenInFile[$signature])){
                $isDuplicate = true;
                $duplicateId = $seenInFile[$signature];
                $this->duplicates_found++;
            } else {
                // check DB duplicates
                $existing = Client::where('company_name',$data['company_name'])
                    ->where('email',$data['email'])
                    ->where('phone_number',$data['phone_number'])->first();

                if($existing){
                    $isDuplicate = true;
                    $duplicateId = $existing->id;
                    $this->duplicates_found++;
                }
            }

            $batch[] = array_merge($data,['is_duplicate'=>$isDuplicate,'duplicate_of_id'=>$duplicateId,'created_at'=>now(),'updated_at'=>now()]);

            if(count($batch)>=1000){
                Client::insert($batch);
                foreach($batch as $b){
                    if(!$b['is_duplicate']) $seenInFile[strtolower($b['company_name']).'||'.strtolower($b['email']).'||'.preg_replace('/\D+/','',$b['phone_number'])]=$b['id'] ?? null;
                }
                $this->imported += count($batch);
                $batch = [];
            }
        }

        if(count($batch)>0){
            Client::insert($batch);
            $this->imported += count($batch);
        }
    }

    public function chunkSize(): int { return 1000; }
}
