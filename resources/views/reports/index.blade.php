@extends('layouts.app')

@section('page_title', 'Reports & Analytics')

@push('styles')
    <link href="{{ asset('css/reports.css') }}" rel="stylesheet" />
@endpush

@section('content')
@php
    $activeFilterSummary = collect([
        ['label' => 'State', 'value' => data_get(collect($filterOptions['state_id'] ?? [])->firstWhere('value', (string) ($activeFilters['state_id'] ?? '')), 'label')],
        ['label' => 'County', 'value' => data_get(collect($filterOptions['county_id'] ?? [])->firstWhere('value', (string) ($activeFilters['county_id'] ?? '')), 'label')],
        ['label' => 'Facility', 'value' => data_get(collect($filterOptions['facility_id'] ?? [])->firstWhere('value', (string) ($activeFilters['facility_id'] ?? '')), 'label')],
        ['label' => 'Period', 'value' => $summary['periodLabel'] ?? 'All time'],
    ])->filter(fn ($item) => filled($item['value']))->values();

    $exportTargets = [
        'summary_pdf' => route('reports.summary.pdf', $filterQuery),
        'summary_excel' => route('reports.summary.excel', $filterQuery),
        'patients_pdf' => route('reports.patients.pdf', $filterQuery),
        'patients_excel' => route('reports.patients.excel', $filterQuery),
        'viral_load_pdf' => route('reports.viral_load.pdf', $filterQuery),
        'viral_load_excel' => route('reports.viral_load.excel', $filterQuery),
    ];
@endphp

