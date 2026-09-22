<?php

use App\Http\Controllers\LandingController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/buscar-lotes', [LandingController::class, 'searchLots'])->name('public.lots.search');
Route::get('/buscar-lotes/presupuestos', [LandingController::class, 'priceRanges'])->name('public.lots.price-ranges');
Route::get('/proyectos/{project}/lotes', [LandingController::class, 'projectLots'])->name('public.projects.lots');
Route::get('/proyectos/{project}/availability-summary', [LandingController::class, 'availabilitySummary'])->name('public.projects.availability');
Route::get('/proyectos/{project}/available-lots', [LandingController::class, 'availableProjectLots'])->name('public.projects.available-lots');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
require __DIR__.'/portal.php';

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
