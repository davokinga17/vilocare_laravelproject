@extends('layouts.app')

@section('content')

<h3>Add Viral Load Result</h3>

<form method="POST" action="/viral-load/store">
    @csrf

    <select name="patient_id" class="form-control mb-2">
        <option value="">Select Patient</option>
        @foreach($patients as $patient)
            <option value="{{ $patient->patient_id }}">
                {{ $patient->first_name }} {{ $patient->last_name }}
            </option>
        @endforeach
    </select>

    <input type="date" name="sample_date" class="form-control mb-2">
    <input type="text" name="lab_number" class="form-control mb-2" placeholder="Lab Number">
    <input type="number" name="result_cpml" class="form-control mb-2" placeholder="Result (cp/ml)">

    <button class="btn btn-success">Save</button>
</form>

@endsection
