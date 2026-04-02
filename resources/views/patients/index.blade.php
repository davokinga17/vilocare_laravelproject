@extends('layouts.app')

@section('content')

<h2>Patients</h2>

<!-- Add Patient Button & Reports -->
<a href="/patients/create" class="btn btn-primary mb-3">Add Patient</a>
<a href="/reports/patients/pdf" class="btn btn-danger">Download PDF</a>
<a href="/reports/patients/excel" class="btn btn-success">Download Excel</a>

<!-- Filters Form -->
<form method="GET" action="/patients" class="mb-3">
    <div class="row g-2">
        <!-- Search Input -->
        <div class="col-md-4">
            <input type="text" name="search" class="form-control"
                placeholder="Search by name or ART"
                value="{{ request('search') }}">
        </div>
        <!-- Sex Filter -->
        <div class="col-md-3">
            <select name="sex" class="form-control">
                <option value="">All Sex</option>
                <option value="Male" {{ request('sex')=='Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ request('sex')=='Female' ? 'selected' : '' }}>Female</option>
            </select>
        </div>
        <!-- Age Category Filter -->
        <div class="col-md-3">
            <select name="age_category" class="form-control">
                <option value="">All Ages</option>
                <option value="Adult" {{ request('age_category')=='Adult' ? 'selected' : '' }}>Adult</option>
                <option value="Child" {{ request('age_category')=='Child' ? 'selected' : '' }}>Child</option>
            </select>
        </div>
        <!-- Buttons -->
        <div class="col-md-2 d-flex align-items-center">
            <button class="btn btn-primary me-2">Filter</button>
            <a href="/patients" class="btn btn-secondary">Reset</a>
        </div>
    </div>
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
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this patient?')">Delete</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5">No patients found</td>
        </tr>
        @endforelse
    </tbody>
</table>

<!-- Pagination Links with filters preserved -->
{{ $patients->appends(request()->query())->links() }}

@endsection