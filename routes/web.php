<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ViralLoadController;
use App\Http\Controllers\EACController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ReportController;

// Show login form
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// Handle login submission
Route::post('/login', [AuthController::class, 'login']);

// Logout route
Route::get('/logout', [AuthController::class, 'logout']);

// Optional: redirect root to login
Route::get('/', function () {
    return redirect('/login');
});

// Step 4: Protect Routes by Role

// PATIENTS and related resources
Route::middleware(['auth', 'role:Administrator,Clinician,Data Clerk'])->group(function () {
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/patients/store', [PatientController::class, 'store'])->name('patients.store');
    Route::get('/patients/{id}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{id}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::post('/patients/{id}/update', [PatientController::class, 'update'])->name('patients.update');

    // EAC for Clinician+Admin
    Route::get('/eac', [EACController::class, 'index']);
    Route::post('/eac/{id}/complete', [EACController::class, 'complete']);

    // Viral load for all roles
    Route::get('/viral-load', [ViralLoadController::class, 'index']);
});

// ADMIN group stay for admin-only routes
Route::middleware(['auth', 'role:Administrator'])->group(function () {
    // Add admin-only routes here if needed
});

// DATA CLERK
Route::middleware(['auth', 'role:Administrator,Data Clerk'])->group(function () {
    // Patients routes are now in the above group

    // Future: Appointments module
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/create', [AppointmentController::class, 'create']);
    Route::post('/appointments/store', [AppointmentController::class, 'store']);
});

// LAB TECHNICIAN
Route::middleware(['auth', 'role:Administrator,Lab Technician'])->group(function () {
    Route::get('/viral-load', [ViralLoadController::class, 'index']);
    Route::get('/viral-load/create', [ViralLoadController::class, 'create']);
    Route::post('/viral-load/store', [ViralLoadController::class, 'store']);
});

Route::middleware(['auth', 'role:Administrator,Data Clerk'])->group(function () {

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/create', [AppointmentController::class, 'create']);
    Route::post('/appointments/store', [AppointmentController::class, 'store']);

});
Route::middleware(['auth'])->group(function () {

    // Reports index
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Patients
    Route::get('/reports/patients/pdf', [ReportController::class, 'patientsPDF'])->name('reports.patients.pdf');
    Route::get('/reports/patients/excel', [ReportController::class, 'patientsExcel'])->name('reports.patients.excel');

    // Viral Load
    Route::get('/reports/viral-load/pdf', [ReportController::class, 'viralLoadPDF'])->name('reports.viral_load.pdf');
    Route::get('/reports/viral-load/excel', [ReportController::class, 'viralLoadExcel'])->name('reports.viral_load.excel');
});

// Dashboard accessible to all authenticated users
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');