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
Route::get('/students/by-section', [StudentController::class, 'getStudentsBySection']);
Route::get('/students/sections/all', [StudentController::class, 'getAllSections']);



Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // ✅ Dean's User Management - Professors Only
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'updateUser']);
    Route::delete('/users/delete-multiple', [UserController::class, 'deleteMultiple']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::put('/users/{id}/change-password', [UserController::class, 'changePassword']);

    // ✅ Super Admin's Account Management - All User Types
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
    Route::get('/faculty-loads/{facultyId}/sections', [FacultyLoadController::class, 'getFacultySections']);
    Route::get('/faculty-loads/sections/all', [FacultyLoadController::class, 'getAllSections']);
    Route::put('/faculty-loads/{id}', [FacultyLoadController::class, 'update']);
    Route::delete('/faculty-loads/{id}', [FacultyLoadController::class, 'destroy']);

    // ✅ Academic Management routes
    // College routes
    Route::get('/colleges', [CollegeController::class, 'index']);
    Route::post('/colleges', [CollegeController::class, 'store']);
    Route::get('/colleges/{id}', [CollegeController::class, 'show']);
    Route::put('/colleges/{id}', [CollegeController::class, 'update']);
    Route::delete('/colleges/{id}', [CollegeController::class, 'destroy']);
      
    // Program routes
    Route::get('/programs', [ProgramController::class, 'index']);
    Route::post('/programs', [ProgramController::class, 'store']);
    Route::get('/programs/{id}', [ProgramController::class, 'show']);
    Route::put('/programs/{id}', [ProgramController::class, 'update']);
    Route::delete('/programs/{id}', [ProgramController::class, 'destroy']);

    // Subject routes
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::post('/subjects', [SubjectController::class, 'store']);
    Route::get('/subjects/{id}', [SubjectController::class, 'show']);
    Route::put('/subjects/{id}', [SubjectController::class, 'update']);
    Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);
});