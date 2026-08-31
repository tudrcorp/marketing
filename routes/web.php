<?php

use App\Livewire\CorporateEvents\PublicRegistration;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/robots.txt', function () {
    $sitemap = url('/sitemap.xml');

    $body = <<<TXT
User-agent: *
Allow: /
Disallow: /marketing
Disallow: /dashboard
Disallow: /settings
Disallow: /livewire/

Sitemap: {$sitemap}

TXT;

    return response($body, 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
    ]);
})->name('robots');

Route::get('/sitemap.xml', function () {
    return response()
        ->view('sitemap')
        ->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::get('/inscripcion/{token}', PublicRegistration::class)
    ->middleware('throttle:30,1')
    ->name('corporate-events.register');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
