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

    <input type="text" name="art_number" class="form-control mb-2" placeholder="ART Number" value="{{ old('art_number') }}">
    <input type="text" name="first_name" class="form-control mb-2" placeholder="First Name" value="{{ old('first_name') }}">
    <input type="text" name="last_name" class="form-control mb-2" placeholder="Last Name" value="{{ old('last_name') }}">
    
    <select name="sex" class="form-control mb-2">
        <option value="">Select Sex</option>
        <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
        <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
    </select>

    <input type="text" name="phone" class="form-control mb-2" placeholder="Phone" value="{{ old('phone') }}">

    <button class="btn btn-success">Save</button>
</form>

@endsection