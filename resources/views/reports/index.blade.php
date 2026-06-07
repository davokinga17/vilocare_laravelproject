@extends('layouts.app')

@section('page_title', 'Reports & Analytics')

@push('styles')
    <link href="{{ asset('css/reports.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="reports-shell">
    <section class="reports-hero">
        <div class="reports-hero-copy">
            <span class="reports-kicker">Analytics workspace</span>
            <h2>ViLoCare reporting center</h2>
            <p>Review patient and viral load performance at a glance, then export the detailed reports when you need formal records.</p>
        </div>
        <div class="reports-hero-meta">
            <div class="meta-pill">
                <span>Latest VL result</span>
                <strong>{{ $latestResultDate }}</strong>
            </div>
            <div class="meta-pill emphasis">
                <span>Suppression rate</span>
                <strong>{{ $suppressionRate }}</strong>
            </div>
        </div>
    </section>

    <section class="reports-summary-grid" aria-label="Report summaries">
        <article class="report-summary-card report-summary-card-feature tone-patients">
            <div class="summary-main">
                <span class="summary-badge">PT</span>
                <div>
                    <p>Total Patients</p>
                    <strong>{{ number_format($totalPatients) }}</strong>
                    <span>Current enrolled records and quick exports</span>
                </div>
            </div>

            <div class="embedded-export-grid">
                <div class="embedded-export-block">
                    <span class="export-label">Patient register</span>
                    <div class="export-actions">
                        <a href="{{ route('reports.patients.pdf') }}" class="btn btn-danger btn-sm">Patients PDF</a>
                        <a href="{{ route('reports.patients.excel') }}" class="btn btn-success btn-sm">Patients Excel</a>
                    </div>
                </div>

                <div class="embedded-export-block">
                    <span class="export-label">Viral load register</span>
                    <div class="export-actions">
                        <a href="{{ route('reports.viral_load.pdf') }}" class="btn btn-danger btn-sm">VL PDF</a>
                        <a href="{{ route('reports.viral_load.excel') }}" class="btn btn-success btn-sm">VL Excel</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="report-summary-card tone-vl">
            <span class="summary-badge">VL</span>
            <div>
                <p>Total Viral Load Results</p>
                <strong>{{ number_format($totalViralLoads) }}</strong>
                <span>Recorded laboratory outcomes</span>
            </div>
        </article>

        <article class="report-summary-card tone-good">
            <span class="summary-badge">SP</span>
            <div>
                <p>Suppressed Results</p>
                <strong>{{ number_format($suppressed) }}</strong>
                <span>Results below 1000 cp/ml</span>
            </div>
        </article>

        <article class="report-summary-card tone-alert">
            <span class="summary-badge">UV</span>
            <div>
                <p>Unsuppressed Results</p>
                <strong>{{ number_format($unsuppressed) }}</strong>
                <span>Need closer follow-up</span>
            </div>
        </article>
    </section>

    <section class="reports-grid">
        <article class="report-panel panel-wide">
            <div class="panel-head">
                <div>
                    <h3>Monthly viral load activity</h3>
                    <p>Results captured over the last six months.</p>
                </div>
            </div>
            <div class="chart-canvas-wrap chart-tall">
                <canvas id="monthlyViralLoadChart"></canvas>
            </div>
        </article>

        <article class="report-panel">
            <div class="panel-head">
                <div>
                    <h3>Viral load status</h3>
                    <p>Suppressed versus unsuppressed results.</p>
                </div>
            </div>
            <div class="chart-canvas-wrap chart-compact">
                <canvas id="vlStatusChart"></canvas>
            </div>
        </article>

        <article class="report-panel">
            <div class="panel-head">
                <div>
                    <h3>Patient sex distribution</h3>
                    <p>Mix of registered patients by sex.</p>
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
                    <p>Most frequently recorded patient ages in care.</p>
                </div>
            </div>
            <div class="chart-canvas-wrap chart-compact">
                <canvas id="patientAgeChart"></canvas>
            </div>
        </article>

        <article class="report-panel">
            <div class="panel-head">
                <div>
                    <h3>Testing indications</h3>
                    <p>Most common reasons for viral load testing.</p>
                </div>
            </div>
            <div class="chart-canvas-wrap chart-compact">
                <canvas id="testingIndicationsChart"></canvas>
            </div>
        </article>

        <article class="report-panel">
            <div class="panel-head">
                <div>
                    <h3>Facility coverage</h3>
                    <p>Facilities contributing the largest patient counts.</p>
                </div>
            </div>
            <div class="chart-canvas-wrap chart-compact">
                <canvas id="facilityCoverageChart"></canvas>
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

    new Chart(document.getElementById('monthlyViralLoadChart'), {
        type: 'line',
        data: {
            labels: monthlyViralLoads.labels,
            datasets: [{
                label: 'Viral load results',
                data: monthlyViralLoads.values,
                borderColor: reportsPalette.teal,
                backgroundColor: 'rgba(15, 159, 143, 0.14)',
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
                maxBarThickness: 40
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
</script>
@endpush
