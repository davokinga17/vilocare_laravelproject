@extends('layouts.app')

@section('page_title', 'Payment Receipt')

@push('styles')
    <link href="{{ asset('css/payment-receipt.css') }}" rel="stylesheet" />
@endpush

@section('content')
@php
    $isPaid = $payment->status === 'paid';
    $patientName = trim(($payment->patient?->first_name ?? '') . ' ' . ($payment->patient?->last_name ?? '')) ?: 'Unknown patient';
    $paidAt = optional($payment->paid_at)->format('d M Y H:i') ?: 'Not yet paid';
    $paymentType = ucwords(str_replace('_', ' ', $payment->payment_type));
    $paymentMethod = strtoupper(str_replace('_', ' ', $payment->payment_method));
    $receiptStatusClass = match ($payment->status) {
        'paid' => 'is-paid',
        'pending' => 'is-pending',
        'failed' => 'is-failed',
        default => 'is-neutral',
    };
@endphp

<div class="receipt-page">
    <div class="receipt-screen-actions">
        <a href="{{ route('payments.show', $payment->payment_id) }}" class="receipt-back-button"><span aria-hidden="true">←</span> Payment Details</a>
        <div>
            @if($payment->payment_method === 'mtn_momo' && !$isPaid)
                <form method="POST" action="{{ route('payments.momo.refresh', $payment->payment_id) }}">
                    @csrf
                    <button type="submit" class="receipt-refresh-button">Refresh Status</button>
                </form>
            @endif
            <button type="button" class="receipt-print-button" onclick="window.print()"><span aria-hidden="true">▣</span> Print Receipt</button>
        </div>
    </div>

    <article class="receipt-sheet">
        <header class="receipt-brand-row">
            <img src="{{ asset('images/vilocarelogo.png') }}" alt="ViLoCare" class="receipt-logo">
            <div class="receipt-title">
                <h2>Payment Receipt</h2>
                <p>ViLoCare Payment Receipt</p>
            </div>
        </header>

        <section class="receipt-hero">
            <div class="receipt-reference">
                <span>Receipt Reference</span>
                <strong>{{ $payment->receipt_number }}</strong>
                <p>Generated for {{ $payment->service_label }}</p>
            </div>
            <div class="receipt-amount">
                <span>Amount</span>
                <strong>{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</strong>
                <em class="receipt-status-pill {{ $receiptStatusClass }}"><i>{{ $isPaid ? '✓' : '•' }}</i> {{ strtoupper($payment->status) }}</em>
            </div>
        </section>

        <section class="receipt-summary-strip">
            <div class="summary-item">
                <span class="receipt-field-icon">#</span>
                <div><small>Receipt Number</small><strong>{{ $payment->receipt_number }}</strong></div>
            </div>
            <div class="summary-item">
                <span class="receipt-field-icon">▦</span>
                <div><small>Paid At</small><strong>{{ $paidAt }}</strong></div>
            </div>
            <div class="summary-item">
                <span class="receipt-field-icon">✓</span>
                <div><small>Status</small><strong>{{ ucfirst($payment->status) }}</strong></div>
            </div>
        </section>

        <section class="receipt-details-card">
            <div class="receipt-detail">
                <span class="receipt-field-icon">PT</span>
                <div><small>Patient Name</small><strong>{{ $patientName }}</strong></div>
            </div>
            <div class="receipt-detail">
                <span class="receipt-field-icon">ID</span>
                <div><small>ART Number</small><strong>{{ $payment->patient?->art_number ?: 'N/A' }}</strong></div>
            </div>
            <div class="receipt-detail">
                <span class="receipt-field-icon">SV</span>
                <div><small>Service</small><strong>{{ $payment->service_label }}</strong></div>
            </div>
            <div class="receipt-detail">
                <span class="receipt-field-icon">TY</span>
                <div><small>Payment Type</small><strong>{{ $paymentType }}</strong></div>
            </div>
            <div class="receipt-detail">
                <span class="receipt-field-icon">¤</span>
                <div><small>Method</small><strong>{{ $paymentMethod }}</strong></div>
            </div>
            <div class="receipt-detail">
                <span class="receipt-field-icon">◷</span>
                <div><small>Paid At</small><strong>{{ $paidAt }}</strong></div>
            </div>
            <div class="receipt-detail">
                <span class="receipt-field-icon">RB</span>
                <div><small>Recorded By</small><strong>{{ $payment->creator?->name ?: 'System' }}</strong></div>
            </div>
            <div class="receipt-detail">
                <span class="receipt-field-icon">AB</span>
                <div><small>Accepted By</small><strong>{{ $payment->acceptedBy?->name ?: 'Not yet accepted' }}</strong></div>
            </div>
            <div class="receipt-detail is-full">
                <span class="receipt-field-icon">RF</span>
                <div><small>External Reference</small><strong class="breakable-value">{{ $payment->external_reference ?: 'N/A' }}</strong></div>
            </div>
            @if($payment->eacSession)
                <div class="receipt-detail">
                    <span class="receipt-field-icon">EA</span>
                    <div><small>EAC Session</small><strong>Session {{ $payment->eacSession->session_number }}</strong></div>
                </div>
            @endif
            @if($payment->payment_method === 'mtn_momo')
                <div class="receipt-detail">
                    <span class="receipt-field-icon">MM</span>
                    <div><small>MoMo Number</small><strong>{{ data_get($payment->meta, 'mtn_momo.phone_number') ?? data_get($payment->meta, 'payment_phone') ?? 'N/A' }}</strong></div>
                </div>
            @endif
        </section>

        <section class="receipt-thank-you">
            <div class="thanks-message">
                <span class="thanks-icon" aria-hidden="true">♡</span>
                <div>
                    <h3>{{ $isPaid ? 'Thank you for choosing ViLoCare' : 'Payment request recorded' }}</h3>
                    <p>{{ $isPaid ? 'Your payment has been successfully processed. We appreciate your trust in our services.' : 'This payment is awaiting confirmation. Please retain this reference for follow-up.' }}</p>
                </div>
            </div>
            <div class="receipt-help">
                <span class="help-icon" aria-hidden="true">?</span>
                <div><h3>Need Help?</h3><p>support@vilocare.com</p><strong>+211923546133</strong></div>
            </div>
        </section>

        <footer class="receipt-footer"><span></span><p><i>✓</i> Computer generated receipt</p><span></span></footer>
    </article>
</div>
@endsection
