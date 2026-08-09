@extends('layouts.app')

@section('page_title', 'Payments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 mb-1">Payments</h2>
        <p class="text-muted mb-0">Track EAC consultation fees and patient result request charges.</p>
    </div>
    <a href="{{ route('payments.create') }}" class="btn btn-primary">Record Payment</a>
</div>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label for="payment_type" class="form-label">Payment Type</label>
        <select name="payment_type" id="payment_type" class="form-select">
            <option value="">All Types</option>
            <option value="eac_consultation" @selected(request('payment_type') === 'eac_consultation')>EAC Consultation</option>
            <option value="result_print" @selected(request('payment_type') === 'result_print')>Result Print</option>
            <option value="result_pdf" @selected(request('payment_type') === 'result_pdf')>Result PDF</option>
        </select>
    </div>
    <div class="col-md-3">
        <label for="status" class="form-label">Status</label>
        <select name="status" id="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="paid" @selected(request('status') === 'paid')>Paid</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="failed" @selected(request('status') === 'failed')>Failed</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-outline-secondary">Filter</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Receipt</th>
                    <th>Patient</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Paid At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->receipt_number }}</td>
                        <td>
                            @if($payment->patient)
                                {{ $payment->patient->first_name }} {{ $payment->patient->last_name }}
                            @else
                                <span class="text-muted">Unknown patient</span>
                            @endif
                        </td>
                        <td>{{ $payment->service_label }}</td>
                        <td>{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                        <td><span class="badge text-bg-{{ $payment->status === 'paid' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($payment->status) }}</span></td>
                        <td>{{ optional($payment->paid_at)->format('d M Y H:i') ?: 'Not yet paid' }}</td>
                        <td class="text-end">
                            <a href="{{ route('payments.receipt', $payment->payment_id) }}" class="btn btn-sm btn-outline-primary">Receipt</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No payments recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $payments->links() }}
</div>
@endsection
