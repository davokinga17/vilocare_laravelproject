@extends('layouts.app')

@section('page_title', 'Payments')

@push('styles')
    <link href="{{ asset('css/payments.css') }}" rel="stylesheet" />
@endpush

@section('content')
@php
    $collectedToday = $paidTodayByCurrency
        ->map(fn ($item) => strtoupper($item->currency) . ' ' . number_format((float) $item->total, 2))
        ->implode(' · ');
    $statusTone = fn (string $status) => match ($status) {
        'paid' => 'is-paid',
        'pending' => 'is-pending',
        'failed' => 'is-failed',
        'cancelled' => 'is-cancelled',
        default => 'is-neutral',
    };
    $methodInitials = fn (string $method) => match ($method) {
        'cash' => 'CA',
        'mtn_momo' => 'MM',
        default => strtoupper(substr($method, 0, 2)),
    };
@endphp

<div class="payments-page">
    <header class="payments-header">
        <div>
            <span class="payments-eyebrow">Reception · Cashier Desk</span>
            <h2>Payment Requests</h2>
            <p>Review, receive and confirm payments for EAC and patient-result services.</p>
        </div>
        <a href="{{ route('payments.create') }}" class="payments-primary-action">
            <span aria-hidden="true">＋</span> Record Payment
        </a>
    </header>

    <section class="payment-metrics" aria-label="Payment overview">
        <article class="payment-metric metric-pending">
            <span class="metric-icon" aria-hidden="true">⌛</span>
            <div><p>Awaiting Reception</p><strong>{{ number_format($pendingCount) }}</strong><small>Payment {{ Str::plural('request', $pendingCount) }} requiring action</small></div>
        </article>
        <article class="payment-metric metric-paid">
            <span class="metric-icon" aria-hidden="true">✓</span>
            <div><p>Payments Accepted Today</p><strong>{{ number_format($paidTodayCount) }}</strong><small>Confirmed since midnight</small></div>
        </article>
        <article class="payment-metric metric-collected">
            <span class="metric-icon" aria-hidden="true">¤</span>
            <div><p>Collected Today</p><strong class="metric-money">{{ $collectedToday ?: 'No collections' }}</strong><small>Separated by transaction currency</small></div>
        </article>
        <article class="payment-metric metric-total">
            <span class="metric-icon" aria-hidden="true">#</span>
            <div><p>Total Transactions</p><strong>{{ number_format($totalPayments) }}</strong><small>All recorded payment requests</small></div>
        </article>
    </section>

    <section class="payment-workspace">
        <div class="payment-workspace-head">
            <div><h3>Payment Register</h3><p>Pending requests appear first for faster reception processing.</p></div>
            <span>{{ number_format($payments->total()) }} {{ Str::plural('record', $payments->total()) }}</span>
        </div>

        <form method="GET" action="{{ route('payments.index') }}" class="payment-filters">
            <div class="payment-search">
                <label for="search">Find a transaction</label>
                <div><span aria-hidden="true">⌕</span><input id="search" name="search" value="{{ request('search') }}" placeholder="Receipt, patient, ART number or service"></div>
            </div>
            <div class="payment-filter-field">
                <label for="payment_type">Service</label>
                <select name="payment_type" id="payment_type">
                    <option value="">All Services</option>
                    <option value="eac_consultation" @selected(request('payment_type') === 'eac_consultation')>EAC Consultation</option>
                    <option value="result_print" @selected(request('payment_type') === 'result_print')>Result Print</option>
                    <option value="result_pdf" @selected(request('payment_type') === 'result_pdf')>Result PDF</option>
                </select>
            </div>
            <div class="payment-filter-field">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>
            <div class="payment-filter-field">
                <label for="payment_method">Method</label>
                <select name="payment_method" id="payment_method">
                    <option value="">All Methods</option>
                    @foreach($paymentMethodOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('payment_method') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="payment-filter-actions">
                <button type="submit" class="payment-filter-button"><span aria-hidden="true">▽</span> Apply</button>
                <a href="{{ route('payments.index') }}" class="payment-reset-button"><span aria-hidden="true">↻</span> Reset</a>
            </div>
        </form>

        <div class="payment-table-wrap">
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Paid At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr class="{{ $payment->status === 'pending' ? 'is-priority-row' : '' }}">
                            <td><a class="receipt-link" href="{{ route('payments.show', $payment->payment_id) }}">{{ $payment->receipt_number }}</a></td>
                            <td>
                                @if($payment->patient)
                                    <div class="patient-cell">
                                        <span>{{ strtoupper(substr($payment->patient->first_name, 0, 1) . substr($payment->patient->last_name, 0, 1)) }}</span>
                                        <div><strong>{{ $payment->patient->first_name }} {{ $payment->patient->last_name }}</strong><small>{{ $payment->patient->art_number ?: 'No ART number' }}</small></div>
                                    </div>
                                @else
                                    <span class="cell-muted">Unknown patient</span>
                                @endif
                            </td>
                            <td><strong class="service-name">{{ $payment->service_label }}</strong></td>
                            <td><strong class="payment-amount">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</strong></td>
                            <td><span class="method-cell"><span>{{ $methodInitials($payment->payment_method) }}</span>{{ $paymentMethodOptions[$payment->payment_method] ?? strtoupper(str_replace('_', ' ', $payment->payment_method)) }}</span></td>
                            <td><span class="payment-status {{ $statusTone($payment->status) }}"><i></i>{{ ucfirst($payment->status) }}</span></td>
                            <td><span class="requester-name">{{ $payment->creator?->name ?: 'System' }}</span></td>
                            <td>
                                @if($payment->paid_at)
                                    <span class="payment-date">{{ $payment->paid_at->format('d M Y') }}<small>{{ $payment->paid_at->format('H:i') }}</small></span>
                                @else
                                    <span class="cell-muted">Not yet paid</span>
                                @endif
                            </td>
                            <td>
                                <div class="payment-row-actions">
                                    @if($payment->status === 'pending')
                                        <form method="POST" action="{{ route('payments.accept_cash', $payment) }}" onsubmit="return confirm('Confirm that cash was received for this service?')">
                                            @csrf
                                            <button type="submit" class="accept-cash-button"><span aria-hidden="true">✓</span> Accept Cash</button>
                                        </form>
                                    @endif
                                    @if($payment->payment_method === 'mtn_momo' && in_array($payment->status, ['failed', 'cancelled'], true))
                                        <form method="POST" action="{{ route('payments.retry', $payment->payment_id) }}">
                                            @csrf
                                            <button type="submit" class="retry-payment-button">Retry</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('payments.show', $payment->payment_id) }}" class="view-payment-button" aria-label="View payment {{ $payment->receipt_number }}">View</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><div class="payment-empty"><span aria-hidden="true">¤</span><strong>No payments found</strong><p>Try adjusting the filters or record a new payment.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="payment-pagination">{{ $payments->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>
</div>
@endsection
