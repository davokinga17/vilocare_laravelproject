@extends('layouts.app')

@section('page_title', 'Mastercard Checkout')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h4 mb-2">Mastercard Hosted Checkout</h2>
                <p class="text-muted mb-3">
                    The checkout session has been created. Use the button below to continue to the Mastercard sandbox payment page.
                </p>

                <div class="alert alert-info">
                    <strong>Receipt:</strong> {{ $payment->receipt_number }}
                    <br>
                    <strong>Amount:</strong> {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
                    <br>
                    <strong>Session:</strong> {{ $checkout['session_id'] }}
                </div>

                <button type="button" class="btn btn-primary" id="mastercardCheckoutButton">Continue to Mastercard</button>
                <a href="{{ route('payments.show', $payment) }}" class="btn btn-outline-secondary">Back to Payment</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ $checkout['checkout_js_url'] }}"
        data-error="errorCallback"
        data-cancel="cancelCallback"></script>
<script>
    function errorCallback(error) {
        console.error('Mastercard checkout error', error);
        alert('Mastercard checkout could not be started. Please check the payment details page for more information.');
    }

    function cancelCallback() {
        window.location.href = @json(route('payments.show', $payment));
    }

    Checkout.configure({
        session: {
            id: @json($checkout['session_id'])
        }
    });

    document.getElementById('mastercardCheckoutButton').addEventListener('click', function () {
        Checkout.showPaymentPage();
    });
</script>
@endpush
