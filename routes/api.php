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
    Route::put('/user', [AuthController::class, 'update']);
    Route::get('/user', [AuthController::class, 'getProfile']);
});


// super admin routes
Route::middleware(['auth:sanctum', 'superadmin'])->group(function () {
    Route::put('/approve/{userId}', [SuperAdminController::class, 'approve']);
    Route::put('/disapprove/{userId}', [SuperAdminController::class, 'disapprove']);
    Route::put('/changeRole/{userId}', [SuperAdminController::class, 'changeRole']);
    Route::get('/users', [SuperAdminController::class, 'getAllUsers']);
});

// to be deleted, not used in front end
Route::get('billings/pdfs', [BillingController::class, 'listOfBillsPdfs'])->middleware('auth:sanctum');

// Billings API routes authorized to honorar only
Route::middleware(['auth:sanctum', 'honorar'])->group(function () {
    Route::post('/billings/create', [BillingController::class, 'store'])->middleware('honorar');
    Route::post('/billings/preview', [BillingController::class, 'preview'])->middleware('honorar');
    Route::post('/billings/{id}/pdf', [BillingController::class, 'storeBillPdf'])->middleware('honorar');
    Route::get('/billings', [BillingController::class, 'getBillings'])->middleware('honorar');
    Route::get('/billings/{month}', [BillingController::class, 'getBillsByMonth'])->middleware('honorar');
    Route::get('/billings/count/created', [BillingController::class, 'getNumberOfBills']);
});

// Billings API routes authorized to admin only
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/billings/allusers', [BillingController::class, 'getAdminAllBillings'])->middleware('admin');
    Route::get('/billings/allusers/{month}', [BillingController::class, 'getAdminBillsByMonth'])->middleware('admin');
});

// Billings API routes authorized to both honorar and admin
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/billings/download/{id}', [BillingController::class, 'download'])
        ->name('pdf.downloadBillings')
        ->middleware('throttle:60,1');
});

// Works API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/work', [WorkController::class, 'creatework']);
    Route::get('/works', [WorkController::class, 'getAllWorks']);
    Route::put('/works/{id}', [WorkController::class, 'updateWork']);
    Route::post('/works/{id}/complete', [WorkController::class, 'completeWork']);
    Route::post('/works/{id}/pdf', [WorkController::class, 'storePdf']);
    Route::get('/works/allusers', [WorkController::class, 'getAdminAllWorks'])->middleware('admin');
    Route::get('/works/{team}', [WorkController::class, 'getWorksByTeam'])->middleware('admin');
    Route::get('/works/count/created', [WorkController::class, 'GetTotalNumberOfWorks']);
    Route::get('/works/count/incomplete', [WorkController::class, 'GetNumberOfIncompleteWorks']);
    Route::get('/works/download/{id}', [WorkController::class, 'download'])
        ->name('pdf.download')
        ->middleware('throttle:60,1', 'auth:sanctum');
});
