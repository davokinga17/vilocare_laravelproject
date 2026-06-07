@extends('layouts.app')

@section('content')

<h3>Edit Patient</h3>

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

<form method="POST" action="{{ route('patients.update', $patient->patient_id) }}">
    @csrf
    @method('PUT')

    @include('patients.form')

    <button class="btn btn-primary mt-3">Update</button>
</form>

@endsection
