<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    @php
        $reportOrientation = 'portrait';
    @endphp
    @include('reports.partials.pdf_styles')
</head>
<body>
@php
    $suppressed = (int) $summary['suppressed_raw'];
    $unsuppressed = (int) $summary['unsuppressed_raw'];
    $resultTotal = $suppressed + $unsuppressed;
    $suppressedPercent = $resultTotal > 0 ? ($suppressed / $resultTotal) * 100 : 0;
    $unsuppressedPercent = $resultTotal > 0 ? ($unsuppressed / $resultTotal) * 100 : 0;
    $circumference = 251.33;
    $suppressedDash = $circumference * ($suppressedPercent / 100);
    $facilityLabels = $analytics['facilityCoverage']['labels'] ?? [];
    $facilityValues = $analytics['facilityCoverage']['values'] ?? [];
    $facilityTotal = array_sum($facilityValues);
    $topFacility = $facilityLabels[0] ?? 'No data';
    $topFacilityValue = (int) ($facilityValues[0] ?? 0);
    $topFacilityPercent = $facilityTotal > 0 ? ($topFacilityValue / $facilityTotal) * 100 : 0;
    $facilityDash = $circumference * ($topFacilityPercent / 100);
    $scope = collect($report['filters'])->map(fn ($value, $label) => $label . ': ' . $value)->implode(' | ');
@endphp

<section class="report-page">
    @include('reports.partials.pdf_masthead')
    <div class="report-title-block">
        <h1>ViLoCare Summary Report</h1>
        <p>HIV Viral Load Monitoring Summary</p>
    </div>

    <table class="info-panel">
        <tr>
            <td>
                <table class="info-row"><tr><td style="width:27px"><span class="info-icon">RF</span></td><td class="info-copy"><span>Report Reference</span><strong>{{ $report['reference'] }}</strong></td></tr></table>
                <table class="info-row"><tr><td style="width:27px"><span class="info-icon">SC</span></td><td class="info-copy"><span>Scope</span><strong>{{ $scope ?: 'All time' }}</strong></td></tr></table>
            </td>
            <td>
                <table class="info-row"><tr><td style="width:27px"><span class="info-icon">DT</span></td><td class="info-copy"><span>Generated</span><strong>{{ $report['generated_at_human'] }}</strong></td></tr></table>
                <table class="info-row"><tr><td style="width:27px"><span class="info-icon">URL</span></td><td class="info-copy"><span>Verification URL</span><strong>{{ $report['verification_url'] }}</strong></td></tr></table>
            </td>
        </tr>
    </table>

    <table class="kpi-grid"><tr>
        @foreach([
            ['PT', 'Total Patients', $summary['totalPatients']],
            ['VL', 'Total Viral Loads', $summary['totalViralLoads']],
            ['SP', 'Suppression Rate', $summary['suppressionRate']],
            ['FC', 'Facilities Covered', $summary['coveredFacilities']],
        ] as $card)
            <td><table class="kpi-layout"><tr><td style="width:37px"><span class="kpi-icon">{{ $card[0] }}</span></td><td class="kpi-copy"><span>{{ $card[1] }}</span><strong>{{ $card[2] }}</strong></td></tr></table></td>
        @endforeach
    </tr></table>

    <table class="two-column"><tr>
        <td>
            <div class="panel">
                <p class="section-heading">Summary Metrics</p>
                <table class="data-table">
                    <thead><tr><th>Metric</th><th>Value</th></tr></thead>
                    <tbody>
                        <tr><td class="metric-name">Total Patients</td><td>{{ $summary['totalPatients'] }}</td></tr>
                        <tr><td class="metric-name">Total Viral Load Results</td><td>{{ $summary['totalViralLoads'] }}</td></tr>
                        <tr><td class="metric-name">Suppressed Results</td><td class="status-good">{{ $summary['suppressed'] }}</td></tr>
                        <tr><td class="metric-name">Unsuppressed Results</td><td class="status-alert">{{ $summary['unsuppressed'] }}</td></tr>
                        <tr><td class="metric-name">Suppression Rate</td><td>{{ $summary['suppressionRate'] }}</td></tr>
                        <tr><td class="metric-name">Latest Result Date</td><td>{{ $summary['latestResultDate'] }}</td></tr>
                        <tr><td class="metric-name">Covered Counties</td><td>{{ $summary['coveredCounties'] }}</td></tr>
                        <tr><td class="metric-name">Covered Facilities</td><td>{{ $summary['coveredFacilities'] }}</td></tr>
                    </tbody>
                </table>
            </div>
        </td>
        <td>
            <div class="panel" style="margin-bottom:9px">
                <p class="section-heading">Suppression Overview</p>
                <table class="donut-table"><tr>
                    <td class="donut-graphic">
                        <svg viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#ef3d37" stroke-width="14" />
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#159a82" stroke-width="14" stroke-dasharray="{{ $suppressedDash }} {{ $circumference - $suppressedDash }}" transform="rotate(-90 50 50)" />
                            <text x="50" y="45" text-anchor="middle" class="donut-label">Total</text><text x="50" y="62" text-anchor="middle" class="donut-total">{{ $resultTotal }}</text>
                        </svg>
                    </td>
                    <td><p class="legend-row"><span class="legend-swatch" style="background:#159a82"></span>Suppressed<br><strong>{{ $suppressed }} ({{ number_format($suppressedPercent, 1) }}%)</strong></p><p class="legend-row"><span class="legend-swatch" style="background:#ef3d37"></span>Unsuppressed<br><strong>{{ $unsuppressed }} ({{ number_format($unsuppressedPercent, 1) }}%)</strong></p></td>
                </tr></table>
            </div>
            <div class="panel">
                <p class="section-heading">Facility Coverage</p>
                <table class="donut-table"><tr>
                    <td class="donut-graphic">
                        <svg viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#e1efec" stroke-width="14" />
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#159a82" stroke-width="14" stroke-dasharray="{{ $facilityDash }} {{ $circumference - $facilityDash }}" transform="rotate(-90 50 50)" />
                            <text x="50" y="45" text-anchor="middle" class="donut-label">Total</text><text x="50" y="62" text-anchor="middle" class="donut-total">{{ $summary['coveredFacilitiesRaw'] }}</text>
                        </svg>
                    </td>
                    <td><p class="legend-row"><span class="legend-swatch" style="background:#159a82"></span>{{ $topFacility }}<br><strong>{{ $topFacilityValue }} ({{ number_format($topFacilityPercent, 1) }}%)</strong></p></td>
                </tr></table>
            </div>
        </td>
    </tr></table>
    @include('reports.partials.pdf_footer', ['pageLabel' => 'Page 1 of 2'])
