@extends('layouts.app')

@section('page_title', 'Reports & Analytics')

@push('styles')
    <link href="{{ asset('css/reports.css') }}" rel="stylesheet" />
@endpush

@section('content')
@php
    $exportTargets = [
        'summary_pdf' => route('reports.summary.pdf', $filterQuery),
        'summary_excel' => route('reports.summary.excel', $filterQuery),
        'patients_pdf' => route('reports.patients.pdf', $filterQuery),
        'patients_excel' => route('reports.patients.excel', $filterQuery),
        'viral_load_pdf' => route('reports.viral_load.pdf', $filterQuery),
        'viral_load_excel' => route('reports.viral_load.excel', $filterQuery),
    ];
    $facilityLabels = $analytics['facilityCoverage']['labels'] ?? [];
    $facilityValues = $analytics['facilityCoverage']['values'] ?? [];
    $leadingFacility = $facilityLabels[0] ?? 'No facility data';
    $leadingFacilityCount = $facilityValues[0] ?? 0;
    $generatedAt = now()->format('d M Y, h:i A');
@endphp

<div class="reports-shell">
    <header class="reports-page-head">
        <div>
            <h2>Reports &amp; Analytics</h2>
            <p>Generate patient, viral load, suppression and facility performance reports.</p>
        </div>
        <div class="reports-head-actions">
            <a href="{{ $exportTargets['summary_pdf'] }}" class="report-action-button">
                <span class="action-icon pdf-icon">PDF</span> Export PDF
            </a>
            <a href="{{ $exportTargets['summary_excel'] }}" class="report-action-button">
                <span class="action-icon excel-icon">X</span> Export Excel
            </a>
            <a href="{{ route('reports.verify') }}" class="report-action-button is-primary">
                <span aria-hidden="true">✓</span> Verify Report
            </a>
        </div>
    </header>

    <section class="reports-workspace" aria-labelledby="filters-title">
        <div class="report-filter-panel-head">
            <h3 id="filters-title"><span class="filter-symbol" aria-hidden="true">⌁</span> Filters</h3>
            <span class="filter-scope">{{ $summary['periodLabel'] ?? 'All time' }}</span>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="reports-filters">
            <div class="filter-field">
                <label for="state_id">State</label>
                <select id="state_id" name="state_id" @disabled(!($filterConfig['state_id']['available'] ?? false))>
                    <option value="">All States</option>
                    @foreach($filterOptions['state_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['state_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="county_id">County</label>
                <select id="county_id" name="county_id" @disabled(!($filterConfig['county_id']['available'] ?? false))>
                    <option value="">All Counties</option>
                    @foreach($filterOptions['county_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['county_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="facility_id">Facility</label>
                <select id="facility_id" name="facility_id" @disabled(!($filterConfig['facility_id']['available'] ?? false))>
                    <option value="">All Facilities</option>
                    @foreach($filterOptions['facility_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['facility_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label for="period">Report Period</label>
                <select id="period" name="period">
                    <option value="all" @selected(($activeFilters['period'] ?? 'all') === 'all')>All Time</option>
                    <option value="day" @selected(($activeFilters['period'] ?? '') === 'day')>Single Day</option>
                    <option value="month" @selected(($activeFilters['period'] ?? '') === 'month')>Month</option>
                    <option value="quarter" @selected(($activeFilters['period'] ?? '') === 'quarter')>Quarter</option>
                    <option value="year" @selected(($activeFilters['period'] ?? '') === 'year')>Year</option>
                </select>
            </div>

            <div class="filter-field period-input" data-period-field="day">
                <label for="period_date">Date</label>
                <input id="period_date" type="date" name="period_date" value="{{ $activeFilters['period_date'] ?? '' }}">
            </div>
            <div class="filter-field period-input" data-period-field="month">
                <label for="month">Month</label>
                <select id="month" name="month">
                    <option value="">All Months</option>
                    @foreach(range(1, 12) as $month)
                        <option value="{{ $month }}" @selected((string) ($activeFilters['month'] ?? '') === (string) $month)>{{ \Carbon\Carbon::create(null, $month, 1)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field period-input" data-period-field="quarter">
                <label for="quarter">Quarter</label>
                <select id="quarter" name="quarter">
                    <option value="">All Quarters</option>
                    @foreach(range(1, 4) as $quarter)
                        <option value="{{ $quarter }}" @selected((string) ($activeFilters['quarter'] ?? '') === (string) $quarter)>Quarter {{ $quarter }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field period-input" data-period-field="year">
                <label for="year">Year</label>
                <input id="year" type="number" name="year" min="2000" max="2100" value="{{ $activeFilters['year'] ?? '' }}" placeholder="YYYY">
            </div>

            <div class="filter-actions">
                <button type="submit" class="filter-button"><span aria-hidden="true">▽</span> Apply Filters</button>
                <a href="{{ route('reports.index') }}" class="reset-button"><span aria-hidden="true">↻</span> Reset</a>
            </div>
        </form>
    </section>

    <section class="reports-summary-grid" aria-label="Reporting overview">
        <article class="report-summary-card tone-patients">
            <span class="summary-icon" aria-hidden="true">PT</span>
            <div><p>Total Patients</p><strong>{{ $summary['totalPatients'] }}</strong><span>In selected scope</span></div>
        </article>
        <article class="report-summary-card tone-vl">
            <span class="summary-icon" aria-hidden="true">VL</span>
            <div><p>Viral Load Results</p><strong>{{ $summary['totalViralLoads'] }}</strong><span>Latest: {{ $summary['latestResultDate'] }}</span></div>
        </article>
        <article class="report-summary-card tone-good">
            <span class="summary-icon" aria-hidden="true">✓</span>
            <div><p>Suppression Rate</p><strong>{{ $summary['suppressionRate'] }}</strong><span>{{ $summary['suppressed'] }} suppressed results</span></div>
        </article>
        <article class="report-summary-card tone-review">
            <span class="summary-icon" aria-hidden="true">!</span>
            <div><p>Pending Review</p><strong>{{ $summary['unsuppressed'] }}</strong><span>Unsuppressed results</span></div>
        </article>
        <article class="report-summary-card tone-facilities">
            <span class="summary-icon" aria-hidden="true">FC</span>
            <div><p>Facilities Covered</p><strong>{{ $summary['coveredFacilities'] }}</strong><span>{{ $summary['coveredCounties'] }} counties represented</span></div>
        </article>
    </section>

    <section class="reports-chart-grid">
        <article class="report-panel chart-trends">
            <div class="panel-head"><div><h3>Monthly Viral Load Trends</h3><p>Testing volume across the reporting year</p></div><span class="panel-tag">{{ $activeFilters['year'] ?: now()->year }}</span></div>
            <div class="chart-canvas-wrap"><canvas id="monthlyViralLoadChart"></canvas></div>
        </article>
        <article class="report-panel chart-suppression">
            <div class="panel-head"><div><h3>Suppression Overview</h3><p>Suppressed vs unsuppressed</p></div></div>
            <div class="chart-canvas-wrap"><canvas id="vlStatusChart"></canvas><div class="donut-total"><strong>{{ $summary['totalViralLoads'] }}</strong><span>Total results</span></div></div>
        </article>
        <article class="report-panel chart-facilities">
            <div class="panel-head"><div><h3>Facility Coverage</h3><p>Patient records by leading facility</p></div><span class="panel-tag">Top 6</span></div>
            <div class="chart-canvas-wrap"><canvas id="facilityCoverageChart"></canvas></div>
        </article>
        <article class="report-panel chart-indications">
            <div class="panel-head"><div><h3>Testing Indications</h3><p>Why viral load tests were requested</p></div></div>
            <div class="chart-canvas-wrap"><canvas id="testingIndicationsChart"></canvas></div>
        </article>
    </section>

    <section class="reports-lower-grid">
        <article class="report-panel export-panel">
            <div class="panel-head"><div><h3>Available Report Packs</h3><p>Download the current filtered dataset in your preferred format.</p></div><span class="report-reference">{{ $summaryReport['reference'] }}</span></div>
            <div class="report-table-wrap">
                <table class="report-table">
                    <thead><tr><th>Report Name</th><th>Scope</th><th>Generated</th><th>Formats</th><th>Actions</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Summary Analytics Report</strong></td><td>{{ $summary['periodLabel'] }}</td><td>{{ $generatedAt }}</td><td><span class="format-badge pdf">PDF</span><span class="format-badge excel">Excel</span></td><td><a href="{{ $exportTargets['summary_pdf'] }}">PDF</a><a href="{{ $exportTargets['summary_excel'] }}">Excel</a></td></tr>
                        <tr><td><strong>Patient Register Report</strong></td><td>{{ $summary['totalPatients'] }} patient records</td><td>{{ $generatedAt }}</td><td><span class="format-badge pdf">PDF</span><span class="format-badge excel">Excel</span></td><td><a href="{{ $exportTargets['patients_pdf'] }}">PDF</a><a href="{{ $exportTargets['patients_excel'] }}">Excel</a></td></tr>
                        <tr><td><strong>Viral Load Results Report</strong></td><td>{{ $summary['totalViralLoads'] }} test results</td><td>{{ $generatedAt }}</td><td><span class="format-badge pdf">PDF</span><span class="format-badge excel">Excel</span></td><td><a href="{{ $exportTargets['viral_load_pdf'] }}">PDF</a><a href="{{ $exportTargets['viral_load_excel'] }}">Excel</a></td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <aside class="report-panel insights-panel">
            <div class="panel-head"><div><h3><span aria-hidden="true">✣</span> Quick Insights</h3><p>Highlights from the current report scope</p></div></div>
            <div class="insight-list">
                <div class="insight-item is-good"><span>✓</span><div><strong>{{ $summary['suppressionRate'] }} suppression rate</strong><small>{{ $summary['suppressed'] }} results are suppressed</small></div></div>
                <div class="insight-item is-review"><span>!</span><div><strong>{{ $summary['unsuppressed'] }} results need review</strong><small>Viral load at or above 1,000 copies/mL</small></div></div>
                <div class="insight-item is-facility"><span>FC</span><div><strong>{{ $leadingFacility }}</strong><small>{{ number_format($leadingFacilityCount) }} patient records in this scope</small></div></div>
                <div class="insight-item is-info"><span>↗</span><div><strong>{{ $summary['coveredFacilities'] }} facilities represented</strong><small>Across {{ $summary['coveredCounties'] }} counties</small></div></div>
            </div>
        </aside>
    </section>
</div>
@endsection

@push('scripts')
<script>
    Chart.defaults.font.family = "'Manrope', sans-serif";
    Chart.defaults.color = '#607087';
    const palette = { teal: '#078678', teal2: '#18a28d', green: '#45bf91', red: '#f06a5f', amber: '#ef9d48', purple: '#7758d8', blue: '#287fe5', grid: '#e7edf3' };
    const monthly = @json($analytics['monthlyViralLoads']);
    const status = @json($analytics['viralLoadStatusMix']);
    const facilities = @json($analytics['facilityCoverage']);
    const indications = @json($analytics['testingIndications']);

    const noData = {
        id: 'reportNoData',
        afterDraw(chart) {
            if ((chart.data.datasets?.[0]?.data || []).some(value => Number(value) > 0) || !chart.chartArea) return;
            const {ctx, chartArea} = chart;
            ctx.save(); ctx.textAlign = 'center'; ctx.fillStyle = '#7b8797'; ctx.font = '600 13px Manrope';
            ctx.fillText('No data available for this selection', (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2); ctx.restore();
        }
    };
    const scales = { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: palette.grid } }, x: { grid: { display: false } } };

    new Chart(document.getElementById('monthlyViralLoadChart'), { type: 'line', data: { labels: monthly.labels, datasets: [{ label: 'Viral load results', data: monthly.values, borderColor: palette.teal2, backgroundColor: 'rgba(24,162,141,.13)', fill: true, tension: .35, pointRadius: 3, pointHoverRadius: 5, pointBackgroundColor: palette.teal2, borderWidth: 2.5 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales }, plugins: [noData] });
    new Chart(document.getElementById('vlStatusChart'), { type: 'doughnut', data: { labels: status.labels, datasets: [{ data: status.values, backgroundColor: [palette.teal2, palette.red], borderWidth: 0, hoverOffset: 4 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 7, padding: 12, font: { size: 10 } } } }, layout: { padding: { top: 4 } } }, plugins: [noData] });
    new Chart(document.getElementById('facilityCoverageChart'), { type: 'bar', data: { labels: facilities.labels, datasets: [{ label: 'Patients', data: facilities.values, backgroundColor: palette.teal2, borderRadius: 5, barThickness: 11 }] }, options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, layout: { padding: { left: 4, right: 8 } }, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: palette.grid } }, y: { grid: { display: false }, ticks: { font: { size: 10 }, callback(value) { const label = this.getLabelForValue(value); return label.length > 18 ? label.slice(0, 17) + '…' : label; } } } } }, plugins: [noData] });
    new Chart(document.getElementById('testingIndicationsChart'), { type: 'bar', data: { labels: indications.labels, datasets: [{ label: 'Tests', data: indications.values, backgroundColor: [palette.teal2, palette.blue, palette.purple, palette.amber, palette.red, palette.green], borderRadius: 5, maxBarThickness: 28 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales }, plugins: [noData] });

    const periodSelect = document.getElementById('period');
    const periodInputs = document.querySelectorAll('[data-period-field]');
    function syncPeriodFields() {
        const current = periodSelect?.value || 'all';
        periodInputs.forEach(field => field.classList.toggle('is-hidden', !(field.dataset.periodField === current || (field.dataset.periodField === 'year' && ['month', 'quarter', 'year'].includes(current)))));
    }
    periodSelect?.addEventListener('change', syncPeriodFields);
    syncPeriodFields();
</script>
@endpush
