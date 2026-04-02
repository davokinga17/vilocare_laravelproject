<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\ViralLoad;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // 🔷 PATIENT PDF
    public function patientsPDF()
    {
        $patients = Patient::all();
        $pdf = Pdf::loadView('reports.patients_pdf', compact('patients'));

        return $pdf->download('patients_report.pdf');
    }

    // 🔷 PATIENT EXCEL
    public function patientsExcel()
    {
        return Excel::download(new \App\Exports\PatientsExport, 'patients.xlsx');
    }

    // 🔷 VIRAL LOAD PDF
    public function viralLoadPDF()
    {
        $results = ViralLoad::with('patient')->get();
        $pdf = Pdf::loadView('reports.viral_load_pdf', compact('results'));

        return $pdf->download('viral_load_report.pdf');
    }

    // 🔷 VIRAL LOAD EXCEL
    public function viralLoadExcel()
    {
        return Excel::download(new \App\Exports\ViralLoadExport, 'viral_load.xlsx');
    }
}