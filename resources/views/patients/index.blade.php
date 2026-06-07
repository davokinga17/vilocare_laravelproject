@extends('layouts.app')

@section('content')

<h2>Patients</h2>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="page-card p-3 h-100">
            <h5>Manual Patient Entry</h5>
            <a href="{{ route('patients.create') }}" class="btn btn-primary">Add Patient</a>
            <a href="{{ route('reports.index') }}" class="btn btn-info">View Reports</a>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="page-card p-3 h-100">
            <h5>Upload Excel or CSV Patients</h5>
            <form method="POST" action="{{ route('patients.import') }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-md-8">
                    <input type="file" name="patients_file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-success w-100">Upload Patients</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('import_errors'))
    <div class="alert alert-warning">
        <strong>Import notes:</strong>
        <ul class="mb-0">
            @foreach(session('import_errors') as $importError)
                <li>{{ $importError }}</li>
            @endforeach
        </ul>
    </div>
@endif

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
        <!-- Age Filter -->
        <div class="col-md-3">
            <input type="number" name="age" class="form-control" min="0" max="130" placeholder="Filter by age" value="{{ request('age') }}">
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
            <th>ART Start Date</th>
            <th>Regimen</th>
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
            <td>{{ $patient->art_start_date }}</td>
            <td>{{ $patient->current_regimen }}</td>
            <td>{{ $patient->phone }}</td>
            <!-- Actions Buttons -->
            <td>
                <a href="{{ route('patients.show', $patient->patient_id) }}" class="btn btn-info btn-sm">View</a>
                <a href="{{ route('patients.edit', $patient->patient_id) }}" class="btn btn-warning btn-sm">Edit</a>
                <form action="{{ route('patients.destroy', $patient->patient_id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this patient?')">Delete</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7">No patients found</td>
        </tr>
        @endforelse
    </tbody>
</table>

<!-- Pagination Links with filters preserved -->
{{ $patients->appends(request()->query())->links() }}

@endsection
