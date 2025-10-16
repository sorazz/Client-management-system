<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Imports\ClientsImport;
use App\Exports\ClientsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\ClientsImportRequest;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter');
        $query = Client::query();
        if ($filter === 'duplicates') $query->where('is_duplicate', true);
        if ($filter === 'unique') $query->where('is_duplicate', false);
        $clients = $query->paginate(10);
        return view('clients.index', compact('clients'));
    }

    public function upload()
    {
        return view('clients.upload');
    }

    public function import(ClientsImportRequest $request)
    {
        Excel::queueImport(new ClientsImport(), $request->file('file'));
        return redirect()->route('clients.upload')->with('status', 'CSV import started in background!');
    }

    public function export(Request $request)
    {
        $filter = $request->get('filter');
        $query = Client::query();
        if ($filter === 'duplicates') $query->where('is_duplicate', true);
        if ($filter === 'unique') $query->where('is_duplicate', false);

        // Check if any data exists before exporting
        if (!$query->exists()) {
            return redirect()
                ->route('clients.index', ['filter' => $filter])
                ->with('status', 'No clients found to export!');
        }
        return Excel::download(
            new ClientsExport($filter),
            'clients.csv'
        );
    }
}
