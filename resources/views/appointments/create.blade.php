@extends('layouts.app')

@section('content')

<h3>Schedule Appointment</h3>

<form method="POST" action="/appointments/store">
    @csrf

    <div class="mb-3">
        <label>Patient</label>
        <select name="patient_id" class="form-control">
            @foreach($patients as $p)
                <option value="{{ $p->patient_id }}">
                    {{ $p->first_name }} {{ $p->last_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Date</label>
        <input type="date" name="appointment_date" class="form-control">
    </div>

    <div class="mb-3">
        <label>Purpose</label>
        <input type="text" name="purpose" class="form-control">
    </div>

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-control">
            <option>Scheduled</option>
            <option>Completed</option>
            <option>Missed</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Notes</label>
        <textarea name="notes" class="form-control"></textarea>
    </div>

    <button class="btn btn-success">Save</button>

</form>

@endsection