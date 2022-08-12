<?php
# PERFORMANCE MANAGEMENT SYSTEM
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

# pms dashboard
Route::get('/pms', function () {
    return Inertia::render('Pms/Index');
})->middleware('auth');


use App\Http\Controllers\Pms\Rsm\RatingScaleMatrixController;
use App\Http\Controllers\Pms\Rsm\SuccessIndicatorController;

# rating scale matrix
Route::get('/pms/rsm', [RatingScaleMatrixController::class, "index"])->middleware('auth');

Route::get('/pms/rsm/{period_id}', [RatingScaleMatrixController::class, "show"]);
Route::post('/pms/rsm/{period_id}', [RatingScaleMatrixController::class, "create"]);
Route::patch('/pms/rsm/{period_id}', [RatingScaleMatrixController::class, "update"]);
Route::delete('/pms/rsm/{period_id}/{id}', [RatingScaleMatrixController::class, "destroy"]);

# success indicator
Route::get('/pms/rsm/{period_id}/mfo/{id}/si', [SuccessIndicatorController::class, "index"]);
