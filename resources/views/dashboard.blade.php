@extends('layouts.app')

@section('page_title', 'Dashboard')

@push('styles')
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet" />
@endpush

@section('content')

<div class="dashboard-head">
    <div>
        <h2 class="dashboard-title">Care Performance Overview</h2>
        <p class="dashboard-copy">A live snapshot of patient outcomes, follow-up activity, and laboratory workflow.</p>
    </div>
    <span class="status-pill">Updated today</span>
</div>

<div class="kpi-grid">
    <div class="kpi-card kpi-accent">
        <p class="kpi-label">Total Patients</p>
        <p class="kpi-value">{{ $totalPatients }}</p>
        <p class="kpi-trend">Active across care pathways</p>
    </div>
    <div class="kpi-card kpi-info">
        <p class="kpi-label">Appointments Today</p>
        <p class="kpi-value">{{ $totalAppointmentsToday }}</p>
        <p class="kpi-trend">Scheduled for current date</p>
    </div>
    <div class="kpi-card kpi-accent">
        <p class="kpi-label">Samples Collected</p>
        <p class="kpi-value">{{ $totalSamplesCollected }}</p>
        <p class="kpi-trend">Captured by laboratory workflow</p>
    </div>
    <div class="kpi-card kpi-danger">
        <p class="kpi-label">High Viral Load</p>
        <p class="kpi-value">{{ $totalHighVL }}</p>
        <p class="kpi-trend">Requires close clinical follow-up</p>
    </div>
    <div class="kpi-card kpi-danger">
        <p class="kpi-label">Samples Rejected</p>
        <p class="kpi-value">{{ $totalSamplesRejected }}</p>
        <p class="kpi-trend">Monitor sample quality issues</p>
    </div>
    <div class="kpi-card kpi-warn">
        <p class="kpi-label">Due for EAC</p>
        <p class="kpi-value">{{ $totalDueEAC }}</p>
        <p class="kpi-trend">Patients flagged for counseling</p>
    </div>
    <div class="kpi-card kpi-info">
        <p class="kpi-label">Repeat VL Required</p>
        <p class="kpi-value">{{ $totalDueRepeatVL }}</p>
        <p class="kpi-trend">Follow-up test needed after EAC</p>
    </div>
</div>

<div class="chart-grid">
    <section class="chart-card">
        <h3 class="chart-title">Viral Load Suppression</h3>
        <div class="chart-canvas-wrap">
            <canvas id="vlChart"></canvas>
        </div>
    </section>
    <section class="chart-card">
        <h3 class="chart-title">Appointment Status</h3>
        <div class="chart-canvas-wrap">
            <canvas id="appointmentChart"></canvas>
        </div>
    </section>
</div>

<div class="chart-grid">
    <section class="chart-card">
        <h3 class="chart-title">EAC Completion Progress</h3>
        <div class="chart-canvas-wrap">
            <canvas id="eacChart"></canvas>
        </div>
    </section>
</div>

<div class="alert-grid">
    <a href="/viral-load" class="alert-card alert-danger">
        <p class="alert-label">High Viral Load Patients</p>
        <p class="alert-value">{{ $highVL }}</p>
    </a>
    <a href="/eac" class="alert-card alert-warning">
        <p class="alert-label">Patients in EAC</p>
        <p class="alert-value">{{ $dueEAC }}</p>
    </a>
    <a href="/viral-load" class="alert-card alert-success">
        <p class="alert-label">Repeat VL Required</p>
        <p class="alert-value">{{ $repeatVL }}</p>
    </a>
</div>

@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('vlChart'), {
        type: 'doughnut',
        data: {
            labels: ['Suppressed', 'Unsuppressed'],
            datasets: [{
                data: [{{ $suppressed }}, {{ $unsuppressed }}],
                backgroundColor: ['#14b8a6', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    new Chart(document.getElementById('appointmentChart'), {
        type: 'bar',
        data: {
            labels: ['Scheduled', 'Completed', 'Missed'],
            datasets: [{
                data: [{{ $scheduled }}, {{ $completed }}, {{ $missed }}],
                backgroundColor: ['#0ea5a0', '#3b82f6', '#f59e0b'],
                borderRadius: 8,
                maxBarThickness: 44
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('eacChart'), {
        type: 'pie',
        data: {
            labels: ['Completed', 'Ongoing'],
            datasets: [{
                data: [{{ $completedEAC }}, {{ $ongoingEAC }}],
                backgroundColor: ['#16a34a', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
@endpush
