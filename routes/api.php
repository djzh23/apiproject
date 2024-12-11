<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SuperAdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public API routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/updateProfile', [AuthController::class, 'update']);
    Route::get('/user/profile', [AuthController::class, 'getProfile']);
});

//Route::put('approve/{userId}', SuperAdminController::class.'@approve')->middleware('superadmin', 'auth:sanctum');
//Route::put('disapprove/{userId}', SuperAdminController::class.'@disapprove')->middleware('superadmin', 'auth:sanctum');

// super admin routes
Route::middleware(['auth:sanctum', 'superadmin'])->group(function () {
    Route::put('/approve/{userId}', [SuperAdminController::class, 'approve']);
    Route::put('/disapprove/{userId}', [SuperAdminController::class, 'disapprove']);
    Route::get('/getUsers', [SuperAdminController::class, 'getAllUsers']);

});
