<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Imports\ClientsImport;
use App\Exports\ClientsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\ClientsImportRequest;
use Illuminate\Support\Str;

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
        Cache::flush();
        $file = $request->file('file');
        $cacheKey = 'import_errors_' . Str::uuid(); // unique key per import

        $import = new ClientsImport($cacheKey);

        Excel::queueImport($import, $file);

        return redirect()->route('clients.importStatus', ['key' => $cacheKey])
            ->with('status', 'Import started! You can check errors after completion.');
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

    public function importStatus(Request $request)
    {
        $key = $request->get('key');
        $errors = Cache::get($key, []);

        return view('clients.import_status', [
            'errors' => $errors
        ]);
    }

    public function delete($id)
    {
        $client = Client::findOrFail($id);

        $client->delete();

        return response()->json([
            'message' => 'Duplicate client deleted successfully',
            'id' => $id
        ]);
    }
}
