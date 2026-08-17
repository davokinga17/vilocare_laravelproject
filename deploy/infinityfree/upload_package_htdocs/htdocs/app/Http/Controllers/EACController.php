<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EACSession; // Make sure this is imported
use App\Models\Payment;

class EACController extends Controller
{
    public function index(Request $request)
    {
        $query = EACSession::with(['patient', 'payments']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('patient', function ($patientQuery) use ($search) {
                $patientQuery->where('art_number', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('session_number')) {
            $query->where('session_number', $request->integer('session_number'));
        }

        if ($request->filled('status')) {
            $query->where('completion_status', $request->input('status'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('session_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('session_date', '<=', $request->input('to_date'));
        }

        $sessions = $query->orderByDesc('session_date')->orderByDesc('eac_id')->paginate(10)->withQueryString();

        $metrics = [
            'active_clients' => EACSession::query()->where('completion_status', '!=', 'Completed')->distinct('patient_id')->count('patient_id'),
            'session_one_due' => EACSession::query()->where('session_number', 1)->where('completion_status', 'Pending')->count(),
            'pending' => EACSession::query()->where('completion_status', 'Pending')->count(),
            'completed' => EACSession::query()->where('completion_status', 'Completed')->count(),
        ];

        return view('eac.index', compact('sessions', 'metrics'));
    }

    public function complete($id)
    {
        $session = EACSession::with('patient')->findOrFail($id);

        $hasConsultationPayment = Payment::paid()
            ->where('eac_id', $session->eac_id)
            ->where('payment_type', 'eac_consultation')
            ->exists();

        if (! $hasConsultationPayment) {
            return back()->with(
                'warning',
                'Record the EAC consultation fee for ' . $session->patient->first_name . ' ' . $session->patient->last_name . ' before completing this session.'
            );
        }

        // Mark current session as completed
        $session->completion_status = 'Completed';
        $session->save();

        // 🔥 CREATE NEXT SESSION LOGIC
        if ($session->session_number < 3) {
            EACSession::create([
                'patient_id' => $session->patient_id,
                'session_number' => $session->session_number + 1,
                'session_date' => now()->addWeeks(4), // next session after 4 weeks
                'completion_status' => 'Pending'
            ]);
        } else {
            // After session 3 → patient needs repeat VL
            // (We’ll display this in UI)
        }

        return back()->with('success', 'Session marked as completed');
    }
}
