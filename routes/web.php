<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ViralLoadController;

// Show login form
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// Handle login submission
Route::post('/login', [AuthController::class, 'login']);

// Logout route
Route::get('/logout', [AuthController::class, 'logout']);

// Dashboard route
Route::get('/dashboard', [DashboardController::class, 'index']); // Added missing semicolon

// Group all patient routes under auth middleware
Route::middleware('auth')->group(function () {
    // Patients routes
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/create', [PatientController::class, 'create']);
    Route::post('/patients/store', [PatientController::class, 'store']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);
    Route::get('/patients/{id}/edit', [PatientController::class, 'edit']);
    Route::post('/patients/{id}/update', [PatientController::class, 'update']);
    Route::get('/patients/{id}/delete', [PatientController::class, 'destroy']);
    Route::post('/patients/{id}/delete', [PatientController::class, 'destroy']);

    Route::get('/viral-load', [ViralLoadController::class, 'index']);
    Route::get('/viral-load/create', [ViralLoadController::class, 'create']);
    Route::post('/viral-load/store', [ViralLoadController::class, 'store']);
});

// Optional: redirect root to login
Route::get('/', function () {
    return redirect('/login');
});