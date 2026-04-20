<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AdminModerationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DoctorProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\VerificationRequestController;
use App\Http\Controllers\Api\WorkingDateController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::get('/doctors',        [DoctorController::class, 'index']);
Route::get('/doctors/{user}', [DoctorController::class, 'show']);
Route::get('/specialties',    [DoctorController::class, 'specialties']);
Route::get('/categories',     [DoctorController::class, 'categories']);
Route::get('/cities',         [DoctorController::class, 'cities']);

// ── Authenticated ─────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/verification-requests', [VerificationRequestController::class, 'store']);
    Route::get('/verification-requests', [VerificationRequestController::class, 'index']);
    Route::patch('/verification-requests/{verificationRequest}/approve', [VerificationRequestController::class, 'approve']);
    Route::patch('/verification-requests/{verificationRequest}/reject', [VerificationRequestController::class, 'reject']);

    Route::apiResource('appointments', AppointmentController::class);

    Route::middleware('role:doctor')->group(function () {
        Route::get('/doctor/profile', [DoctorProfileController::class, 'show']);
        Route::patch('/doctor/profile', [DoctorProfileController::class, 'update']);
        Route::apiResource('schedules', ScheduleController::class);
        Route::post('/working-dates', [WorkingDateController::class, 'store']);
    });

    Route::apiResource('reviews', ReviewController::class)
        ->only(['store', 'show', 'index']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', [AdminModerationController::class, 'getUsers']);
        Route::patch('/admin/users/{user}/suspend', [AdminModerationController::class, 'suspendUser']);
        Route::patch('/admin/users/{user}/activate', [AdminModerationController::class, 'activateUser']);
        Route::patch('/admin/doctor-profiles/{doctorProfile}/verify', [AdminModerationController::class, 'verifyDoctor']);
        Route::patch('/admin/doctor-profiles/{doctorProfile}/unverify', [AdminModerationController::class, 'unverifyDoctor']);
        Route::delete('/admin/reviews/{review}', [ReviewController::class, 'destroy']);
    });
});
