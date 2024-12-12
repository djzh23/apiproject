<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\WorkController;
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


// super admin routes
Route::middleware(['auth:sanctum', 'superadmin'])->group(function () {
    Route::put('/approve/{userId}', [SuperAdminController::class, 'approve']);
    Route::put('/disapprove/{userId}', [SuperAdminController::class, 'disapprove']);
    Route::get('/getUsers', [SuperAdminController::class, 'getAllUsers']);
});

Route::get('billings/billings-pdfs', [BillingController::class, 'listOfBillsPdfs'])->middleware('auth:sanctum');
//// Billings API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/billings/createbill', [BillingController::class, 'store']);
    Route::post('/billings/preview', [BillingController::class, 'preview']);
    Route::post('/billings/{id}/pdf', [BillingController::class, 'storeBillPdf']);
    Route::get('/billings', [BillingController::class, 'getAllUserBillings']);
    Route::get('/billings/{month}', [BillingController::class, 'getBillsByMonth']);
    Route::get('/billings/download/{filename}', [BillingController::class, 'download'])
        ->name('pdf.download')
        ->middleware('throttle:60,1', 'auth:sanctum');
});

// Works API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/works/create', [WorkController::class, 'creatework']);
    Route::get('/works', [WorkController::class, 'getAllWorks']);
    Route::put('/works/{id}', [WorkController::class, 'updateWork']);
    Route::post('/works/{id}/pdf', [WorkController::class, 'storePdf']);
    Route::get('/works/allusersworks', [WorkController::class, 'getAdminAllWorks'])->middleware('admin');
    Route::get('/works/{team}', [WorkController::class, 'getWorksByTeam'])->middleware('admin');
});

