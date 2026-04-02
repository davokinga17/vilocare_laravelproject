<h2>Viral Load Report</h2>

<table border="1" width="100%">
    <tr>
        <th>Patient</th>
        <th>Result</th>
        <th>Date</th>
    </tr>

    @foreach($results as $r)
    <tr>
        <td>{{ $r->patient->first_name }} {{ $r->patient->last_name }}</td>
        <td>{{ $r->result_cpml }}</td>
        <td>{{ $r->sample_date }}</td>
    </tr>
    @endforeach
</table>