@extends('layouts.app')

@section('content')

<h3>Patient Profile</h3>


<p><strong>ART Number:</strong> {{ $patient->art_number }}</p>
<p><strong>Name:</strong> {{ $patient->first_name }} {{ $patient->last_name }}</p>
<p><strong>Sex:</strong> {{ $patient->sex }}</p>
<p><strong>Address:</strong> {{ $patient->address }}</p>
<p><strong>Phone:</strong> {{ $patient->phone }}</p>
<p><strong>ART Start Date:</strong> {{ $patient->art_start_date }}</p>
<p><strong>Current Regimen:</strong> {{ $patient->current_regimen }}</p>
<p><strong>Age:</strong> {{ $patient->age }}</p>
<p><strong>Pregnant:</strong> {{ $patient->is_pregnant ? 'Yes' : 'No' }}</p>
<p><strong>Breastfeeding:</strong> {{ $patient->is_breastfeeding ? 'Yes' : 'No' }}</p>
<p><strong>ARV Adherence:</strong> {{ $patient->arv_adherence }}</p>
<p><strong>State ID:</strong> {{ $patient->state_id }}</p>
<p><strong>County ID:</strong> {{ $patient->county_id }}</p>
<p><strong>Facility ID:</strong> {{ $patient->facility_id }}</p>

<a href="/patients" class="btn btn-secondary">Back</a>

@endsection
