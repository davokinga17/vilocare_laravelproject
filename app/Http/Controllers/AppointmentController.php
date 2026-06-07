<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Patient;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with('patient')->get();
        return view('appointments.index', compact('appointments'));
    }

    public function create()
    {
        $patients = Patient::all();
        return view('appointments.create', compact('patients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,patient_id'],
            'appointment_date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'status' => ['required', 'in:Pending,Completed,Missed,Cancelled'],
        ]);

        Appointment::create($validated);

        return redirect('/appointments')->with('success', 'Appointment created');
    }
}
