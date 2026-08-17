<?php

namespace App\Services;

use App\Mail\AppointmentReminderMail;
use App\Mail\OperationalAlertMail;
use App\Models\Appointment;
use App\Models\EACSession;
use App\Models\NotificationLog;
use App\Models\Patient;
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
        $smsMessage = $this->buildAppointmentReminderMessage($appointment);

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

    public function sendDueVlReminder(Patient $patient, Carbon|string $dueDate, ?User $actor = null): void
    {
        $date = $dueDate instanceof Carbon ? $dueDate : Carbon::parse($dueDate);

        $this->sendSms(
            $patient->phone ? [$patient->phone] : [],
            $this->buildDueVlReminderMessage($patient, $date),
            'vl_due_reminder',
            $patient,
            $actor,
            [
                'patient_id' => $patient->patient_id,
                'due_date' => $date->toDateString(),
            ],
            true
        );
    }

    public function sendDueEacReminder(Patient $patient, EACSession $session, ?User $actor = null): void
    {
        $this->sendSms(
            $patient->phone ? [$patient->phone] : [],
            $this->buildDueEacReminderMessage($patient, $session),
            'eac_due_reminder',
            $session,
            $actor,
            [
                'patient_id' => $patient->patient_id,
                'eac_id' => $session->eac_id,
                'session_number' => $session->session_number,
                'due_date' => Carbon::parse($session->session_date)->toDateString(),
            ],
            true
        );
    }

    public function sendViralLoadResultMessage(ViralLoad $viralLoad, ?User $actor = null): void
    {
        $viralLoad->loadMissing('patient');
        $patient = $viralLoad->patient;

        if (! $patient) {
            return;
        }

        $result = (float) ($viralLoad->result_cpml ?? 0);
        $date = $viralLoad->result_date ? Carbon::parse($viralLoad->result_date) : Carbon::today();
        $patientName = trim($patient->first_name . ' ' . $patient->last_name);
        $isSuppressed = $result < 1000;

        $message = $this->buildViralLoadResultMessage($viralLoad);

        $this->sendSms(
            $patient->phone ? [$patient->phone] : [],
            $message,
            $isSuppressed ? 'viral_load_result_suppressed' : 'viral_load_result_unsuppressed',
            $viralLoad,
            $actor,
            [
                'patient_id' => $patient->patient_id,
                'vl_id' => $viralLoad->vl_id,
                'result_cpml' => $result,
                'result_date' => $date->toDateString(),
            ],
            true
        );
    }

    public function buildAppointmentReminderMessage(Appointment $appointment): string
    {
        $appointment->loadMissing('patient');

        return sprintf(
            'ViLoCare reminder: %s has an appointment on %s.%s',
            trim((string) optional($appointment->patient)->first_name . ' ' . (string) optional($appointment->patient)->last_name),
            Carbon::parse($appointment->appointment_date)->format('d M Y'),
            $appointment->reason ? ' Reason: ' . $appointment->reason : ''
        );
    }

    public function buildDueVlReminderMessage(Patient $patient, Carbon|string $dueDate): string
    {
        $date = $dueDate instanceof Carbon ? $dueDate : Carbon::parse($dueDate);

        return sprintf(
            'ViLoCare reminder: %s is due for a repeat viral load visit on %s. Please return to the clinic for follow-up.',
            trim($patient->first_name . ' ' . $patient->last_name),
            $date->format('d M Y')
        );
    }

    public function buildDueEacReminderMessage(Patient $patient, EACSession $session): string
    {
        return sprintf(
            'ViLoCare reminder: %s is due for EAC session %s on %s. Please return to the clinic or contact your health care provider.',
            trim($patient->first_name . ' ' . $patient->last_name),
            $session->session_number,
            Carbon::parse($session->session_date)->format('d M Y')
        );
    }

    public function buildViralLoadResultMessage(ViralLoad $viralLoad): string
    {
        $viralLoad->loadMissing('patient');
        $patient = $viralLoad->patient;

        if (! $patient) {
            return 'ViLoCare update: your viral load result is ready. Please contact the clinic for follow-up.';
        }

        $result = (float) ($viralLoad->result_cpml ?? 0);
        $date = $viralLoad->result_date ? Carbon::parse($viralLoad->result_date) : Carbon::today();
        $patientName = trim($patient->first_name . ' ' . $patient->last_name);

        return $result < 1000
            ? sprintf(
                'ViLoCare update: Dear %s, your viral load result from %s is good and below 1000 cp/ml. Keep taking your medicine well and attend your routine clinic visits.',
                $patientName,
                $date->format('d M Y')
            )
            : sprintf(
                'ViLoCare update: Dear %s, your viral load result from %s needs follow-up. Please return to the clinic or call your health care provider to discuss challenges with taking your medicine.',
                $patientName,
                $date->format('d M Y')
            );
    }

    public function sendCustomSms(
        string|array $recipients,
        string $message,
        string $category,
        ?Model $notifiable,
        ?User $actor,
        array $context = [],
        bool $preventDuplicateForToday = false
    ): void {
        $recipientList = is_array($recipients) ? $recipients : [$recipients];

        $this->sendSms($recipientList, $message, $category, $notifiable, $actor, $context, $preventDuplicateForToday);
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
        array $context = [],
        bool $preventDuplicateForToday = false
    ): void {
        if ($recipients === []) {
            $this->log('sms', $category, 'skipped', null, null, 'twilio', $message, $notifiable, $actor, $context + [
                'detail' => 'No SMS recipients available.',
            ]);
            return;
        }

        foreach ($recipients as $recipient) {
            if ($preventDuplicateForToday && $this->alreadySentToday((string) $recipient, $category, $context)) {
                $this->log(
                    'sms',
                    $category,
                    'skipped',
                    (string) $recipient,
                    null,
                    'duplicate_guard',
                    'Duplicate reminder skipped for today.',
                    $notifiable,
                    $actor,
                    $context
                );
                continue;
            }

            $result = $this->smsService->send((string) $recipient, $message);

            $this->log(
                'sms',
                $category,
                $result['status'] ?? 'failed',
                (string) $recipient,
                null,
                $result['provider'] ?? 'twilio',
                $message,
                $notifiable,
                $actor,
                $context + [
                    'provider_detail' => $result['detail'] ?? null,
                ]
            );
        }
    }

    private function alreadySentToday(string $recipient, string $category, array $context = []): bool
    {
        if (! Schema::hasTable('notification_logs')) {
            return false;
        }

        $query = NotificationLog::query()
            ->where('channel', 'sms')
            ->where('category', $category)
            ->where('recipient', $recipient)
            ->whereDate('created_at', Carbon::today());

        if (isset($context['patient_id'])) {
            $query->where('context->patient_id', $context['patient_id']);
        }

        if (isset($context['due_date'])) {
            $query->where('context->due_date', $context['due_date']);
        }

        if (isset($context['vl_id'])) {
            $query->where('context->vl_id', $context['vl_id']);
        }

        if (isset($context['eac_id'])) {
            $query->where('context->eac_id', $context['eac_id']);
        }

        return $query->exists();
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
