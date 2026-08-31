@php
    $siteName = 'TDG Marketing';
    $groupName = 'Tu Dr Group';
    $title = 'TDG Marketing | Tu Dr Group — Tu Dr en Casa y Tu Dr en Viajes';
    $description = 'TDG Marketing impulsa Tu Dr en Casa y Tu Dr en Viajes: asistencia médica a domicilio en Venezuela, seguro de viaje global y operaciones de marketing para empresas del rubro.';
    $canonical = url('/');
    $logoUrl = asset('images/logos/logoNewTDG.png');
    $casaUrl = 'https://www.tudrencasa.com/';
    $viajesUrl = 'https://www.tudrenviajes.com/';
    $panel = \Filament\Facades\Filament::getPanel('marketing');
    $loginUrl = $panel->getLoginUrl();
    $panelUrl = $panel->getUrl();
    $canAccessPanel = auth()->user()?->canAccessPanel($panel) === true;
    $accessUrl = $canAccessPanel ? $panelUrl : $loginUrl;
    $accessLabel = $canAccessPanel ? 'Ir al panel' : 'Acceso al panel';
    $locale = 'es-VE';
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $canonical.'#organization',
                'name' => $groupName,
                'alternateName' => ['TDG', $siteName, 'TuDrGroup'],
                'url' => $canonical,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logoUrl,
                ],
                'description' => $description,
                'areaServed' => [
                    '@type' => 'Country',
                    'name' => 'Venezuela',
                ],
                'sameAs' => [$casaUrl, $viajesUrl],
                'subOrganization' => [
                    [
                        '@type' => 'MedicalOrganization',
                        'name' => 'Tu Dr en Casa',
                        'url' => $casaUrl,
                        'description' => 'Asistencia médica a domicilio, telemedicina y planes de salud en Venezuela.',
                    ],
                    [
                        '@type' => 'InsuranceAgency',
                        'name' => 'Tu Dr en Viajes',
                        'url' => $viajesUrl,
                        'description' => 'Seguro médico de viaje con cobertura global, planes Schengen y protección 24/7.',
                    ],
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $canonical.'#website',
                'url' => $canonical,
                'name' => $siteName,
                'inLanguage' => $locale,
                'publisher' => ['@id' => $canonical.'#organization'],
                'description' => $description,
            ],
            [
                '@type' => 'WebPage',
                '@id' => $canonical.'#webpage',
                'url' => $canonical,
                'name' => $title,
                'isPartOf' => ['@id' => $canonical.'#website'],
                'about' => ['@id' => $canonical.'#organization'],
                'inLanguage' => $locale,
                'description' => $description,
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="es-VE">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">
        <meta name="author" content="Tu Dr Group">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="googlebot" content="index, follow">
        <meta name="theme-color" content="#2d3250">
        <meta name="color-scheme" content="light">
        <meta name="format-detection" content="telephone=no">
        <meta name="geo.region" content="VE">
        <meta name="geo.placename" content="Venezuela">
        <link rel="canonical" href="{{ $canonical }}">
        <link rel="alternate" hreflang="es" href="{{ $canonical }}">
        <link rel="alternate" hreflang="es-VE" href="{{ $canonical }}">
        <link rel="alternate" hreflang="x-default" href="{{ $canonical }}">
        <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:locale" content="es_VE">
        <meta property="og:locale:alternate" content="es_ES">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:image" content="{{ $logoUrl }}">
        <meta property="og:image:alt" content="Logotipo de Tu Dr Group">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $title }}">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ $logoUrl }}">
        <meta name="twitter:image:alt" content="Logotipo de Tu Dr Group">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preload" as="image" href="{{ asset('images/welcome/tdg-casa-bg.jpg') }}" fetchpriority="high">

        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

        @vite(['resources/css/welcome.css', 'resources/js/welcome.js'])
    </head>
    <body class="tdg-home-body">
        <a class="tdg-home__skip" href="#contenido">Saltar al contenido</a>

        <div class="tdg-home">
            <div class="tdg-home__orb tdg-home__orb--a" aria-hidden="true"></div>
            <div class="tdg-home__orb tdg-home__orb--b" aria-hidden="true"></div>
            <div class="tdg-home__orb tdg-home__orb--c" aria-hidden="true"></div>

            <header class="tdg-home__header">
                <input class="tdg-home__nav-toggle" type="checkbox" id="tdg-nav" aria-controls="tdg-menu">
                <div class="tdg-home__header-inner">
                    <a class="tdg-home__brand" href="{{ $canonical }}" aria-label="Inicio Tu Dr Group">
                        <img
                            class="tdg-home__logo"
                            src="{{ $logoUrl }}"
                            width="180"
                            height="44"
                            alt="Tu Dr Group"
                        >
                    </a>

                    <label class="tdg-home__nav-button" for="tdg-nav" aria-expanded="false" aria-controls="tdg-menu">
                        <span class="sr-only">Menú</span>
                        <span class="tdg-home__nav-bar" aria-hidden="true"></span>
                        <span class="tdg-home__nav-bar" aria-hidden="true"></span>
                    </label>

                    <nav id="tdg-menu" class="tdg-home__nav" aria-label="Principal">
                        <div class="tdg-home__nav-handle" aria-hidden="true"></div>
                        <div class="tdg-home__nav-sheet-head">
                            <p class="tdg-home__nav-sheet-title">Menú</p>
                            <label class="tdg-home__nav-close" for="tdg-nav">
                                <span class="sr-only">Cerrar menú</span>
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                                    <path d="M2 2l10 10M12 2 2 12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </label>
                        </div>
                        <a class="tdg-home__nav-link" href="#marcas">Marcas</a>
                        <a class="tdg-home__nav-link" href="#solucion">Solución</a>
                        <a class="tdg-home__nav-link" href="#capacidades">Capacidades</a>
                        <a class="tdg-home__cta tdg-home__cta--primary tdg-home__nav-cta" href="{{ $accessUrl }}">{{ $accessLabel }}</a>
                    </nav>
                </div>
                <label class="tdg-home__nav-scrim" for="tdg-nav"><span class="sr-only">Cerrar menú</span></label>
            </header>

            <main id="contenido" class="tdg-home__main">
                <section class="tdg-home__section tdg-home__hero" aria-labelledby="hero-title">
                    <div class="tdg-home__stage">
                        <p class="tdg-home__eyebrow">Grupo · Salud · Viajes</p>
                        <h1 id="hero-title" class="tdg-home__title">
                            El marketing de <span class="tdg-home__gradient">Tu Dr Group</span> para marcas que cuidan personas.
                        </h1>
                        <p class="tdg-home__lead">
                            TDG Marketing orquesta <strong>Tu Dr en Casa</strong> y <strong>Tu Dr en Viajes</strong>:
                            asistencia médica con empatía en Venezuela y protección global para quien viaja.
                            Si operas en salud, asistencia o seguros, también somos tu capa de marketing.
                        </p>
                        <div class="tdg-home__actions">
                            <a class="tdg-home__cta tdg-home__cta--primary" href="#marcas">Conocer las marcas</a>
                            <a class="tdg-home__cta tdg-home__cta--ghost" href="#solucion">Marketing para tu red</a>
                        </div>
                    </div>

                    <div class="tdg-home__stack" aria-label="Marcas del grupo">
                        <a class="tdg-home__brand-card tdg-home__brand-card--casa" href="{{ $casaUrl }}" rel="noopener noreferrer" target="_blank">
                            <img
                                class="tdg-home__brand-card-bg"
                                src="{{ asset('images/welcome/tdg-casa-bg.jpg') }}"
                                alt=""
                                width="1400"
                                height="933"
                                decoding="async"
                                sizes="(max-width: 640px) 100vw, 420px"
                                fetchpriority="high"
                            >
                            <p class="tdg-home__brand-kicker">Marca de salud</p>
                            <h2>Tu Dr en Casa</h2>
                            <p>Asistencia médica a domicilio, telemedicina y planes de gastos menores que llegan a cualquier rincón de Venezuela.</p>
                            <div class="tdg-home__brand-meta">
                                <span class="tdg-home__chip">24/7</span>
                                <span class="tdg-home__chip">Domicilio</span>
                                <span class="tdg-home__chip">Inicial</span>
                                <span class="tdg-home__chip">Ideal</span>
                                <span class="tdg-home__chip">Especial</span>
                            </div>
                            <span class="tdg-home__link-out">Visitar tudrencasa.com →</span>
                        </a>
                        <a class="tdg-home__brand-card tdg-home__brand-card--viajes" href="{{ $viajesUrl }}" rel="noopener noreferrer" target="_blank">
                            <img
                                class="tdg-home__brand-card-bg"
                                src="{{ asset('images/welcome/tdg-viajes-bg.jpg') }}"
                                alt=""
                                width="1400"
                                height="933"
                                decoding="async"
                                sizes="(max-width: 640px) 100vw, 420px"
                                loading="lazy"
                            >
                            <p class="tdg-home__brand-kicker">Marca de viajes</p>
                            <h2>Tu Dr en Viajes</h2>
                            <p>Seguro médico de viaje con cobertura en más de 190 países, asistencia 24/7 y upgrades VIP para cada itinerario.</p>
                            <div class="tdg-home__brand-meta">
                                <span class="tdg-home__chip">Mundial</span>
                                <span class="tdg-home__chip">Europa Low Cost</span>
                                <span class="tdg-home__chip">Plan Local VE</span>
                            </div>
                            <span class="tdg-home__link-out">Visitar tudrenviajes.com →</span>
                        </a>
                    </div>
                </section>

                <div class="tdg-home__ticker" aria-hidden="true">
                    <div class="tdg-home__ticker-track">
                        @foreach ([1, 2] as $copy)
                            <span>Tu Dr en Casa</span>
                            <span>Asistencia médica a domicilio</span>
                            <span>Tu Dr en Viajes</span>
                            <span>Seguro médico global</span>
                            <span>TDG Marketing</span>
                            <span>Agentes · Agencias · Aliados</span>
                        @endforeach
                    </div>
                </div>

                <section id="marcas" class="tdg-home__section tdg-home__reveal" aria-labelledby="marcas-title">
                    <div class="tdg-home__section-head">
                        <p class="tdg-home__eyebrow">El grupo</p>
                        <h2 id="marcas-title">Dos marcas. Una misma promesa de cuidado.</h2>
                        <p>
                            Tu Dr Group une salud en casa y protección en movimiento.
                            TDG Marketing da voz, ritmo y alcance a ambas frente a familias, viajeros y redes comerciales.
                        </p>
                    </div>

                    <div class="tdg-home__grid-2">
                        <article class="tdg-home__card">
                            <div class="tdg-home__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <h3>Salud que llega a casa</h3>
                            <p>
                                En <a href="{{ $casaUrl }}" rel="noopener noreferrer" target="_blank">Tu Dr en Casa</a>
                                la cotización es el primer gesto de empatía: atención telefónica, tratamiento a domicilio,
                                laboratorios, imagenología y seguimiento. Los planes Inicial, Ideal y Especial cubren
                                desde monitoreo evolutivo hasta especialistas, ambulancia y urgencias menores.
                            </p>
                        </article>
                        <article class="tdg-home__card">
                            <div class="tdg-home__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 17h18M5 17 8 7h8l3 10M8 11h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <h3>Tranquilidad en cada viaje</h3>
                            <p>
                                <a href="{{ $viajesUrl }}" rel="noopener noreferrer" target="_blank">Tu Dr en Viajes</a>
                                protege itinerarios con planes mundial, Europa Low Cost y local Venezuela, más upgrades VIP:
                                materna, sport, objetos personales, mascotas, preexistencias y salas VIP.
                                Quince años acompañando viajeros, con asistencia en su idioma.
                            </p>
                        </article>
                    </div>
                </section>

                <section id="solucion" class="tdg-home__section tdg-home__reveal" aria-labelledby="solucion-title">
                    <div class="tdg-home__solution">
                        <p class="tdg-home__eyebrow">Para empresas del rubro</p>
                        <h2 id="solucion-title">No somos una agencia que aprende tu industria. Somos el marketing de esta industria.</h2>
                        <p class="tdg-home__lead">
                            Agencias master, agencias generales, agentes de corretaje y de viajes, afiliados y proveedores
                            ya viven en nuestras audiencias. TDG Marketing convierte esa red en campañas medibles:
                            el mismo músculo que mueve a Tu Dr en Casa y Tu Dr en Viajes, al servicio de aliados que hablan el mismo idioma.
                        </p>
                        <ul class="tdg-home__solution-list">
                            <li>Campañas a redes de agentes y agencias, no a un público genérico.</li>
                            <li>Mensajes de cumpleaños, masivos y eventos corporativos con inscripción pública.</li>
                            <li>Calendario editorial y cuentas oficiales, con flujo de aprobación.</li>
                            <li>WhatsApp, correo y SMS orquestados, con historial y remediación para el analista.</li>
                        </ul>
                        <div class="tdg-home__stats">
                            <p class="tdg-home__stat"><strong>24/7</strong><span>Asistencia de las marcas</span></p>
                            <p class="tdg-home__stat"><strong>190+</strong><span>Países de cobertura en viajes</span></p>
                            <p class="tdg-home__stat"><strong>VE + mundo</strong><span>Hogar y desplazamiento</span></p>
                        </div>
                        <div class="tdg-home__actions">
                            <a class="tdg-home__cta tdg-home__cta--primary" href="{{ $casaUrl }}" rel="noopener noreferrer" target="_blank">Sé aliado de Tu Dr en Casa</a>
                            <a class="tdg-home__cta tdg-home__cta--light" href="{{ $viajesUrl }}" rel="noopener noreferrer" target="_blank">Cotizar Tu Dr en Viajes</a>
                        </div>
                    </div>
                </section>

                <section id="capacidades" class="tdg-home__section tdg-home__reveal" aria-labelledby="capacidades-title">
                    <div class="tdg-home__section-head">
                        <p class="tdg-home__eyebrow">Operaciones</p>
                        <h2 id="capacidades-title">La plataforma con la que el grupo habla a su red</h2>
                        <p>
                            Un solo panel para audiencias TDG, envíos y contenidos.
                            Diseñado para analistas de marketing que no pueden permitirse perder un lote ni improvisar el tono de marca.
                        </p>
                    </div>

                    <div class="tdg-home__grid-3">
                        <article class="tdg-home__card">
                            <div class="tdg-home__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M5 19c.8-3.2 3.5-5 7-5s6.2 1.8 7 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <h3>Audiencias vivas</h3>
                            <p>Agentes y agencias de corretaje y de viajes, afiliados, proveedores y colaboradores: la red comercial del grupo, lista para segmentar.</p>
                        </article>
                        <article class="tdg-home__card">
                            <div class="tdg-home__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                    <rect x="4" y="6" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="m4 8 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <h3>Correo y WhatsApp</h3>
                            <p>Cumpleaños, campañas masivas e invitaciones con el tono TDG. Lotes controlados, adjuntos y visibilidad de cada envío.</p>
                        </article>
                        <article class="tdg-home__card">
                            <div class="tdg-home__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                    <rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <h3>Eventos y editorial</h3>
                            <p>Calendario corporativo, inscripción pública y publicaciones en redes con aprobación. La marca se ve igual en cada canal.</p>
                        </article>
                    </div>
                </section>
            </main>

            <footer class="tdg-home__footer">
                <div class="tdg-home__footer-glow" aria-hidden="true"></div>
                <div class="tdg-home__footer-inner">
                    <div class="tdg-home__footer-brand">
                        <img
                            class="tdg-home__logo tdg-home__logo--on-dark"
                            src="{{ asset('images/logos/logoTDG.png') }}"
                            width="168"
                            height="42"
                            alt="Tu Dr Group"
                        >
                        <p class="tdg-home__footer-lead">
                            El grupo que humaniza la salud en casa y protege cada viaje.
                            Marketing hecho para redes de asistencia, seguros y aliados comerciales.
                        </p>
                    </div>

                    <div class="tdg-home__footer-col">
                        <h3>Marcas</h3>
                        <a class="tdg-home__footer-brand-link" href="{{ $casaUrl }}" rel="noopener noreferrer" target="_blank">
                            <span class="tdg-home__footer-dot tdg-home__footer-dot--casa" aria-hidden="true"></span>
                            <span>
                                <strong>Tu Dr en Casa</strong>
                                <span>Asistencia médica a domicilio</span>
                            </span>
                        </a>
                        <a class="tdg-home__footer-brand-link" href="{{ $viajesUrl }}" rel="noopener noreferrer" target="_blank">
                            <span class="tdg-home__footer-dot tdg-home__footer-dot--viajes" aria-hidden="true"></span>
                            <span>
                                <strong>Tu Dr en Viajes</strong>
                                <span>Seguro médico global</span>
                            </span>
                        </a>
                    </div>

                    <div class="tdg-home__footer-panel">
                        <p class="tdg-home__footer-panel-kicker">Panel interno</p>
                        <p class="tdg-home__footer-panel-copy">Campañas, audiencias y envíos del grupo, en un solo lugar.</p>
                        <a class="tdg-home__cta tdg-home__cta--primary" href="{{ $accessUrl }}">{{ $accessLabel }}</a>
                    </div>
                </div>
                <div class="tdg-home__legal">
                    <p>© {{ now()->year }} {{ $groupName }}. Todos los derechos reservados.</p>
                    <p>Venezuela · Salud en casa · Protección en viaje</p>
                </div>
            </footer>
        </div>
    </body>
</html>
