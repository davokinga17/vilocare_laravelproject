<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Patient;
use App\Models\ViralLoad;
use App\Models\Appointment;
use App\Models\EACSession;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = date('Y-m-d');

        // Total Patients
        try {
            $totalPatients = Patient::count();
        } catch (\Exception $e) {
            $totalPatients = 0;
        }

        // Appointments Today
        try {
            $totalAppointmentsToday = DB::table('appointments')
                ->whereDate('appointment_date', $today)
                ->count();
        } catch (\Exception $e) {
            $totalAppointmentsToday = 0;
        }

        // Samples Collected
        try {
            $totalSamplesCollected = DB::table('sample_collection')->count();
        } catch (\Exception $e) {
            $totalSamplesCollected = 0;
        }

        // High Viral Load (>= 1000 cpml)
        try {
            $totalHighVL = ViralLoad::where('result_cpml', '>=', 1000)->count();
        } catch (\Exception $e) {
            $totalHighVL = 0;
        }

        // Samples Rejected
        try {
            $totalSamplesRejected = DB::table('sample_rejections')->count();
        } catch (\Exception $e) {
            $totalSamplesRejected = 0;
        }

        // Patients Due for EAC (viral load >= 1000)
        try {
            $totalDueEAC = ViralLoad::where('result_cpml', '>=', 1000)
                ->distinct('patient_id')
                ->count('patient_id');
        } catch (\Exception $e) {
            $totalDueEAC = 0;
        }

        // Patients Due for Repeat VL (after session 3 completed)
        try {
            $totalDueRepeatVL = EACSession::where('session_number', 3)
                ->where('completion_status', 'Completed')
                ->distinct('patient_id')
                ->count('patient_id');
        } catch (\Exception $e) {
            $totalDueRepeatVL = 0;
        }

        // 🔴 High Viral Load Patients
        try {
            $highVL = ViralLoad::where('result_cpml', '>=', 1000)
                ->distinct('patient_id')
                ->count('patient_id');
        } catch (\Exception $e) {
            $highVL = 0;
        }

        // 🟡 Patients in EAC (Pending sessions)
        try {
            $dueEAC = EACSession::where('completion_status', 'Pending')
                ->distinct('patient_id')
                ->count('patient_id');
        } catch (\Exception $e) {
            $dueEAC = 0;
        }

        // 🟢 Patients needing Repeat VL (Completed session 3)
        try {
            $repeatVL = EACSession::where('session_number', 3)
                ->where('completion_status', 'Completed')
                ->distinct('patient_id')
                ->count('patient_id');
        } catch (\Exception $e) {
            $repeatVL = 0;
        }

        // Additional metrics from previous code
        // Total scheduled, completed, missed appointments
        try {
            $scheduledAppointments = Appointment::where('status', 'Scheduled')->count();
        } catch (\Exception $e) {
            $scheduledAppointments = 0;
        }
        try {
            $completedAppointments = Appointment::where('status', 'Completed')->count();
        } catch (\Exception $e) {
            $completedAppointments = 0;
        }
        try {
            $missedAppointments = Appointment::where('status', 'Missed')->count();
        } catch (\Exception $e) {
            $missedAppointments = 0;
        }

        // EAC: completed and ongoing
        try {
            $completedEAC = EACSession::where('completion_status', 'Completed')->count();
        } catch (\Exception $e) {
            $completedEAC = 0;
        }
        try {
            $ongoingEAC = EACSession::where('completion_status', 'Ongoing')->count();
        } catch (\Exception $e) {
            $ongoingEAC = 0;
        }

        return view('dashboard', compact(
            // Existing metrics
            'totalPatients',
            'totalAppointmentsToday',
            'totalSamplesCollected',
            'totalHighVL',
            'totalSamplesRejected',
            'totalDueEAC',
            'totalDueRepeatVL',

            // New metrics from previous code
            'scheduledAppointments',
            'completedAppointments',
            'missedAppointments',
            'completedEAC',
            'ongoingEAC',

            // Additional metrics
            'highVL',
            'dueEAC',
            'repeatVL'
        ));
    }
}