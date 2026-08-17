@extends('layouts.app')

@section('page_title', 'Payment Simulator')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h4 mb-2">{{ strtoupper(str_replace('_', ' ', $gateway)) }} Sandbox Simulator</h2>
                <p class="text-muted mb-4">
                    This local simulator lets us rehearse the full gateway flow on localhost before using the real sandbox on InfinityFree.
                </p>

                <div class="alert alert-info">
                    <strong>Payment:</strong> {{ $payment->service_label }} for {{ $payment->patient?->first_name }} {{ $payment->patient?->last_name }}
                    <br>
                    <strong>Amount:</strong> {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
                </div>

                <p class="mb-3">Choose the outcome you want to test:</p>

                <div class="d-flex flex-wrap gap-2">
                    @foreach(['paid' => 'Simulate Paid', 'failed' => 'Simulate Failed', 'cancelled' => 'Simulate Cancelled', 'pending' => 'Leave Pending'] as $value => $label)
                        <form method="POST" action="{{ route('payments.gateway.simulate.complete', [$payment, $gateway]) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $value }}">
                            <button type="submit" class="btn {{ $value === 'paid' ? 'btn-success' : ($value === 'pending' ? 'btn-outline-secondary' : 'btn-outline-danger') }}">
                                {{ $label }}
                            </button>
                        </form>
                    @endforeach
                </div>

                <div class="mt-4">
                    <a href="{{ route('payments.show', $payment) }}" class="btn btn-link px-0">Back to payment details</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
