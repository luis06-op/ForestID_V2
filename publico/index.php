<?php
/**
 * ForestID — Portada pública (rediseño editorial)
 * Emincut S.A.S. | El Bagre, Antioquia
 *
 * Esta página no consulta la base de datos ni muestra datos reales de árboles.
 * Presenta el sistema y lleva al acceso. Si hay sesión de administrador u
 * operario, redirige al panel correspondiente.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Solo los paneles internos se redirigen. Un UsuarioPublico (o un rol sin
// panel) debe ver esta portada; redirigirlo a sí mismo crearía un bucle.
if (esta_autenticado() && (es_admin() || es_operario())) {
    redirigir_segun_rol();
    exit;
}

$titulo_pagina = 'ForestID — Cada árbol sembrado queda registrado | Emincut';
$meta_descripcion = 'ForestID reúne el registro, la ubicación y el seguimiento de los árboles sembrados por Emincut después de la minería en El Bagre, Antioquia.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo_pagina) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_descripcion) ?>">
    <meta name="author" content="Emincut S.A.S.">
    <meta name="theme-color" content="#2C5C3D">

    <!-- Open Graph -->
    <meta property="og:title" content="ForestID — Cada árbol sembrado queda registrado">
    <meta property="og:description" content="Registro, ubicación y seguimiento del inventario forestal de Emincut en El Bagre, Antioquia.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_CO">

    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%232C5C3D'/%3E%3Ccircle cx='42' cy='20' r='11' fill='%23F5CC43'/%3E%3Cpath d='M6 46c12-10 28-12 52-3v9H6z' fill='%23467F36'/%3E%3Cpath d='M6 54c16-4 34-3 52 2v2H6z' fill='%23FBFAF5'/%3E%3C/svg%3E">

    <!-- Tipografía editorial: Bricolage Grotesque + Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/publico-inicio.css?v=20261107">
    <script>document.documentElement.classList.add('fid-js');</script>
</head>
<body class="fid-inicio">
<a class="fid-skip" href="#contenido">Saltar al contenido</a>

<!-- ════════════════════════════════════════════════════
     NAVEGACIÓN
     ════════════════════════════════════════════════════ -->
<header class="fid-nav" id="fid-nav">
    <div class="fid-shell fid-nav__inner">
        <a class="fid-brand" href="<?= BASE_URL ?>publico/index.php" aria-label="ForestID — Inicio">
            <span class="fid-brand__mark" aria-hidden="true">
                <svg viewBox="0 0 48 48">
                    <circle cx="33" cy="13" r="8" fill="#F5CC43"/>
                    <path d="M3 33c9-7 20-9 42-2v7H3z" fill="#467F36"/>
                    <path d="M3 39c12-3 27-2 42 2v4H3z" fill="#2C5C3D"/>
                </svg>
            </span>
            <span class="fid-brand__text">
                <strong>Forest<span>ID</span></strong>
                <small>Emincut · El Bagre</small>
            </span>
        </a>

        <nav class="fid-nav__links" aria-label="Secciones de la portada">
            <a href="#sistema">El sistema</a>
            <a href="#proceso">El proceso</a>
            <a href="#territorio">Territorio</a>
            <a href="#equipo">Equipo</a>
        </nav>

        <div class="fid-nav__actions">
            <a class="fid-btn fid-btn--small fid-btn--forest" href="<?= BASE_URL ?>auth/login.php">
                Ingresar
                <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>

        <button class="fid-nav__toggle" id="fid-menu-btn" type="button" aria-expanded="false" aria-controls="fid-menu" aria-label="Abrir menú de navegación">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<div class="fid-menu" id="fid-menu" hidden>
    <div class="fid-shell fid-menu__inner">
        <a href="#sistema">El sistema</a>
        <a href="#proceso">El proceso</a>
        <a href="#territorio">Territorio</a>
        <a href="#equipo">Equipo</a>
        <a class="fid-btn fid-btn--forest" href="<?= BASE_URL ?>auth/login.php">
            Ingresar al sistema
            <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    </div>
</div>

<main id="contenido">

    <!-- ════════════════════════════════════════════════════
         HERO
         ════════════════════════════════════════════════════ -->
    <section class="fid-hero" aria-labelledby="fid-hero-title">
        <div class="fid-shell fid-hero__grid">
            <div class="fid-hero__copy">
                <p class="fid-kicker fid-reveal">Restauración forestal · El Bagre, Antioquia</p>

                <h1 class="fid-hero__title fid-reveal" id="fid-hero-title">
                    Sembrar es el comienzo.
                    <span class="fid-mark">Registrar</span> es cómo lo demostramos.
                </h1>

                <p class="fid-hero__lead fid-reveal">
                    ForestID reúne cada árbol sembrado por Emincut después de la minería:
                    especie, coordenada, estado y seguimiento. La restauración deja de ser
                    una cifra y se vuelve verificable.
                </p>

                <div class="fid-hero__actions fid-reveal">
                    <a class="fid-btn fid-btn--forest" href="<?= BASE_URL ?>auth/login.php">
                        Ingresar al sistema
                        <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <a class="fid-link" href="#sistema">
                        Conocer el sistema
                        <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M8 2v11M3.5 9 8 13.5 12.5 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>

                <p class="fid-hero__note fid-reveal">
                    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                        <path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                    </svg>
                    Acceso exclusivo para personal autorizado de Emincut.
                </p>
            </div>

            <div class="fid-hero__art" aria-hidden="true">
                <span class="fid-hero__sun"></span>

                <svg class="fid-contours" viewBox="0 0 560 560" fill="none">
                    <g stroke="currentColor" stroke-width="1">
                        <path d="M280 48c126 0 222 76 222 182 0 112-94 190-222 190S58 342 58 230 154 48 280 48Z"/>
                        <path d="M280 83c105 0 187 64 187 151s-82 158-187 158S93 334 93 234 175 83 280 83Z"/>
                        <path d="M280 118c85 0 151 52 151 121s-66 126-151 126-151-57-151-126 66-121 151-121Z"/>
                        <path d="M280 154c64 0 113 39 113 90s-49 94-113 94-113-43-113-94 49-90 113-90Z"/>
                        <path d="M280 190c43 0 76 26 76 60s-33 62-76 62-76-28-76-62 33-60 76-60Z"/>
                        <path d="M280 226c22 0 38 13 38 30s-16 30-38 30-38-13-38-30 16-30 38-30Z"/>
                    </g>
                    <path d="M67 380c38-24 62-7 91-29s60-44 104-28 57 12 90-9 73-16 112 10" stroke="#F49B14" stroke-width="1.4" opacity=".65"/>
                    <path d="M37 430c46-20 79-4 111-25s68-38 111-18 70 5 108-15 74-7 120 19" stroke="currentColor" stroke-width="1"/>
                </svg>

                <figure class="fid-specimen">
                    <div class="fid-specimen__top">
                        <span>ForestID · Ficha de campo</span>
                        <span>N.º 001</span>
                    </div>

                    <div class="fid-specimen__art">
                        <svg class="fid-specimen__rings" viewBox="0 0 260 190" fill="none">
                            <ellipse cx="132" cy="94" rx="90" ry="82" stroke="#2C5C3D" stroke-width="1" opacity=".45"/>
                            <ellipse cx="129" cy="96" rx="81" ry="74" stroke="#2C5C3D" stroke-width="1" opacity=".55"/>
                            <ellipse cx="131" cy="95" rx="72" ry="66" stroke="#2C5C3D" stroke-width="1" opacity=".65"/>
                            <ellipse cx="128" cy="97" rx="62" ry="57" stroke="#2C5C3D" stroke-width="1" opacity=".7"/>
                            <ellipse cx="130" cy="96" rx="52" ry="48" stroke="#F49B14" stroke-width="1.1" opacity=".85"/>
                            <ellipse cx="129" cy="96" rx="43" ry="40" stroke="#2C5C3D" stroke-width="1" opacity=".7"/>
                            <ellipse cx="130" cy="95" rx="34" ry="31" stroke="#2C5C3D" stroke-width="1" opacity=".75"/>
                            <ellipse cx="130" cy="96" rx="24" ry="22" stroke="#2C5C3D" stroke-width="1" opacity=".8"/>
                            <ellipse cx="130" cy="95" rx="14" ry="13" stroke="#2C5C3D" stroke-width="1" opacity=".9"/>
                            <circle cx="130" cy="96" r="5" fill="#2C5C3D"/>
                            <path d="M130 96 207 48M130 96 66 151M130 96V18" stroke="#2C5C3D" stroke-width=".8" opacity=".5"/>
                            <g transform="translate(191 137) rotate(-12)" stroke="#467F36" stroke-width="1.2">
                                <path d="M0 0c13-10 28-10 39 1-13 10-28 10-39-1Z"/>
                                <path d="M3 0h33"/>
                            </g>
                        </svg>
                        <span class="fid-specimen__stamp">Trazable</span>
                    </div>

                    <figcaption class="fid-specimen__body">
                        <div class="fid-specimen__species">
                            <span>Especie</span>
                            <strong>Ceiba pentandra</strong>
                        </div>
                        <dl class="fid-specimen__data">
                            <div>
                                <dt>Zona</dt>
                                <dd>Z-04 · El Bagre</dd>
                            </div>
                            <div>
                                <dt>Estado</dt>
                                <dd>En seguimiento</dd>
                            </div>
                        </dl>
                        <div class="fid-specimen__qr">
                            <svg viewBox="0 0 48 48">
                                <rect width="48" height="48" fill="#FFFFFF"/>
                                <g fill="#2C5C3D">
                                    <rect x="2" y="2" width="14" height="14"/>
                                    <rect x="5" y="5" width="8" height="8" fill="#FFFFFF"/>
                                    <rect x="7" y="7" width="4" height="4"/>
                                    <rect x="32" y="2" width="14" height="14"/>
                                    <rect x="35" y="5" width="8" height="8" fill="#FFFFFF"/>
                                    <rect x="37" y="7" width="4" height="4"/>
                                    <rect x="2" y="32" width="14" height="14"/>
                                    <rect x="5" y="35" width="8" height="8" fill="#FFFFFF"/>
                                    <rect x="7" y="37" width="4" height="4"/>
                                    <rect x="20" y="2" width="3" height="3"/>
                                    <rect x="25" y="5" width="4" height="3"/>
                                    <rect x="20" y="10" width="3" height="5"/>
                                    <rect x="24" y="12" width="5" height="3"/>
                                    <rect x="20" y="19" width="4" height="4"/>
                                    <rect x="27" y="20" width="3" height="3"/>
                                    <rect x="33" y="19" width="4" height="4"/>
                                    <rect x="39" y="21" width="3" height="3"/>
                                    <rect x="2" y="20" width="4" height="4"/>
                                    <rect x="9" y="21" width="3" height="3"/>
                                    <rect x="14" y="19" width="4" height="5"/>
                                    <rect x="19" y="27" width="5" height="3"/>
                                    <rect x="27" y="26" width="3" height="4"/>
                                    <rect x="33" y="27" width="5" height="3"/>
                                    <rect x="40" y="26" width="3" height="4"/>
                                    <rect x="20" y="33" width="4" height="4"/>
                                    <rect x="26" y="39" width="3" height="4"/>
                                    <rect x="33" y="33" width="4" height="3"/>
                                    <rect x="40" y="34" width="3" height="4"/>
                                    <rect x="36" y="40" width="5" height="3"/>
                                </g>
                            </svg>
                            <span>Placa física<br>Escanear para abrir la ficha</span>
                        </div>
                    </figcaption>
                </figure>

                <div class="fid-hero__meta">
                    <span class="fid-hero__tag">Muestra ilustrativa</span>
                    <span class="fid-hero__coords">7°59′ N · 74°52′ O</span>
                </div>
            </div>
        </div>

        <div class="fid-shell">
            <dl class="fid-stats fid-reveal">
                <div class="fid-stat">
                    <dt>1.250<span>+</span></dt>
                    <dd>Árboles registrados</dd>
                </div>
                <div class="fid-stat">
                    <dt>18</dt>
                    <dd>Hectáreas en restauración</dd>
                </div>
                <div class="fid-stat">
                    <dt>42</dt>
                    <dd>Especies catalogadas</dd>
                </div>
                <div class="fid-stat">
                    <dt>100<span>%</span></dt>
                    <dd>Trazabilidad por QR</dd>
                </div>
            </dl>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════════
         EL SISTEMA
         ════════════════════════════════════════════════════ -->
    <section class="fid-section fid-section--paper" id="sistema" aria-labelledby="fid-sistema-title">
        <div class="fid-shell">
            <header class="fid-section__head">
                <div class="fid-reveal">
                    <p class="fid-kicker">El sistema</p>
                    <h2 id="fid-sistema-title">Lo que no se registra, no se puede cuidar.</h2>
                </div>
                <p class="fid-section__lead fid-reveal">
                    Cada individuo tiene una ficha, una placa y un historial. Así el trabajo
                    de campo se convierte en información que se puede revisar, cruzar y presentar.
                </p>
            </header>

            <div class="fid-system">
                <aside class="fid-note fid-reveal" aria-label="Nota de campo">
                    <span class="fid-note__label">Nota de campo</span>
                    <p>En campo se registra la especie, la ubicación y las medidas. En oficina se revisan conteos, hectáreas y estado de salud. La misma información, dos miradas.</p>
                    <span class="fid-note__sign">Equipo ForestID</span>
                </aside>

                <ol class="fid-features">
                    <li class="fid-feature fid-reveal">
                        <span class="fid-feature__num">01</span>
                        <div>
                            <h3>Ficha por individuo</h3>
                            <p>Especie, fecha de siembra, altura, diámetro y fotografías. Cada árbol tiene su propio registro y su placa con código QR.</p>
                        </div>
                    </li>
                    <li class="fid-feature fid-reveal">
                        <span class="fid-feature__num">02</span>
                        <div>
                            <h3>Seguimiento de campo</h3>
                            <p>Riegos, podas, fertilizaciones y estado fitosanitario quedan asociados a cada visita del operario.</p>
                        </div>
                    </li>
                    <li class="fid-feature fid-reveal">
                        <span class="fid-feature__num">03</span>
                        <div>
                            <h3>Zonas y coordenadas</h3>
                            <p>Los individuos se agrupan por área de siembra y quedan georreferenciados para saber qué se plantó y dónde.</p>
                        </div>
                    </li>
                    <li class="fid-feature fid-reveal">
                        <span class="fid-feature__num">04</span>
                        <div>
                            <h3>Reportes de restauración</h3>
                            <p>Conteos, hectáreas y estado de salud organizados para revisar el avance y presentarlo ante la autoridad ambiental.</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════════
         EL PROCESO
         ════════════════════════════════════════════════════ -->
    <section class="fid-section fid-section--forest" id="proceso" aria-labelledby="fid-proceso-title">
        <div class="fid-shell">
            <header class="fid-section__head">
                <div class="fid-reveal">
                    <p class="fid-kicker fid-kicker--light">El proceso</p>
                    <h2 id="fid-proceso-title">Del vivero al reporte, sin perder el hilo.</h2>
                </div>
                <p class="fid-section__lead fid-reveal">
                    Tres momentos sostienen la trazabilidad: lo que se registra al sembrar,
                    lo que se marca en campo y lo que se verifica después.
                </p>
            </header>

            <ol class="fid-steps">
                <li class="fid-step fid-reveal">
                    <span class="fid-step__num">01</span>
                    <h3>Siembra y registro</h3>
                    <p>El operario registra la especie, la ubicación y las medidas iniciales directamente desde el campo.</p>
                </li>
                <li class="fid-step fid-reveal">
                    <span class="fid-step__num">02</span>
                    <h3>Placa y código QR</h3>
                    <p>Cada individuo recibe una placa física con un código que abre su ficha pública desde cualquier teléfono.</p>
                </li>
                <li class="fid-step fid-reveal">
                    <span class="fid-step__num">03</span>
                    <h3>Verificación y reporte</h3>
                    <p>Administración revisa el avance, el estado de los árboles y genera los reportes de restauración.</p>
                </li>
            </ol>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════════
         TERRITORIO
         ════════════════════════════════════════════════════ -->
    <section class="fid-section fid-section--white" id="territorio" aria-labelledby="fid-territorio-title">
        <div class="fid-shell fid-territory">
            <div class="fid-territory__copy">
                <p class="fid-kicker fid-reveal">Territorio</p>
                <h2 class="fid-reveal" id="fid-territorio-title">El territorio también se puede leer.</h2>
                <p class="fid-reveal">
                    Las zonas de siembra se dibujan, se agrupan y se cruzan con las coordenadas
                    de cada árbol. Así se sabe qué área está intervenida, cuántos individuos
                    la componen y cómo evoluciona la restauración.
                </p>

                <ul class="fid-data-list fid-reveal">
                    <li><span>Zonas</span><strong>Polígonos de siembra</strong></li>
                    <li><span>Ubicación</span><strong>Coordenadas por individuo</strong></li>
                    <li><span>Cobertura</span><strong>Hectáreas en restauración</strong></li>
                    <li><span>Consulta</span><strong>Ficha pública por QR</strong></li>
                </ul>
            </div>

            <figure class="fid-map fid-reveal">
                <div class="fid-map__frame">
                    <svg viewBox="0 0 600 450" role="img" aria-label="Plano esquemático de zonas de siembra y ríos en El Bagre, Antioquia">
                        <rect width="600" height="450" fill="#2C5C3D"/>

                        <!-- Retícula de plano -->
                        <g stroke="rgba(251,250,245,.07)" stroke-width="1">
                            <path d="M60 0v450M120 0v450M180 0v450M240 0v450M300 0v450M360 0v450M420 0v450M480 0v450M540 0v450"/>
                            <path d="M0 50h600M0 100h600M0 150h600M0 200h600M0 250h600M0 300h600M0 350h600M0 400h600"/>
                        </g>

                        <!-- Curvas de nivel -->
                        <g fill="none" stroke="rgba(251,250,245,.13)" stroke-width="1">
                            <path d="M-20 95c82-46 132 19 213-8s102-68 194-45 132 4 233-40"/>
                            <path d="M-20 130c88-44 136 21 218-7s100-64 192-42 132 3 230-38"/>
                            <path d="M-20 168c92-41 139 23 222-5s98-60 188-38 132 2 228-35"/>
                            <path d="M-20 360c72-24 122 8 188-18s118-54 195-22 132 24 255-14"/>
                            <path d="M-20 398c76-24 124 8 190-17s116-50 193-19 132 23 255-12"/>
                        </g>

                        <!-- Río Nechí -->
                        <path d="M-30 322c84-18 132-64 218-66s130 48 216 16 128-16 226 30" fill="none" stroke="#467F36" stroke-width="24" opacity=".48"/>
                        <path d="M-30 322c84-18 132-64 218-66s130 48 216 16 128-16 226 30" fill="none" stroke="#FBFAF5" stroke-width="1.3" stroke-dasharray="3 6" opacity=".55"/>
                        <text x="408" y="300" fill="rgba(251,250,245,.5)" font-family="Manrope, sans-serif" font-size="9" font-weight="700" letter-spacing="3" transform="rotate(9 408 300)">RÍO NECHÍ</text>

                        <!-- Zonas de siembra -->
                        <g fill="rgba(244,155,20,.16)" stroke="#F49B14" stroke-width="1.2" stroke-dasharray="5 4">
                            <path d="M389 105l76 14 27 62-43 48-77-9-21-58z"/>
                            <path d="M139 292l66-19 47 39-13 58-65 18-45-36z"/>
                        </g>

                        <!-- Individuos georreferenciados -->
                        <g>
                            <g fill="#F5CC43">
                                <circle cx="425" cy="168" r="5"/>
                                <circle cx="452" cy="191" r="4"/>
                                <circle cx="404" cy="205" r="4"/>
                                <circle cx="172" cy="336" r="5"/>
                                <circle cx="204" cy="360" r="4"/>
                            </g>
                            <g fill="none" stroke="#F5CC43" stroke-width="1" opacity=".55">
                                <circle cx="425" cy="168" r="12"/>
                                <circle cx="172" cy="336" r="12"/>
                            </g>
                        </g>

                        <!-- Rótulos del plano -->
                        <g fill="#FBFAF5" font-family="Manrope, sans-serif">
                            <text x="34" y="40" font-size="11" font-weight="800" letter-spacing="3.4">EL BAGRE</text>
                            <text x="34" y="57" font-size="6.5" font-weight="700" letter-spacing="2.2" opacity=".5">ANTIOQUIA · COLOMBIA</text>
                            <text x="398" y="160" font-size="6.5" font-weight="700" letter-spacing="1.8" opacity=".6">Z-04</text>
                            <text x="146" y="329" font-size="6.5" font-weight="700" letter-spacing="1.8" opacity=".6">Z-07</text>
                        </g>

                        <!-- Escala y norte -->
                        <g stroke="rgba(251,250,245,.4)" stroke-width="1">
                            <path d="M520 382v36M512 390l8-8 8 8"/>
                            <path d="M512 410h16M512 418h16"/>
                        </g>
                        <text x="505" y="432" fill="rgba(251,250,245,.45)" font-family="Manrope, sans-serif" font-size="6" font-weight="700" letter-spacing="2">N</text>
                    </svg>

                    <div class="fid-map__legend">
                        <span><i></i> Individuo registrado</span>
                        <span><i class="is-zone"></i> Zona de siembra</span>
                    </div>
                </div>
                <figcaption>
                    <span>Plano esquemático</span>
                    <span>No a escala</span>
                </figcaption>
            </figure>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════════
         EQUIPO
         ════════════════════════════════════════════════════ -->
    <section class="fid-section fid-section--paper" id="equipo" aria-labelledby="fid-equipo-title">
        <div class="fid-shell fid-team">
            <div class="fid-team__seal-wrap fid-reveal">
                <div class="fid-seal" aria-hidden="true">
                    <span>Hecho en</span>
                    <strong>El Bagre</strong>
                    <span>Antioquia · Colombia</span>
                    <small>SENA · Emincut · 2026</small>
                </div>
                <p class="fid-team__seal-note">Un proyecto formativo que terminó resolviendo una necesidad real de operación.</p>
            </div>

            <div class="fid-team__copy">
                <p class="fid-kicker fid-reveal">Quién lo hizo</p>
                <h2 class="fid-reveal" id="fid-equipo-title">Un proyecto de aprendices. Un compromiso de empresa.</h2>
                <p class="fid-reveal">
                    ForestID nació durante la etapa productiva de tres aprendices del SENA,
                    Centro de Formación Minero Ambiental de El Bagre, dentro de Emincut S.A.S.
                    La empresa necesitaba demostrar lo que siembra; el equipo construyó la
                    herramienta para hacerlo.
                </p>

                <ul class="fid-team__list fid-reveal">
                    <li>
                        <span>01</span>
                        <div>
                            <strong>Luis Ángel Muñoz M.</strong>
                            <small>Arquitectura y desarrollo</small>
                        </div>
                    </li>
                    <li>
                        <span>02</span>
                        <div>
                            <strong>Gabriel Jaime Correa C.</strong>
                            <small>Backend y base de datos</small>
                        </div>
                    </li>
                    <li>
                        <span>03</span>
                        <div>
                            <strong>Valentina Hernández Z.</strong>
                            <small>Verificación y calidad</small>
                        </div>
                    </li>
                </ul>

                <p class="fid-team__memory fid-reveal">
                    En memoria de <strong>Michael Rodríguez L.</strong> (2008–2026), parte de este equipo.
                </p>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════════
         LLAMADO FINAL
         ════════════════════════════════════════════════════ -->
    <section class="fid-cta" aria-labelledby="fid-cta-title">
        <div class="fid-shell fid-cta__inner">
            <div class="fid-cta__copy">
                <p class="fid-kicker fid-reveal">Acceso al sistema</p>
                <h2 class="fid-reveal" id="fid-cta-title">¿Haces parte del equipo de Emincut?</h2>
                <p class="fid-reveal">
                    Ingresa con tu usuario para registrar, verificar o consultar los árboles
                    de la empresa.
                </p>
            </div>

            <div class="fid-cta__actions fid-reveal">
                <a class="fid-btn fid-btn--forest fid-btn--lg" href="<?= BASE_URL ?>auth/login.php">
                    Ingresar al sistema
                    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <a class="fid-link" href="<?= BASE_URL ?>publico/buscar.php">
                    Buscar un árbol por su código QR
                    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <span class="fid-cta__note">Acceso restringido a personal autorizado</span>
            </div>
        </div>
    </section>

</main>

<!-- ════════════════════════════════════════════════════
     PIE
     ════════════════════════════════════════════════════ -->
<footer class="fid-footer">
    <div class="fid-shell">
        <div class="fid-footer__grid">
            <div>
                <a class="fid-brand fid-brand--light" href="<?= BASE_URL ?>publico/index.php" aria-label="ForestID — Inicio">
                    <span class="fid-brand__mark" aria-hidden="true">
                        <svg viewBox="0 0 48 48">
                            <circle cx="33" cy="13" r="8" fill="#F5CC43"/>
                            <path d="M3 33c9-7 20-9 42-2v7H3z" fill="#467F36"/>
                            <path d="M3 39c12-3 27-2 42 2v4H3z" fill="#FBFAF5"/>
                        </svg>
                    </span>
                    <span class="fid-brand__text">
                        <strong>Forest<span>ID</span></strong>
                        <small>Emincut · El Bagre</small>
                    </span>
                </a>
                <p class="fid-footer__desc">
                    Aplicación de Emincut S.A.S. para el registro, seguimiento y trazabilidad
                    del inventario forestal en El Bagre, Antioquia.
                </p>
            </div>

            <div>
                <h3>Navegación</h3>
                <div class="fid-footer__links">
                    <a href="<?= BASE_URL ?>publico/index.php">Inicio público</a>
                    <a href="<?= BASE_URL ?>publico/buscar.php">Buscador QR</a>
                    <a href="<?= BASE_URL ?>auth/login.php">Acceso restringido</a>
                </div>
            </div>

            <div class="fid-footer__project">
                <h3>Proyecto</h3>
                <p>
                    Desarrollado por aprendices del SENA, Centro de Formación Minero Ambiental
                    de El Bagre, en etapa productiva con <strong>Emincut S.A.S.</strong>
                </p>
                <p>
                    En memoria de <strong>Michael Rodríguez L.</strong> (2008–2026),
                    parte de este equipo.
                </p>
            </div>
        </div>

        <div class="fid-footer__bottom">
            <span>&copy; <?= date('Y') ?> Emincut S.A.S. — Todos los derechos reservados.</span>
            <span>Hecho en El Bagre, Antioquia</span>
        </div>
    </div>
</footer>

<script>
(function () {
    'use strict';

    var nav = document.getElementById('fid-nav');
    var toggle = document.getElementById('fid-menu-btn');
    var menu = document.getElementById('fid-menu');

    function marcarScroll() {
        if (!nav) return;
        nav.classList.toggle('is-scrolled', window.scrollY > 8);
    }
    marcarScroll();
    window.addEventListener('scroll', marcarScroll, { passive: true });

    function abrirMenu(abierto) {
        if (!toggle || !menu) return;
        toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        toggle.setAttribute('aria-label', abierto ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
        toggle.classList.toggle('is-open', abierto);
        menu.hidden = !abierto;
    }

    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            abrirMenu(toggle.getAttribute('aria-expanded') !== 'true');
        });

        menu.querySelectorAll('a').forEach(function (enlace) {
            enlace.addEventListener('click', function () { abrirMenu(false); });
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') abrirMenu(false);
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) abrirMenu(false);
        });
    }

    var reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var elementos = document.querySelectorAll('.fid-reveal');

    if (reducirMovimiento || !('IntersectionObserver' in window)) {
        elementos.forEach(function (elemento) { elemento.classList.add('is-visible'); });
        return;
    }

    var observador = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (entrada) {
            if (entrada.isIntersecting) {
                entrada.target.classList.add('is-visible');
                observador.unobserve(entrada.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });

    elementos.forEach(function (elemento) { observador.observe(elemento); });
})();
</script>
</body>
</html>
