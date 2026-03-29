<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $patients = Patient::when($search, function ($query) use ($search) {
            $query->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('art_number', 'like', "%$search%");
        })->paginate(10);

        return view('patients.index', compact('patients', 'search'));
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
        Patient::create($request->only(['art_number', 'first_name', 'last_name', 'sex', 'phone']));

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
        ]);

        // Find the patient by ID
        $patient = Patient::findOrFail($id);

        // Update patient data
        $patient->update($request->only(['art_number', 'first_name', 'last_name', 'sex', 'phone']));

        return redirect('/patients')->with('success', 'Patient updated successfully');
    }

    public function destroy($id)
    {
        Patient::destroy($id);
        return redirect('/patients')->with('success', 'Patient deleted');
    }
}