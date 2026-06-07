@extends('layouts.app')

@section('content')

<h3>Add Patient</h3>

<a href="/patients" class="btn btn-secondary mb-2">Back</a>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="/patients/store">
    @csrf

    @include('patients.form')

    <button class="btn btn-success mt-3">Save</button>
</form>

@endsection
