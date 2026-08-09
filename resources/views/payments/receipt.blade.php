@extends('layouts.app')

@section('page_title', 'Payment Receipt')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 mb-1">Receipt {{ $payment->receipt_number }}</h2>
        <p class="text-muted mb-0">Generated for {{ $payment->service_label }}</p>
    </div>
    <div class="d-flex gap-2">
        @if($payment->payment_method === 'mtn_momo')
            <form method="POST" action="{{ route('payments.momo.refresh', $payment->payment_id) }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary">Refresh MTN Status</button>
            </form>
        @endif
        <button type="button" class="btn btn-primary" onclick="window.print()">Print Receipt</button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h3 class="h5 mb-1">ViLoCare Payment Receipt</h3>
                <div class="text-muted">Reference: {{ $payment->receipt_number }}</div>
            </div>
            <div class="text-end">
                <div class="fw-semibold">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</div>
                <div class="text-muted">{{ ucfirst($payment->status) }}</div>
            </div>
        </div>

        <div class="row gy-3">
            <div class="col-md-6">
                <strong>Patient Name</strong>
                <div>{{ $payment->patient?->first_name }} {{ $payment->patient?->last_name }}</div>
            </div>
            <div class="col-md-6">
                <strong>ART Number</strong>
                <div>{{ $payment->patient?->art_number ?: 'N/A' }}</div>
            </div>
            <div class="col-md-6">
                <strong>Service</strong>
                <div>{{ $payment->service_label }}</div>
            </div>
            <div class="col-md-6">
                <strong>Payment Type</strong>
                <div>{{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}</div>
            </div>
            <div class="col-md-6">
                <strong>Method</strong>
                <div>{{ strtoupper(str_replace('_', ' ', $payment->payment_method)) }}</div>
            </div>
            <div class="col-md-6">
                <strong>Paid At</strong>
                <div>{{ optional($payment->paid_at)->format('d M Y H:i') ?: 'Not yet paid' }}</div>
            </div>
            <div class="col-md-6">
                <strong>Recorded By</strong>
                <div>{{ $payment->creator?->name ?: 'System' }}</div>
            </div>
            <div class="col-md-6">
                <strong>External Reference</strong>
                <div>{{ $payment->external_reference ?: 'N/A' }}</div>
            </div>
            @if($payment->payment_method === 'mtn_momo')
                <div class="col-md-6">
                    <strong>MoMo Number</strong>
                    <div>{{ data_get($payment->meta, 'mtn_momo.phone_number') ?? data_get($payment->meta, 'payment_phone') ?? 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                    <strong>Gateway Transaction</strong>
                    <div>{{ data_get($payment->meta, 'mtn_momo.financial_transaction_id', 'Pending') }}</div>
                </div>
                <div class="col-md-6">
                    <strong>Gateway Status</strong>
                    <div>{{ data_get($payment->meta, 'mtn_momo.raw_status', strtoupper($payment->status)) }}</div>
                </div>
            @endif
            @if($payment->eacSession)
                <div class="col-md-6">
                    <strong>EAC Session</strong>
                    <div>Session {{ $payment->eacSession->session_number }}</div>
                </div>
            @endif
            @if($payment->notes)
                <div class="col-12">
                    <strong>Notes</strong>
                    <div>{{ $payment->notes }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
