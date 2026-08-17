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
        <h1>ViLoCare Viral Load Report</h1>
        <p>Clinical virologic results, suppression status and facility coverage</p>
    </div>

    <table class="report-meta-band"><tr>
        <td><span>Report Reference</span><strong>{{ $report['reference'] }}</strong></td>
        <td><span>Generated</span><strong>{{ $report['generated_at_human'] }}</strong></td>
        <td><span>Records</span><strong>{{ number_format($report['record_count']) }}</strong></td>
        <td><span>Verification URL</span><strong>{{ $report['verification_url'] }}</strong></td>
    </tr></table>

    <table class="kpi-grid"><tr>
        @foreach($report['summary_cards'] as $index => $card)
            <td><table class="kpi-layout"><tr><td style="width:37px"><span class="kpi-icon">{{ ['PT','VL','SP','FC'][$index] ?? 'KP' }}</span></td><td class="kpi-copy"><span>{{ $card['label'] }}</span><strong>{{ $card['value'] }}</strong></td></tr></table></td>
        @endforeach
    </tr></table>

    <p class="section-heading"><span class="section-icon">VL</span>Viral Load Results Register</p>
    <table class="data-table record-table">
        <thead><tr><th style="width:8%">ART Number</th><th style="width:12%">Patient Name</th><th style="width:8%">VL Status</th><th style="width:8%">Result cp/mL</th><th style="width:7%">Result log</th><th style="width:8%">Sample Date</th><th style="width:8%">Result Date</th><th style="width:11%">Indication</th><th style="width:8%">State</th><th style="width:9%">County</th><th style="width:13%">Facility</th></tr></thead>
        <tbody>
            @forelse($results as $result)
                <tr><td>{{ $result->art_number }}</td><td class="metric-name">{{ $result->full_name }}</td><td class="{{ $result->viral_load_status === 'Suppressed' ? 'status-good' : 'status-alert' }}">{{ $result->viral_load_status }}</td><td>{{ $result->result_cpml ?? 'N/A' }}</td><td>{{ $result->result_log ?? 'N/A' }}</td><td>{{ $result->sample_date ?? 'N/A' }}</td><td>{{ $result->result_date ?? 'N/A' }}</td><td>{{ $result->vl_testing_indication ?: 'N/A' }}</td><td>{{ $result->state_name ?? 'Unassigned' }}</td><td>{{ $result->county_name ?? 'Unassigned' }}</td><td>{{ $result->facility_name ?? 'Unassigned' }}</td></tr>
            @empty
                <tr><td colspan="11" class="empty-row">No viral load records matched the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="running-footer"><table><tr><td>Confidential - For Official Use Only</td><td>Authorized Signature: ____________________</td><td>Page <span class="page-number"></span></td></tr></table></div>
</body>
</html>
