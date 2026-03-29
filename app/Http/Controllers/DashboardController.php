<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ViralLoad;
use App\Models\EACSession;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = date('Y-m-d');

        // Total Patients
        $totalPatients = DB::table('patients')->count();

        // Appointments Today
        $totalAppointmentsToday = DB::table('appointments')
            ->whereDate('appointment_date', $today)
            ->count();

        // Samples Collected
        $totalSamplesCollected = DB::table('sample_collection')->count();

        // High Viral Load (>= 1000)
        $totalHighVL = DB::table('viral_load_results')
            ->where('result_cpml', '>=', 1000)
            ->count();

        // Samples Rejected
        $totalSamplesRejected = DB::table('sample_rejections')->count();

        // Patients Due for EAC (viral load >= 1000)
        $totalDueEAC = DB::table('viral_load_results')
            ->where('result_cpml', '>=', 1000)
            ->distinct('patient_id')
            ->count('patient_id');

        // Patients Due for Repeat VL (after session 3 completed)
        $totalDueRepeatVL = DB::table('eac_sessions')
            ->where('completion_status', 'Completed')
            ->where('session_number', 3)
            ->distinct('patient_id')
            ->count('patient_id');

        // 🔴 High Viral Load Patients
        $highVL = ViralLoad::where('result_cpml', '>=', 1000)
            ->distinct('patient_id')
            ->count('patient_id');

        // 🟡 Patients in EAC (Pending sessions)
        $dueEAC = EACSession::where('completion_status', 'Pending')
            ->distinct('patient_id')
            ->count('patient_id');

        // 🟢 Patients needing Repeat VL (Completed session 3)
        $repeatVL = EACSession::where('session_number', 3)
            ->where('completion_status', 'Completed')
            ->distinct('patient_id')
            ->count('patient_id');

        return view('dashboard', compact(
            'totalPatients',
            'totalAppointmentsToday',
            'totalSamplesCollected',
            'totalHighVL',
            'totalSamplesRejected',
            'totalDueEAC',
            'totalDueRepeatVL',

            // New
            'highVL',
            'dueEAC',
            'repeatVL'
        ));
    }
}