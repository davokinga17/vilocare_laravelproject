@extends('layouts.app')

@section('page_title', 'Sample Management')

@section('content')

<h2>Sample Collection Details and Rejections</h2>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="page-card p-3 h-100">
            <h5>Record Sample Collection</h5>
            <form method="POST" action="{{ route('samples.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Patient</label>
                    <select name="patient_id" class="form-control" required>
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->patient_id }}" {{ old('patient_id') == $patient->patient_id ? 'selected' : '' }}>
                                {{ $patient->art_number }} - {{ $patient->first_name }} {{ $patient->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Collection Date</label>
                    <input type="date" name="collection_date" value="{{ old('collection_date') }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sample Type</label>
                    <input type="text" name="sample_type" value="{{ old('sample_type') }}" class="form-control" placeholder="DBS, Plasma">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Collector</label>
                    <input type="text" name="collector" value="{{ old('collector') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">Select Status</option>
                        @foreach(['Collected', 'Received', 'Rejected', 'Processed'] as $status)
                            <option value="{{ $status }}" {{ old('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sample Reception Date</label>
                    <input type="date" name="sample_reception_date" value="{{ old('sample_reception_date') }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Health Facility Code</label>
                    <input type="text" name="health_facility_code" value="{{ old('health_facility_code') }}" class="form-control">
                </div>
                <div class="col-12">
                    <button class="btn btn-success">Save Sample</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="page-card p-3 h-100">
            <h5>Record Rejection</h5>
            <form method="POST" action="{{ route('samples.reject') }}" class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="form-label">Sample</label>
                    <select name="sample_id" class="form-control" required>
                        <option value="">Select Sample</option>
                        @foreach($samples as $sample)
                            <option value="{{ $sample->sample_id }}" {{ old('sample_id') == $sample->sample_id ? 'selected' : '' }}>
                                #{{ $sample->sample_id }} - {{ optional($sample->patient)->art_number }} - {{ optional($sample->patient)->first_name }} {{ optional($sample->patient)->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Rejection Date</label>
                    <input type="date" name="rejection_date" value="{{ old('rejection_date') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-control" rows="2">{{ old('reason') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Action Taken</label>
                    <textarea name="action_taken" class="form-control" rows="2">{{ old('action_taken') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Corrective Action</label>
                    <textarea name="corrective_action" class="form-control" rows="2">{{ old('corrective_action') }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-danger">Save Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="page-card p-3 mb-4">
    <h5>Sample Collection Records</h5>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Sample ID</th>
                    <th>Patient</th>
                    <th>Collection Date</th>
                    <th>Sample Type</th>
                    <th>Collector</th>
                    <th>Status</th>
                    <th>Reception Date</th>
                    <th>Facility Code</th>
                    <th>Rejections</th>
                </tr>
            </thead>
            <tbody>
                @forelse($samples as $sample)
                    <tr>
                        <td>{{ $sample->sample_id }}</td>
                        <td>{{ optional($sample->patient)->art_number }} - {{ optional($sample->patient)->first_name }} {{ optional($sample->patient)->last_name }}</td>
                        <td>{{ $sample->collection_date }}</td>
                        <td>{{ $sample->sample_type }}</td>
                        <td>{{ $sample->collector }}</td>
                        <td>{{ $sample->status }}</td>
                        <td>{{ $sample->sample_reception_date }}</td>
                        <td>{{ $sample->health_facility_code }}</td>
                        <td>{{ $sample->rejections->count() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">No sample collection records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $samples->links() }}
</div>

<div class="page-card p-3">
    <h5>Recent Sample Rejections</h5>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Rejection ID</th>
                    <th>Sample</th>
                    <th>Patient</th>
                    <th>Rejection Date</th>
                    <th>Reason</th>
                    <th>Action Taken</th>
                    <th>Corrective Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rejections as $rejection)
                    <tr>
                        <td>{{ $rejection->rejection_id }}</td>
                        <td>{{ $rejection->sample_id }}</td>
                        <td>{{ optional(optional($rejection->sample)->patient)->art_number }} - {{ optional(optional($rejection->sample)->patient)->first_name }} {{ optional(optional($rejection->sample)->patient)->last_name }}</td>
                        <td>{{ $rejection->rejection_date }}</td>
                        <td>{{ $rejection->reason }}</td>
                        <td>{{ $rejection->action_taken }}</td>
                        <td>{{ $rejection->corrective_action }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No sample rejections found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
