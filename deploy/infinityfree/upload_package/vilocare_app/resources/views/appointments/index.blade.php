@extends('layouts.app')

@section('content')

<h3>Appointments</h3>

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<a href="/appointments/create" class="btn btn-primary mb-3">New Appointment</a>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Patient</th>
            <th>Date</th>
            <th>Purpose</th>
            <th>Status</th>
        </tr>
    </thead>

    <tbody>
        @foreach($appointments as $a)
        <tr>
            <td>{{ $a->patient->first_name }} {{ $a->patient->last_name }}</td>
            <td>{{ $a->appointment_date }}</td>
            <td>{{ $a->reason }}</td>
            <td>{{ $a->status }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@endsection
