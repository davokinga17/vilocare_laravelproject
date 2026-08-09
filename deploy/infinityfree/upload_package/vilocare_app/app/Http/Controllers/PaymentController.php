<?php

namespace App\Http\Controllers;

use App\Models\EACSession;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\ViralLoad;
use App\Services\MtnMomoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(private readonly MtnMomoService $mtnMomoService)
    {
    }

    public function index(Request $request)
    {
        $query = Payment::with(['patient', 'eacSession', 'creator'])->latest('paid_at')->latest('payment_id');

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->string('payment_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $payments = $query->paginate(12)->withQueryString();

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $patients = Patient::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['patient_id', 'art_number', 'first_name', 'last_name']);

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

        $momoConfigured = $this->mtnMomoService->isConfigured();

        return view('payments.create', compact('patient', 'patients', 'eacSession', 'viralLoad', 'paymentType', 'defaults', 'momoConfigured'));
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
            'payment_method' => ['required', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:pending,paid,failed,cancelled'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
            'paid_at' => ['nullable', 'date'],
            'payment_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $isMtnMomo = $validated['payment_method'] === 'mtn_momo';

        if (! $isMtnMomo && blank($validated['status'] ?? null)) {
            throw ValidationException::withMessages([
                'status' => ['Select a payment status.'],
            ]);
        }

        if ($isMtnMomo && blank($validated['payment_phone'] ?? null)) {
            throw ValidationException::withMessages([
                'payment_phone' => ['Enter the MTN Mobile Money phone number to charge.'],
            ]);
        }

        if ($isMtnMomo && ! $this->mtnMomoService->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => ['MTN MoMo is not configured yet. Add the MTN MoMo API credentials in the environment first.'],
            ]);
        }

        $status = $isMtnMomo ? 'pending' : (string) $validated['status'];
        $paidAt = $status === 'paid'
            ? Carbon::parse($validated['paid_at'] ?? now())
            : null;

        $payment = Payment::create([
            'patient_id' => $validated['patient_id'],
            'eac_id' => $validated['eac_id'] ?? null,
            'vl_id' => $validated['vl_id'] ?? null,
            'created_by' => auth()->id(),
            'payment_type' => $validated['payment_type'],
            'service_label' => $validated['service_label'],
            'amount' => $validated['amount'],
            'currency' => strtoupper($validated['currency']),
            'payment_method' => $validated['payment_method'],
            'status' => $status,
            'receipt_number' => $this->nextReceiptNumber(),
            'external_reference' => $validated['external_reference'] ?? null,
            'paid_at' => $paidAt,
            'notes' => $validated['notes'] ?? null,
            'meta' => [
                'recorded_from' => $validated['payment_type'] === 'eac_consultation' ? 'eac' : 'patient_results',
                'payment_phone' => $validated['payment_phone'] ?? null,
            ],
        ]);

        if ($isMtnMomo) {
            try {
                $momoRequest = $this->mtnMomoService->requestPayment(
                    $payment,
                    (string) $validated['payment_phone'],
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
                            'phone_number' => $validated['payment_phone'],
                            'error' => $exception->getMessage(),
                        ],
                    ]),
                ])->save();

                throw ValidationException::withMessages([
                    'payment_method' => [$exception->getMessage()],
                ]);
            }
        }

        return redirect()
            ->route('payments.receipt', $payment->getKey())
            ->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['patient', 'eacSession', 'viralLoad', 'creator']);

        return view('payments.show', compact('payment'));
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['patient', 'eacSession', 'viralLoad', 'creator']);

        return view('payments.receipt', compact('payment'));
    }

    public function refreshMomoStatus(Payment $payment)
    {
        if ($payment->payment_method !== 'mtn_momo') {
            return back()->with('warning', 'This payment was not created through MTN MoMo.');
        }

        if (blank($payment->external_reference)) {
            return back()->with('warning', 'This MTN MoMo payment has no gateway reference yet.');
        }

        try {
            $statusData = $this->mtnMomoService->getPaymentStatus((string) $payment->external_reference);
            $this->applyMomoStatus($payment, $statusData);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('warning', $exception->getMessage());
        }

        return back()->with('success', 'MTN MoMo payment status refreshed.');
    }

    public function momoCallback(Request $request, Payment $payment)
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

    private function paymentDefaults(string $paymentType, ?EACSession $eacSession): array
    {
        return match ($paymentType) {
            'result_print' => [
                'service_label' => 'Patient Result Print Fee',
                'amount' => '10.00',
                'currency' => 'SSP',
                'payment_method' => 'manual',
                'status' => 'paid',
            ],
            'result_pdf' => [
                'service_label' => 'Patient Result PDF Fee',
                'amount' => '8.00',
                'currency' => 'SSP',
                'payment_method' => 'manual',
                'status' => 'paid',
            ],
            default => [
                'service_label' => 'EAC Consultation Fee' . ($eacSession ? ' - Session ' . $eacSession->session_number : ''),
                'amount' => '25.00',
                'currency' => 'SSP',
                'payment_method' => 'manual',
                'status' => 'paid',
            ],
        };
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
