@extends('layouts.app')

@section('page_title', 'Dashboard')

@push('styles')
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet" />
@endpush

@section('content')

<div class="dashboard-shell">
    <section class="surveillance-bar">
        <div>
            <h2 class="dashboard-title">VILoCare Performance Dashboard</h2>
        </div>
        <div class="as-of">
            <span>Period</span>
            <strong>{{ $periodLabel }}</strong>
        </div>
    </section>

    <section class="filter-panel">
        <div class="filter-panel-head">
            <div>
                <h3>Filters</h3>
            </div>
            @if($filterConfig['state_id']['available'] && $filterConfig['county_id']['available'] && $filterConfig['facility_id']['available'])
                <span class="filter-state is-ready">Location ready</span>
            @else
                <span class="filter-state">Add location columns to activate all location filters</span>
            @endif
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="dashboard-filters">
            <div class="filter-field">
                <label for="state_id">State</label>
                <select id="state_id" name="state_id" class="form-select form-select-sm" @disabled(!$filterConfig['state_id']['available'])>
                    <option value="">All States</option>
                    @foreach($filterOptions['state_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['state_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="county_id">County</label>
                <select id="county_id" name="county_id" class="form-select form-select-sm" @disabled(!$filterConfig['county_id']['available'])>
                    <option value="">All Counties</option>
                    @foreach($filterOptions['county_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['county_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="facility_id">Facility</label>
                <select id="facility_id" name="facility_id" class="form-select form-select-sm" @disabled(!$filterConfig['facility_id']['available'])>
                    <option value="">All Facilities</option>
                    @foreach($filterOptions['facility_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['facility_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="due_date">Date</label>
                <input id="due_date" type="date" name="due_date" value="{{ $activeFilters['due_date'] ?? '' }}" class="form-control form-control-sm">
            </div>

            <div class="filter-field">
                <label for="month">Month</label>
                <select id="month" name="month" class="form-select form-select-sm">
                    <option value="">All Months</option>
                    @foreach(range(1, 12) as $month)
                        <option value="{{ $month }}" @selected((string) ($activeFilters['month'] ?? '') === (string) $month)>
                            {{ \Carbon\Carbon::create(null, $month, 1)->format('M') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="quarter">Quarter</label>
                <select id="quarter" name="quarter" class="form-select form-select-sm">
                    <option value="">All Quarters</option>
                    @foreach(range(1, 4) as $quarter)
                        <option value="{{ $quarter }}" @selected((string) ($activeFilters['quarter'] ?? '') === (string) $quarter)>Q{{ $quarter }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="year">Year</label>
                <input id="year" type="number" name="year" min="2000" max="2100" value="{{ $activeFilters['year'] ?? '' }}" class="form-control form-control-sm" placeholder="YYYY">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                <a href="{{ route('dashboard.vl_due.list', $filterQuery) }}" class="btn btn-outline-success btn-sm">VL Due Line List</a>
            </div>
        </form>
    </section>

    @php
        $todayFilterQuery = array_filter([
            'state_id' => $activeFilters['state_id'] ?? null,
            'county_id' => $activeFilters['county_id'] ?? null,
            'facility_id' => $activeFilters['facility_id'] ?? null,
            'due_date' => now()->toDateString(),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <section class="summary-grid" aria-label="Critical summaries">
        <article class="summary-card summary-patient">
            <span class="summary-icon">PT</span>
            <div>
                <p>Total Patients Enrolled</p>
                <strong>{{ number_format($totalPatients) }}</strong>
                <span>Filtered enrolment cohort</span>
            </div>
        </article>

        <a href="{{ route('dashboard.vl_due.list', $todayFilterQuery) }}" class="summary-card summary-today">
            <span class="summary-icon">VL</span>
            <div>
                <p>Total Due for VL Today</p>
                <strong>{{ number_format($totalVlDueToday) }}</strong>
                <span>Due on {{ now()->format('d M Y') }}</span>
            </div>
        </a>

        <a href="{{ route('dashboard.vl_due.list', $filterQuery) }}" class="summary-card summary-vl">
            <span class="summary-icon">VL</span>
            <div>
                <p>Total Due for VL Cumulative</p>
                <strong>{{ number_format($totalVlDue) }}</strong>
                <span>Open repeat VL workload</span>
            </div>
        </a>

        <a href="/eac" class="summary-card summary-eac">
            <span class="summary-icon">EC</span>
            <div>
                <p>Total EAC Due</p>
                <strong>{{ number_format($totalDueEAC) }}</strong>
                <span>High VL clients needing EAC</span>
            </div>
        </a>

        <a href="{{ route('dashboard.vl_due.list', $filterQuery) }}" class="summary-card summary-repeat">
            <span class="summary-icon">RV</span>
            <div>
                <p>Total Due for Repeat VL After EAC</p>
                <strong>{{ number_format($totalDueRepeatVL) }}</strong>
                <span>Session 3 completed</span>
            </div>
        </a>
    </section>

    <section class="mini-metrics">
        <div>
            <span>Appointments Today</span>
            <strong>{{ number_format($totalAppointmentsToday) }}</strong>
        </div>
        <div>
            <span>Samples Collected</span>
            <strong>{{ number_format($totalSamplesCollected) }}</strong>
        </div>
        <div>
            <span>Samples Rejected</span>
            <strong>{{ number_format($totalSamplesRejected) }}</strong>
        </div>
        <div>
            <span>High Viral Load Results</span>
            <strong>{{ number_format($totalHighVL) }}</strong>
        </div>
    </section>

    <section class="insight-grid">
        <article class="insight-card insight-wide">
            <div class="card-head">
                <h3>Monthly VL Due Trend</h3>
                <span>{{ $trendYear }}</span>
            </div>
            <div class="chart-canvas-wrap">
                <canvas id="vlDueTrendChart"></canvas>
            </div>
        </article>

        <article class="insight-card">
            <div class="card-head">
                <h3>Viral Load Suppression</h3>
                <span>{{ number_format($suppressed + $unsuppressed) }} results</span>
            </div>
            <div class="chart-canvas-wrap compact-chart">
                <canvas id="vlChart"></canvas>
            </div>
        </article>

        <article class="insight-card">
            <div class="card-head">
                <h3>Appointment Status</h3>
                <span>{{ number_format($scheduled + $completed + $missed) }} visits</span>
            </div>
            <div class="chart-canvas-wrap compact-chart">
                <canvas id="appointmentChart"></canvas>
            </div>
        </article>

        <article class="insight-card">
            <div class="card-head">
                <h3>EAC Progress</h3>
                <span>{{ number_format($completedEAC + $ongoingEAC) }} sessions</span>
            </div>
            <div class="chart-canvas-wrap compact-chart">
                <canvas id="eacChart"></canvas>
            </div>
        </article>

        <article class="insight-card">
            <div class="card-head">
                <h3>{{ count($facilityDue['labels']) ? 'VL Due by Facility' : 'Patients by Age Group' }}</h3>
                <span>Top groups</span>
            </div>
            <div class="chart-canvas-wrap compact-chart">
                <canvas id="facilityChart"></canvas>
            </div>
        </article>
    </section>
</div>

@endsection

@push('scripts')
<script>
    const dashboardPalette = {
        teal: '#0f9f8f',
        green: '#2f8f46',
        red: '#d94b3d',
        amber: '#d58922',
        blue: '#2d73b9',
        ink: '#243447',
        grid: '#e5edf3'
    };

    Chart.defaults.font.family = "'Manrope', sans-serif";
    Chart.defaults.color = '#526276';

    const commonScales = {
        y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: dashboardPalette.grid }
        },
        x: {
            grid: { display: false }
        }
    };

    new Chart(document.getElementById('vlDueTrendChart'), {
        type: 'line',
        data: {
            labels: @json($vlDueTrend['labels']),
            datasets: [{
                label: 'VL due',
                data: @json($vlDueTrend['values']),
                borderColor: dashboardPalette.teal,
                backgroundColor: 'rgba(15, 159, 143, 0.14)',
                pointBackgroundColor: dashboardPalette.teal,
                pointRadius: 3,
                borderWidth: 2,
                fill: true,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: commonScales
        }
    });

    new Chart(document.getElementById('vlChart'), {
        type: 'doughnut',
        data: {
            labels: ['Suppressed', 'Unsuppressed'],
            datasets: [{
                data: [{{ $suppressed }}, {{ $unsuppressed }}],
                backgroundColor: [dashboardPalette.green, dashboardPalette.red],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: { legend: { position: 'bottom' } }
        }
    });

    new Chart(document.getElementById('appointmentChart'), {
        type: 'bar',
        data: {
            labels: ['Scheduled', 'Completed', 'Missed'],
            datasets: [{
                data: [{{ $scheduled }}, {{ $completed }}, {{ $missed }}],
                backgroundColor: [dashboardPalette.blue, dashboardPalette.teal, dashboardPalette.amber],
                borderRadius: 4,
                maxBarThickness: 34
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: commonScales
        }
    });

    new Chart(document.getElementById('eacChart'), {
        type: 'pie',
        data: {
            labels: ['Completed', 'Pending or Ongoing'],
            datasets: [{
                data: [{{ $completedEAC }}, {{ $ongoingEAC }}],
                backgroundColor: [dashboardPalette.green, dashboardPalette.amber],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    const facilityLabels = @json(count($facilityDue['labels']) ? $facilityDue['labels'] : $ageMix['labels']);
    const facilityValues = @json(count($facilityDue['labels']) ? $facilityDue['values'] : $ageMix['values']);

    new Chart(document.getElementById('facilityChart'), {
        type: 'bar',
        data: {
            labels: facilityLabels,
            datasets: [{
                data: facilityValues,
                backgroundColor: dashboardPalette.ink,
                borderRadius: 4,
                maxBarThickness: 28
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: dashboardPalette.grid }
                },
                y: { grid: { display: false } }
            }
        }
    });
</script>
@endpush
