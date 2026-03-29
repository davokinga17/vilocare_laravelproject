<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EACSession; // Make sure this is imported

class EACController extends Controller
{
    public function index()
    {
        $sessions = EACSession::with('patient')->get();
        return view('eac.index', compact('sessions'));
    }

    public function complete($id)
    {
        $session = EACSession::findOrFail($id);

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