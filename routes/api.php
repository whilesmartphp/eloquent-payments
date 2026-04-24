<?php

use Illuminate\Support\Facades\Route;
use Whilesmart\Payments\Http\Controllers\PaymentController;

Route::apiResource('payments', PaymentController::class);
