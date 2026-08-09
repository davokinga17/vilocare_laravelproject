@extends('layouts.app')

@section('page_title', 'Payment Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 mb-1">{{ $payment->service_label }}</h2>
        <p class="text-muted mb-0">Receipt {{ $payment->receipt_number }}</p>
    </div>
    <div class="d-flex gap-2">
        @if($payment->payment_method === 'mtn_momo')
            <form method="POST" action="{{ route('payments.momo.refresh', $payment->payment_id) }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary">Refresh MTN Status</button>
            </form>
        @endif
        <a href="{{ route('payments.receipt', $payment->payment_id) }}" class="btn btn-primary">Open Receipt</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="row gy-3">
            <div class="col-md-6"><strong>Patient:</strong> {{ $payment->patient?->first_name }} {{ $payment->patient?->last_name }}</div>
            <div class="col-md-6"><strong>ART Number:</strong> {{ $payment->patient?->art_number }}</div>
            <div class="col-md-6"><strong>Amount:</strong> {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</div>
            <div class="col-md-6"><strong>Status:</strong> {{ ucfirst($payment->status) }}</div>
            <div class="col-md-6"><strong>Method:</strong> {{ strtoupper(str_replace('_', ' ', $payment->payment_method)) }}</div>
            <div class="col-md-6"><strong>Paid At:</strong> {{ optional($payment->paid_at)->format('d M Y H:i') ?: 'Not yet paid' }}</div>
            <div class="col-md-6"><strong>Recorded By:</strong> {{ $payment->creator?->name ?: 'System' }}</div>
            <div class="col-md-6"><strong>External Ref:</strong> {{ $payment->external_reference ?: 'N/A' }}</div>
            @if($payment->payment_method === 'mtn_momo')
                <div class="col-md-6"><strong>MoMo Number:</strong> {{ data_get($payment->meta, 'mtn_momo.phone_number') ?? data_get($payment->meta, 'payment_phone') ?? 'N/A' }}</div>
                <div class="col-md-6"><strong>Gateway Transaction:</strong> {{ data_get($payment->meta, 'mtn_momo.financial_transaction_id', 'Pending') }}</div>
                <div class="col-12"><strong>Gateway Status:</strong> {{ data_get($payment->meta, 'mtn_momo.raw_status', strtoupper($payment->status)) }}</div>
                @if(data_get($payment->meta, 'mtn_momo.reason'))
                    <div class="col-12"><strong>Status Note:</strong> {{ data_get($payment->meta, 'mtn_momo.reason') }}</div>
                @endif
            @endif
            @if($payment->notes)
                <div class="col-12"><strong>Notes:</strong> {{ $payment->notes }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
