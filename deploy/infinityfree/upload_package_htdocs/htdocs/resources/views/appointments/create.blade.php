@extends('layouts.app')

@section('content')

<h3>Schedule Appointment</h3>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="/appointments/store">
    @csrf

    <div class="mb-3">
        <label>Patient</label>
        <select name="patient_id" class="form-control">
            @foreach($patients as $p)
                <option value="{{ $p->patient_id }}" {{ old('patient_id') == $p->patient_id ? 'selected' : '' }}>
                    {{ $p->first_name }} {{ $p->last_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Date</label>
        <input type="date" name="appointment_date" class="form-control" value="{{ old('appointment_date') }}">
    </div>

    <div class="mb-3">
        <label>Reason</label>
        <input type="text" name="reason" class="form-control" value="{{ old('reason') }}">
    </div>

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-control">
            @foreach (['Pending', 'Completed', 'Missed', 'Cancelled'] as $status)
                <option value="{{ $status }}" {{ old('status', 'Pending') == $status ? 'selected' : '' }}>
                    {{ $status }}
                </option>
            @endforeach
        </select>
    </div>

    <button class="btn btn-success">Save</button>

</form>

@endsection
