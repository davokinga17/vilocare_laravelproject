<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ViralLoadController;
use App\Http\Controllers\EACController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\ProfileController;

// Show login form
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// Handle login submission
Route::post('/login', [AuthController::class, 'login']);

Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Logout route
Route::get('/logout', [AuthController::class, 'logout']);

// Optional: redirect root to login
Route::get('/', function () {
    return redirect('/login');
});

// Step 4: Protect Routes by Role

// PATIENTS and related resources
Route::middleware(['auth', 'password.change.required', 'role:Administrator,Clinician,Data Clerk'])->group(function () {
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/patients/store', [PatientController::class, 'store'])->name('patients.store');
    Route::post('/patients/import', [PatientController::class, 'import'])->name('patients.import');
    Route::get('/patients/{id}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{id}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::match(['post', 'put'], '/patients/{id}/update', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{id}/delete', [PatientController::class, 'destroy'])->name('patients.destroy');

    Route::get('/samples', [SampleController::class, 'index'])->name('samples.index');
    Route::post('/samples', [SampleController::class, 'store'])->name('samples.store');
    Route::post('/samples/rejections', [SampleController::class, 'reject'])->name('samples.reject');

    // EAC for Clinician+Admin
    Route::get('/eac', [EACController::class, 'index']);
    Route::post('/eac/{id}/complete', [EACController::class, 'complete']);

    // Viral load for all roles
    Route::get('/viral-load', [ViralLoadController::class, 'index']);
});

// ADMIN group stay for admin-only routes
Route::middleware(['auth', 'password.change.required', 'role:Administrator,Clinician'])->group(function () {
    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [UserManagementController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [UserManagementController::class, 'edit'])->name('admin.users.edit');
    Route::post('/admin/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
});

// DATA CLERK
Route::middleware(['auth', 'password.change.required', 'role:Administrator,Data Clerk'])->group(function () {
    // Patients routes are now in the above group

    // Future: Appointments module
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/create', [AppointmentController::class, 'create']);
    Route::post('/appointments/store', [AppointmentController::class, 'store']);
});

// LAB TECHNICIAN
Route::middleware(['auth', 'password.change.required', 'role:Administrator,Lab Technician'])->group(function () {
    Route::get('/viral-load', [ViralLoadController::class, 'index']);
    Route::get('/viral-load/create', [ViralLoadController::class, 'create']);
    Route::post('/viral-load/store', [ViralLoadController::class, 'store']);
});

Route::middleware(['auth', 'password.change.required'])->group(function () {
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('password.change.edit');
    Route::post('/change-password', [AuthController::class, 'updatePassword'])->name('password.change.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'password.change.required'])->group(function () {

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
    ->middleware(['auth', 'password.change.required'])
    ->name('dashboard');

Route::middleware(['auth', 'password.change.required'])->group(function () {
    Route::get('/dashboard/vl-due', [DashboardController::class, 'dueForVlList'])
        ->name('dashboard.vl_due.list');
    Route::get('/dashboard/vl-due/export', [DashboardController::class, 'exportDueForVlList'])
        ->name('dashboard.vl_due.export');
});
