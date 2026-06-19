<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ViralLoad;
use App\Models\Patient;
use App\Models\EACSession;
use App\Services\NotificationService;

class ViralLoadController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function index()
    {
        $results = ViralLoad::with('patient')->orderBy('sample_date', 'desc')->get();

        return view('viral_load.index', compact('results'));
    }

    public function create()
    {
        $patients = Patient::all();
        return view('viral_load.create', compact('patients'));
    }

    public function store(Request $request)
{
    $request->validate([
        'patient_id' => 'required|exists:patients,patient_id',
        'result_cpml' => 'required|numeric'
    ]);

    $vl = ViralLoad::create($request->all());

    // 🔥 EAC TRIGGER LOGIC
    if ($request->result_cpml >= 1000) {

        // Check if patient already has EAC started
        $exists = EACSession::where('patient_id', $request->patient_id)->exists();

        if (!$exists) {
            EACSession::create([
                'patient_id' => $request->patient_id,
                'session_number' => 1,
                'session_date' => now(),
                'completion_status' => 'Pending'
            ]);
        }
    }

    if ((float) $request->result_cpml >= 1000) {
        $this->notificationService->sendHighViralLoadAlert($vl->load('patient'), $request->user());
    }

    return redirect('/viral-load')->with('success', 'Viral Load recorded');
}
}
