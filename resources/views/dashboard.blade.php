@extends('layouts.app')

@section('content')

<h2>Dashboard</h2>

<div class="row">

    <div class="col-md-3">
        <div class="card p-3">
            <h5>Total Patients</h5>
            <h3>{{ $totalPatients }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <h5>Appointments Today</h5>
            <h3>{{ $totalAppointmentsToday }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <h5>Samples Collected</h5>
            <h3>{{ $totalSamplesCollected }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <h5>High Viral Load</h5>
            <h3>{{ $totalHighVL }}</h3>
        </div>
    </div>

</div>

<div class="row mt-3">

    <div class="col-md-3">
        <div class="card p-3">
            <h5>Samples Rejected</h5>
            <h3>{{ $totalSamplesRejected }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <h5>Due for EAC</h5>
            <h3>{{ $totalDueEAC }}</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <h5>Repeat VL</h5>
            <h3>{{ $totalDueRepeatVL }}</h3>
        </div>
    </div>

</div>

<!-- New Cards Section with Clickable Alerts -->
<div class="row mt-4">

    <div class="col-md-4">
        <a href="/viral-load" class="text-decoration-none">
            <div class="card p-3 border-danger">
                <h5>High Viral Load Patients</h5>
                <h3 class="text-danger">{{ $highVL }}</h3>
            </div>
        </a>
    </div>

    <div class="col-md-4">
        <a href="/eac" class="text-decoration-none">
            <div class="card p-3 border-warning">
                <h5>Patients in EAC</h5>
                <h3 class="text-warning">{{ $dueEAC }}</h3>
            </div>
        </a>
    </div>

    <div class="col-md-4">
        <a href="/viral-load" class="text-decoration-none">
            <div class="card p-3 border-success">
                <h5>Repeat VL Required</h5>
                <h3 class="text-success">{{ $repeatVL }}</h3>
            </div>
        </a>
    </div>

</div>

@endsection