@extends('layouts.app')

@section('page_title', 'Patients Due for VL')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2 class="mb-1">VL Due Line List</h2>
        <p class="text-muted mb-0">Patients are arranged from the most recent VL due date.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('dashboard', $filterQuery) }}" class="btn btn-outline-secondary">Back to Dashboard</a>
        <a href="{{ route('dashboard.vl_due.export', $filterQuery) }}" class="btn btn-success">Export Excel</a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('dashboard.vl_due.list') }}" class="row g-3 align-items-end">
            <div class="col-md-4 col-xl-2">
                <label for="state_id" class="form-label">State</label>
                <select id="state_id" name="state_id" class="form-select" @disabled(!$filterConfig['state_id']['available'])>
                    <option value="">All States</option>
                    @foreach($filterOptions['state_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['state_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 col-xl-2">
                <label for="county_id" class="form-label">County</label>
                <select id="county_id" name="county_id" class="form-select" @disabled(!$filterConfig['county_id']['available'])>
                    <option value="">All Counties</option>
                    @foreach($filterOptions['county_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['county_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 col-xl-2">
                <label for="facility_id" class="form-label">Facility</label>
                <select id="facility_id" name="facility_id" class="form-select" @disabled(!$filterConfig['facility_id']['available'])>
                    <option value="">All Facilities</option>
                    @foreach($filterOptions['facility_id'] as $option)
                        <option value="{{ $option['value'] }}" @selected((string) ($activeFilters['facility_id'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 col-xl-2">
                <label for="due_date" class="form-label">Due Date</label>
                <input id="due_date" type="date" name="due_date" value="{{ $activeFilters['due_date'] ?? '' }}" class="form-control">
            </div>

            <div class="col-md-4 col-xl-2">
                <label for="month" class="form-label">Month</label>
                <select id="month" name="month" class="form-select">
                    <option value="">All Months</option>
                    @foreach(range(1, 12) as $month)
                        <option value="{{ $month }}" @selected((string) ($activeFilters['month'] ?? '') === (string) $month)>
                            {{ \Carbon\Carbon::create()->month($month)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 col-xl-2">
                <label for="year" class="form-label">Year</label>
                <input id="year" type="number" min="2000" max="2100" name="year" value="{{ $activeFilters['year'] ?? '' }}" class="form-control" placeholder="YYYY">
            </div>

            <div class="col-md-4 col-xl-2">
                <label for="quarter" class="form-label">Quarter</label>
                <select id="quarter" name="quarter" class="form-select">
                    <option value="">All Quarters</option>
                    @foreach(range(1, 4) as $quarter)
                        <option value="{{ $quarter }}" @selected((string) ($activeFilters['quarter'] ?? '') === (string) $quarter)>Q{{ $quarter }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('dashboard.vl_due.list') }}" class="btn btn-outline-secondary">Reset</a>
                <a href="{{ route('dashboard.vl_due.export', $filterQuery) }}" class="btn btn-outline-success">Export Current View</a>
            </div>
        </form>

        @if(!($filterConfig['state_id']['available'] && $filterConfig['county_id']['available'] && $filterConfig['facility_id']['available']))
            <div class="alert alert-warning mt-3 mb-0">
                State, County, and Facility filters are ready in the UI, but the current database does not yet contain those patient location columns.
            </div>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ART Number</th>
                        <th>Patient Name</th>
                        <th>Sex</th>
                        <th>Phone</th>
                        @if($filterConfig['state_id']['available'])
                            <th>State</th>
                        @endif
                        @if($filterConfig['county_id']['available'])
                            <th>County</th>
                        @endif
                        @if($filterConfig['facility_id']['available'])
                            <th>Facility</th>
                        @endif
                        <th>Last EAC Session 3 Date</th>
                        <th>VL Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                        <tr>
                            <td>{{ $patient->art_number }}</td>
                            <td>{{ $patient->full_name }}</td>
                            <td>{{ $patient->sex ?? 'N/A' }}</td>
                            <td>{{ $patient->phone ?? 'N/A' }}</td>
                            @if($filterConfig['state_id']['available'])
                                <td>{{ $patient->state_id ?? 'N/A' }}</td>
                            @endif
                            @if($filterConfig['county_id']['available'])
                                <td>{{ $patient->county_id ?? 'N/A' }}</td>
                            @endif
                            @if($filterConfig['facility_id']['available'])
                                <td>{{ $patient->facility_id ?? 'N/A' }}</td>
                            @endif
                            <td>{{ $patient->last_session_date ?? 'N/A' }}</td>
                            <td>{{ $patient->due_date ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + (int) $filterConfig['state_id']['available'] + (int) $filterConfig['county_id']['available'] + (int) $filterConfig['facility_id']['available'] }}" class="text-center text-muted py-4">
                                No patients matched the selected VL due filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $patients->links() }}
        </div>
    </div>
</div>
@endsection
