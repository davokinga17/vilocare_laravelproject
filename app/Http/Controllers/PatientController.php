<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;

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
                  ->orWhere('art_number', 'like', '%' . $request->search . '%');
            });
        }

        // 🔽 Filter by sex
        if ($request->sex) {
            $query->where('sex', $request->sex);
        }

        // 🔽 Filter by age category
        if ($request->age_category) {
            $query->where('age_category', $request->age_category);
        }

        // Retrieve filtered patients with pagination
        $patients = $query->paginate(10)->appends($request->query());

        // Pass patients and current filters to the view for display
        return view('patients.index', [
            'patients' => $patients,
            'search' => $request->search,
            'sex' => $request->sex,
            'age_category' => $request->age_category,
        ]);
    }

    public function create()
    {
        return view('patients.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'art_number' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
        ], [
            'art_number.required' => 'ART Number is required',
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
        ]);

        // Create a new patient record
        Patient::create($request->only(['art_number', 'first_name', 'last_name', 'sex', 'phone', 'age_category']));

        return redirect('/patients')->with('success', 'Patient added successfully');
    }

    public function show($id)
    {
        $patient = Patient::findOrFail($id);
        return view('patients.show', compact('patient'));
    }

    public function edit($id)
    {
        $patient = Patient::findOrFail($id);
        return view('patients.edit', compact('patient'));
    }

    public function update(Request $request, $id)
    {
        // Validate incoming data
        $request->validate([
            'art_number' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'sex' => 'required|string',
            'phone' => 'nullable|string',
            'age_category' => 'nullable|string',
        ]);

        // Find the patient by ID
        $patient = Patient::findOrFail($id);

        // Update patient data
        $patient->update($request->only(['art_number', 'first_name', 'last_name', 'sex', 'phone', 'age_category']));

        return redirect('/patients')->with('success', 'Patient updated successfully');
    }

    public function destroy($id)
    {
        Patient::destroy($id);
        return redirect('/patients')->with('success', 'Patient deleted');
    }
}