@extends('layouts.app')

@section('content')

<h3>Viral Load Results</h3>

<a href="/viral-load/create" class="btn btn-primary mb-3">Add Result</a>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Patient</th>
            <th>Sample Date</th>
            <th>Result</th>
            <th>Status</th>
        </tr>
    </thead>

    <tbody>
        @foreach($results as $row)
        <tr>
            <td>{{ $row->patient->first_name }} {{ $row->patient->last_name }}</td>
            <td>{{ $row->sample_date }}</td>
            <td>{{ $row->result_cpml }}</td>

            <td>
                @if($row->result_cpml >= 1000)
                    <span class="badge bg-danger">High</span>
                @else
                    <span class="badge bg-success">Suppressed</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@endsection