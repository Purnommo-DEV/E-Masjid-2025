<?php

use Illuminate\Support\Facades\Route;

Route::post('/push/subscribe', [App\Http\Controllers\PushController::class, 'subscribe']);