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

<form method="POST" action="/patients/{{ $patient->id }}/update">
    @csrf
    @method('PUT') <!-- Make sure your route supports PUT method for update -->

    <input type="text" name="art_number" value="{{ old('art_number', $patient->art_number) }}" class="form-control mb-2" placeholder="ART Number">
    <input type="text" name="first_name" value="{{ old('first_name', $patient->first_name) }}" class="form-control mb-2" placeholder="First Name">
    <input type="text" name="last_name" value="{{ old('last_name', $patient->last_name) }}" class="form-control mb-2" placeholder="Last Name">

    <select name="sex" class="form-control mb-2">
        <option value="">Select Sex</option>
        <option value="Male" {{ old('sex', $patient->sex) == 'Male' ? 'selected' : '' }}>Male</option>
        <option value="Female" {{ old('sex', $patient->sex) == 'Female' ? 'selected' : '' }}>Female</option>
    </select>

    <input type="text" name="phone" value="{{ old('phone', $patient->phone) }}" class="form-control mb-2" placeholder="Phone">

    <button class="btn btn-primary">Update</button>
</form>

@endsection