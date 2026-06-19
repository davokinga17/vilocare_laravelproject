<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; margin: 34px 26px 72px; }
        .header, .footer { width: 100%; }
        .brand { width: 100%; border-bottom: 2px solid #0f766e; padding-bottom: 12px; margin-bottom: 16px; }
        .brand td { vertical-align: top; }
        .logo { width: 170px; max-height: 62px; object-fit: contain; }
        .title { font-size: 22px; font-weight: 700; margin: 0; }
        .subtitle { margin: 4px 0 0; color: #4b5563; }
        .meta { text-align: right; font-size: 10px; }
        .cards { width: 100%; margin: 16px 0; }
        .cards td { width: 25%; border: 1px solid #d9e2ec; border-radius: 8px; padding: 10px; background: #f8fbfd; }
        .cards strong { display: block; font-size: 16px; margin-top: 4px; }
        .barcode-box { width: 180px; text-align: center; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { border: 1px solid #dbe4ee; padding: 6px 7px; }
        .data-table th { background: #103f53; color: #fff; font-size: 10px; }
        .data-table tr:nth-child(even) td { background: #f8fafc; }
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
                <p class="subtitle">Standardized ViLoCare patient summary with applied filters and verification reference.</p>
            </td>
            <td class="meta">
                <div>Reference: <strong>{{ $report['reference'] }}</strong></div>
                <div>Generated: {{ $report['generated_at_human'] }}</div>
                <div>Records: {{ number_format($report['record_count']) }}</div>
            </td>
        </tr>
    </table>

    <table class="cards" cellspacing="10">
        <tr>
            @foreach($report['summary_cards'] as $card)
                <td>
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                </td>
            @endforeach
        </tr>
    </table>

    <div class="barcode-box" style="margin: 0 0 14px auto;">
        {!! $report['barcode_svg'] !!}
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ART Number</th>
                <th>Full Name</th>
                <th>Sex</th>
                <th>Phone</th>
                <th>State</th>
                <th>County</th>
                <th>Facility</th>
                <th>ART Start Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($patients as $patient)
                <tr>
                    <td>{{ $patient->art_number }}</td>
                    <td>{{ $patient->full_name }}</td>
                    <td>{{ $patient->sex ?: 'N/A' }}</td>
                    <td>{{ $patient->phone ?: 'N/A' }}</td>
                    <td>{{ $patient->state_name ?? 'Unassigned' }}</td>
                    <td>{{ $patient->county_name ?? 'Unassigned' }}</td>
                    <td>{{ $patient->facility_name ?? 'Unassigned' }}</td>
                    <td>{{ $patient->art_start_date ?: 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No patient records matched the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">{{ $report['footer_text'] }}</div>
</body>
</html>
