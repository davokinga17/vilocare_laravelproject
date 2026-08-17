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

    public function index(Request $request)
    {
        $query = ViralLoad::with(['patient', 'payments']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('vl_id', 'like', '%' . $search . '%')
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('art_number', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->input('status') === 'suppressed') {
            $query->where('result_cpml', '<', 1000);
        } elseif ($request->input('status') === 'high') {
            $query->where('result_cpml', '>=', 1000);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('result_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('result_date', '<=', $request->input('to_date'));
        }

        $results = $query
            ->orderByDesc('result_date')
            ->orderByDesc('vl_id')
            ->paginate(12)
            ->withQueryString();
        $latestResultIds = ViralLoad::query()
            ->selectRaw('MAX(vl_id) as vl_id')
            ->groupBy('patient_id')
            ->pluck('vl_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('viral_load.index', compact('results', 'latestResultIds'));
    }

    public function create()
    {
        $patients = Patient::orderBy('first_name')->orderBy('last_name')->get();
        return view('viral_load.create', compact('patients'));
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'patient_id' => ['required', 'exists:patients,patient_id'],
        'sample_date' => ['nullable', 'date'],
        'result_date' => ['nullable', 'date'],
        'sample_type' => ['nullable', 'string', 'max:100'],
        'lab' => ['nullable', 'string', 'max:100'],
        'result_cpml' => ['required', 'numeric', 'min:0'],
        'result_log' => ['nullable', 'numeric'],
        'requesting_clinician' => ['nullable', 'string', 'max:100'],
        'clinician_cellphone' => ['nullable', 'string', 'max:30'],
        'request_date' => ['nullable', 'date'],
        'vl_testing_indication' => ['nullable', 'string', 'max:150'],
        'comments' => ['nullable', 'string'],
    ]);

    $validated['status'] = (float) $validated['result_cpml'] >= 1000 ? 'High' : 'Suppressed';
    $vl = ViralLoad::create($validated);

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
