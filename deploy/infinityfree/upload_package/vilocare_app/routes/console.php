<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\NotificationService;
use App\Services\PatientReminderService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('vilocare:send-appointment-reminders', function (
    PatientReminderService $reminderService,
    NotificationService $notificationService
) {
    $appointments = $reminderService->upcomingAppointmentsForReminder();

    foreach ($appointments as $appointment) {
        $notificationService->sendAppointmentReminder($appointment, null, false);
    }

    $this->info(sprintf('Processed %d appointment reminder(s).', $appointments->count()));
})->purpose('Send scheduled appointment reminders for upcoming visits');

Artisan::command('vilocare:send-vl-due-reminders {--fresh-only}', function (
    PatientReminderService $reminderService,
    NotificationService $notificationService
) {
    $sessions = $reminderService->patientsDueForVl(null, (bool) $this->option('fresh-only'));

    foreach ($sessions as $session) {
        $notificationService->sendDueVlReminder(
            $session->patient,
            $session->next_session_date ?: $session->session_date
        );
    }

    $this->info(sprintf('Processed %d VL due reminder(s).', $sessions->count()));
})->purpose('Send repeat viral load due reminders to patients');

Artisan::command('vilocare:send-eac-due-reminders {--fresh-only}', function (
    PatientReminderService $reminderService,
    NotificationService $notificationService
) {
    $sessions = $reminderService->patientsDueForEac(null, (bool) $this->option('fresh-only'));

    foreach ($sessions as $session) {
        $notificationService->sendDueEacReminder($session->patient, $session);
    }

    $this->info(sprintf('Processed %d EAC due reminder(s).', $sessions->count()));
})->purpose('Send EAC due reminders to patients');
