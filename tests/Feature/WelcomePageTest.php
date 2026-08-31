<?php

use App\Models\MarketingRole;
use App\Models\User;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

test('welcome page presents tdg brands and seo metadata', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('TDG Marketing', false)
        ->assertSee('Tu Dr Group', false)
        ->assertSee('Tu Dr en Casa', false)
        ->assertSee('Tu Dr en Viajes', false)
        ->assertSee('https://www.tudrencasa.com/', false)
        ->assertSee('https://www.tudrenviajes.com/', false)
        ->assertSee('viewport-fit=cover', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('Grupo · Salud · Viajes', false)
        ->assertSee('images/welcome/tdg-casa-bg.jpg', false)
        ->assertSee('images/welcome/tdg-viajes-bg.jpg', false)
        ->assertSee('Marketing para tu red', false)
        ->assertSee('<meta name="description"', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('og:title', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('hreflang="es-VE"', false)
        ->assertSee('Acceso al panel', false)
        ->assertSee('Cerrar menú', false)
        ->assertSee('tdg-home__nav-handle', false)
        ->assertSee('/marketing/login', false)
        ->assertDontSee("Let's get started", false)
        ->assertDontSee('Laracasts', false)
        ->assertDontSee('tdg-home__brand-copy', false)
        ->assertDontSee('fonts.bunny.net', false);

    expect(file_get_contents(resource_path('views/welcome.blade.php')))
        ->toContain('resources/js/welcome.js')
        ->toContain('viewport-fit=cover')
        ->and(file_get_contents(resource_path('css/welcome.css')))
        ->toContain('(hover: hover) and (pointer: fine)')
        ->toContain('safe-area-inset-bottom')
        ->toContain('min-h-12')
        ->toContain('tdg-home__nav-handle')
        ->toContain('translate3d(0, 110%, 0)');
});

test('robots.txt indexes the landing and excludes the panel', function () {
    $response = $this->get(route('robots'));

    $response->assertOk()
        ->assertSee('Allow: /', false)
        ->assertSee('Disallow: /marketing', false)
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false);

    expect($response->headers->get('Content-Type'))->toContain('text/plain');
});

test('sitemap includes the home url', function () {
    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertSee('<loc>'.url('/').'</loc>', false);

    expect($response->headers->get('Content-Type'))->toContain('application/xml');
});

test('users with a marketing role see the panel shortcut on the welcome page', function () {
    $role = MarketingRole::factory()->create();
    $user = User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Ir al panel', false)
        ->assertDontSee('Acceso al panel', false);
});
