<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // High Viral Load
        $totalHighVL = DB::table('viral_load_results')
            ->where('result_cpml', '>=', 1000)
            ->count();

        // Samples Rejected
        $totalSamplesRejected = DB::table('sample_rejections')->count();

        // Patients Due for EAC
        $totalDueEAC = DB::table('viral_load_results')
            ->where('result_cpml', '>=', 1000)
            ->distinct('patient_id')
            ->count('patient_id');

        // Patients Due for Repeat VL
        $totalDueRepeatVL = DB::table('eac_sessions')
            ->where('completion_status', 'Completed')
            ->where('session_number', 3)
            ->distinct('patient_id')
            ->count('patient_id');

        return view('dashboard', compact(
            'totalPatients',
            'totalAppointmentsToday',
            'totalSamplesCollected',
            'totalHighVL',
            'totalSamplesRejected',
            'totalDueEAC',
            'totalDueRepeatVL'
        ));
    }
}