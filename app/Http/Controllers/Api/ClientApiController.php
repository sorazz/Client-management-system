<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Imports\ClientsImport;
use App\Exports\ClientsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
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
        Cache::flush();
        $file = $request->file('file');
        $cacheKey = 'import_errors_' . Str::uuid(); // unique key per import

        $import = new ClientsImport($cacheKey);

        Excel::queueImport($import, $file);

        return redirect()->route('clients.importStatus', ['key' => $cacheKey])
            ->with('status', 'Import started! You can check errors after completion.');
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

    public function importStatus(Request $request)
    {
        $request->validate(['import_key' => 'required|string']);
        $key = $request->import_key;

        $errors = Cache::get($key, []);
        $status = Queue::size('default'); // optional: check if queue is still running

        return response()->json([
            'errors' => $errors,
            'status' => empty($errors) && $status > 0 ? 'processing' : 'finished',
            'error_count' => count($errors),

        ]);
    }
}
