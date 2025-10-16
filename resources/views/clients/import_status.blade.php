@extends('layouts.app')
@section('content')
    <div class="container">
        @if (empty($errors))
            <p>Import in progress… refreshing every 5 seconds.</p>
            <script>
                setTimeout(function() {
                    location.reload();
                }, 5000); // reload every 5 seconds
            </script>
        @else
            <h4>Invalid Rows</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Sn.</th>
                        <th>Company Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Errors</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sn = 1; ?>
                    @foreach ($errors as $error)
                        <tr>
                            <td>{{ $sn++ }}</td>
                            <td>{{ $error['row']['company_name'] ?? '' }}</td>
                            <td>{{ $error['row']['email'] ?? '' }}</td>
                            <td>{{ $error['row']['phone_number'] ?? '' }}</td>
                            <td>{{ implode(', ', $error['errors'] ?? []) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>
@endsection
