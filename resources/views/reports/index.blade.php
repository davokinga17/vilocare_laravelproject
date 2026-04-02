@extends('layouts.app')

@section('content')

<h2>Reports</h2>

<p>Select a report to download:</p>

<div class="row">
    <div class="col-md-6">
        <h4>Patients Reports</h4>
        <a href="{{ route('reports.patients.pdf') }}" class="btn btn-danger mb-2">Download Patients PDF</a><br>
        <a href="{{ route('reports.patients.excel') }}" class="btn btn-success">Download Patients Excel</a>
    </div>
    <div class="col-md-6">
        <h4>Viral Load Reports</h4>
        <a href="{{ route('reports.viral_load.pdf') }}" class="btn btn-danger mb-2">Download Viral Load PDF</a><br>
        <a href="{{ route('reports.viral_load.excel') }}" class="btn btn-success">Download Viral Load Excel</a>
    </div>
</div>

@endsection