<div class="reports-shell">
    <section class="reports-page-head">
        <div>
            <span class="reports-kicker">Signal line</span>
            <h2>ViLoCare report board</h2>
            <p>Clean reporting for patient coverage, viral load performance, and operational export packs.</p>
        </div>
        <div class="reports-page-meta">
            <span>Reference</span>
            <strong>{{ $summaryReport['reference'] }}</strong>
        </div>
    </section>

    <section class="reports-workspace">
        <div class="report-filter-panel-head">
            <div>
                <h3>Filters</h3>
            </div>
            @if(($filterConfig['state_id']['available'] ?? false) && ($filterConfig['county_id']['available'] ?? false) && ($filterConfig['facility_id']['available'] ?? false))
                <span class="report-filter-state is-ready">Location ready</span>
            @else
                <span class="report-filter-state">Partial location filters</span>
            @endif
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="reports-filters reports-filters-simple">
            <div class="filter-field">
                <label for="state_id">State</label>
                <select id="state_id" name="state_id" class="form-select form-select-sm" @disabled(!($filterConfig['state_id']['available'] ?? false))>
                    <option value="">All States</option>
                    @foreach($filterOptions['state_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['state_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="county_id">County</label>
                <select id="county_id" name="county_id" class="form-select form-select-sm" @disabled(!($filterConfig['county_id']['available'] ?? false))>
                    <option value="">All Counties</option>
                    @foreach($filterOptions['county_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['county_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="facility_id">Facility</label>
                <select id="facility_id" name="facility_id" class="form-select form-select-sm" @disabled(!($filterConfig['facility_id']['available'] ?? false))>
                    <option value="">All Facilities</option>
                    @foreach($filterOptions['facility_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['facility_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="period">Period</label>
                <select id="period" name="period" class="form-select form-select-sm">
                    <option value="all" @selected(($activeFilters['period'] ?? 'all') === 'all')>All Time</option>
                    <option value="day" @selected(($activeFilters['period'] ?? '') === 'day')>Day</option>
                    <option value="month" @selected(($activeFilters['period'] ?? '') === 'month')>Month</option>
                    <option value="quarter" @selected(($activeFilters['period'] ?? '') === 'quarter')>Quarter</option>
                    <option value="year" @selected(($activeFilters['period'] ?? '') === 'year')>Year</option>
                </select>
            </div>

            <div class="filter-field period-input" data-period-field="day">
                <label for="period_date">Date</label>
                <input id="period_date" type="date" name="period_date" value="{{ $activeFilters['period_date'] ?? '' }}" class="form-control form-control-sm">
            </div>

            <div class="filter-field period-input" data-period-field="month">
                <label for="month">Month</label>
                <select id="month" name="month" class="form-select form-select-sm">
                    <option value="">All Months</option>
                    @foreach(range(1, 12) as $month)
                        <option value="{{ $month }}" @selected((string) ($activeFilters['month'] ?? '') === (string) $month)>{{ \Carbon\Carbon::create(null, $month, 1)->format('M') }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field period-input" data-period-field="quarter">
                <label for="quarter">Quarter</label>
                <select id="quarter" name="quarter" class="form-select form-select-sm">
                    <option value="">All Quarters</option>
                    @foreach(range(1, 4) as $quarter)
                        <option value="{{ $quarter }}" @selected((string) ($activeFilters['quarter'] ?? '') === (string) $quarter)>Q{{ $quarter }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field period-input" data-period-field="year">
                <label for="year">Year</label>
                <input id="year" type="number" name="year" min="2000" max="2100" value="{{ $activeFilters['year'] ?? '' }}" class="form-control form-control-sm" placeholder="YYYY">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>

            <div class="report-export-inline">
                <label for="report_export_select">Export</label>
                <div class="report-export-inline-controls">
                    <select id="report_export_select" class="form-select form-select-sm">
                        <option value="summary_pdf">Summary PDF</option>
                        <option value="summary_excel">Summary Excel</option>
                        <option value="patients_pdf">Patients PDF</option>
                        <option value="patients_excel">Patients Excel</option>
                        <option value="viral_load_pdf">Viral Load PDF</option>
                        <option value="viral_load_excel">Viral Load Excel</option>
                    </select>
                    <a href="{{ $exportTargets['summary_pdf'] }}" class="btn btn-dark btn-sm" id="reportExportButton">Export</a>
                </div>
            </div>
        </form>
    </section>

    @if($activeFilterSummary->isNotEmpty())
        <section class="active-filter-strip">
            @foreach($activeFilterSummary as $item)
                <div class="active-filter-chip">
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ $item['value'] }}</strong>
                </div>
            @endforeach
        </section>
    @endif

    <section class="reports-summary-grid" aria-label="Report summaries">
        <article class="report-summary-card tone-patients">
            <span class="summary-badge">PT</span>
            <div>
                <p>Total Patients</p>
                <strong>{{ $summary['totalPatients'] }}</strong>
                <span>Registered patient records in the active scope.</span>
            </div>
        </article>

        <article class="report-summary-card tone-vl">
            <span class="summary-badge">VL</span>
            <div>
                <p>Total Viral Load Results</p>
                <strong>{{ $summary['totalViralLoads'] }}</strong>
                <span>{{ $summary['latestResultDate'] }} latest result date.</span>
            </div>
        </article>

        <article class="report-summary-card tone-good">
            <span class="summary-badge">SP</span>
            <div>
                <p>Suppression Rate</p>
                <strong>{{ $summary['suppressionRate'] }}</strong>
                <span>{{ $summary['suppressed'] }} suppressed results recorded.</span>
            </div>
        </article>

        <article class="report-summary-card tone-alert">
            <span class="summary-badge">FC</span>
            <div>
                <p>Facilities Covered</p>
                <strong>{{ $summary['coveredFacilities'] }}</strong>
                <span>{{ $summary['coveredCounties'] }} counties represented.</span>
            </div>
        </article>
    </section>

    <section class="reports-main-grid">
        <article class="report-panel panel-wide">
            <div class="panel-head">
                <div>
                    <h3>Monthly viral load activity</h3>
                    <p>Year-long view of viral load results linked to the active filters.</p>
                </div>
                <span class="panel-tag">Overview</span>
            </div>
            <div class="chart-canvas-wrap chart-tall">
                <canvas id="monthlyViralLoadChart"></canvas>
            </div>
        </article>

        <article class="report-panel report-panel-stack">
            <div class="panel-head">
                <div>
                    <h3>Coverage snapshot</h3>
                    <p>Quick view of suppression and facility contribution.</p>
                </div>
            </div>
            <div class="chart-canvas-wrap chart-slim">
                <canvas id="vlStatusChart"></canvas>
            </div>
            <div class="chart-canvas-wrap chart-slim">
                <canvas id="facilityCoverageChart"></canvas>
            </div>
        </article>

        <article class="report-panel">
            <div class="panel-head">
                <div>
                    <h3>Patient sex distribution</h3>
                    <p>Mix of patients represented in the report.</p>
                </div>
            </div>
            <div class="chart-canvas-wrap chart-compact">
                <canvas id="patientSexChart"></canvas>
            </div>
        </article>

        <article class="report-panel">
            <div class="panel-head">
                <div>
                    <h3>Patient age mix</h3>
                    <p>Most represented age groups in current scope.</p>
                </div>
            </div>
            <div class="chart-canvas-wrap chart-compact">
                <canvas id="patientAgeChart"></canvas>
            </div>
        </article>

        <article class="report-panel panel-wide">
            <div class="panel-head">
                <div>
                    <h3>Testing indications</h3>
                    <p>Most common reasons for viral load testing in the filtered data.</p>
                </div>
            </div>
            <div class="chart-canvas-wrap chart-compact">
                <canvas id="testingIndicationsChart"></canvas>
            </div>
        </article>
    </section>
</div>
@endsection

@push('scripts')
<script>
    Chart.defaults.font.family = "'Manrope', sans-serif";
    Chart.defaults.color = '#5f6f84';

    const reportsPalette = {
        teal: '#0f9f8f',
        ocean: '#2d73b9',
        coral: '#d95d4d',
        amber: '#d7922f',
        mint: '#38b27c',
        plum: '#6d5bd0',
        slate: '#314357',
        grid: '#e6edf5'
    };

    function emptyStatePlugin(message) {
        return {
            id: 'emptyState_' + message.replace(/\s+/g, '_'),
            afterDraw(chart) {
                const dataset = chart.data.datasets?.[0]?.data ?? [];
                const hasAnyData = dataset.some(value => Number(value) > 0);

                if (hasAnyData) {
                    return;
                }

                const {ctx, chartArea} = chart;
                if (!chartArea) {
                    return;
                }

                ctx.save();
                ctx.textAlign = 'center';
                ctx.fillStyle = '#7b8a9b';
                ctx.font = "600 14px Manrope";
                ctx.fillText(message, (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
                ctx.restore();
            }
        };
    }

    const standardScales = {
        y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: reportsPalette.grid }
        },
        x: {
            grid: { display: false }
        }
    };

    const monthlyViralLoads = @json($analytics['monthlyViralLoads']);
    const viralLoadStatus = @json($analytics['viralLoadStatusMix']);
    const patientSexMix = @json($analytics['patientSexMix']);
    const patientAgeMix = @json($analytics['patientAgeMix']);
    const testingIndications = @json($analytics['testingIndications']);
    const facilityCoverage = @json($analytics['facilityCoverage']);
    const exportTargets = @json($exportTargets);

    new Chart(document.getElementById('monthlyViralLoadChart'), {
        type: 'line',
        data: {
            labels: monthlyViralLoads.labels,
            datasets: [{
                label: 'Viral load results',
                data: monthlyViralLoads.values,
                borderColor: reportsPalette.teal,
                backgroundColor: 'rgba(15, 159, 143, 0.12)',
                pointBackgroundColor: reportsPalette.teal,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: true,
                borderWidth: 3,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: standardScales
        },
        plugins: [emptyStatePlugin('No monthly viral load data available')]
    });

    new Chart(document.getElementById('vlStatusChart'), {
        type: 'doughnut',
        data: {
            labels: viralLoadStatus.labels,
            datasets: [{
                data: viralLoadStatus.values,
                backgroundColor: [reportsPalette.mint, reportsPalette.coral],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '64%',
            plugins: { legend: { position: 'bottom' } }
        },
        plugins: [emptyStatePlugin('No viral load status data available')]
    });

    new Chart(document.getElementById('patientSexChart'), {
        type: 'pie',
        data: {
            labels: patientSexMix.labels,
            datasets: [{
                data: patientSexMix.values,
                backgroundColor: [reportsPalette.ocean, reportsPalette.amber, reportsPalette.plum, reportsPalette.teal],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        },
        plugins: [emptyStatePlugin('No patient sex data available')]
    });

    new Chart(document.getElementById('patientAgeChart'), {
        type: 'bar',
        data: {
            labels: patientAgeMix.labels,
            datasets: [{
                label: 'Patients',
                data: patientAgeMix.values,
                backgroundColor: reportsPalette.slate,
                borderRadius: 8,
                maxBarThickness: 32
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
                    grid: { color: reportsPalette.grid }
                },
                y: {
                    grid: { display: false }
                }
            }
        },
        plugins: [emptyStatePlugin('No age data available')]
    });

    new Chart(document.getElementById('testingIndicationsChart'), {
        type: 'bar',
        data: {
            labels: testingIndications.labels,
            datasets: [{
                label: 'Tests',
                data: testingIndications.values,
                backgroundColor: [reportsPalette.teal, reportsPalette.ocean, reportsPalette.amber, reportsPalette.plum, reportsPalette.coral],
                borderRadius: 8,
                maxBarThickness: 44
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: standardScales
        },
        plugins: [emptyStatePlugin('No testing indication data available')]
    });

    new Chart(document.getElementById('facilityCoverageChart'), {
        type: 'doughnut',
        data: {
            labels: facilityCoverage.labels,
            datasets: [{
                data: facilityCoverage.values,
                backgroundColor: [reportsPalette.teal, reportsPalette.ocean, reportsPalette.amber, reportsPalette.plum, reportsPalette.coral],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '58%',
            plugins: { legend: { position: 'bottom' } }
        },
        plugins: [emptyStatePlugin('No facility coverage data available')]
    });

    const periodSelect = document.getElementById('period');
    const periodInputs = document.querySelectorAll('[data-period-field]');
    const exportSelect = document.getElementById('report_export_select');
    const exportButton = document.getElementById('reportExportButton');

    function syncPeriodFields() {
        const current = periodSelect?.value || 'all';

        periodInputs.forEach((field) => {
            const shouldShow = field.dataset.periodField === current || (field.dataset.periodField === 'year' && ['month', 'quarter', 'year'].includes(current));
            field.classList.toggle('is-hidden', !shouldShow);
        });
    }

    function syncExportTarget() {
        if (!exportSelect || !exportButton) {
            return;
        }

        exportButton.href = exportTargets[exportSelect.value] || exportTargets.summary_pdf;
    }

    periodSelect?.addEventListener('change', syncPeriodFields);
    exportSelect?.addEventListener('change', syncExportTarget);

    syncPeriodFields();
    syncExportTarget();
</script>
@endpush
