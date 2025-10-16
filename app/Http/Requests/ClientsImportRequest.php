<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Maatwebsite\Excel\Imports\HeadingRowImport;

class ClientsImportRequest extends FormRequest
{
    /**
     * Store missing headers if found.
     *
     * @var array
     */
    protected $missingHeaders = [];

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'file' => 'required|file|mimes:csv,xlsx,txt',
        ];
    }

    public function withValidator($validator)
{
    $validator->after(function ($validator) {
        if (! $this->hasFile('file')) {
            return;
        }
        $file = $this->file('file');

        // Try using PHP’s CSV reading if file is CSV
        if ($file->getClientOriginalExtension() === 'csv') {
            $fh = fopen($file->getPathname(), 'r');
            $headerRow = fgetcsv($fh);
            fclose($fh);

            $firstRow = $headerRow ?: [];
        } else {
            // For Excel (.xlsx), you can use PhpSpreadsheet directly:
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $headerRow = [];
            foreach ($sheet->getRowIterator(1,1) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $headerRow[] = $cell->getFormattedValue();
                }
            }
            $firstRow = $headerRow;
        }

        $required = ['company_name', 'email', 'phone_number'];
        $missing = array_diff($required, $firstRow);
        $this->missingHeaders = $missing;

        if (!empty($missing)) {
            $validator->errors()->add('file', 'Missing required headers: ' . implode(', ', $missing));
        }
    });
}


    /**
     * Override failedValidation to redirect to a specific route with custom flash data.
     */
    protected function failedValidation(Validator $validator)
    {
        $response = redirect()
            ->route('clients.upload')
            ->withErrors($validator)
            ->with('status', 'Import error: Missing required headers: ' . implode(', ', $this->missingHeaders));

        throw new HttpResponseException($response);
    }
}
