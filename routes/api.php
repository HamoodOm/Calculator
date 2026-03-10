<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CertificateApiController;
use App\Models\ApiClient;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| External Platform API Routes (v1)
|--------------------------------------------------------------------------
|
| API endpoints for external platforms like FEP to generate certificates.
| These routes use API key authentication via the api.client middleware.
|
*/

Route::prefix('v1')->group(function () {
    // Public endpoint - API status
    Route::get('/status', function () {
        return response()->json([
            'status' => 'operational',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
            'documentation' => url('/api-docs'),
        ]);
    });

    // Certificate generation endpoint
    Route::middleware(['api.client:' . ApiClient::SCOPE_GENERATE_CERTIFICATE])->group(function () {
        Route::post('/certificates/generate', [CertificateApiController::class, 'generate'])
            ->name('api.v1.certificates.generate');

        Route::get('/certificates/{id}/verify', [CertificateApiController::class, 'verify'])
            ->name('api.v1.certificates.verify');
    });

    // Course listing endpoint
    Route::middleware(['api.client:' . ApiClient::SCOPE_VIEW_COURSES])->group(function () {
        Route::get('/courses', [CertificateApiController::class, 'listCourses'])
            ->name('api.v1.courses.list');
    });

    // Statistics endpoint
    Route::middleware(['api.client:' . ApiClient::SCOPE_VIEW_STATS])->group(function () {
        Route::get('/stats', [CertificateApiController::class, 'stats'])
            ->name('api.v1.stats');
    });
});
