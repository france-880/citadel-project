<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AcademicManagement\CollegeController;
use App\Http\Controllers\AcademicManagement\ProgramController;
use App\Http\Controllers\AcademicManagement\SubjectController;
use App\Http\Controllers\FacultyLoadController;
use App\Http\Controllers\YearSectionController;

/* ===============================================
   =========== Login Credentials Routes ==========
   =============================================== */
   
// Login Route
Route::post('/login', [AuthController::class, 'login']);

// Password Reset Routes
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// YearSection Routes - READ-ONLY
Route::get('/year-sections', [YearSectionController::class, 'index']);

// ✅ student routes
Route::get('/students', [StudentController::class, 'index']);
Route::post('/students', [StudentController::class, 'store']);
Route::get('/students/{id}', [StudentController::class, 'show']);
Route::put('/students/{id}', [StudentController::class, 'update']);
Route::delete('/students/delete-multiple', [StudentController::class, 'deleteMultiple']);
Route::delete('/students/{id}', [StudentController::class, 'destroy']);
Route::delete('/students', [StudentController::class, 'bulkDestroy']);

/* ================================================================
   =========== Protected routes - require authentication ==========
   ================================================================ */

Route::middleware('auth:sanctum')->group(function () {
    // Dean's User Management - Professors Only
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'updateUser']);
    Route::delete('/users/delete-multiple', [UserController::class, 'deleteMultiple']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::put('/users/{id}/change-password', [UserController::class, 'changePassword']);

    // Super Admin's Account Management - All User Types
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::get('/accounts/{id}', [AccountController::class, 'show']);
    Route::put('/accounts/{id}', [AccountController::class, 'updateUser']);
    Route::delete('/accounts/delete-multiple', [AccountController::class, 'deleteMultiple']);
    Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);

    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me']);
    
    // Profile routes
    Route::get('/profile', [AuthController::class, 'me']); // Get current user profile
    Route::put('/profile', [AuthController::class, 'updateProfile']); // Update current user profile
    Route::put('/change-password', [AuthController::class, 'changePassword']); // Change password for current user

    // Faculty Load routes
    Route::get('/faculty-loads', [FacultyLoadController::class, 'index']);
    Route::post('/faculty-loads', [FacultyLoadController::class, 'store']);
    Route::get('/faculty-loads/{facultyId}', [FacultyLoadController::class, 'getFacultyLoads']);
    Route::put('/faculty-loads/{id}', [FacultyLoadController::class, 'update']);
    Route::delete('/faculty-loads/{id}', [FacultyLoadController::class, 'destroy']);

    // Academic Management routes
     // College routes - WALANG MIDDLEWARE SA CONTROLLER
    Route::apiResource('colleges', CollegeController::class);
    Route::get('colleges/deans/available', [CollegeController::class, 'getAvailableDeans']);
    
    // Program routes - WALANG MIDDLEWARE SA CONTROLLER  
    Route::apiResource('programs', ProgramController::class);
    Route::get('programs/college/{collegeId}', [ProgramController::class, 'getByCollege']);
    Route::get('programs/program-heads/available', [ProgramController::class, 'getAvailableProgramHeads']);
    
    // Subject routes - WALANG MIDDLEWARE SA CONTROLLER
    Route::apiResource('subjects', SubjectController::class);
    Route::get('subjects/type/{type}', [SubjectController::class, 'getByType']);
});
