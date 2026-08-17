<?php

namespace App\Http\Controllers;

use App\Imports\PatientsImport;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Patient::query();

        // 🔍 Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('art_number', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        // 🔽 Filter by sex
        if ($request->sex) {
            $query->where('sex', $request->sex);
        }

        // Filter by exact age
        if ($request->filled('age')) {
            $query->where('age', $request->age);
        }

        if ($request->filled('facility_id')) {
            $query->where('facility_id', $request->input('facility_id'));
        }

        // Retrieve filtered patients with pagination
        $patients = $query->paginate(10)->appends($request->query());

        // Pass patients and current filters to the view for display
        return view('patients.index', [
            'patients' => $patients,
            'search' => $request->search,
            'sex' => $request->sex,
            'age' => $request->age,
            'facility_id' => $request->facility_id,
        ] + $this->locationOptions());
    }

    public function create()
    {
        return view('patients.create', $this->locationOptions());
    }

    public function store(Request $request)
    {
        $request->validate([
            'art_number' => ['required', 'string', 'max:100', Rule::unique('patients', 'art_number')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'art_start_date' => ['nullable', 'date'],
            'current_regimen' => ['nullable', 'string', 'max:100'],
            'age' => ['nullable', 'integer', 'min:0', 'max:130'],
            'is_pregnant' => ['nullable', 'boolean'],
            'is_breastfeeding' => ['nullable', 'boolean'],
            'arv_adherence' => ['nullable', 'string', 'max:50'],
            'state_id' => $this->lookupValidationRules('states', ['state_id', 'id']),
            'county_id' => $this->lookupValidationRules('counties', ['county_id', 'id']),
            'facility_id' => $this->lookupValidationRules('facilities', ['facility_id', 'id']),
        ], [
            'art_number.required' => 'ART Number is required',
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
        ]);

        // Create a new patient record
        Patient::create($this->patientPayload($request));

        return redirect('/patients')->with('success', 'Patient added successfully');
    }

    public function show($id)
    {
        $patient = Patient::with([
            'viralLoads' => fn ($query) => $query->orderByDesc('result_date')->orderByDesc('vl_id'),
            'eacSessions' => fn ($query) => $query->orderByDesc('session_date')->orderByDesc('eac_id'),
            'payments' => fn ($query) => $query->orderByDesc('paid_at')->orderByDesc('payment_id'),
        ])->findOrFail($id);

        return view('patients.show', compact('patient'));
    }

    public function printLatestResult($id)
    {
        $patient = Patient::with(['viralLoads' => fn ($query) => $query->orderByDesc('result_date')->orderByDesc('vl_id')])
            ->findOrFail($id);

        $result = $patient->viralLoads->first();

        if (! $result) {
            return redirect()
                ->route('patients.show', $patient->patient_id)
                ->with('warning', 'No viral load result is available for printing yet.');
        }

        if (! $this->hasPaidForPatientResult($patient->patient_id, $result->vl_id, 'result_print')) {
            return back()->with('warning', 'Result printing is locked until Reception accepts the print-fee payment request.');
        }

        return view('patients.result_print', compact('patient', 'result'));
    }

    public function pdfLatestResult($id)
    {
        $patient = Patient::with(['viralLoads' => fn ($query) => $query->orderByDesc('result_date')->orderByDesc('vl_id')])
            ->findOrFail($id);

        $result = $patient->viralLoads->first();

        if (! $result) {
            return redirect()
                ->route('patients.show', $patient->patient_id)
                ->with('warning', 'No viral load result is available for PDF export yet.');
        }

        if (! $this->hasPaidForPatientResult($patient->patient_id, $result->vl_id, 'result_pdf')) {
            return back()->with('warning', 'Result PDF download is locked until Reception accepts the PDF-fee payment request.');
        }

        return Pdf::loadView('patients.result_pdf', compact('patient', 'result'))
            ->setPaper('a4', 'portrait')
            ->download('patient_result_' . $patient->art_number . '.pdf');
    }

    public function edit($id)
    {
        $patient = Patient::findOrFail($id);
        return view('patients.edit', array_merge(compact('patient'), $this->locationOptions()));
    }

    public function update(Request $request, $id)
    {
        // Validate incoming data
        $request->validate([
            'art_number' => ['required', 'string', 'max:100', Rule::unique('patients', 'art_number')->ignore($id, 'patient_id')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'art_start_date' => ['nullable', 'date'],
            'current_regimen' => ['nullable', 'string', 'max:100'],
            'age' => ['nullable', 'integer', 'min:0', 'max:130'],
            'is_pregnant' => ['nullable', 'boolean'],
            'is_breastfeeding' => ['nullable', 'boolean'],
            'arv_adherence' => ['nullable', 'string', 'max:50'],
            'state_id' => $this->lookupValidationRules('states', ['state_id', 'id']),
            'county_id' => $this->lookupValidationRules('counties', ['county_id', 'id']),
            'facility_id' => $this->lookupValidationRules('facilities', ['facility_id', 'id']),
        ]);

        // Find the patient by ID
        $patient = Patient::findOrFail($id);

        // Update patient data
        $patient->update($this->patientPayload($request));

        return redirect('/patients')->with('success', 'Patient updated successfully');
    }

    public function import(Request $request)
    {
        $request->validate([
            'patients_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $import = new PatientsImport();
        Excel::import($import, $request->file('patients_file'));

        $message = "{$import->imported} patient record(s) imported.";

        if ($import->skipped > 0) {
            $message .= " {$import->skipped} row(s) skipped.";
        }

        return redirect('/patients')
            ->with('success', $message)
            ->with('import_errors', array_slice($import->errors, 0, 8));
    }

    public function destroy($id)
    {
        Patient::destroy($id);
        return redirect('/patients')->with('success', 'Patient deleted');
    }

    private function locationOptions(): array
    {
        return [
            'states' => $this->lookupOptions('states', ['state_id', 'id'], ['state_name', 'name']),
            'counties' => $this->lookupOptions('counties', ['county_id', 'id'], ['county_name', 'name']),
            'facilities' => $this->lookupOptions('facilities', ['facility_id', 'id'], ['facility_name', 'name']),
        ];
    }

    private function lookupOptions(string $table, array $idCandidates, array $labelCandidates): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $idColumn = $this->lookupColumn($table, $idCandidates);
        $labelColumn = $this->lookupColumn($table, $labelCandidates);

        if (! $idColumn || ! $labelColumn) {
            return [];
        }

        return DB::table($table)
            ->whereNotNull($labelColumn)
            ->orderBy($labelColumn)
            ->get([$idColumn . ' as value', $labelColumn . ' as label'])
            ->map(fn ($option) => [
                'value' => (string) $option->value,
                'label' => (string) $option->label,
            ])
            ->all();
    }

    private function lookupColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function patientPayload(Request $request): array
    {
        return [
            'art_number' => $request->art_number,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'sex' => $request->sex,
            'address' => $request->address,
            'phone' => $request->phone,
            'art_start_date' => $request->art_start_date,
            'current_regimen' => $request->current_regimen,
            'age' => $this->normalizeAgeValue($request->input('age')),
            'is_pregnant' => $request->boolean('is_pregnant'),
            'is_breastfeeding' => $request->boolean('is_breastfeeding'),
            'arv_adherence' => $request->arv_adherence,
            'facility_id' => $this->normalizeLookupValue($request->input('facility_id')),
            'county_id' => $this->normalizeLookupValue($request->input('county_id')),
            'state_id' => $this->normalizeLookupValue($request->input('state_id')),
        ];
    }

    private function normalizeLookupValue(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : $value;

        if ($value === '' || $value === '0' || $value === 0) {
            return null;
        }

        return (int) $value;
    }

    private function normalizeAgeValue(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : $value;

        if ($value === '') {
            return null;
        }

        return (int) $value;
    }

    private function lookupValidationRules(string $table, array $idCandidates): array
    {
        $rules = ['nullable', 'integer', 'min:1'];
        $idColumn = $this->lookupColumn($table, $idCandidates);

        if (Schema::hasTable($table) && $idColumn) {
            $rules[] = Rule::exists($table, $idColumn);
        }

        return $rules;
    }

    private function hasPaidForPatientResult(int $patientId, int $viralLoadId, string $paymentType): bool
    {
        return Payment::paid()
            ->where('patient_id', $patientId)
            ->where('vl_id', $viralLoadId)
            ->where('payment_type', $paymentType)
            ->exists();
    }
}
