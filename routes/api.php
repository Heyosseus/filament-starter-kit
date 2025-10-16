<?php

use App\Http\Controllers\OperatorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/operator', [OperatorController::class, 'index']);
Route::post('/operator', [OperatorController::class, 'store']);
