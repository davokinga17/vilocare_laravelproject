@extends('layouts.app')

@section('page_title', 'Add Viral Load Result')

@push('styles')
    <link href="{{ asset('css/clinical-pages.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="clinical-page">
    <header class="clinical-page-header"><div><h2>Add Viral Load Result</h2><p>Record a new patient viral load test result.</p></div><a href="/viral-load" class="clinical-btn">← Back to Results</a></header>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="/viral-load/store" class="clinical-card clinical-form-card">
        @csrf
        <section class="clinical-form-section">
            <div class="clinical-section-title"><span>PT</span><div><h3>Patient &amp; Sample</h3><p>Select the patient and describe the submitted sample.</p></div></div>
            <div class="clinical-form-grid">
                <div class="clinical-field clinical-col-6"><label>Patient <span class="required">*</span></label><select name="patient_id" class="form-select" required><option value="">Search by ART number or patient name</option>@foreach($patients as $patient)<option value="{{ $patient->patient_id }}" @selected((string) old('patient_id') === (string) $patient->patient_id)>{{ $patient->art_number }} · {{ $patient->first_name }} {{ $patient->last_name }}</option>@endforeach</select></div>
                <div class="clinical-field clinical-col-3"><label>Sample Collection Date</label><input type="date" name="sample_date" value="{{ old('sample_date') }}" class="form-control"></div>
                <div class="clinical-field clinical-col-3"><label>Sample Type</label><select name="sample_type" class="form-select"><option value="">Select sample type</option>@foreach(['Plasma','DBS','Whole Blood'] as $type)<option value="{{ $type }}" @selected(old('sample_type') === $type)>{{ $type }}</option>@endforeach</select></div>
            </div>
        </section>
        <section class="clinical-form-section">
            <div class="clinical-section-title"><span>LB</span><div><h3>Laboratory Information</h3><p>Testing laboratory and request information.</p></div></div>
            <div class="clinical-form-grid">
                <div class="clinical-field clinical-col-4"><label>Testing Lab</label><input type="text" name="lab" value="{{ old('lab') }}" class="form-control" placeholder="Enter laboratory"></div>
                <div class="clinical-field clinical-col-4"><label>Indication for Viral Load Testing</label><select name="vl_testing_indication" class="form-select"><option value="">Select indication</option>@foreach(['Routine','Treatment Failure','Repeat after EAC','Monitoring'] as $indication)<option value="{{ $indication }}" @selected(old('vl_testing_indication') === $indication)>{{ $indication }}</option>@endforeach</select></div>
                <div class="clinical-field clinical-col-4"><label>Request Date</label><input type="date" name="request_date" value="{{ old('request_date') }}" class="form-control"></div>
                <div class="clinical-field clinical-col-4"><label>Requesting Clinician</label><input type="text" name="requesting_clinician" value="{{ old('requesting_clinician') }}" class="form-control" placeholder="Clinician name"></div>
                <div class="clinical-field clinical-col-4"><label>Clinician Cellphone</label><input type="text" name="clinician_cellphone" value="{{ old('clinician_cellphone') }}" class="form-control" placeholder="07xxxxxxxx"></div>
            </div>
        </section>
        <section class="clinical-form-section">
            <div class="clinical-section-title"><span>RS</span><div><h3>Testing &amp; Result</h3><p>Enter the reported viral load values.</p></div></div>
            <div class="clinical-form-grid">
                <div class="clinical-field clinical-col-4"><label>Result Date</label><input type="date" name="result_date" value="{{ old('result_date', now()->toDateString()) }}" class="form-control"></div>
                <div class="clinical-field clinical-col-4"><label>Result (copies/ml) <span class="required">*</span></label><input type="number" step="0.01" min="0" name="result_cpml" value="{{ old('result_cpml') }}" class="form-control" placeholder="e.g. 4000" required></div>
                <div class="clinical-field clinical-col-4"><label>Result (log)</label><input type="number" step="0.01" name="result_log" value="{{ old('result_log') }}" class="form-control" placeholder="e.g. 3.60"></div>
                <div class="clinical-field clinical-col-12"><label>Comments</label><textarea name="comments" class="form-control" rows="3" placeholder="Additional laboratory comments or notes">{{ old('comments') }}</textarea></div>
            </div>
        </section>
        <footer class="clinical-form-footer"><a href="/viral-load" class="clinical-btn">Cancel</a><button class="clinical-btn clinical-btn-primary" type="submit">Save Result</button></footer>
    </form>
</div>
@endsection
