<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ViralLoad;
use App\Models\Patient;

class ViralLoadController extends Controller
{
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
            'patient_id' => 'required',
            'result_cpml' => 'required|numeric'
        ]);

        ViralLoad::create($request->all());

        return redirect('/viral-load')->with('success', 'Viral Load recorded');
    }
}