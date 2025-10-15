<?php
namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientsExport implements FromCollection, WithHeadings
{
    protected $filter;
    public function __construct($filter=null){ $this->filter=$filter; }

    public function collection(){
        $query = Client::query();
        if($this->filter==='duplicates') $query->where('is_duplicate',true);
        if($this->filter==='unique') $query->where('is_duplicate',false);
        return $query->get(['company_name','email','phone_number']);
    }

    public function headings(): array { return ['company_name','email','phone_number']; }
}
