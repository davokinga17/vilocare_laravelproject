<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Patient Result Print</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; margin: 32px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 2px solid #d1d9e6; padding-bottom: 16px; }
        .logo { height: 52px; }
        .meta { text-align: right; font-size: 12px; color: #4b5563; }
        .card { border: 1px solid #dbe4ee; border-radius: 12px; padding: 18px; margin-bottom: 18px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 24px; }
        .label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 4px; }
        .value { font-size: 16px; font-weight: 600; }
        .status-high { color: #b91c1c; }
        .status-good { color: #047857; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div>
            <img src="{{ asset('images/vilocarelogo.png') }}" alt="ViLoCare Logo" class="logo">
            <h1>Patient Viral Load Result</h1>
        </div>
        <div class="meta">
            <div>Printed on {{ now()->format('d M Y H:i') }}</div>
            <div>Facility copy</div>
        </div>
    </div>

    <div class="card">
        <div class="grid">
            <div>
                <div class="label">ART Number</div>
                <div class="value">{{ $patient->art_number }}</div>
            </div>
            <div>
                <div class="label">Patient Name</div>
                <div class="value">{{ $patient->first_name }} {{ $patient->last_name }}</div>
            </div>
            <div>
                <div class="label">Sex</div>
                <div class="value">{{ $patient->sex }}</div>
            </div>
            <div>
                <div class="label">Phone</div>
                <div class="value">{{ $patient->phone ?: 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="grid">
            <div>
                <div class="label">Sample Date</div>
                <div class="value">{{ $result->sample_date ?: 'N/A' }}</div>
            </div>
            <div>
                <div class="label">Result Date</div>
                <div class="value">{{ $result->result_date ?: 'N/A' }}</div>
            </div>
            <div>
                <div class="label">Result (copies/ml)</div>
                <div class="value">{{ $result->result_cpml !== null ? number_format((float) $result->result_cpml, 2) : 'N/A' }}</div>
            </div>
            <div>
                <div class="label">Result (log)</div>
                <div class="value">{{ $result->result_log !== null ? number_format((float) $result->result_log, 2) : 'N/A' }}</div>
            </div>
            <div>
                <div class="label">Clinical Status</div>
                <div class="value {{ ($result->result_cpml !== null && $result->result_cpml >= 1000) ? 'status-high' : 'status-good' }}">
                    {{ ($result->result_cpml !== null && $result->result_cpml >= 1000) ? 'High viral load - EAC required' : 'Suppressed / stable' }}
                </div>
            </div>
            <div>
                <div class="label">Testing Indication</div>
                <div class="value">{{ $result->vl_testing_indication ?: 'N/A' }}</div>
            </div>
        </div>
    </div>
</body>
</html>
