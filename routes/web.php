<?php

use App\Filament\Admin\Resources\BusinessManagers\Requests\BusinessManagerCallback;
use App\Http\Controllers\OrderInvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/facebook/oauth/callback', BusinessManagerCallback::class)->name('facebook.oauth.callback');
Route::get('/orders/{order}/invoice', OrderInvoiceController::class)
    ->middleware('signed')
    ->name('orders.invoice');

Route::get('/', function () {
    return view('welcome');
});
