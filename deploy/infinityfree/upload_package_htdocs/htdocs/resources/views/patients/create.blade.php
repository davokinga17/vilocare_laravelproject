@extends('layouts.app')

@section('page_title', 'Add Patient')

@push('styles')
    <link href="{{ asset('css/clinical-pages.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="clinical-page">
    <header class="clinical-page-header">
        <div>
            <h2>Add New Patient</h2>
            <p>Enter patient details using the ViLoCare clinical data structure.</p>
        </div>
        <a href="{{ route('patients.index') }}" class="clinical-btn">← Back to Patients</a>
    </header>

    @if($errors->any())
        <div class="alert alert-danger"><strong>Please correct the highlighted details.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('patients.store') }}" class="clinical-card clinical-form-card">
        @csrf
        @include('patients.form')
        <footer class="clinical-form-footer">
            <a href="{{ route('patients.index') }}" class="clinical-btn">Cancel</a>
            <button class="clinical-btn clinical-btn-primary" type="submit">Save Patient</button>
        </footer>
    </form>
</div>
@endsection
