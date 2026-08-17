@extends('layouts.app')

@section('content')

<h3>Patient Profile</h3>

@php($latestResult = $patient->viralLoads->first())
<div class="d-flex gap-2 mb-3">
    @if($latestResult && in_array(auth()->user()->role, ['Administrator', 'Clinician', 'Lab Technician']))
        <form method="POST" action="{{ route('payments.request') }}">
            @csrf
            <input type="hidden" name="patient_id" value="{{ $patient->patient_id }}">
            <input type="hidden" name="vl_id" value="{{ $latestResult->vl_id }}">
            <input type="hidden" name="payment_type" value="result_print">
            <button type="submit" class="btn btn-outline-primary btn-sm">Request Print Payment</button>
        </form>
        <form method="POST" action="{{ route('payments.request') }}">
            @csrf
            <input type="hidden" name="patient_id" value="{{ $patient->patient_id }}">
            <input type="hidden" name="vl_id" value="{{ $latestResult->vl_id }}">
            <input type="hidden" name="payment_type" value="result_pdf">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Request PDF Payment</button>
        </form>
        <a href="{{ route('patients.results.print', $patient->patient_id) }}" class="btn btn-primary btn-sm">Print Latest Result</a>
        <a href="{{ route('patients.results.pdf', $patient->patient_id) }}" class="btn btn-dark btn-sm">Download Latest Result PDF</a>
    @endif
    @if(auth()->user()?->canManageUsers())
        <a href="{{ route('ai.assistant.index', ['patient_id' => $patient->patient_id]) }}" class="btn btn-outline-info btn-sm">Ask AI About Patient</a>
    @endif
</div>

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

<hr>

<h4>Latest Viral Load Results</h4>
@if($patient->viralLoads->isEmpty())
    <p class="text-muted">No viral load results recorded yet.</p>
@else
    <div class="table-responsive mb-4">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Result Date</th>
                    <th>Copies/ml</th>
                    <th>Status</th>
                    <th>EAC Flag</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patient->viralLoads->take(5) as $viralLoad)
                    <tr>
                        <td>{{ $viralLoad->result_date ?: 'N/A' }}</td>
                        <td>{{ $viralLoad->result_cpml !== null ? number_format((float) $viralLoad->result_cpml, 2) : 'N/A' }}</td>
                        <td>{{ $viralLoad->status ?: 'N/A' }}</td>
                        <td>
                            @if($viralLoad->result_cpml !== null && $viralLoad->result_cpml >= 1000)
                                <span class="badge bg-warning text-dark">High VL - EAC required</span>
                            @else
                                <span class="badge bg-success">Stable</span>
                            @endif
                        </td>
                        <td>
                            @if($loop->first && in_array(auth()->user()->role, ['Administrator', 'Clinician', 'Lab Technician']))
                                <a href="{{ route('patients.results.print', $patient->patient_id) }}" class="btn btn-outline-primary btn-sm">Print</a>
                                <a href="{{ route('patients.results.pdf', $patient->patient_id) }}" class="btn btn-outline-dark btn-sm">PDF</a>
                                @if(in_array(auth()->user()->role, ['Administrator', 'Clinician']))
                                    <button
                                        type="button"
                                        class="btn btn-outline-success btn-sm"
                                        data-sms-action="{{ route('sms.viral_load.send_result', $viralLoad->vl_id) }}"
                                        data-sms-title="Viral Load Result SMS"
                                        data-sms-hint="Confirm the patient's number and customize the result message before sending."
                                        data-sms-phone="{{ $patient->phone }}"
                                        data-sms-message="{{ app(\App\Services\NotificationService::class)->buildViralLoadResultMessage($viralLoad) }}"
                                    >Send SMS</button>
                                @endif
                            @else
                                <span class="text-muted small">Latest result only</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<h4>EAC Sessions</h4>
@if($patient->eacSessions->isEmpty())
    <p class="text-muted">No EAC sessions recorded yet.</p>
@else
    <div class="table-responsive mb-4">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Session</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patient->eacSessions->take(5) as $session)
                    <tr>
                        <td>{{ $session->session_number }}</td>
                        <td>{{ $session->session_date ?: 'N/A' }}</td>
                        <td>{{ $session->completion_status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<h4>Payment History</h4>
@if($patient->payments->isEmpty())
    <p class="text-muted">No payments recorded for this patient yet.</p>
@else
    <div class="table-responsive mb-4">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Receipt</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patient->payments->take(8) as $payment)
                    <tr>
                        <td>{{ $payment->receipt_number }}</td>
                        <td>{{ $payment->service_label }}</td>
                        <td>{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ ucfirst($payment->status) }}</td>
                        <td>{{ optional($payment->paid_at)->format('d M Y H:i') ?: 'Not paid yet' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<a href="/patients" class="btn btn-secondary">Back</a>

@endsection
