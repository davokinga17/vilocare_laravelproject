<?php

namespace App\Services;

use App\Mail\AppointmentReminderMail;
use App\Mail\OperationalAlertMail;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Models\SampleCollection;
use App\Models\SampleRejection;
use App\Models\User;
use App\Models\ViralLoad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    public function __construct(
        private readonly SmsService $smsService
    ) {
    }

    public function sendAppointmentReminder(Appointment $appointment, ?User $actor = null, bool $manual = false): void
    {
        $appointment->loadMissing('patient');
        $patient = $appointment->patient;

        if (! $patient) {
            return;
        }

        $subject = $manual ? 'Manual appointment reminder sent' : 'New appointment reminder created';
        $smsMessage = sprintf(
            'ViLoCare reminder: %s has an appointment on %s.%s',
            trim($patient->first_name . ' ' . $patient->last_name),
            Carbon::parse($appointment->appointment_date)->format('d M Y'),
            $appointment->reason ? ' Reason: ' . $appointment->reason : ''
        );

        $this->sendMailables(
            $this->emailRecipients(),
            new AppointmentReminderMail($patient, $appointment, $manual),
            'email',
            'appointment_reminder',
            $subject,
            $smsMessage,
            $appointment,
            $actor,
            [
                'patient_id' => $patient->patient_id,
                'appointment_id' => $appointment->appointment_id,
            ]
        );

        $this->sendSms(
            $patient->phone ? [$patient->phone] : [],
            $smsMessage,
            'appointment_reminder',
            $appointment,
            $actor,
            [
                'patient_id' => $patient->patient_id,
                'appointment_id' => $appointment->appointment_id,
                'manual' => $manual,
            ]
        );
    }

    public function sendHighViralLoadAlert(ViralLoad $viralLoad, ?User $actor = null): void
    {
        $viralLoad->loadMissing('patient');
        $patient = $viralLoad->patient;

        $details = [
            'Patient' => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : 'Unknown patient',
            'ART Number' => $patient?->art_number ?: 'N/A',
            'Result (cp/ml)' => (string) $viralLoad->result_cpml,
            'Result Date' => $viralLoad->result_date ? Carbon::parse($viralLoad->result_date)->format('d M Y') : 'N/A',
            'Testing Indication' => $viralLoad->vl_testing_indication ?: 'N/A',
        ];

        $summary = 'A high viral load result was recorded and may require follow-up.';

        $this->sendMailables(
            $this->emailRecipients(),
            new OperationalAlertMail('High Viral Load Alert', $summary, $details),
            'email',
            'high_viral_load_alert',
            'High Viral Load Alert',
            $summary,
            $viralLoad,
            $actor,
            $details
        );

        $smsRecipients = array_filter(array_merge(
            $this->smsRecipients(),
            $viralLoad->clinician_cellphone ? [$viralLoad->clinician_cellphone] : []
        ));

        $this->sendSms(
            $smsRecipients,
            sprintf(
                'ViLoCare alert: high viral load for %s (%s cp/ml).',
                $details['Patient'],
                $details['Result (cp/ml)']
            ),
            'high_viral_load_alert',
            $viralLoad,
            $actor,
            $details
        );
    }

    public function sendSampleRejectionAlert(SampleCollection $sample, ?SampleRejection $rejection = null, ?User $actor = null): void
    {
        $sample->loadMissing('patient');
        $patient = $sample->patient;

        $details = [
            'Patient' => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : 'Unknown patient',
            'ART Number' => $patient?->art_number ?: 'N/A',
            'Collection Date' => $sample->collection_date ? Carbon::parse($sample->collection_date)->format('d M Y') : 'N/A',
            'Rejection Date' => $rejection?->rejection_date ? Carbon::parse($rejection->rejection_date)->format('d M Y') : now()->format('d M Y'),
            'Reason' => $rejection?->reason ?: 'Not provided',
        ];

        $summary = 'A sample rejection was recorded and needs operational follow-up.';

        $this->sendMailables(
            $this->emailRecipients(),
            new OperationalAlertMail('Sample Rejection Alert', $summary, $details),
            'email',
            'sample_rejection_alert',
            'Sample Rejection Alert',
            $summary,
            $sample,
            $actor,
            $details
        );

        $this->sendSms(
            $this->smsRecipients(),
            sprintf(
                'ViLoCare alert: sample rejected for %s. Reason: %s.',
                $details['Patient'],
                $details['Reason']
            ),
            'sample_rejection_alert',
            $sample,
            $actor,
            $details
        );
    }

    private function sendMailables(
        array $recipients,
        mixed $mailable,
        string $channel,
        string $category,
        string $subject,
        string $message,
        ?Model $notifiable,
        ?User $actor,
        array $context = []
    ): void {
        if ($recipients === []) {
            $this->log($channel, $category, 'skipped', null, $subject, 'mail', $message, $notifiable, $actor, $context + [
                'detail' => 'No email recipients configured.',
            ]);
            return;
        }

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(clone $mailable);

                $this->log($channel, $category, 'sent', $recipient, $subject, 'mail', $message, $notifiable, $actor, $context);
            } catch (\Throwable $exception) {
                $this->log($channel, $category, 'failed', $recipient, $subject, 'mail', $exception->getMessage(), $notifiable, $actor, $context);
            }
        }
    }

    private function sendSms(
        array $recipients,
        string $message,
        string $category,
        ?Model $notifiable,
        ?User $actor,
        array $context = []
    ): void {
        if ($recipients === []) {
            $this->log('sms', $category, 'skipped', null, null, 'twilio', $message, $notifiable, $actor, $context + [
                'detail' => 'No SMS recipients available.',
            ]);
            return;
        }

        foreach ($recipients as $recipient) {
            $result = $this->smsService->send((string) $recipient, $message);

            $this->log(
                'sms',
                $category,
                $result['status'] ?? 'failed',
                (string) $recipient,
                null,
                $result['provider'] ?? 'twilio',
                $result['detail'] ?? $message,
                $notifiable,
                $actor,
                $context
            );
        }
    }

    private function log(
        string $channel,
        string $category,
        string $status,
        ?string $recipient,
        ?string $subject,
        ?string $provider,
        ?string $message,
        ?Model $notifiable,
        ?User $actor,
        array $context = []
    ): void {
        if (! Schema::hasTable('notification_logs')) {
            return;
        }

        NotificationLog::create([
            'channel' => $channel,
            'category' => $category,
            'status' => $status,
            'recipient' => $recipient,
            'subject' => $subject,
            'provider' => $provider,
            'message' => $message,
            'notifiable_type' => $notifiable ? $notifiable::class : null,
            'notifiable_id' => $notifiable?->getKey(),
            'triggered_by_user_id' => $actor?->getKey(),
            'context' => $context,
        ]);
    }

    private function emailRecipients(): array
    {
        $configured = config('vilocare.notifications.email_recipients', []);

        if ($configured !== []) {
            return $configured;
        }

        if (! Schema::hasColumn('users', 'email')) {
            return [];
        }

        return User::query()
            ->whereIn('role', ['Administrator', 'Clinician'])
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function smsRecipients(): array
    {
        $configured = config('vilocare.notifications.sms_recipients', []);

        if ($configured !== []) {
            return $configured;
        }

        if (! Schema::hasColumn('users', 'phone')) {
            return [];
        }

        return User::query()
            ->whereIn('role', ['Administrator', 'Clinician'])
            ->whereNotNull('phone')
            ->pluck('phone')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
