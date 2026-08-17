<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\EACSession;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class PatientReminderService
{
    public function upcomingAppointmentsForReminder(?Carbon $targetDate = null): Collection
    {
        $date = ($targetDate ?: Carbon::today())
            ->copy()
            ->addDays((int) config('vilocare.sms.appointment_reminder_days_before', 1))
            ->toDateString();

        return Appointment::query()
            ->with('patient')
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['Pending', 'Scheduled'])
            ->get();
    }

    public function patientsDueForVl(?Carbon $asOfDate = null, bool $onlyFreshDue = false): Collection
    {
        $date = ($asOfDate ?: Carbon::today())->toDateString();

        $query = EACSession::query()
            ->with('patient')
            ->where('session_number', 3)
            ->where('completion_status', 'Completed')
            ->whereNotNull('patient_id');

        if ($onlyFreshDue) {
            $query->whereDate(\DB::raw('COALESCE(next_session_date, session_date)'), $date);
        } else {
            $query->whereDate(\DB::raw('COALESCE(next_session_date, session_date)'), '<=', $date);
        }

        return $query
            ->orderByRaw('COALESCE(next_session_date, session_date)')
            ->get()
            ->filter(fn (EACSession $session) => $session->patient instanceof Patient)
            ->values();
    }

    public function patientsDueForEac(?Carbon $asOfDate = null, bool $onlyFreshDue = false): Collection
    {
        $date = ($asOfDate ?: Carbon::today())->toDateString();

        $query = EACSession::query()
            ->with('patient')
            ->where('completion_status', 'Pending')
            ->whereNotNull('patient_id');

        if ($onlyFreshDue) {
            $query->whereDate('session_date', $date);
        } else {
            $query->whereDate('session_date', '<=', $date);
        }

        return $query
            ->orderBy('session_date')
            ->get()
            ->filter(fn (EACSession $session) => $session->patient instanceof Patient)
            ->values();
    }
}
