<h2>Patients Report</h2>

<table border="1" width="100%">
    <tr>
        <th>ART</th>
        <th>Name</th>
        <th>Sex</th>
        <th>Phone</th>
    </tr>

    @foreach($patients as $p)
    <tr>
        <td>{{ $p->art_number }}</td>
        <td>{{ $p->first_name }} {{ $p->last_name }}</td>
        <td>{{ $p->sex }}</td>
        <td>{{ $p->phone }}</td>
    </tr>
    @endforeach
</table>