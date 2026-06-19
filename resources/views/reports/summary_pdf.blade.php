<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; margin: 34px 28px 72px; }
        .brand { width: 100%; border-bottom: 2px solid #0f766e; padding-bottom: 12px; margin-bottom: 18px; }
        .brand td { vertical-align: top; }
        .logo { width: 170px; max-height: 62px; object-fit: contain; }
        .title { font-size: 24px; font-weight: 700; margin: 0; }
        .subtitle { margin: 4px 0 0; color: #4b5563; }
        .meta { text-align: right; font-size: 10px; }
        .summary-grid { width: 100%; margin: 0 0 18px; }
        .summary-grid td { width: 25%; border: 1px solid #d9e2ec; padding: 12px; background: #f8fbfd; }
        .summary-grid strong { display: block; margin-top: 6px; font-size: 16px; }
        .section-title { font-size: 14px; font-weight: 700; margin: 18px 0 10px; color: #0f172a; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { border: 1px solid #dbe4ee; padding: 8px; text-align: left; }
        .data-table th { background: #103f53; color: #fff; }
        .data-table tr:nth-child(even) td { background: #f8fafc; }
        .barcode-box { margin-top: 12px; text-align: center; }
        .footer { position: fixed; bottom: -40px; left: 0; right: 0; height: 40px; color: #6b7280; font-size: 10px; border-top: 1px solid #dbe4ee; padding-top: 8px; }
    </style>
</head>
<body>
    <table class="brand">
        <tr>
            <td style="width: 190px;">
                @if(!empty($report['logo_image']))
                    <img src="{{ $report['logo_image'] }}" alt="ViLoCare Logo" class="logo">
                @else
                    {!! $report['logo_svg'] !!}
                @endif
            </td>
            <td>
                <p class="title">{{ $report['title'] }}</p>
            </td>
            <td class="meta">
                <div>Reference: <strong>{{ $report['reference'] }}</strong></div>
                <div>Generated: {{ $report['generated_at_human'] }}</div>
                <div>Scope: {{ $summary['periodLabel'] }}</div>
            </td>
        </tr>
    </table>

    <table class="summary-grid" cellspacing="10">
        <tr>
            <td><span>Total Patients</span><strong>{{ $summary['totalPatients'] }}</strong></td>
            <td><span>Total Viral Loads</span><strong>{{ $summary['totalViralLoads'] }}</strong></td>
            <td><span>Suppression Rate</span><strong>{{ $summary['suppressionRate'] }}</strong></td>
            <td><span>Facilities Covered</span><strong>{{ $summary['coveredFacilities'] }}</strong></td>
        </tr>
    </table>

    <div class="barcode-box">
        {!! $report['barcode_svg'] !!}
    </div>

    <p class="section-title">Summary Metrics</p>
    <table class="data-table">
        <thead>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Total Patients</td><td>{{ $summary['totalPatients'] }}</td></tr>
            <tr><td>Total Viral Load Results</td><td>{{ $summary['totalViralLoads'] }}</td></tr>
            <tr><td>Suppressed Results</td><td>{{ $summary['suppressed'] }}</td></tr>
            <tr><td>Unsuppressed Results</td><td>{{ $summary['unsuppressed'] }}</td></tr>
            <tr><td>Suppression Rate</td><td>{{ $summary['suppressionRate'] }}</td></tr>
            <tr><td>Latest Result Date</td><td>{{ $summary['latestResultDate'] }}</td></tr>
            <tr><td>Covered Counties</td><td>{{ $summary['coveredCounties'] }}</td></tr>
            <tr><td>Covered Facilities</td><td>{{ $summary['coveredFacilities'] }}</td></tr>
        </tbody>
    </table>

    <p class="section-title">Top Breakdown Snapshot</p>
    <table class="data-table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Top Values</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Patient Sex Mix</td>
                <td>{{ implode(', ', collect($analytics['patientSexMix']['labels'])->take(4)->all()) ?: 'No data' }}</td>
            </tr>
            <tr>
                <td>Testing Indications</td>
                <td>{{ implode(', ', collect($analytics['testingIndications']['labels'])->take(4)->all()) ?: 'No data' }}</td>
            </tr>
            <tr>
                <td>Facility Coverage</td>
                <td>{{ implode(', ', collect($analytics['facilityCoverage']['labels'])->take(4)->all()) ?: 'No data' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">{{ $report['footer_text'] }}</div>
</body>
</html>
