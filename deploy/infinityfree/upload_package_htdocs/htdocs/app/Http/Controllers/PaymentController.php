<?php

namespace App\Http\Controllers;

use App\Models\EACSession;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\ViralLoad;
use App\Services\MastercardService;
use App\Services\MtnMomoService;
use App\Services\PesapalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly MtnMomoService $mtnMomoService,
        private readonly PesapalService $pesapalService,
        private readonly MastercardService $mastercardService
    ) {
    }

    public function index(Request $request): View
    {
        $query = Payment::with(['patient', 'eacSession', 'creator', 'acceptedBy'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('payment_id');

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(function ($paymentQuery) use ($search) {
                $paymentQuery
                    ->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('service_label', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery
                            ->where('art_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->string('payment_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->string('payment_method'));
        }

        $payments = $query->paginate(12)->withQueryString();

        $paidTodayQuery = Payment::paid()->whereDate('paid_at', today());
        $paidTodayByCurrency = (clone $paidTodayQuery)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        return view('payments.index', [
            'payments' => $payments,
            'paymentMethodOptions' => $this->paymentMethodOptions(),
            'pendingCount' => Payment::where('status', 'pending')->count(),
            'paidToday' => (clone $paidTodayQuery)->sum('amount'),
            'paidTodayCount' => (clone $paidTodayQuery)->count(),
            'paidTodayByCurrency' => $paidTodayByCurrency,
            'totalPayments' => Payment::count(),
        ]);
    }

    public function create(Request $request): View
    {
        $patients = Patient::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['patient_id', 'art_number', 'first_name', 'last_name', 'phone']);

        $patient = $request->filled('patient_id')
            ? Patient::findOrFail($request->integer('patient_id'))
            : null;

        $eacSession = $request->filled('eac_id')
            ? EACSession::with('patient')->findOrFail($request->integer('eac_id'))
            : null;

        $viralLoad = $request->filled('vl_id')
            ? ViralLoad::find($request->integer('vl_id'))
            : null;

        if (! $patient && $eacSession) {
            $patient = $eacSession->patient;
        }

        $paymentType = (string) $request->string('payment_type', 'eac_consultation');
        $defaults = $this->paymentDefaults($paymentType, $eacSession);
        $gatewayConfig = $this->gatewayConfiguration();

        return view('payments.create', compact(
            'patient',
            'patients',
            'eacSession',
            'viralLoad',
            'paymentType',
            'defaults',
            'gatewayConfig'
        ) + [
            'paymentMethodOptions' => $this->paymentMethodOptions(),
            'gatewaySimulationEnabled' => $this->gatewaySimulationEnabled(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,patient_id'],
            'eac_id' => ['nullable', 'integer', 'exists:eac_sessions,eac_id'],
            'vl_id' => ['nullable', 'integer', 'exists:viral_load_results,vl_id'],
            'payment_type' => ['required', 'string', 'in:eac_consultation,result_print,result_pdf'],
            'service_label' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', array_keys($this->paymentMethodOptions()))],
            'status' => ['nullable', 'string', 'in:pending,paid,failed,cancelled'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
            'paid_at' => ['nullable', 'date'],
            'payment_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $paymentMethod = (string) $validated['payment_method'];
        $isGatewayPayment = $paymentMethod === 'mtn_momo';

        if (! $isGatewayPayment && blank($validated['status'] ?? null)) {
            throw ValidationException::withMessages([
                'status' => ['Select a payment status.'],
            ]);
        }

        if ($paymentMethod === 'mtn_momo' && blank($validated['payment_phone'] ?? null)) {
            throw ValidationException::withMessages([
                'payment_phone' => ['Enter the MTN Mobile Money phone number to charge.'],
            ]);
        }

        if ($isGatewayPayment && ! $this->gatewaySimulationEnabled() && ! $this->gatewayIsConfigured($paymentMethod)) {
            throw ValidationException::withMessages([
                'payment_method' => ['The selected payment gateway is not configured yet. Add its sandbox credentials in the environment first.'],
            ]);
        }

        $currency = strtoupper((string) $validated['currency']);

        if ($paymentMethod === 'mtn_momo') {
            $currency = $this->mtnMomoService->requestCurrency($currency);
        }

        $status = $isGatewayPayment ? 'pending' : (string) $validated['status'];
        $paidAt = $status === 'paid'
            ? Carbon::parse($validated['paid_at'] ?? now())
            : null;

        $payment = Payment::create([
            'patient_id' => $validated['patient_id'],
            'eac_id' => $validated['eac_id'] ?? null,
            'vl_id' => $validated['vl_id'] ?? null,
            'created_by' => auth()->id(),
            'accepted_by' => $status === 'paid' ? auth()->id() : null,
            'payment_type' => $validated['payment_type'],
            'service_label' => $validated['service_label'],
            'amount' => $validated['amount'],
            'currency' => $currency,
            'payment_method' => $paymentMethod,
            'status' => $status,
            'receipt_number' => $this->nextReceiptNumber(),
            'external_reference' => $validated['external_reference'] ?? null,
            'paid_at' => $paidAt,
            'accepted_at' => $status === 'paid' ? $paidAt : null,
            'notes' => $validated['notes'] ?? null,
            'meta' => [
                'recorded_from' => $validated['payment_type'] === 'eac_consultation' ? 'eac' : 'patient_results',
                'payment_phone' => $validated['payment_phone'] ?? null,
                'gateway_mode' => $isGatewayPayment && $this->gatewaySimulationEnabled() ? 'simulation' : 'sandbox',
            ],
        ]);

        return match ($paymentMethod) {
            'mtn_momo' => $this->startMtnMomoPayment($payment, (string) $validated['payment_phone']),
            default => redirect()
                ->route('payments.receipt', $payment->getKey())
                ->with('success', 'Payment recorded successfully.'),
        };
    }

    public function requestPayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,patient_id'],
            'eac_id' => ['nullable', 'integer', 'exists:eac_sessions,eac_id'],
            'vl_id' => ['nullable', 'integer', 'exists:viral_load_results,vl_id'],
            'payment_type' => ['required', 'string', 'in:eac_consultation,result_print,result_pdf'],
        ]);

        $patientId = (int) $validated['patient_id'];
        $paymentType = (string) $validated['payment_type'];
        $eacSession = null;
        $viralLoad = null;

        if ($paymentType === 'eac_consultation') {
            $eacSession = EACSession::findOrFail((int) ($validated['eac_id'] ?? 0));
            abort_unless((int) $eacSession->patient_id === $patientId, 422, 'The EAC session does not belong to this patient.');
        } else {
            $viralLoad = ViralLoad::findOrFail((int) ($validated['vl_id'] ?? 0));
            abort_unless((int) $viralLoad->patient_id === $patientId, 422, 'The viral load result does not belong to this patient.');
        }

        $existing = Payment::query()
            ->where('patient_id', $patientId)
            ->where('payment_type', $paymentType)
            ->when($eacSession, fn ($query) => $query->where('eac_id', $eacSession->eac_id))
            ->when($viralLoad, fn ($query) => $query->where('vl_id', $viralLoad->vl_id))
            ->whereIn('status', ['pending', 'paid'])
            ->latest('payment_id')
            ->first();

        if ($existing) {
            return back()->with(
                $existing->isPaid() ? 'success' : 'warning',
                $existing->isPaid()
                    ? 'This service has already been paid for.'
                    : 'A payment request for this service is already waiting at Reception.'
            );
        }

        $defaults = $this->paymentDefaults($paymentType, $eacSession);

        Payment::create([
            'patient_id' => $patientId,
            'eac_id' => $eacSession?->eac_id,
            'vl_id' => $viralLoad?->vl_id,
            'created_by' => $request->user()->id,
            'payment_type' => $paymentType,
            'service_label' => $defaults['service_label'],
            'amount' => $defaults['amount'],
            'currency' => $defaults['currency'],
            'payment_method' => 'cash',
            'status' => 'pending',
            'receipt_number' => $this->nextReceiptNumber(),
            'notes' => 'Service payment requested by ' . $request->user()->name . '.',
            'meta' => [
                'recorded_from' => $paymentType === 'eac_consultation' ? 'eac' : 'patient_results',
                'request_source' => 'clinical_service',
            ],
        ]);

        return back()->with('success', 'Payment request sent to the Receptionist desk. The service will unlock after payment is accepted.');
    }

    public function acceptCash(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending') {
            return back()->with('warning', 'Only pending payment requests can be accepted.');
        }

        $validated = $request->validate([
            'external_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $acceptedAt = now();
        $payment->forceFill([
            'payment_method' => 'cash',
            'status' => 'paid',
            'paid_at' => $acceptedAt,
            'accepted_by' => $request->user()->id,
            'accepted_at' => $acceptedAt,
            'external_reference' => $validated['external_reference'] ?? $payment->external_reference,
            'notes' => filled($validated['notes'] ?? null)
                ? trim((string) $payment->notes . "\n" . $validated['notes'])
                : $payment->notes,
            'meta' => $this->mergeMeta($payment, [
                'cashier' => [
                    'accepted_by' => $request->user()->id,
                    'accepted_at' => $acceptedAt->toIso8601String(),
                ],
            ]),
        ])->save();

        return redirect()
            ->route('payments.receipt', $payment)
            ->with('success', 'Cash payment accepted. The requested service is now authorized.');
    }

    public function show(Payment $payment): View
    {
        $payment->load(['patient', 'eacSession', 'viralLoad', 'creator', 'acceptedBy']);

        return view('payments.show', [
            'payment' => $payment,
            'paymentMethodOptions' => $this->paymentMethodOptions(),
        ]);
    }

    public function receipt(Payment $payment): View
    {
        $payment->load(['patient', 'eacSession', 'viralLoad', 'creator', 'acceptedBy']);

        return view('payments.receipt', compact('payment'));
    }

    public function retry(Payment $payment)
    {
        if ($payment->payment_method !== 'mtn_momo') {
            return back()->with('warning', 'Only MTN MoMo payments can be retried.');
        }

        if (! in_array($payment->status, ['failed', 'cancelled'], true)) {
            return back()->with('warning', 'Only failed or cancelled payments can be retried.');
        }

        $replacement = Payment::create([
            'patient_id' => $payment->patient_id,
            'eac_id' => $payment->eac_id,
            'vl_id' => $payment->vl_id,
            'created_by' => auth()->id(),
            'payment_type' => $payment->payment_type,
            'service_label' => $payment->service_label,
            'amount' => $payment->amount,
            'currency' => $payment->payment_method === 'mtn_momo'
                ? $this->mtnMomoService->requestCurrency((string) $payment->currency)
                : $payment->currency,
            'payment_method' => $payment->payment_method,
            'status' => 'pending',
            'receipt_number' => $this->nextReceiptNumber(),
            'notes' => $payment->notes,
            'meta' => [
                'recorded_from' => data_get($payment->meta, 'recorded_from'),
                'payment_phone' => data_get($payment->meta, 'payment_phone'),
                'gateway_mode' => $payment->payment_method === 'mtn_momo' && ! $this->gatewaySimulationEnabled() ? 'sandbox' : data_get($payment->meta, 'gateway_mode'),
                'retried_from_payment_id' => $payment->payment_id,
            ],
        ]);

        return match ($replacement->payment_method) {
            'mtn_momo' => $this->startMtnMomoPayment(
                $replacement,
                (string) (data_get($replacement->meta, 'payment_phone') ?: data_get($payment->meta, 'mtn_momo.phone_number'))
            ),
            default => redirect()
                ->route('payments.show', $replacement)
                ->with('success', 'Payment retried successfully.'),
        };
    }

    public function refreshMomoStatus(Payment $payment): RedirectResponse
    {
        return $this->refreshGatewayStatus($payment);
    }

    public function refreshGatewayStatus(Payment $payment): RedirectResponse
    {
        try {
            match ($payment->payment_method) {
                'mtn_momo' => $this->refreshMomoPayment($payment),
                default => throw ValidationException::withMessages([
                    'payment' => ['This payment method does not support manual status refresh.'],
                ]),
            };
        } catch (ValidationException $exception) {
            return back()->with('warning', Arr::first($exception->errors()['payment'] ?? ['Unable to refresh the payment status.']));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('warning', $exception->getMessage());
        }

        return back()->with('success', 'Payment status refreshed successfully.');
    }

    public function momoCallback(Request $request, Payment $payment): JsonResponse
    {
        $statusData = [
            'status' => $this->mapIncomingMomoStatus((string) $request->input('status', 'pending')),
            'raw_status' => $request->input('status'),
            'financial_transaction_id' => $request->input('financialTransactionId'),
            'reason' => $request->input('reason'),
            'payload' => $request->all(),
        ];

        $this->applyMomoStatus($payment, $statusData);

        return response()->json(['ok' => true]);
    }

    public function pesapalCallback(Request $request, Payment $payment): RedirectResponse
    {
        try {
            $trackingId = (string) $request->string('OrderTrackingId', $payment->external_reference ?? '');

            if ($trackingId !== '') {
                $statusData = $this->pesapalService->getTransactionStatus($trackingId);
                $this->applyPesapalStatus($payment, $statusData);
            }
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('payments.show', $payment)
                ->with('warning', 'Pesapal returned to the app, but the status could not be verified automatically. Please use refresh.');
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Pesapal payment return processed.');
    }

    public function pesapalIpn(Request $request): JsonResponse
    {
        $merchantReference = (string) $request->input('OrderMerchantReference', '');
        $trackingId = (string) $request->input('OrderTrackingId', '');

        if ($merchantReference === '' || $trackingId === '') {
            return response()->json([
                'status' => 400,
                'message' => 'Missing Pesapal IPN identifiers.',
            ], 400);
        }

        $payment = Payment::query()->where('receipt_number', $merchantReference)->first();

        if (! $payment) {
            return response()->json([
                'status' => 404,
                'message' => 'Payment not found.',
            ], 404);
        }

        try {
            $statusData = $this->pesapalService->getTransactionStatus($trackingId);
            $this->applyPesapalStatus($payment, $statusData);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'orderNotificationType' => $request->input('OrderNotificationType', 'IPNCHANGE'),
                'orderTrackingId' => $trackingId,
                'orderMerchantReference' => $merchantReference,
                'status' => 500,
            ], 500);
        }

        return response()->json([
            'orderNotificationType' => $request->input('OrderNotificationType', 'IPNCHANGE'),
            'orderTrackingId' => $trackingId,
            'orderMerchantReference' => $merchantReference,
            'status' => 200,
        ]);
    }

    public function mastercardReturn(Request $request, Payment $payment): RedirectResponse
    {
        $resultIndicator = (string) $request->string('resultIndicator', '');
        $expectedIndicator = (string) data_get($payment->meta, 'mastercard.success_indicator', '');
        $status = $resultIndicator !== '' && hash_equals($expectedIndicator, $resultIndicator) ? 'paid' : 'failed';

        $payment->forceFill([
            'status' => $status,
            'paid_at' => $status === 'paid' ? ($payment->paid_at ?? now()) : null,
            'meta' => $this->mergeMeta($payment, [
                'mastercard' => array_filter([
                    'session_id' => data_get($payment->meta, 'mastercard.session_id'),
                    'success_indicator' => $expectedIndicator,
                    'result_indicator' => $resultIndicator,
                    'returned_at' => now()->toIso8601String(),
                ]),
            ]),
        ])->save();

        return redirect()
            ->route('payments.show', $payment)
            ->with($status === 'paid' ? 'success' : 'warning', $status === 'paid'
                ? 'Mastercard payment completed successfully.'
                : 'Mastercard returned without a verified success indicator. Please review the payment details.');
    }

    public function simulateGateway(Payment $payment, string $gateway): View
    {
        $this->ensureSimulationEnabled($gateway, $payment);

        return view('payments.gateway_simulator', [
            'payment' => $payment->loadMissing('patient'),
            'gateway' => $gateway,
        ]);
    }

    public function completeSimulation(Request $request, Payment $payment, string $gateway): RedirectResponse
    {
        $this->ensureSimulationEnabled($gateway, $payment);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:paid,failed,cancelled,pending'],
        ]);

        $status = (string) $validated['status'];
        $label = strtoupper(str_replace('_', ' ', $gateway));

        $payment->forceFill([
            'status' => $status,
            'paid_at' => $status === 'paid' ? ($payment->paid_at ?? now()) : null,
            'external_reference' => $payment->external_reference ?: strtoupper($gateway) . '-SIM-' . $payment->payment_id,
            'meta' => $this->mergeMeta($payment, [
                $gateway => [
                    'simulation' => true,
                    'result' => $status,
                    'completed_at' => now()->toIso8601String(),
                ],
            ]),
        ])->save();

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', $label . ' simulation completed with status: ' . ucfirst($status) . '.');
    }

    private function startMtnMomoPayment(Payment $payment, string $phoneNumber): RedirectResponse
    {
        if ($this->gatewaySimulationEnabled()) {
            $payment->forceFill([
                'meta' => $this->mergeMeta($payment, [
                    'mtn_momo' => [
                        'phone_number' => $phoneNumber,
                        'simulation' => true,
                    ],
                ]),
            ])->save();

            return redirect()->route('payments.gateway.simulate', [
                'payment' => $payment,
                'gateway' => 'mtn_momo',
            ]);
        }

        try {
            $momoRequest = $this->mtnMomoService->requestPayment(
                $payment,
                $phoneNumber,
                route('payments.momo.callback', $payment->getKey())
            );

            $payment->forceFill([
                'external_reference' => $momoRequest['reference_id'],
                'meta' => $this->mergeMeta($payment, [
                    'mtn_momo' => $momoRequest,
                ]),
            ])->save();

            return redirect()
                ->route('payments.show', $payment->getKey())
                ->with('success', 'MTN MoMo request sent. Ask the customer to approve the payment on their phone, then refresh the status.');
        } catch (\Throwable $exception) {
            report($exception);

            $payment->forceFill([
                'status' => 'failed',
                'meta' => $this->mergeMeta($payment, [
                    'mtn_momo' => [
                        'phone_number' => $phoneNumber,
                        'error' => $exception->getMessage(),
                    ],
                ]),
            ])->save();

            throw ValidationException::withMessages([
                'payment_method' => [$exception->getMessage()],
            ]);
        }
    }

    private function startPesapalPayment(Payment $payment)
    {
        if ($this->gatewaySimulationEnabled()) {
            $payment->forceFill([
                'external_reference' => 'PESAPAL-SIM-' . $payment->payment_id,
                'meta' => $this->mergeMeta($payment, [
                    'pesapal' => [
                        'simulation' => true,
                    ],
                ]),
            ])->save();

            return redirect()->route('payments.gateway.simulate', [
                'payment' => $payment,
                'gateway' => 'pesapal',
            ]);
        }

        try {
            $payment->loadMissing('patient');
            $checkout = $this->pesapalService->createOrder(
                $payment,
                [
                    'email_address' => $payment->patient?->email ?? '',
                    'phone_number' => $payment->patient?->phone ?? '',
                    'country_code' => 'SS',
                    'first_name' => $payment->patient?->first_name ?? 'Patient',
                    'last_name' => $payment->patient?->last_name ?? 'ViLoCare',
                ],
                route('payments.pesapal.callback', $payment),
                route('payments.pesapal.ipn')
            );

            $payment->forceFill([
                'external_reference' => $checkout['order_tracking_id'],
                'meta' => $this->mergeMeta($payment, [
                    'pesapal' => $checkout,
                ]),
            ])->save();

            return redirect()->away($checkout['redirect_url']);
        } catch (\Throwable $exception) {
            report($exception);

            $payment->forceFill([
                'status' => 'failed',
                'meta' => $this->mergeMeta($payment, [
                    'pesapal' => [
                        'error' => $exception->getMessage(),
                    ],
                ]),
            ])->save();

            throw ValidationException::withMessages([
                'payment_method' => [$exception->getMessage()],
            ]);
        }
    }

    private function startMastercardPayment(Payment $payment)
    {
        if ($this->gatewaySimulationEnabled()) {
            $payment->forceFill([
                'external_reference' => 'MASTERCARD-SIM-' . $payment->payment_id,
                'meta' => $this->mergeMeta($payment, [
                    'mastercard' => [
                        'simulation' => true,
                    ],
                ]),
            ])->save();

            return redirect()->route('payments.gateway.simulate', [
                'payment' => $payment,
                'gateway' => 'mastercard',
            ]);
        }

        try {
            $checkout = $this->mastercardService->initiateCheckout(
                $payment,
                route('payments.mastercard.return', $payment)
            );

            $payment->forceFill([
                'external_reference' => $checkout['session_id'],
                'meta' => $this->mergeMeta($payment, [
                    'mastercard' => $checkout,
                ]),
            ])->save();

            return response()->view('payments.mastercard_checkout', [
                'payment' => $payment,
                'checkout' => $checkout,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            $payment->forceFill([
                'status' => 'failed',
                'meta' => $this->mergeMeta($payment, [
                    'mastercard' => [
                        'error' => $exception->getMessage(),
                    ],
                ]),
            ])->save();

            throw ValidationException::withMessages([
                'payment_method' => [$exception->getMessage()],
            ]);
        }
    }

    private function refreshMomoPayment(Payment $payment): void
    {
        if (blank($payment->external_reference)) {
            throw ValidationException::withMessages([
                'payment' => ['This MTN MoMo payment has no gateway reference yet.'],
            ]);
        }

        $statusData = $this->mtnMomoService->getPaymentStatus((string) $payment->external_reference);
        $this->applyMomoStatus($payment, $statusData);
    }

    private function refreshPesapalPayment(Payment $payment): void
    {
        if (blank($payment->external_reference)) {
            throw ValidationException::withMessages([
                'payment' => ['This Pesapal payment has no order tracking reference yet.'],
            ]);
        }

        $statusData = $this->pesapalService->getTransactionStatus((string) $payment->external_reference);
        $this->applyPesapalStatus($payment, $statusData);
    }

    private function paymentDefaults(string $paymentType, ?EACSession $eacSession): array
    {
        return match ($paymentType) {
            'result_print' => [
                'service_label' => 'Patient Result Print Fee',
                'amount' => '10.00',
                'currency' => 'SSP',
                'payment_method' => 'cash',
                'status' => 'paid',
            ],
            'result_pdf' => [
                'service_label' => 'Patient Result PDF Fee',
                'amount' => '8.00',
                'currency' => 'SSP',
                'payment_method' => 'cash',
                'status' => 'paid',
            ],
            default => [
                'service_label' => 'EAC Consultation Fee' . ($eacSession ? ' - Session ' . $eacSession->session_number : ''),
                'amount' => '25.00',
                'currency' => 'SSP',
                'payment_method' => 'cash',
                'status' => 'paid',
            ],
        };
    }

    private function paymentMethodOptions(): array
    {
        return [
            'cash' => 'Cash',
            'mtn_momo' => 'MTN MoMo Pay',
        ];
    }

    private function gatewayConfiguration(): array
    {
        return [
            'mtn_momo' => $this->mtnMomoService->isConfigured(),
        ];
    }

    private function gatewayIsConfigured(string $method): bool
    {
        return match ($method) {
            'mtn_momo' => $this->mtnMomoService->isConfigured(),
            default => false,
        };
    }

    private function gatewaySimulationEnabled(): bool
    {
        return (bool) config('vilocare.payments.simulate_gateways', false);
    }

    private function ensureSimulationEnabled(string $gateway, Payment $payment): void
    {
        abort_unless($this->gatewaySimulationEnabled(), 404);
        abort_unless($gateway === 'mtn_momo', 404);
        abort_unless($payment->payment_method === $gateway, 404);
    }

    private function nextReceiptNumber(): string
    {
        return 'VCR-' . now()->format('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }

    private function applyMomoStatus(Payment $payment, array $statusData): void
    {
        $status = Arr::get($statusData, 'status', 'pending');

        $payment->forceFill([
            'status' => $status,
            'paid_at' => $status === 'paid' ? ($payment->paid_at ?? now()) : null,
            'meta' => $this->mergeMeta($payment, [
                'mtn_momo' => array_filter([
                    'reference_id' => $payment->external_reference,
                    'financial_transaction_id' => Arr::get($statusData, 'financial_transaction_id'),
                    'raw_status' => Arr::get($statusData, 'raw_status'),
                    'reason' => Arr::get($statusData, 'reason'),
                    'last_status_payload' => Arr::get($statusData, 'payload'),
                    'last_checked_at' => now()->toIso8601String(),
                    'phone_number' => data_get($payment->meta, 'mtn_momo.phone_number') ?? data_get($payment->meta, 'payment_phone'),
                ], fn ($value) => ! is_null($value)),
            ]),
        ])->save();
    }

    private function applyPesapalStatus(Payment $payment, array $statusData): void
    {
        $status = Arr::get($statusData, 'status', 'pending');

        $payment->forceFill([
            'status' => $status,
            'paid_at' => $status === 'paid' ? ($payment->paid_at ?? now()) : null,
            'meta' => $this->mergeMeta($payment, [
                'pesapal' => array_filter([
                    'order_tracking_id' => $payment->external_reference,
                    'raw_status' => Arr::get($statusData, 'raw_status'),
                    'payment_method' => Arr::get($statusData, 'payment_method'),
                    'confirmation_code' => Arr::get($statusData, 'confirmation_code'),
                    'description' => Arr::get($statusData, 'description'),
                    'last_status_payload' => Arr::get($statusData, 'payload'),
                    'last_checked_at' => now()->toIso8601String(),
                ], fn ($value) => ! is_null($value) && $value !== ''),
            ]),
        ])->save();
    }

    private function mergeMeta(Payment $payment, array $attributes): array
    {
        return array_replace_recursive($payment->meta ?? [], $attributes);
    }

    private function mapIncomingMomoStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'SUCCESSFUL' => 'paid',
            'FAILED', 'REJECTED', 'TIMEOUT', 'EXPIRED' => 'failed',
            default => 'pending',
        };
    }
}
