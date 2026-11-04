<?php

use App\Http\Controllers\IncomingSmsWebhookController;
use App\Http\Controllers\TelegramSmsWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/sms', IncomingSmsWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.sms');

Route::post('/webhooks/telegram/sms', TelegramSmsWebhookController::class)
    ->name('webhooks.telegram.sms');
