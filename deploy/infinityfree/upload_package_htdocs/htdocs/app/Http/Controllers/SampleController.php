<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\SampleCollection;
use App\Models\SampleRejection;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SampleController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function index(Request $request)
    {
        $query = SampleCollection::with(['patient', 'rejections']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('sample_id', 'like', '%' . $search . '%')
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('art_number', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('sample_type')) {
            $query->where('sample_type', $request->input('sample_type'));
        }

        $samples = $query->orderByDesc('collection_date')
            ->paginate(10)
            ->withQueryString();

        $rejections = SampleRejection::with(['sample.patient'])
            ->orderByDesc('rejection_date')
            ->limit(10)
            ->get();

        $sampleTypes = SampleCollection::query()
            ->whereNotNull('sample_type')
            ->where('sample_type', '!=', '')
            ->distinct()
            ->orderBy('sample_type')
            ->pluck('sample_type');

        $rejectableSamples = SampleCollection::with('patient')
            ->where(function ($builder) {
                $builder->whereNull('status')->orWhere('status', '!=', 'Rejected');
            })
            ->orderByDesc('collection_date')
            ->limit(100)
            ->get();

        return view('samples.index', compact('samples', 'rejections', 'sampleTypes', 'rejectableSamples'));
    }

    public function create()
    {
        $patients = Patient::orderBy('first_name')
            ->orderBy('last_name')
            ->get(['patient_id', 'art_number', 'first_name', 'last_name']);

        return view('samples.create', compact('patients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,patient_id'],
            'collection_date' => ['required', 'date'],
            'sample_type' => ['nullable', 'string', 'max:50'],
            'collector' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'sample_reception_date' => ['nullable', 'date'],
            'health_facility_code' => ['nullable', 'string', 'max:50'],
        ]);

        SampleCollection::create($validated);

        return redirect()->route('samples.index')->with('success', 'Sample collection details saved successfully.');
    }

    public function reject(Request $request)
    {
        $validated = $request->validate([
            'sample_id' => ['required', 'exists:sample_collection,sample_id'],
            'rejection_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string'],
            'action_taken' => ['nullable', 'string'],
            'corrective_action' => ['nullable', 'string'],
        ]);

        $rejection = SampleRejection::create($validated);

        SampleCollection::where('sample_id', $validated['sample_id'])->update(['status' => 'Rejected']);

        $sample = SampleCollection::with('patient')->find($validated['sample_id']);
        if ($sample) {
            $this->notificationService->sendSampleRejectionAlert($sample, $rejection, $request->user());
        }

        return redirect()->route('samples.index')->with('success', 'Sample rejection recorded successfully.');
    }
}
