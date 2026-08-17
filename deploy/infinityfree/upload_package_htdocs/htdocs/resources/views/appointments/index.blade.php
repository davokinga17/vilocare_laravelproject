@extends('layouts.app')

@section('page_title', 'Appointments')

@push('styles')
    <link href="{{ asset('css/clinical-pages.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="clinical-page">
    <header class="clinical-page-header">
        <div><h2>Appointments</h2><p>Manage patient appointment schedules and reminders.</p></div>
        <div class="clinical-page-actions">
            @if(auth()->user()->role !== 'Receptionist')<a href="/appointments/create" class="clinical-btn clinical-btn-primary"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>New Appointment</a>@endif
            <button type="button" class="clinical-btn" onclick="window.print()"><svg viewBox="0 0 24 24"><path d="M12 3v12M7 8l5-5 5 5M5 13v7h14v-7"/></svg>Export</button>
        </div>
    </header>

    @if(auth()->user()->role === 'Receptionist')<div class="alert alert-info">Receptionist access is view-only. Appointment creation and reminders remain with authorized staff.</div>@endif

    <section class="clinical-metrics appointments-metrics">
        <article class="clinical-metric"><span class="clinical-metric-icon">TD</span><div><span>Today</span><strong>{{ number_format($metrics['today']) }}</strong><small>appointments</small></div></article>
        <article class="clinical-metric"><span class="clinical-metric-icon">UP</span><div><span>Upcoming</span><strong>{{ number_format($metrics['upcoming']) }}</strong><small>appointments</small></div></article>
        <article class="clinical-metric"><span class="clinical-metric-icon">OK</span><div><span>Completed</span><strong>{{ number_format($metrics['completed']) }}</strong><small>appointments</small></div></article>
        <article class="clinical-metric"><span class="clinical-metric-icon">MS</span><div><span>Missed</span><strong>{{ number_format($metrics['missed']) }}</strong><small>appointments</small></div></article>
    </section>

    <form method="GET" action="{{ route('appointments.index') }}" class="clinical-card clinical-toolbar clinical-toolbar-appointments">
        <div class="clinical-field clinical-search"><label for="appointment_search">Search Patient</label><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input id="appointment_search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by name or ART number..."></div>
        <div class="clinical-field"><label>Date Range</label><div class="clinical-date-pair"><input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control" aria-label="From date"><input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control" aria-label="To date"></div></div>
        <div class="clinical-field"><label for="appointment_purpose">Purpose</label><select id="appointment_purpose" name="purpose" class="form-select"><option value="">All purposes</option>@foreach($purposes as $purpose)<option value="{{ $purpose }}" @selected(request('purpose') === $purpose)>{{ $purpose }}</option>@endforeach</select></div>
        <div class="clinical-field"><label for="appointment_status">Status</label><select id="appointment_status" name="status" class="form-select"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="clinical-toolbar-actions"><button class="clinical-btn clinical-btn-primary">Filter</button><a href="{{ route('appointments.index') }}" class="clinical-btn">Reset</a></div>
    </form>

    <section class="clinical-card clinical-table-card">
        <div class="clinical-table-meta"><strong>Appointment Register</strong><span>Showing {{ $appointments->firstItem() ?? 0 }}–{{ $appointments->lastItem() ?? 0 }} of {{ $appointments->total() }}</span></div>
        <div class="table-responsive">
            <table class="table clinical-table">
                <thead><tr><th>Patient</th><th>ART No.</th><th>Appointment Date</th><th>Purpose</th><th>Facility / Clinic</th><th>Status</th><th>SMS Reminder</th><th>Access</th></tr></thead>
                <tbody>
                @forelse($appointments as $appointment)
                    <tr>
                        <td class="primary-cell">{{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}<span class="secondary-line">{{ $appointment->patient?->sex ?: '—' }}{{ $appointment->patient?->age ? ', '.$appointment->patient->age.' years' : '' }}</span></td>
                        <td>{{ $appointment->patient?->art_number ?: '—' }}</td>
                        <td class="primary-cell">{{ optional($appointment->appointment_date)->format('d M Y') ?: '—' }}</td>
                        <td>{{ $appointment->reason ?: 'Routine follow-up' }}</td>
                        <td>{{ $facilityLabels->get($appointment->patient?->facility_id, 'Not assigned') }}</td>
                        <td>@php($status = $appointment->status ?: 'Scheduled')<span class="clinical-badge {{ in_array($status, ['Completed','Confirmed']) ? 'clinical-badge-success' : ($status === 'Missed' || $status === 'Cancelled' ? 'clinical-badge-danger' : 'clinical-badge-warning') }}">{{ $status }}</span></td>
                        <td>
                            @if(auth()->user()->role === 'Receptionist')
                                <span class="clinical-badge clinical-badge-neutral">View only</span>
                            @elseif($appointment->status === 'Pending' && in_array(auth()->user()->role, ['Administrator','Clinician']))
                                <button
                                    type="button"
                                    class="clinical-btn"
                                    data-sms-action="{{ route('sms.appointments.send', $appointment->appointment_id) }}"
                                    data-sms-title="Appointment Reminder"
                                    data-sms-hint="Confirm the patient's number and adjust the appointment reminder before sending."
                                    data-sms-phone="{{ $appointment->patient?->phone }}"
                                    data-sms-message="{{ app(\App\Services\NotificationService::class)->buildAppointmentReminderMessage($appointment) }}"
                                >Send SMS</button>
                            @else<span class="clinical-badge clinical-badge-success">No action</span>@endif
                        </td>
                        <td><span class="clinical-badge {{ auth()->user()->role === 'Receptionist' ? 'clinical-badge-neutral' : 'clinical-badge-success' }}">{{ auth()->user()->role === 'Receptionist' ? 'Read only' : 'Authorized' }}</span></td>
                    </tr>
                @empty<tr><td colspan="8" class="clinical-table-empty">No appointments match the selected filters.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="clinical-pagination">{{ $appointments->links() }}</div>
    </section>
</div>
@endsection
