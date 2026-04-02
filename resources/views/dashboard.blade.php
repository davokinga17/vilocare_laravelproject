@extends('layouts.app')

@section('content')

<h2>Dashboard</h2>

<!-- Summary Cards -->
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

<!-- Charts Section -->
<div class="row mt-4">
    <div class="col-md-6">
        <canvas id="vlChart"></canvas>
    </div>
    <div class="col-md-6">
        <canvas id="appointmentChart"></canvas>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <canvas id="eacChart"></canvas>
    </div>
</div>

<!-- Summary Cards with Clickable Alerts -->
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

<!-- Chart.js Scripts -->
<script>
    // Viral Load Pie Chart
    new Chart(document.getElementById('vlChart'), {
        type: 'pie',
        data: {
            labels: ['Suppressed', 'Unsuppressed'],
            datasets: [{
                data: [{{ $suppressed }}, {{ $unsuppressed }}],
                backgroundColor: ['#36A2EB', '#FF6384']
            }]
        }
    });

    // Appointments Bar Chart
    new Chart(document.getElementById('appointmentChart'), {
        type: 'bar',
        data: {
            labels: ['Scheduled', 'Completed', 'Missed'],
            datasets: [{
                data: [{{ $scheduled }}, {{ $completed }}, {{ $missed }}],
                backgroundColor: ['#4BC0C0', '#36A2EB', '#FFCE56']
            }]
        }
    });

    // EAC Doughnut Chart
    new Chart(document.getElementById('eacChart'), {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Ongoing'],
            datasets: [{
                data: [{{ $completedEAC }}, {{ $ongoingEAC }}],
                backgroundColor: ['#4CAF50', '#FFC107']
            }]
        }
    });
</script>

@endsection