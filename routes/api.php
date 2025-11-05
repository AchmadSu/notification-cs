<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Models\NotificationJob;

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

Route::post('/notifications', [NotificationController::class, 'enqueue']);

Route::get('/internal/queue/stats', function () {
    $notification = new NotificationJob;
    $total = $notification->count();
    $pending = $notification->where('status', 'PENDING')->count();
    $processing = $notification->where('status', 'PROCESSING')->count();
    $retry = $notification->where('status', 'RETRY')->count();
    $failed = $notification->where('status', 'FAILED')->count();
    $success = $notification->where('status', 'SUCCESS')->count();

    $avg_success = $notification->where('status', 'SUCCESS')->avg('attempts');

    return [
        'total_jobs' => $total,
        'pending' => $pending,
        'processing' => $processing,
        'retry' => $retry,
        'failed' => $failed,
        'success' => $success,
        'avg_attempts_success' => round($avg_success ?? 0, 3),
    ];
});
