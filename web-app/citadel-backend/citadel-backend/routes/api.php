<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\API\AuthController;


// ✅ student routes
Route::get('/students', [StudentController::class, 'index']);
Route::post('/students', [StudentController::class, 'store']);
Route::get('/students/{id}', [StudentController::class, 'show']);
Route::put('/students/{id}', [StudentController::class, 'update']);
Route::delete('/students/delete-multiple', [StudentController::class, 'deleteMultiple']);
Route::delete('/students/{id}', [StudentController::class, 'destroy']);
Route::delete('/students', [StudentController::class, 'bulkDestroy']);



// ✅ user routes
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/delete-multiple', [UserController::class, 'deleteMultiple']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::delete('/users', [UserController::class, 'bulkDestroy']);



Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']); // ✅ Add this line
    Route::get('/me', [AuthController::class, 'me']);

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

});