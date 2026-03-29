@extends('layouts.app')

@section('content')

<h2>Patients</h2>

<!-- Add Patient Button -->
<a href="/patients/create" class="btn btn-primary mb-3">Add Patient</a>

<!-- Search Form -->
<form method="GET" class="mb-3">
    <input type="text" name="search" value="{{ $search }}" placeholder="Search patients..." class="form-control" />
</form>

<!-- Patients Table -->
<table class="table table-bordered">
    <thead>
        <tr>
            <th>ART Number</th>
            <th>Name</th>
            <th>Sex</th>
            <th>Phone</th>
            <!-- Add Actions Column -->
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($patients as $patient)
        <tr>
            <td>{{ $patient->art_number }}</td>
            <td>{{ $patient->first_name }} {{ $patient->last_name }}</td>
            <td>{{ $patient->sex }}</td>
            <td>{{ $patient->phone }}</td>
            <!-- Actions Buttons -->
            <td>
                <a href="/patients/{{ $patient->id }}" class="btn btn-info btn-sm">View</a>
                <a href="/patients/{{ $patient->id }}/edit" class="btn btn-warning btn-sm">Edit</a>
                <form action="/patients/{{ $patient->id }}/delete" method="POST" style="display:inline;">
    @csrf
    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this patient?')">
        Delete
    </button>
</form>
        </tr>
        @empty
        <tr>
            <td colspan="5">No patients found</td>
        </tr>
        @endforelse
    </tbody>
</table>

<!-- Pagination Links -->
{{ $patients->links() }}

@endsection