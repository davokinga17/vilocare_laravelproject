<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\EACSession;
use App\Models\ViralLoad;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ManualSmsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function sendAppointmentReminder(Request $request, Appointment $appointment): RedirectResponse
    {
        $appointment->loadMissing('patient');
        $validated = $this->validateManualSms($request);

        $this->notificationService->sendCustomSms(
            $validated['phone'],
            $validated['message'],
            'appointment_reminder',
            $appointment,
            $request->user(),
            [
                'patient_id' => $appointment->patient?->patient_id,
                'appointment_id' => $appointment->appointment_id,
                'manual' => true,
            ]
        );

        return back()->with('success', 'Appointment reminder SMS processed.');
    }

    public function sendViralLoadResultMessage(Request $request, ViralLoad $viralLoad): RedirectResponse
    {
        $viralLoad->loadMissing('patient');
        $validated = $this->validateManualSms($request);
        $category = ((float) ($viralLoad->result_cpml ?? 0)) < 1000
            ? 'viral_load_result_suppressed'
            : 'viral_load_result_unsuppressed';

        $this->notificationService->sendCustomSms(
            $validated['phone'],
            $validated['message'],
            $category,
            $viralLoad,
            $request->user(),
            [
                'patient_id' => $viralLoad->patient?->patient_id,
                'vl_id' => $viralLoad->vl_id,
                'result_cpml' => (float) ($viralLoad->result_cpml ?? 0),
                'result_date' => $viralLoad->result_date ? Carbon::parse($viralLoad->result_date)->toDateString() : Carbon::today()->toDateString(),
            ],
            true
        );

        return back()->with('success', 'Viral load result SMS processed.');
    }

    public function sendEacDueReminder(Request $request, EACSession $session): RedirectResponse
    {
        $session->loadMissing('patient');

        if ($session->completion_status !== 'Pending') {
            return back()->with('warning', 'Only pending EAC sessions can receive this reminder.');
        }

        $validated = $this->validateManualSms($request);

        $this->notificationService->sendCustomSms(
            $validated['phone'],
            $validated['message'],
            'eac_due_reminder',
            $session,
            $request->user(),
            [
                'patient_id' => $session->patient?->patient_id,
                'eac_id' => $session->eac_id,
                'session_number' => $session->session_number,
                'due_date' => Carbon::parse($session->session_date)->toDateString(),
            ],
            true
        );

        return back()->with('success', 'EAC due reminder SMS processed.');
    }

    public function sendVlDueReminder(Request $request, EACSession $session): RedirectResponse
    {
        $session->loadMissing('patient');

        if ($session->session_number !== 3 || $session->completion_status !== 'Completed') {
            return back()->with('warning', 'Only completed session 3 records can receive a repeat VL reminder.');
        }

        $validated = $this->validateManualSms($request);
        $dueDate = $session->next_session_date ?: $session->session_date;

        $this->notificationService->sendCustomSms(
            $validated['phone'],
            $validated['message'],
            'vl_due_reminder',
            $session->patient,
            $request->user(),
            [
                'patient_id' => $session->patient?->patient_id,
                'eac_id' => $session->eac_id,
                'due_date' => Carbon::parse($dueDate)->toDateString(),
            ],
            true
        );

        return back()->with('success', 'VL due reminder SMS processed.');
    }

    private function validateManualSms(Request $request): array
    {
        return $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:1000'],
        ]);
    }
}
