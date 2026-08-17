<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\ChatbotService;
use App\Services\DashboardSummaryService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardSupportController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly DashboardSummaryService $dashboardSummaryService,
        private readonly ChatbotService $chatbotService
    ) {
    }

    public function sendAppointmentReminder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'appointment_id' => ['required', 'exists:appointments,appointment_id'],
        ]);

        $appointment = Appointment::with('patient')->findOrFail($validated['appointment_id']);
        $this->notificationService->sendAppointmentReminder($appointment, $request->user(), true);

        return redirect()->route('dashboard')->with('success', 'Appointment reminder processed. Check notification history for delivery status.');
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'filters' => ['nullable', 'array'],
        ]);

        $summary = $this->dashboardSummaryService->summary($validated['filters'] ?? []);
        $result = $this->chatbotService->askDashboardAssistant($validated['question'], $summary, $request->user());

        return response()->json($result, $result['status'] === 'failed' ? 422 : 200);
    }
}
