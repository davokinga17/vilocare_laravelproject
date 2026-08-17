@extends('layouts.app')

@section('page_title', 'Patients')

@push('styles')
    <link href="{{ asset('css/clinical-pages.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="clinical-page">
    @php($facilityLabels = collect($facilities)->pluck('label', 'value'))
    <header class="clinical-page-header">
        <div>
            <h2>Patients</h2>
            <p>Manage and view all patients enrolled in ViLoCare.</p>
        </div>
        <div class="clinical-page-actions">
            <a href="{{ route('reports.index') }}" class="clinical-btn">View Reports</a>
            <a href="{{ route('patients.create') }}" class="clinical-btn clinical-btn-primary">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Add Patient
            </a>
        </div>
    </header>

    @if(session('import_errors'))
        <div class="alert alert-warning">
            <strong>Import notes:</strong>
            <ul class="mb-0 mt-1">
                @foreach(session('import_errors') as $importError)<li>{{ $importError }}</li>@endforeach
            </ul>
        </div>
    @endif

    <section class="clinical-card clinical-import">
        <div class="clinical-import-copy">
            <strong>Bulk patient upload</strong>
            <span>Import Excel or CSV records using the approved patient template.</span>
        </div>
        <form method="POST" action="{{ route('patients.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="patients_file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required>
            <button class="clinical-btn" type="submit">Upload Patients</button>
        </form>
    </section>

    <form method="GET" action="{{ route('patients.index') }}" class="clinical-card clinical-toolbar">
        <div class="clinical-field clinical-search">
            <label for="patient_search">Search</label>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input id="patient_search" type="text" name="search" class="form-control" placeholder="Search by ART number, name or phone..." value="{{ request('search') }}">
        </div>
        <div class="clinical-field">
            <label for="patient_sex">Sex</label>
            <select id="patient_sex" name="sex" class="form-select">
                <option value="">All Sex</option>
                <option value="Male" @selected(request('sex') === 'Male')>Male</option>
                <option value="Female" @selected(request('sex') === 'Female')>Female</option>
                <option value="Other" @selected(request('sex') === 'Other')>Other</option>
            </select>
        </div>
        <div class="clinical-field">
            <label for="patient_age">Age</label>
            <input id="patient_age" type="number" name="age" class="form-control" min="0" max="130" placeholder="All ages" value="{{ request('age') }}">
        </div>
        <div class="clinical-field">
            <label for="patient_facility">Facility</label>
            <select id="patient_facility" name="facility_id" class="form-select"><option value="">All facilities</option>@foreach($facilities as $facility)<option value="{{ $facility['value'] }}" @selected((string) request('facility_id') === $facility['value'])>{{ $facility['label'] }}</option>@endforeach</select>
        </div>
        <div class="clinical-toolbar-actions">
            <button class="clinical-btn clinical-btn-primary" type="submit">Apply Filters</button>
            <a href="{{ route('patients.index') }}" class="clinical-btn">Reset</a>
        </div>
    </form>

    <section class="clinical-card clinical-table-card">
        <div class="clinical-table-meta">
            <strong>Patient Register</strong>
            <span>Showing {{ $patients->firstItem() ?? 0 }}–{{ $patients->lastItem() ?? 0 }} of {{ $patients->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table clinical-table">
                <thead>
                    <tr>
                        <th>ART Number</th>
                        <th>Patient</th>
                        <th>Sex</th>
                        <th>Age</th>
                        <th>Phone</th>
                        <th>Facility</th>
                        <th>Current Regimen</th>
                        <th>ART Start Date</th>
                        <th>Adherence</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                        <tr>
                            <td class="primary-cell">{{ $patient->art_number }}</td>
                            <td class="primary-cell">{{ $patient->first_name }} {{ $patient->last_name }}</td>
                            <td>{{ $patient->sex ?: '—' }}</td>
                            <td>{{ $patient->age ?? '—' }}</td>
                            <td>{{ $patient->phone ?: '—' }}</td>
                            <td>{{ $facilityLabels->get((string) $patient->facility_id, '—') }}</td>
                            <td>{{ $patient->current_regimen ?: 'Not recorded' }}</td>
                            <td>{{ optional($patient->art_start_date)->format('d M Y') ?: '—' }}</td>
                            <td>
                                @php($adherence = strtolower((string) $patient->arv_adherence))
                                <span class="clinical-badge {{ $adherence === 'good' ? 'clinical-badge-success' : ($adherence === 'poor' ? 'clinical-badge-danger' : 'clinical-badge-warning') }}">
                                    {{ $patient->arv_adherence ?: 'Not set' }}
                                </span>
                            </td>
                            <td>
                                <div class="clinical-row-actions">
                                    <a href="{{ route('patients.show', $patient->patient_id) }}" class="clinical-icon-btn" title="View patient" aria-label="View patient">
                                        <svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                    </a>
                                    <a href="{{ route('patients.edit', $patient->patient_id) }}" class="clinical-icon-btn" title="Edit patient" aria-label="Edit patient">
                                        <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                    </a>
                                    <form action="{{ route('patients.destroy', $patient->patient_id) }}" method="POST" onsubmit="return confirm('Delete this patient?')">
                                        @csrf @method('DELETE')
                                        <button class="clinical-icon-btn danger" type="submit" title="Delete patient" aria-label="Delete patient">
                                            <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v5M14 11v5"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="clinical-table-empty">No patients match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="clinical-pagination">{{ $patients->links() }}</div>
    </section>
</div>
@endsection
