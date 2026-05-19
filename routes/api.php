<?php

use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/notifications/send', [NotificationController::class, 'send']);
    Route::get('/users/{user}/notifications', [NotificationController::class, 'history']);
});
