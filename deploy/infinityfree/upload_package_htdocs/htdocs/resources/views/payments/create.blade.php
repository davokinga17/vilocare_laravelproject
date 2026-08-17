@extends('layouts.app')

@section('page_title', 'Record Payment')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h4 mb-2">Record Payment</h2>
                <p class="text-muted mb-4">Capture a payment for an EAC consultation or a patient result request.</p>

                @if($gatewaySimulationEnabled)
                    <div class="alert alert-info">
                        <strong>Local testing mode is enabled.</strong> MTN MoMo Pay will use the built-in simulator on localhost until you set <code>VILOCARE_SIMULATE_PAYMENT_GATEWAYS=false</code>.
                    </div>
                @endif

                <div class="alert alert-light border">
                    <strong>Gateway readiness:</strong>
                    <div class="small mt-2">
                        MTN MoMo:
                        <span class="{{ $gatewayConfig['mtn_momo'] ? 'text-success' : 'text-warning' }}">
                            {{ $gatewayConfig['mtn_momo'] ? 'configured' : 'credentials needed' }}
                        </span>
                    </div>
                </div>

                @if($patient)
                    <div class="alert alert-info">
                        <strong>Patient:</strong> {{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->art_number }})
                    </div>
                @endif

                @if($eacSession)
                    <div class="alert alert-warning">
                        <strong>EAC Session:</strong> Session {{ $eacSession->session_number }} on {{ $eacSession->session_date ?: 'Pending scheduling' }}
                    </div>
                @endif

                <form method="POST" action="{{ route('payments.store') }}">
                    @csrf
                    @if($patient)
                        <input type="hidden" name="patient_id" value="{{ old('patient_id', $patient->patient_id) }}">
                    @else
                        <div class="mb-3">
                            <label for="patient_id" class="form-label">Patient</label>
                            <select name="patient_id" id="patient_id" class="form-select" required>
                                <option value="">Select patient</option>
                                @foreach($patients as $patientOption)
                                    <option value="{{ $patientOption->patient_id }}" @selected((string) old('patient_id') === (string) $patientOption->patient_id)>
                                        {{ $patientOption->art_number }} - {{ $patientOption->first_name }} {{ $patientOption->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <input type="hidden" name="eac_id" value="{{ old('eac_id', $eacSession?->eac_id) }}">
                    <input type="hidden" name="vl_id" value="{{ old('vl_id', $viralLoad?->vl_id) }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="payment_type" class="form-label">Payment Type</label>
                            <select name="payment_type" id="payment_type" class="form-select" required>
                                <option value="eac_consultation" @selected(old('payment_type', $paymentType) === 'eac_consultation')>EAC Consultation</option>
                                <option value="result_print" @selected(old('payment_type', $paymentType) === 'result_print')>Result Print</option>
                                <option value="result_pdf" @selected(old('payment_type', $paymentType) === 'result_pdf')>Result PDF</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="service_label" class="form-label">Service Label</label>
                            <input type="text" name="service_label" id="service_label" class="form-control" value="{{ old('service_label', $defaults['service_label']) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control" value="{{ old('amount', $defaults['amount']) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="currency" class="form-label">Currency</label>
                            <input type="text" name="currency" id="currency" class="form-control" value="{{ old('currency', $defaults['currency']) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                @foreach($paymentMethodOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_method', $defaults['payment_method']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6" id="statusField">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select" required>
                                @foreach(['paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed', 'cancelled' => 'Cancelled'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $defaults['status']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6" id="paidAtField">
                            <label for="paid_at" class="form-label">Paid At</label>
                            <input type="datetime-local" name="paid_at" id="paid_at" class="form-control" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-md-6 d-none" id="paymentPhoneField">
                            <label for="payment_phone" class="form-label">MTN MoMo Number</label>
                            <input type="text" name="payment_phone" id="payment_phone" class="form-control" value="{{ old('payment_phone', $patient?->phone) }}" placeholder="e.g. +2119XXXXXXXX">
                            <div class="form-text">Enter the customer number in international format when possible. The patient will receive a prompt on their phone.</div>
                        </div>
                        <div class="col-12 d-none" id="momoNotice">
                            <div class="alert alert-info mb-0">
                                MTN MoMo payments are started as <strong>pending</strong>. After the customer approves on the phone, open the payment details page and refresh the status.
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="external_reference" class="form-label">External Reference</label>
                            <input type="text" name="external_reference" id="external_reference" class="form-control" value="{{ old('external_reference') }}" placeholder="Optional mobile money or ledger reference">
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Optional payment notes">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary" id="paymentSubmitButton">Save Payment</button>
                        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var paymentMethod = document.getElementById('payment_method');
        var statusField = document.getElementById('statusField');
        var paidAtField = document.getElementById('paidAtField');
        var paymentPhoneField = document.getElementById('paymentPhoneField');
        var momoNotice = document.getElementById('momoNotice');
        var submitButton = document.getElementById('paymentSubmitButton');
        var statusInput = document.getElementById('status');
        var paidAtInput = document.getElementById('paid_at');

        if (!paymentMethod) {
            return;
        }

        function syncPaymentForm() {
            var isMomo = paymentMethod.value === 'mtn_momo';
            var isGateway = isMomo;

            statusField.classList.toggle('d-none', isGateway);
            paidAtField.classList.toggle('d-none', isGateway);
            paymentPhoneField.classList.toggle('d-none', !isMomo);
            momoNotice.classList.toggle('d-none', !isMomo);

            if (isGateway) {
                statusInput.value = 'pending';
                paidAtInput.value = '';
            }

            if (isMomo) {
                var currencyInput = document.getElementById('currency');
                if (currencyInput) {
                    currencyInput.value = 'EUR';
                }
                submitButton.textContent = 'Start MTN MoMo Request';
                momoNotice.querySelector('.alert').innerHTML = 'MTN MoMo sandbox uses <strong>EUR</strong>. On localhost, ViLoCare will skip the callback header and you can use <strong>Refresh Gateway Status</strong> after the customer approves the request.';
            } else {
                submitButton.textContent = 'Save Payment';
            }
        }

        paymentMethod.addEventListener('change', syncPaymentForm);
        syncPaymentForm();
    })();
</script>
@endpush
