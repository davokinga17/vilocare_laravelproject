@extends('layouts.app')

@section('page_title', 'Add Sample')

@push('styles')
    <link href="{{ asset('css/clinical-pages.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="clinical-page">
    <header class="clinical-page-header"><div><h2>Add Sample Collection</h2><p>Register a collected patient sample and its reception details.</p></div><a href="{{ route('samples.index') }}" class="clinical-btn">← Back to Samples</a></header>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('samples.store') }}" class="clinical-card clinical-form-card">
        @csrf
        <section class="clinical-form-section">
            <div class="clinical-section-title"><span>PT</span><div><h3>Patient &amp; Sample</h3><p>Identify the patient and sample collection details.</p></div></div>
            <div class="clinical-form-grid">
                <div class="clinical-field clinical-col-6"><label>Patient <span class="required">*</span></label><select name="patient_id" class="form-select" required><option value="">Search or select patient</option>@foreach($patients as $patient)<option value="{{ $patient->patient_id }}" @selected((string) old('patient_id') === (string) $patient->patient_id)>{{ $patient->art_number }} · {{ $patient->first_name }} {{ $patient->last_name }}</option>@endforeach</select></div>
                <div class="clinical-field clinical-col-3"><label>Collection Date <span class="required">*</span></label><input type="date" name="collection_date" value="{{ old('collection_date', now()->toDateString()) }}" class="form-control" required></div>
                <div class="clinical-field clinical-col-3"><label>Sample Type</label><select name="sample_type" class="form-select"><option value="">Select sample type</option>@foreach(['Plasma','DBS','Whole Blood'] as $type)<option value="{{ $type }}" @selected(old('sample_type') === $type)>{{ $type }}</option>@endforeach</select></div>
            </div>
        </section>
        <section class="clinical-form-section">
            <div class="clinical-section-title"><span>RC</span><div><h3>Collection &amp; Reception</h3><p>Capture chain-of-custody and receiving information.</p></div></div>
            <div class="clinical-form-grid">
                <div class="clinical-field clinical-col-3"><label>Collector</label><input type="text" name="collector" value="{{ old('collector') }}" class="form-control" placeholder="Collector name"></div>
                <div class="clinical-field clinical-col-3"><label>Status</label><select name="status" class="form-select"><option value="Collected">Collected</option>@foreach(['Received','Rejected','Processed'] as $status)<option value="{{ $status }}" @selected(old('status') === $status)>{{ $status }}</option>@endforeach</select></div>
                <div class="clinical-field clinical-col-3"><label>Reception Date</label><input type="date" name="sample_reception_date" value="{{ old('sample_reception_date') }}" class="form-control"></div>
                <div class="clinical-field clinical-col-3"><label>Health Facility Code</label><input type="text" name="health_facility_code" value="{{ old('health_facility_code') }}" class="form-control" placeholder="e.g. TSH"></div>
            </div>
        </section>
        <footer class="clinical-form-footer"><a href="{{ route('samples.index') }}" class="clinical-btn">Cancel</a><button class="clinical-btn clinical-btn-primary" type="submit">Save Sample</button></footer>
    </form>
</div>
@endsection
