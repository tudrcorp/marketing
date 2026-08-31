<?php

use App\Livewire\CorporateEvents\PublicRegistration;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/inscripcion/{token}', PublicRegistration::class)
    ->middleware('throttle:30,1')
    ->name('corporate-events.register');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
