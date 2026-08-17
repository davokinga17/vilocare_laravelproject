<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\ChatbotService;
use App\Services\DashboardSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly ChatbotService $chatbotService,
        private readonly DashboardSummaryService $dashboardSummaryService
    ) {
    }

    public function index(Request $request)
    {
        $selectedPatient = null;

        if ($request->filled('patient_id')) {
            $selectedPatient = $this->loadPatient((int) $request->input('patient_id'));
        }

        $patients = Patient::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['patient_id', 'art_number', 'first_name', 'last_name']);

        $summary = $this->dashboardSummaryService->summary();
        $signals = $selectedPatient ? $this->chatbotService->patientAttentionSignals($selectedPatient) : [];

        return view('ai.index', [
            'patients' => $patients,
            'selectedPatient' => $selectedPatient,
            'summary' => $summary,
            'signals' => $signals,
            'assistantEnabled' => $this->chatbotService->isConfigured(),
            'assistantProvider' => $this->chatbotService->providerLabel(),
        ]);
    }

    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'patient_id' => ['nullable', 'exists:patients,patient_id'],
        ]);

        $patient = ! empty($validated['patient_id'])
            ? $this->loadPatient((int) $validated['patient_id'])
            : null;

        $summary = $this->dashboardSummaryService->summary();
        $result = $this->chatbotService->askOperationalAssistant(
            $validated['question'],
            $summary,
            $request->user(),
            $patient
        );

        return response()->json($result, $result['status'] === 'failed' ? 422 : 200);
    }

    private function loadPatient(int $patientId): Patient
    {
        return Patient::query()
            ->with([
                'viralLoads' => fn ($query) => $query->orderByDesc('result_date')->orderByDesc('vl_id'),
                'eacSessions' => fn ($query) => $query->orderByDesc('session_date')->orderByDesc('eac_id'),
                'appointments' => fn ($query) => $query->orderByDesc('appointment_date')->orderByDesc('appointment_id'),
                'payments' => fn ($query) => $query->orderByDesc('paid_at')->orderByDesc('payment_id'),
            ])
            ->findOrFail($patientId);
    }
}
