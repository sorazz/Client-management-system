@extends('layouts.app')
@section('content')
    <div class="container">
        <h2>Upload CSV</h2>
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('imported'))
            <div class="alert alert-success">
                {{ session('imported') }} records imported successfully!
            </div>
        @endif

        <form action="{{ route('clients.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" class="form-control" required>
            <button class="btn btn-primary mt-2">Upload</button>
        </form>
    </div>
@endsection
