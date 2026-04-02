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

// ADMIN (FULL ACCESS)
Route::middleware(['auth', 'role:Administrator'])->group(function () {
    // Can access everything, optional grouping
    // Add admin-specific routes here if needed
});

// CLINICIAN
Route::middleware(['auth', 'role:Administrator,Clinician'])->group(function () {
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);

    Route::get('/eac', [EACController::class, 'index']);
    Route::post('/eac/{id}/complete', [EACController::class, 'complete']);

    Route::get('/viral-load', [ViralLoadController::class, 'index']);
});

// DATA CLERK
Route::middleware(['auth', 'role:Administrator,Data Clerk'])->group(function () {
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/create', [PatientController::class, 'create']);
    Route::post('/patients/store', [PatientController::class, 'store']);

    Route::get('/patients/{id}/edit', [PatientController::class, 'edit']);
    Route::post('/patients/{id}/update', [PatientController::class, 'update']);

    // Future: Appointments module
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

    // Patients
    Route::get('/reports/patients/pdf', [ReportController::class, 'patientsPDF']);
    Route::get('/reports/patients/excel', [ReportController::class, 'patientsExcel']);

    // Viral Load
    Route::get('/reports/viral-load/pdf', [ReportController::class, 'viralLoadPDF']);
    Route::get('/reports/viral-load/excel', [ReportController::class, 'viralLoadExcel']);
});

// Dashboard accessible to all authenticated users
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');