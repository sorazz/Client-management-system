@extends('layouts.app')
@section('content')
    <div class="container">
        <h2>Clients</h2>
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
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
        <table class="table mt-2" id="duplicates-table">

            <thead>
                <tr>
                    <th>Sn.</th>
                    <th>Id</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Duplicate</th>
                    <th>Primary ID</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $sn = 1; ?>
                @foreach ($clients as $c)
                    <tr @if ($c->is_duplicate) class="table-warning" @endif>
                        <td id="client-{{ $c->id }}">{{ $sn++ }}</td>
                        <td>{{ $c->id }}</td>
                        <td>{{ $c->company_name }}</td>
                        <td>{{ $c->email }}</td>
                        <td>{{ $c->phone_number }}</td>
                        <td>{{ $c->is_duplicate ? 'Yes' : 'No' }}</td>
                        <td>{{ $c->duplicate_of_id }}</td>
                        <td>
                            <button class="btn btn-danger btn-sm delete-client" data-id="{{ $c->id }}">
                                Delete
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $clients->withQueryString()->links() }}
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function reindexSN() {
        $('#duplicates-table tbody tr').each(function(index) {
            // First <td> = SN
            $(this).find('td').eq(0).text(index + 1);
        });
    }

    $(document).on('click', '.delete-client', function() {
        if (!confirm('Are you sure you want to delete this duplicate?')) return;

        let clientId = $(this).data('id');

        $.ajax({
            url: '/clients/' + clientId,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}' // CSRF token
            },
            success: function(response) {
                alert(response.message);
                // Remove the entire row
                $('#client-' + clientId).closest('tr').remove();
                reindexSN();
            },

            error: function(xhr) {
                alert(xhr.responseJSON.error || 'Something went wrong');
            }
        });
    });
</script>
