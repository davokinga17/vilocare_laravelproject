@extends('layouts.app')

@section('content')

<h3>Patient Profile</h3>


<p><strong>ART Number:</strong> {{ $patient->art_number }}</p>
<p><strong>Name:</strong> {{ $patient->first_name }} {{ $patient->last_name }}</p>
<p><strong>Sex:</strong> {{ $patient->sex }}</p>
<p><strong>Phone:</strong> {{ $patient->phone }}</p>

<a href="/patients" class="btn btn-secondary">Back</a>

@endsection