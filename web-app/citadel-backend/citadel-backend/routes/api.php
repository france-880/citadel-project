<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;

Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);


// ✅ student routes
Route::get('/students', [StudentController::class, 'index']);
Route::post('/students', [StudentController::class, 'store']);
Route::get('/students/{id}', [StudentController::class, 'show']);
Route::put('/students/{id}', [StudentController::class, 'update']);
Route::delete('/students/delete-multiple', [StudentController::class, 'deleteMultiple']);
Route::delete('/students/{id}', [StudentController::class, 'destroy']);
Route::delete('/students', [StudentController::class, 'bulkDestroy']);



Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // ✅ user routes - now protected by authentication
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'updateUser']);
    Route::delete('/users/delete-multiple', [UserController::class, 'deleteMultiple']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::delete('/users', [UserController::class, 'bulkDestroy']);
    Route::put('/users/{id}/change-password', [UserController::class, 'changePassword']);

    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me']);
    
    // Profile routes
    Route::get('/profile', [AuthController::class, 'me']); // Get current user profile
    Route::put('/profile', [UserController::class, 'updateProfile']); // Update current user profile
});