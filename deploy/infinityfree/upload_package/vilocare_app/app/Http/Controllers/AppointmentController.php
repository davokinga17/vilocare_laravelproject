<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with('patient');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('patient', function ($patientQuery) use ($search) {
                $patientQuery->where('art_number', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('purpose')) {
            $query->where('reason', $request->input('purpose'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('appointment_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('appointment_date', '<=', $request->input('to_date'));
        }

        $appointments = $query->orderByDesc('appointment_date')->orderByDesc('appointment_id')->paginate(10)->withQueryString();
        $purposes = Appointment::query()->whereNotNull('reason')->where('reason', '!=', '')->distinct()->orderBy('reason')->pluck('reason');
        $statuses = Appointment::query()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status');
        $metrics = [
            'today' => Appointment::query()->whereDate('appointment_date', today())->count(),
            'upcoming' => Appointment::query()->whereDate('appointment_date', '>', today())->whereNotIn('status', ['Completed', 'Missed', 'Cancelled'])->count(),
            'completed' => Appointment::query()->where('status', 'Completed')->count(),
            'missed' => Appointment::query()->where('status', 'Missed')->count(),
        ];

        $facilityIdColumn = Schema::hasColumn('facilities', 'facility_id') ? 'facility_id' : 'id';
        $facilityNameColumn = Schema::hasColumn('facilities', 'facility_name') ? 'facility_name' : 'name';
        $facilityLabels = Schema::hasTable('facilities')
            && Schema::hasColumn('facilities', $facilityIdColumn)
            && Schema::hasColumn('facilities', $facilityNameColumn)
                ? DB::table('facilities')->pluck($facilityNameColumn, $facilityIdColumn)
                : collect();

        return view('appointments.index', compact('appointments', 'purposes', 'statuses', 'metrics', 'facilityLabels'));
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
