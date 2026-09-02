<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h1 { color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 8px; }
        h2 { color: #374151; font-size: 14px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 10px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; }
        .footer { margin-top: 40px; font-size: 10px; color: #9ca3af; text-align: center; }
        .confidential { background: #fef3c7; padding: 6px; border-left: 4px solid #f59e0b; margin-bottom: 16px; }
    </style>
</head>
<body>
    <h1>OpesCare — Medical Record Summary</h1>
    <p class="confidential">CONFIDENTIAL — For authorised use only</p>

    <h2>Patient Information</h2>
    <table>
        <tr><th>Name</th><td>{{ $patient->first_name }} {{ $patient->last_name }}</td></tr>
        <tr><th>Patient ID</th><td>{{ $patient->id }}</td></tr>
        <tr><th>CNAMGS No.</th><td>{{ $patient->cnamgs_id ?? 'Not registered' }}</td></tr>
        <tr><th>Generated</th><td>{{ now()->format('d M Y H:i') }} UTC</td></tr>
    </table>

    @if($vitals->isNotEmpty())
    <h2>Recent Vital Signs</h2>
    <table>
        <tr><th>Date</th><th>Pulse</th><th>SpO2</th><th>Temp (°C)</th></tr>
        @foreach($vitals as $v)
        <tr>
            <td>{{ $v->created_at->format('d M Y') }}</td>
            <td>{{ $v->pulse_rate ?? '-' }}</td>
            <td>{{ $v->oxygen_saturation ?? '-' }}</td>
            <td>{{ $v->temperature ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    @if($labResults->isNotEmpty())
    <h2>Recent Lab Results</h2>
    <table>
        <tr><th>Test</th><th>Result</th><th>Unit</th><th>Date</th></tr>
        @foreach($labResults as $r)
        <tr>
            <td>{{ $r->parameter_name }}</td>
            <td>{{ $r->value }}</td>
            <td>{{ $r->unit }}</td>
            <td>{{ $r->created_at->format('d M Y') }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <div class="footer">
        OpesCare Health Information Platform &bull; Generated {{ now()->toIso8601String() }}
    </div>
</body>
</html>
