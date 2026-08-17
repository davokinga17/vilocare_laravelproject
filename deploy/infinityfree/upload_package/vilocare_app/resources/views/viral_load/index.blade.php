@extends('layouts.app')

@section('page_title', 'Viral Load Results')

@push('styles')
    <link href="{{ asset('css/clinical-pages.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="clinical-page">
    <header class="clinical-page-header">
        <div><h2>Viral Load Results</h2><p>View, filter, and manage patient viral load test results.</p></div>
        <div class="clinical-page-actions">
            <a href="{{ route('reports.viral_load.excel') }}" class="clinical-btn">Export Excel</a>
            <a href="{{ route('reports.viral_load.pdf') }}" class="clinical-btn clinical-btn-danger">Export PDF</a>
            @if(in_array(auth()->user()->role, ['Administrator','Lab Technician']))
                <a href="/viral-load/create" class="clinical-btn clinical-btn-primary"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add Result</a>
            @endif
        </div>
    </header>

    <form method="GET" action="/viral-load" class="clinical-card clinical-toolbar">
        <div class="clinical-field clinical-search"><label for="vl_search">Search</label><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input id="vl_search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search patient, ART number or result ID..."></div>
        <div class="clinical-field"><label for="vl_status">Result Status</label><select id="vl_status" name="status" class="form-select"><option value="">All statuses</option><option value="suppressed" @selected(request('status') === 'suppressed')>Suppressed</option><option value="high" @selected(request('status') === 'high')>High Viral Load</option></select></div>
        <div class="clinical-field"><label for="from_date">From Date</label><input id="from_date" type="date" name="from_date" value="{{ request('from_date') }}" class="form-control"></div>
        <div class="clinical-field"><label for="to_date">To Date</label><input id="to_date" type="date" name="to_date" value="{{ request('to_date') }}" class="form-control"></div>
        <div class="clinical-toolbar-actions"><button class="clinical-btn clinical-btn-primary">Apply</button><a href="/viral-load" class="clinical-btn">Reset</a></div>
    </form>

    <section class="clinical-card clinical-table-card">
        <div class="clinical-table-meta"><strong>Viral Load Register</strong><span>Showing {{ $results->firstItem() ?? 0 }}–{{ $results->lastItem() ?? 0 }} of {{ $results->total() }}</span></div>
        <div class="table-responsive">
            <table class="table clinical-table">
                <thead><tr><th>Result ID</th><th>ART Number</th><th>Patient</th><th>Sample Date</th><th>Result Date</th><th>Result (cp/ml)</th><th>Log</th><th>Status</th><th>Lab</th><th>Service Actions</th></tr></thead>
                <tbody>
                @forelse($results as $row)
                    <tr>
                        <td class="primary-cell">VL-{{ str_pad($row->vl_id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $row->patient?->art_number ?: '—' }}</td>
                        <td class="primary-cell">{{ $row->patient?->first_name }} {{ $row->patient?->last_name }}</td>
                        <td>{{ $row->sample_date ? \Illuminate\Support\Carbon::parse($row->sample_date)->format('d M Y') : '—' }}</td>
                        <td>{{ $row->result_date ? \Illuminate\Support\Carbon::parse($row->result_date)->format('d M Y') : '—' }}</td>
                        <td class="primary-cell">{{ $row->result_cpml !== null ? number_format((float) $row->result_cpml, 2) : '—' }}</td>
                        <td>{{ $row->result_log !== null ? number_format((float) $row->result_log, 2) : '—' }}</td>
                        <td>@if((float) $row->result_cpml >= 1000)<span class="clinical-badge clinical-badge-danger">High VL</span>@else<span class="clinical-badge clinical-badge-success">Suppressed</span>@endif</td>
                        <td>{{ $row->lab ?: '—' }}</td>
                        <td>
                            <div class="clinical-row-actions">
                                @php($printPayment = $row->payments->where('payment_type', 'result_print')->sortByDesc('payment_id')->first())
                                @if(!in_array($row->vl_id, $latestResultIds, true))
                                    <span class="clinical-badge clinical-badge-neutral">Historical</span>
                                @elseif(in_array(auth()->user()->role, ['Administrator','Clinician','Lab Technician']))
                                    @if($printPayment?->status === 'paid')
                                        <a href="{{ route('patients.results.print', $row->patient_id) }}" class="clinical-btn">Print</a>
                                    @elseif($printPayment?->status === 'pending')
                                        <span class="clinical-badge clinical-badge-warning">Awaiting Reception</span>
                                    @else
                                        <form method="POST" action="{{ route('payments.request') }}">@csrf<input type="hidden" name="patient_id" value="{{ $row->patient_id }}"><input type="hidden" name="vl_id" value="{{ $row->vl_id }}"><input type="hidden" name="payment_type" value="result_print"><button class="clinical-btn" type="submit">Request Print</button></form>
                                    @endif
                                @endif
                                @if(in_array(auth()->user()->role, ['Administrator','Clinician']))
                                    <button
                                        type="button"
                                        class="clinical-btn"
                                        data-sms-action="{{ route('sms.viral_load.send_result', $row->vl_id) }}"
                                        data-sms-title="Viral Load Result SMS"
                                        data-sms-hint="Verify the recipient number and edit the viral load result message if needed."
                                        data-sms-phone="{{ $row->patient?->phone }}"
                                        data-sms-message="{{ app(\App\Services\NotificationService::class)->buildViralLoadResultMessage($row) }}"
                                    >Send SMS</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty<tr><td colspan="10" class="clinical-table-empty">No viral load results match the selected filters.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="clinical-pagination">{{ $results->links() }}</div>
    </section>
</div>
@endsection
