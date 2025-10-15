@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Clients</h2>
    <a href="{{ route('clients.upload') }}" class="btn btn-secondary">Upload CSV</a>
    <a href="{{ route('clients.export', ['filter'=>request('filter')]) }}" class="btn btn-success">Export CSV</a>
    <div class="mt-2">
        <a href="?filter=all" class="btn btn-sm btn-light">All</a>
        <a href="?filter=unique" class="btn btn-sm btn-light">Unique</a>
        <a href="?filter=duplicates" class="btn btn-sm btn-warning">Duplicates</a>
    </div>
    <table class="table mt-2">
        <thead>
            <tr><th>#</th><th>Company</th><th>Email</th><th>Phone</th><th>Duplicate</th><th>Primary ID</th></tr>
        </thead>
        <tbody>
            @foreach($clients as $c)
            <tr @if($c->is_duplicate) class="table-warning" @endif>
                <td>{{ $c->id }}</td>
                <td>{{ $c->company_name }}</td>
                <td>{{ $c->email }}</td>
                <td>{{ $c->phone_number }}</td>
                <td>{{ $c->is_duplicate?'Yes':'No' }}</td>
                <td>{{ $c->duplicate_of_id }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $clients->withQueryString()->links() }}
</div>
@endsection
