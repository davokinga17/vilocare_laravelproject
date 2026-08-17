@extends('layouts.app')

@section('page_title', 'EAC Sessions')

@push('styles')
    <link href="{{ asset('css/clinical-pages.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="clinical-page">
    <header class="clinical-page-header">
        <div><h2>EAC Sessions</h2><p>Track enhanced adherence counselling sessions and follow-up progress.</p></div>
        <div class="clinical-page-actions"><button type="button" class="clinical-btn" onclick="window.print()"><svg viewBox="0 0 24 24"><path d="M12 3v12M7 8l5-5 5 5M5 13v7h14v-7"/></svg>Export</button></div>
    </header>

    <section class="clinical-metrics">
        <article class="clinical-metric"><span class="clinical-metric-icon">AC</span><div><span>Active EAC Clients</span><strong>{{ number_format($metrics['active_clients']) }}</strong><small>clients</small></div></article>
        <article class="clinical-metric"><span class="clinical-metric-icon">S1</span><div><span>Session 1 Due</span><strong>{{ number_format($metrics['session_one_due']) }}</strong><small>sessions</small></div></article>
        <article class="clinical-metric"><span class="clinical-metric-icon">PR</span><div><span>Pending Review</span><strong>{{ number_format($metrics['pending']) }}</strong><small>sessions</small></div></article>
        <article class="clinical-metric"><span class="clinical-metric-icon">OK</span><div><span>Completed</span><strong>{{ number_format($metrics['completed']) }}</strong><small>sessions</small></div></article>
    </section>

    <form method="GET" action="{{ route('eac.index') }}" class="clinical-card clinical-toolbar">
        <div class="clinical-field clinical-search"><label for="eac_search">Search Patient</label><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input id="eac_search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by patient name or ART number..."></div>
        <div class="clinical-field"><label for="session_number">Session Number</label><select id="session_number" name="session_number" class="form-select"><option value="">All sessions</option>@foreach(range(1,3) as $number)<option value="{{ $number }}" @selected((string) request('session_number') === (string) $number)>Session {{ $number }}</option>@endforeach</select></div>
        <div class="clinical-field"><label for="eac_status">Status</label><select id="eac_status" name="status" class="form-select"><option value="">All statuses</option>@foreach(['Pending','Ongoing','Completed','Missed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="clinical-field"><label>Date Range</label><div class="clinical-date-pair"><input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control" aria-label="From date"><input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control" aria-label="To date"></div></div>
        <div class="clinical-toolbar-actions"><button class="clinical-btn clinical-btn-primary" type="submit">Filter</button><a href="{{ route('eac.index') }}" class="clinical-btn">Reset</a></div>
    </form>

    <section class="clinical-card clinical-table-card">
        <div class="clinical-table-meta"><strong>EAC Session Register</strong><span>Showing {{ $sessions->firstItem() ?? 0 }}–{{ $sessions->lastItem() ?? 0 }} of {{ $sessions->total() }}</span></div>
        <div class="table-responsive">
            <table class="table clinical-table">
                <thead><tr><th>Patient</th><th>ART No.</th><th>Session</th><th>Session Date</th><th>Assigned Clinician</th><th>Status</th><th>Outcome / Next Step</th><th>Payment / Approval</th><th>SMS Reminder</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td class="primary-cell">{{ $session->patient?->first_name }} {{ $session->patient?->last_name }}<span class="secondary-line">{{ $session->patient?->sex ?: '—' }}{{ $session->patient?->age ? ', '.$session->patient->age.' years' : '' }}</span></td>
                        <td>{{ $session->patient?->art_number ?: '—' }}</td>
                        <td class="primary-cell">{{ $session->session_number }}</td>
                        <td>{{ optional($session->session_date)->format('d M Y') ?: 'Not scheduled' }}</td>
                        <td>{{ $session->counselor ?: 'Not assigned' }}</td>
                        <td>
                            @if($session->session_number === 3 && $session->completion_status === 'Completed')
                                <span class="clinical-badge clinical-badge-warning">Repeat VL Required</span>
                            @else
                                <span class="clinical-badge {{ $session->completion_status === 'Completed' ? 'clinical-badge-success' : ($session->completion_status === 'Missed' ? 'clinical-badge-danger' : 'clinical-badge-warning') }}">{{ $session->completion_status }}</span>
                            @endif
                        </td>
                        <td>{{ $session->action_plan ?: $session->notes ?: 'Adherence counselling' }}<span class="secondary-line">@if($session->next_session_date)Next: {{ optional($session->next_session_date)->format('d M Y') }}@elseif($session->session_number < 3 && $session->completion_status === 'Completed')Next: Session {{ $session->session_number + 1 }}@else{{ $session->completion_status === 'Completed' ? 'Follow-up as indicated' : 'Awaiting session completion' }}@endif</span></td>
                        <td>
                            @if($session->hasPaidConsultationFee())
                                <span class="clinical-badge clinical-badge-success">Reception Approved</span>
                            @elseif($session->hasPendingConsultationFee())
                                <span class="clinical-badge clinical-badge-warning">Pending Reception</span>
                            @else
                                <form method="POST" action="{{ route('payments.request') }}">@csrf<input type="hidden" name="patient_id" value="{{ $session->patient_id }}"><input type="hidden" name="eac_id" value="{{ $session->eac_id }}"><input type="hidden" name="payment_type" value="eac_consultation"><button type="submit" class="clinical-btn">Request Payment</button></form>
                            @endif
                        </td>
                        <td>
                            @if($session->completion_status === 'Pending')
                                <button
                                    type="button"
                                    class="clinical-btn"
                                    data-sms-action="{{ route('sms.eac.send_reminder', $session->eac_id) }}"
                                    data-sms-title="EAC Reminder SMS"
                                    data-sms-hint="Verify the phone number and customize the EAC follow-up reminder before sending."
                                    data-sms-phone="{{ $session->patient?->phone }}"
                                    data-sms-message="{{ app(\App\Services\NotificationService::class)->buildDueEacReminderMessage($session->patient, $session) }}"
                                >Send SMS</button>
                            @elseif($session->session_number === 3 && $session->completion_status === 'Completed')
                                <button
                                    type="button"
                                    class="clinical-btn"
                                    data-sms-action="{{ route('sms.vl_due.send_reminder', $session->eac_id) }}"
                                    data-sms-title="Repeat VL Reminder SMS"
                                    data-sms-hint="Confirm the patient's number and tailor the repeat viral load reminder before sending."
                                    data-sms-phone="{{ $session->patient?->phone }}"
                                    data-sms-message="{{ app(\App\Services\NotificationService::class)->buildDueVlReminderMessage($session->patient, $session->next_session_date ?: $session->session_date) }}"
                                >Send VL SMS</button>
                            @else<span class="clinical-badge clinical-badge-neutral">Not needed</span>@endif
                        </td>
                        <td>
                            @if($session->completion_status === 'Pending' && $session->hasPaidConsultationFee())
                                <form method="POST" action="{{ route('eac.complete', $session->eac_id) }}" onsubmit="return confirm('Mark this EAC session complete?')">@csrf<button type="submit" class="clinical-btn clinical-btn-primary">Complete</button></form>
                            @elseif($session->completion_status === 'Pending')
                                <span class="clinical-badge clinical-badge-neutral">Payment locked</span>
                            @else<span class="clinical-badge clinical-badge-success">Done</span>@endif
                        </td>
                    </tr>
                @empty<tr><td colspan="10" class="clinical-table-empty">No EAC sessions match the selected filters.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="clinical-pagination">{{ $sessions->links() }}</div>
    </section>
</div>
@endsection
