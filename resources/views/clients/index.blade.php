@extends('layouts.app')
@section('content')
    <div class="container">
        <h2>Clients</h2>
         @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        <a href="{{ route('clients.upload') }}" class="btn btn-secondary">Upload CSV</a>
        <a href="{{ route('clients.export', ['filter' => request('filter')]) }}" class="btn btn-success">Export CSV</a>
        <div class="mt-2">
            <a href="?filter=all" class="btn btn-sm btn-light {{ request('filter') === 'all' ? 'active' : '' }}">
                All
            </a>

            <a href="?filter=unique" class="btn btn-sm btn-light {{ request('filter') === 'unique' ? 'active' : '' }}">
                Unique
            </a>

            <a href="?filter=duplicates"
                class="btn btn-sm btn-light {{ request('filter') === 'duplicates' ? 'active' : '' }}">
                Duplicates
            </a>

        </div>
        <table class="table mt-2">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Id</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Duplicate</th>
                    <th>Primary ID</th>
                </tr>
            </thead>
            <tbody>
            <?php $sn = 1; ?>
                @foreach ($clients as $c)
                    <tr @if ($c->is_duplicate) class="table-warning" @endif>
                        <td>{{ $sn++ }}</td>
                        <td>{{ $c->id }}</td>
                        <td>{{ $c->company_name }}</td>
                        <td>{{ $c->email }}</td>
                        <td>{{ $c->phone_number }}</td>
                        <td>{{ $c->is_duplicate ? 'Yes' : 'No' }}</td>
                        <td>{{ $c->duplicate_of_id }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $clients->withQueryString()->links() }}
    </div>
@endsection
