<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Imports\ClientsImport;
use App\Exports\ClientsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\ClientsImportRequest;

class ClientApiController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter');
        $query = Client::query();
        if ($filter === 'duplicates') $query->where('is_duplicate', true);
        if ($filter === 'unique') $query->where('is_duplicate', false);
        return response()->json($query->with('primary')->paginate(20));
    }

    public function show($id)
    {
        $client = Client::with('duplicates')->find($id);
        if (!$client) return response()->json(['error' => 'Client not found'], 404);
        return response()->json($client);
    }

    public function import(ClientsImportRequest $request)
    {
        Excel::queueImport(new ClientsImport(), $request->file('file'));
        return response()->json(['status' => 'Import started']);
    }

    public function exportFile(Request $request)
    {

        $filter = $request->get('filter');
        $query = Client::query();
        if ($filter === 'duplicates') $query->where('is_duplicate', true);
        if ($filter === 'unique') $query->where('is_duplicate', false);

        // Check if any data exists before exporting
        if (!$query->exists()) {

            return response()->json(['status' => 'No clients found to export!']);
        }
        return Excel::download(new ClientsExport($filter), 'clients.csv');
    }
}
