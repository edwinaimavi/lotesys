<?php

use App\Http\Controllers\CustomerPortalAuthController as PortalAuth;
use App\Http\Controllers\CustomerPortalController as Portal;
use App\Http\Middleware\CustomerPortalPrivacy;
use App\Http\Middleware\CustomerPortalSession;
use Illuminate\Support\Facades\Route;

Route::prefix('portal-cliente')->name('portal.')->middleware(CustomerPortalPrivacy::class)->group(function () {
    Route::post('login', [PortalAuth::class, 'login'])->middleware('throttle:5,1')->name('login');
    Route::post('logout', [PortalAuth::class, 'logout'])->name('logout');
    Route::middleware(CustomerPortalSession::class.':initial')->group(function () {
        Route::get('session', [PortalAuth::class, 'session'])->name('session');
        Route::post('change-password', [PortalAuth::class, 'changePassword'])->middleware('throttle:5,1')->name('password');
    });
    Route::middleware(CustomerPortalSession::class)->group(function () {
        Route::get('me', [Portal::class, 'me'])->name('me');
        Route::get('ventas/{sale}', [Portal::class, 'show'])->name('sale');
        Route::get('ventas/{sale}/cronograma', [Portal::class, 'schedules'])->name('schedules');
        Route::get('ventas/{sale}/pagos', [Portal::class, 'payments'])->name('payments');
        Route::get('ventas/{sale}/estado-cuenta', [Portal::class, 'statement'])->name('statement');
        Route::get('comprobantes/{receipt}', [Portal::class, 'receipt'])->name('receipt');
        Route::get('facturas/{invoice}', [Portal::class, 'invoice'])->name('invoice');
    });
});
