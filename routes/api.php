<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestScenarioController;
use App\Http\Controllers\BugReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/test-scenarios/generate', [TestScenarioController::class, 'generate']);
Route::post('/test-scenarios/publish', [TestScenarioController::class, 'publish']);
Route::post('/bug-reporter/generate', [BugReportController::class, 'generate']);
