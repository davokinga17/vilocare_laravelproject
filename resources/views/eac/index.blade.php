@extends('layouts.app')

@section('content')

<h3>EAC Sessions</h3>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Patient</th>
            <th>Session</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        @foreach($sessions as $s)
        <tr>
            <td>{{ $s->patient->first_name }} {{ $s->patient->last_name }}</td>
            <td>{{ $s->session_number }}</td>
            <td>{{ $s->session_date }}</td>
            <td>
                @if($s->session_number == 3 && $s->completion_status == 'Completed')
                    <span class="badge bg-warning">Repeat VL Required</span>
                @else
                    {{ $s->completion_status }}
                @endif
            </td>
            <td>
                @if($s->completion_status == 'Pending')
                    <form method="POST" action="/eac/{{ $s->id }}/complete" style="display:inline;">
                        @csrf
                        <button class="btn btn-success btn-sm">Mark Complete</button>
                    </form>
                @else
                    <span class="badge bg-success">Done</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@endsection