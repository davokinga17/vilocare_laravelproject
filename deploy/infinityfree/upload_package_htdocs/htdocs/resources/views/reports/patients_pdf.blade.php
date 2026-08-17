<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    @php
        $reportOrientation = 'landscape';
    @endphp
    @include('reports.partials.pdf_styles')
</head>
<body>
    @include('reports.partials.pdf_masthead')
    <div class="report-title-block">
        <h1>ViLoCare Patients Report</h1>
        <p>Filtered patient register and facility coverage export</p>
    </div>

    <table class="report-meta-band"><tr>
        <td><span>Report Reference</span><strong>{{ $report['reference'] }}</strong></td>
        <td><span>Generated</span><strong>{{ $report['generated_at_human'] }}</strong></td>
        <td><span>Records</span><strong>{{ number_format($report['record_count']) }}</strong></td>
        <td><span>Scope</span><strong>{{ collect($report['filters'])->map(fn ($value, $key) => $key . ': ' . $value)->implode(' | ') }}</strong></td>
    </tr></table>

    <table class="kpi-grid"><tr>
        @foreach($report['summary_cards'] as $index => $card)
            <td><table class="kpi-layout"><tr><td style="width:37px"><span class="kpi-icon">{{ ['PT','VL','SP','FC'][$index] ?? 'KP' }}</span></td><td class="kpi-copy"><span>{{ $card['label'] }}</span><strong>{{ $card['value'] }}</strong></td></tr></table></td>
        @endforeach
    </tr></table>

    <p class="section-heading"><span class="section-icon">PT</span>Patient Register</p>
    <table class="data-table record-table">
        <thead><tr><th style="width:10%">ART Number</th><th style="width:16%">Patient Name</th><th style="width:6%">Sex</th><th style="width:11%">Phone</th><th style="width:11%">State</th><th style="width:12%">County</th><th style="width:22%">Facility</th><th style="width:12%">ART Start Date</th></tr></thead>
        <tbody>
            @forelse($patients as $patient)
                <tr><td>{{ $patient->art_number }}</td><td class="metric-name">{{ $patient->full_name }}</td><td>{{ $patient->sex ?: 'N/A' }}</td><td>{{ $patient->phone ?: 'N/A' }}</td><td>{{ $patient->state_name ?? 'Unassigned' }}</td><td>{{ $patient->county_name ?? 'Unassigned' }}</td><td>{{ $patient->facility_name ?? 'Unassigned' }}</td><td>{{ $patient->art_start_date ?: 'N/A' }}</td></tr>
            @empty
                <tr><td colspan="8" class="empty-row">No patient records matched the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="running-footer"><table><tr><td>Confidential - For Official Use Only</td><td>Authorized Signature: ____________________</td><td>Page <span class="page-number"></span></td></tr></table></div>
</body>
</html>
