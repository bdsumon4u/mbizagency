<?php

use App\Filament\Admin\Resources\BusinessManagers\Requests\BusinessManagerCallback;
use Illuminate\Support\Facades\Route;

Route::get('/facebook/oauth/callback', BusinessManagerCallback::class)->name('facebook.oauth.callback');

Route::get('/', function () {
    return view('welcome');
});
