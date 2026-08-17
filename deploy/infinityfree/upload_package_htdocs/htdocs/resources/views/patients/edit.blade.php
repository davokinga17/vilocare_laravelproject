@extends('layouts.app')

@section('page_title', 'Edit Patient')

@push('styles')
    <link href="{{ asset('css/clinical-pages.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="clinical-page">
    <header class="clinical-page-header"><div><h2>Edit Patient</h2><p>Update the clinical record for {{ $patient->first_name }} {{ $patient->last_name }}.</p></div><a href="{{ route('patients.index') }}" class="clinical-btn">← Back to Patients</a></header>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('patients.update', $patient->patient_id) }}" class="clinical-card clinical-form-card">
        @csrf @method('PUT')
        @include('patients.form')
        <footer class="clinical-form-footer"><a href="{{ route('patients.index') }}" class="clinical-btn">Cancel</a><button class="clinical-btn clinical-btn-primary" type="submit">Update Patient</button></footer>
    </form>
</div>
@endsection