</section>

<section class="report-page">
    @include('reports.partials.pdf_masthead')
    <div class="report-title-block"><h1>Breakdown Snapshot &amp; Reporting Details</h1></div>
    <p class="section-heading with-icon"><span class="section-icon">TOP</span>Top Breakdown Snapshot</p>
    <table class="data-table breakdown-table">
        <tbody>
            <tr><td>Patient Sex Mix</td><td>{{ implode(', ', collect($analytics['patientSexMix']['labels'])->take(4)->all()) ?: 'No data' }}</td></tr>
            <tr><td>Testing Indications</td><td>{{ implode(', ', collect($analytics['testingIndications']['labels'])->take(4)->all()) ?: 'No data' }}</td></tr>
            <tr><td>Facility Coverage</td><td>{{ implode(', ', collect($analytics['facilityCoverage']['labels'])->take(4)->all()) ?: 'No data' }}</td></tr>
        </tbody>
    </table>

    <p class="section-heading with-icon" style="margin-top:20px"><span class="section-icon">EXP</span>Report Export Structure</p>
    <table class="data-table">
        <thead><tr><th style="width:35%">Field</th><th>Value</th></tr></thead>
        <tbody>
            <tr><td class="metric-name">Report Reference</td><td>{{ $report['reference'] }}</td></tr>
            <tr><td class="metric-name">Generated At</td><td>{{ $report['generated_at_human'] }}</td></tr>
            <tr><td class="metric-name">Total Patients</td><td>{{ $summary['totalPatients'] }}</td></tr>
            <tr><td class="metric-name">Total Viral Loads</td><td>{{ $summary['totalViralLoads'] }}</td></tr>
            <tr><td class="metric-name">Suppressed Results</td><td>{{ $summary['suppressed'] }}</td></tr>
            <tr><td class="metric-name">Unsuppressed Results</td><td>{{ $summary['unsuppressed'] }}</td></tr>
            <tr><td class="metric-name">Suppression Rate</td><td>{{ $summary['suppressionRate'] }}</td></tr>
            <tr><td class="metric-name">Latest Result Date</td><td>{{ $summary['latestResultDate'] }}</td></tr>
            <tr><td class="metric-name">Verification URL</td><td>{{ $report['verification_url'] }}</td></tr>
        </tbody>
    </table>
    <div class="system-note"><strong>System Note</strong><p>{{ $report['footer_text'] }}. Data reflects the current state of the ViLoCare HIV Viral Load Monitoring System.</p></div>
    @include('reports.partials.pdf_footer', ['pageLabel' => 'Page 2 of 2'])
</section>
</body>
</html>
