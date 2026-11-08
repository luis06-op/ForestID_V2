<?php
/**
 * ForestID — Portada pública · Diseño v2
 * Emincut S.A.S. | El Bagre, Antioquia
 *
 * Reinterpretación de la plantilla editorial de Oranssi con la paleta de marca
 * de Emincut: verde bosque #467F36, verde selva #2C5C3D, naranja energía
 * #F49B14 y amarillo sol #F5CC43.
 *
 * Las fotografías de siembra viven en assets/uploads/emincut_siembra/
 * (material interno de Emincut, excluido de Git mediante .gitignore).
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/qrcode_helper.php';

// Solo los paneles internos se redirigen: un UsuarioPublico (o un rol sin panel)
// debe ver esta portada. Si se redirige a sí mismo se produce un bucle infinito.
if (esta_autenticado() && (es_admin() || es_operario())) {
    redirigir_segun_rol();
    exit;
}

$base = htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8');

// URL canónica de esta portada (para los QR). En producción usa APP_URL.
$url_portada = rtrim(APP_URL_EFECTIVA, '/') . '/publico/index.php';

/**
 * Genera un QR y devuelve el SVG. Si la librería falla, devuelve un aviso
 * legible en lugar de romper la portada.
 */
$generar_qr = static function (string $url) : string {
    try {
        return QRCodeGenerator::generarSvg($url, 220, '#101A0D', '#FFFFFF');
    } catch (Throwable $e) {
        error_log('ForestID · QR portada v2: ' . $e->getMessage());
        return '<p style="font-size:.7rem;color:#5F6B5A;padding:24px 0">QR no disponible</p>';
    }
};

$codigos_qr = [
    ['titulo' => 'Portada', 'detalle' => 'Inicio de ForestID', 'svg' => $generar_qr($url_portada)],
    ['titulo' => 'Especies', 'detalle' => 'Galería de campo', 'svg' => $generar_qr($url_portada . '#especies')],
    ['titulo' => 'Acceso', 'detalle' => 'Ingreso al sistema', 'svg' => $generar_qr($url_portada . '#acceso')],
];

$img = static function (string $archivo) use ($base) : string {
    return $base . 'assets/uploads/emincut_siembra/' . rawurlencode($archivo);
};

$mapa_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(
    EMINCUT_DIRECCION . ', ' . EMINCUT_MUNICIPIO . ', ' . EMINCUT_DEPARTAMENTO . ', Colombia'
);

$titulo_pagina = 'ForestID — Restauración forestal de Emincut | El Bagre, Antioquia';
$meta_descripcion = 'ForestID es el sistema de Emincut para registrar, ubicar y verificar cada árbol sembrado. Cada individuo tiene su placa con código QR y su ficha pública.';
$css_extra = [BASE_URL . 'assets/css/diseno-v2.css'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="v2-page">

    <!-- ===================== NAVEGACIÓN ===================== -->
    <header class="v2-nav">
        <div class="v2-wrap">
            <a href="<?= $base ?>" class="v2-brand">
                <img src="<?= $base ?>assets/img/logo_emincut_oficial.svg" alt="Emincut S.A.S.">
                <span class="v2-brand-text">
                    <strong>Forest<span>ID</span></strong>
                    <small>Emincut · El Bagre</small>
                </span>
            </a>

            <nav aria-label="Navegación principal">
                <ul class="v2-links">
                    <li><a href="#servicios">Servicios</a></li>
                    <li><a href="#especies">Especies</a></li>
                    <li><a href="#qr">Códigos QR</a></li>
                    <li><a href="#proyecto">Proyecto</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                </ul>
            </nav>

            <a href="<?= $base ?>auth/login.php" class="v2-btn v2-btn-dark">
                Iniciar sesión
            </a>
        </div>
    </header>

    <!-- ===================== HERO ===================== -->
    <section class="v2-hero" id="inicio">
        <div class="v2-hero-media">
            <img src="<?= $img('hero-siembra.jpg') ?>" onerror="this.remove()"
                 alt="Cuadrilla de Emincut sembrando árboles en una zona de restauración en El Bagre, Antioquia">
        </div>

        <span class="v2-hero-tag">Restauración posminería · Antioquia</span>

        <div class="v2-hero-inner">
            <div class="v2-wrap">
                <span class="v2-kicker">Sistema de control de restauración forestal posminería</span>

                <h1>
                    Cada árbol cuenta,<br>
                    <span class="v2-marca">ahora también se cuenta.</span>
                </h1>

                <p>
                    ForestID le permite a Emincut llevar el registro de los árboles que
                    siembra y de las hectáreas forestadas después de la minería. Una placa
                    con código QR conecta cada árbol con su historia.
                </p>

                <div class="v2-hero-actions">
                    <a href="<?= $base ?>auth/login.php" class="v2-btn v2-btn-accent">
                        Ingresar al sistema
                    </a>
                    <a href="#especies" class="v2-btn v2-btn-ghost">
                        Ver el trabajo en campo
                    </a>
                </div>

                <p class="v2-hero-note">Acceso restringido para personal autorizado de Emincut.</p>
            </div>
        </div>
    </section>

    <!-- ===================== CIFRAS ===================== -->
    <div class="v2-stats">
        <div class="v2-wrap">
            <div class="v2-stat">
                <strong><span data-contador="1250">1.250</span>+</strong>
                <span>Árboles registrados</span>
            </div>
            <div class="v2-stat">
                <strong><span data-contador="18">18</span> ha</strong>
                <span>Hectáreas forestadas</span>
            </div>
            <div class="v2-stat">
                <strong><span data-contador="42">42</span></strong>
                <span>Especies catalogadas</span>
            </div>
            <div class="v2-stat">
                <strong><span data-contador="100">100</span>%</strong>
                <span>Trazabilidad verificable</span>
            </div>
        </div>
    </div>

    <!-- ===================== MÓDULOS ===================== -->
    <section class="v2-section" id="servicios">
        <div class="v2-wrap">
            <div class="v2-section-head">
                <div>
                    <span class="v2-kicker">Módulos de ForestID</span>
                    <h2 class="v2-title">Todo el bosque,<br>en seis módulos</h2>
                    <div class="v2-rule"></div>
                    <p class="v2-lead">
                        Del registro en campo a la verificación en oficina: cada módulo
                        cubre una parte del ciclo y todos comparten la misma placa QR.
                    </p>
                </div>
                <a href="#proceso" class="v2-btn v2-btn-dark">Conoce el proceso</a>
            </div>

            <div class="v2-modules">
                <article class="v2-module v2-module--wide al-scroll">
                    <span class="v2-module-glow" aria-hidden="true"></span>
                    <span class="v2-module-num">01</span>
                    <span class="v2-module-ico"><i data-lucide="tree-pine"></i></span>
                    <h3>Registro de cada árbol</h3>
                    <p>Especie, fecha de siembra, diámetro, altura y fotos. Cada individuo tiene su ficha y su placa con código QR.</p>
                    <span class="v2-module-bar" aria-hidden="true"></span>
                </article>

                <article class="v2-module al-scroll d1">
                    <span class="v2-module-glow" aria-hidden="true"></span>
                    <span class="v2-module-num">02</span>
                    <span class="v2-module-ico"><i data-lucide="layers"></i></span>
                    <h3>Hectáreas forestadas</h3>
                    <p>Los árboles se agrupan por zona, para saber qué área ya está sembrada y cuál falta.</p>
                    <span class="v2-module-bar" aria-hidden="true"></span>
                </article>

                <article class="v2-module al-scroll d2">
                    <span class="v2-module-glow" aria-hidden="true"></span>
                    <span class="v2-module-num">03</span>
                    <span class="v2-module-ico"><i data-lucide="map-pinned"></i></span>
                    <h3>Ubicación exacta</h3>
                    <p>Cada árbol queda georreferenciado en el mapa, con sus coordenadas.</p>
                    <span class="v2-module-bar" aria-hidden="true"></span>
                </article>

                <article class="v2-module al-scroll">
                    <span class="v2-module-glow" aria-hidden="true"></span>
                    <span class="v2-module-num">04</span>
                    <span class="v2-module-ico"><i data-lucide="clipboard-check"></i></span>
                    <h3>Verificación en campo</h3>
                    <p>Los operarios revisan el estado de salud y registran riegos, podas y fertilizaciones. Así se sabe cuántos árboles siguen vivos.</p>
                    <span class="v2-module-bar" aria-hidden="true"></span>
                </article>

                <article class="v2-module al-scroll d1">
                    <span class="v2-module-glow" aria-hidden="true"></span>
                    <span class="v2-module-num">05</span>
                    <span class="v2-module-ico"><i data-lucide="bar-chart-3"></i></span>
                    <h3>Reportes de avance</h3>
                    <p>Gráficos y cifras del total sembrado, el estado de los árboles y las zonas, listos para presentar.</p>
                    <span class="v2-module-bar" aria-hidden="true"></span>
                </article>

                <article class="v2-module v2-module--wide v2-module--dark al-scroll d2">
                    <span class="v2-module-glow" aria-hidden="true"></span>
                    <span class="v2-module-num">06</span>
                    <span class="v2-module-ico"><i data-lucide="shield-check"></i></span>
                    <h3>Información privada</h3>
                    <p>Solo entra personal autorizado, con usuario y contraseña. Administración y operarios ven lo que les corresponde.</p>
                    <span class="v2-module-bar" aria-hidden="true"></span>
                </article>
            </div>
        </div>
    </section>

    <!-- ===================== CÓMO SE TRABAJA ===================== -->
    <section class="v2-section v2-section-dark" id="proceso">
        <div class="v2-wrap">
            <span class="v2-kicker">Cómo se trabaja</span>
            <h2 class="v2-title">Del vivero a la verificación</h2>
            <div class="v2-rule"></div>

            <ol class="v2-process">
                <li class="v2-step al-scroll">
                    <span class="v2-step-num">01</span>
                    <h3>Se siembra y se registra</h3>
                    <p>El operario registra el árbol en campo: especie, ubicación y medidas iniciales.</p>
                </li>
                <li class="v2-step al-scroll d1">
                    <span class="v2-step-num">02</span>
                    <h3>Se marca con su placa</h3>
                    <p>El árbol recibe una placa con código QR para identificarlo en los recorridos de seguimiento.</p>
                </li>
                <li class="v2-step al-scroll d2">
                    <span class="v2-step-num">03</span>
                    <h3>Se verifica y se reporta</h3>
                    <p>Administración revisa conteos, hectáreas y estado de salud, y genera los reportes de avance.</p>
                </li>
            </ol>
        </div>
    </section>

    <!-- ===================== TRAZABILIDAD PÚBLICA ===================== -->
    <section class="v2-band" id="trazabilidad">
        <div class="v2-wrap v2-band-inner">
            <div>
                <span class="v2-band-kicker">
                    <i data-lucide="scan-line"></i> Consulta pública
                </span>
                <h2>Un código,<br>toda la historia del árbol.</h2>
                <p>Escanea la placa y consulta especie, siembra, ubicación y estado. Sin cuenta y sin intermediarios.</p>
            </div>
            <div class="v2-band-actions">
                <a href="<?= $base ?>publico/buscar.php" class="v2-btn v2-btn-dark">Buscar un árbol</a>
                <a href="#qr" class="v2-band-link">
                    Ver los códigos QR <i data-lucide="arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- ===================== GALERÍA DE CAMPO ===================== -->
    <section class="v2-section v2-section-alt" id="especies">
        <div class="v2-wrap">
            <div class="v2-section-head">
                <div>
                    <span class="v2-kicker">Especies y registros</span>
                    <h2 class="v2-title">Árboles que dejan huella</h2>
                    <div class="v2-rule"></div>
                    <p class="v2-lead">
                        El equipo de Emincut siembra, mide y registra cada individuo.
                        Estas son algunas escenas del trabajo que alimenta ForestID.
                    </p>
                </div>
                <a href="<?= $base ?>publico/buscar.php" class="v2-btn v2-btn-dark">Buscar por código</a>
            </div>

            <div class="v2-gallery">
                <a href="<?= $base ?>publico/buscar.php" class="v2-card al-scroll">
                    <img src="<?= $img('siembra-planta.jpg') ?>" alt="Colaboradora de Emincut sembrando un árbol joven en campo" onerror="this.remove()">
                    <span class="v2-card-info">
                        <small>Siembra</small>
                        <strong>El primer registro</strong>
                        <span>Especie, ubicación y medidas iniciales del individuo.</span>
                    </span>
                </a>

                <a href="<?= $base ?>publico/buscar.php" class="v2-card al-scroll d1">
                    <img src="<?= $img('registro-digital.jpg') ?>" alt="Dos colaboradoras de Emincut registrando la siembra con un teléfono" onerror="this.remove()">
                    <span class="v2-card-info">
                        <small>Registro digital</small>
                        <strong>Datos en terreno</strong>
                        <span>La información se levanta en el momento de la siembra.</span>
                    </span>
                </a>

                <a href="#qr" class="v2-card al-scroll d2">
                    <img src="<?= $img('placa-emincut.jpg') ?>" alt="Colaboradora de Emincut sosteniendo la placa de identificación de un árbol" onerror="this.remove()">
                    <span class="v2-card-info">
                        <small>Placas QR</small>
                        <strong>Identidad física</strong>
                        <span>Cada árbol recibe una placa para consultarlo en campo.</span>
                    </span>
                </a>

                <a href="<?= $base ?>publico/buscar.php" class="v2-card al-scroll d3">
                    <img src="<?= $img('equipo-siembra.jpg') ?>" alt="Equipo de Emincut sembrando árboles junto a maquinaria en una zona de restauración" onerror="this.remove()">
                    <span class="v2-card-info">
                        <small>Equipo</small>
                        <strong>Cuadrillas en campo</strong>
                        <span>El trabajo colectivo que hace posible la restauración.</span>
                    </span>
                </a>
            </div>
        </div>
    </section>

    <!-- ===================== CÓDIGOS QR ===================== -->
    <section class="v2-section v2-section-dark" id="qr">
        <div class="v2-wrap">
            <span class="v2-kicker">Códigos QR</span>
            <h2 class="v2-title">Escanea. Llega a ForestID.</h2>
            <div class="v2-rule"></div>
            <p class="v2-lead">
                Cada placa física de Emincut lleva un código QR. Al escanearlo se abre
                la ficha pública del árbol o una sección de esta portada, sin iniciar sesión.
            </p>

            <div class="v2-qr">
                <figure class="v2-qr-photo al-scroll">
                    <img src="<?= $img('placa-detalle.jpg') ?>" alt="Placa de identificación de Emincut junto a un árbol recién sembrado" onerror="this.remove()">
                    <figcaption>
                        <strong>Placa ForestID</strong>
                        La identificación física viaja con el árbol durante todo su seguimiento.
                    </figcaption>
                </figure>

                <div>
                    <div class="v2-qr-grid">
                        <?php foreach ($codigos_qr as $codigo): ?>
                            <div class="v2-qr-card al-scroll">
                                <?= $codigo['svg'] ?>
                                <strong><?= htmlspecialchars($codigo['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars($codigo['detalle'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <p class="v2-qr-note">
                        <i data-lucide="scan-line"></i>
                        <span>
                            Apunta la cámara del teléfono al código. Los QR de esta página
                            se generan con la URL canónica configurada en el servidor.
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== PROYECTO ===================== -->
    <section class="v2-section" id="proyecto">
        <div class="v2-wrap v2-about">
            <div class="v2-about-media al-scroll">
                <img src="<?= $img('grupo-siembra.jpg') ?>" alt="Grupo de trabajadores de Emincut sembrando árboles en una zona de restauración" onerror="this.remove()">
                <img src="<?= $img('siembra-campo.jpg') ?>" alt="Detalle de la siembra de un árbol joven en campo" onerror="this.remove()">
            </div>

            <div class="al-scroll d1">
                <span class="v2-kicker">El proyecto</span>
                <h2 class="v2-title">Hecho a la medida de Emincut</h2>
                <div class="v2-rule"></div>
                <p class="v2-lead">
                    Emincut es una empresa minera legal y certificada de El Bagre, Antioquia.
                    Su reto era contar, sembrar y verificar las hectáreas forestadas después
                    de la minería. ForestID se construyó para resolverlo.
                </p>
                <p class="v2-text">
                    Lo desarrollamos <strong>Luis Ángel Muñoz, Gabriel Correa y Valentina Hernández</strong>.
                </p>

                <ul class="v2-about-list">
                    <li>Registro individual con fotos, medidas y código de placa.</li>
                    <li>Georreferenciación por zona para controlar las hectáreas.</li>
                    <li>Ficha pública accesible por QR, sin iniciar sesión.</li>
                    <li>Reportes de avance para la verificación interna.</li>
                </ul>

                <div class="v2-about-meta">
                    <div>
                        <strong>3</strong>
                        <span>Integrantes del equipo</span>
                    </div>
                    <div>
                        <strong>100%</strong>
                        <span>Trazabilidad</span>
                    </div>
                    <div>
                        <strong>El Bagre</strong>
                        <span>Antioquia, Colombia</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== EQUIPO ===================== -->
    <section class="v2-section v2-section-alt" id="equipo">
        <div class="v2-wrap">
            <span class="v2-kicker">Nuestro equipo</span>
            <h2 class="v2-title">Quiénes construyeron ForestID</h2>
            <div class="v2-rule"></div>
            <p class="v2-lead">
                El equipo que diseñó y construyó ForestID, con roles de arquitectura,
                backend y verificación de calidad.
            </p>

            <div class="v2-team">
                <article class="v2-member al-scroll">
                    <span class="v2-avatar">LM</span>
                    <h3>Luis Ángel Muñoz M.</h3>
                    <p>Arquitectura</p>
                </article>

                <article class="v2-member al-scroll d1">
                    <span class="v2-avatar">GC</span>
                    <h3>Gabriel Jaime Correa C.</h3>
                    <p>Backend</p>
                </article>

                <article class="v2-member al-scroll d2">
                    <span class="v2-avatar">VH</span>
                    <h3>Valentina Hernández Z.</h3>
                    <p>Verificación de calidad</p>
                </article>

                <article class="v2-member v2-member--memoria al-scroll d3">
                    <span class="v2-avatar">MR</span>
                    <h3>Michael Rodríguez L.</h3>
                    <p>UI / QA</p>
                    <span class="v2-in-memoriam">
                        <i data-lucide="star"></i> In memoriam
                    </span>
                </article>
            </div>
        </div>
    </section>

    <!-- ===================== CITA ===================== -->
    <section class="v2-quote">
        <div class="v2-wrap">
            <blockquote>
                «La trazabilidad no es un requisito: es la forma de demostrar que lo sembrado sigue vivo.»
            </blockquote>
            <cite>Equipo ForestID · El Bagre, Antioquia</cite>
        </div>
    </section>

    <!-- ===================== CONTACTO / ACCESO ===================== -->
    <section class="v2-section v2-contact" id="contacto">
        <div class="v2-wrap">
            <div class="v2-contact-head">
                <span class="v2-kicker">Contáctanos</span>
                <h2 class="v2-title">Hablemos del<br>territorio</h2>
                <div class="v2-rule"></div>
                <p class="v2-lead">
                    Estamos en El Bagre, Antioquia. Escríbenos, llámanos o visítanos
                    para conocer el proyecto y la restauración que estamos midiendo.
                </p>
            </div>

            <div class="v2-contact-grid">
                <a class="v2-contact-tile" href="<?= htmlspecialchars($mapa_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                    <span class="v2-contact-ico"><i data-lucide="map-pin"></i></span>
                    <span class="v2-contact-label">Dirección</span>
                    <strong>
                        <?= htmlspecialchars(EMINCUT_DIRECCION, ENT_QUOTES, 'UTF-8') ?>
                        <span><?= htmlspecialchars(EMINCUT_MUNICIPIO . ', ' . EMINCUT_DEPARTAMENTO . ', ' . EMINCUT_PAIS, ENT_QUOTES, 'UTF-8') ?></span>
                    </strong>
                    <span class="v2-contact-action">Ver en el mapa <i data-lucide="arrow-up-right"></i></span>
                </a>

                <a class="v2-contact-tile" href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', EMINCUT_TELEFONO), ENT_QUOTES, 'UTF-8') ?>">
                    <span class="v2-contact-ico"><i data-lucide="phone"></i></span>
                    <span class="v2-contact-label">Teléfono</span>
                    <strong><?= htmlspecialchars(EMINCUT_TELEFONO, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="v2-contact-action">Llamar <i data-lucide="arrow-up-right"></i></span>
                </a>

                <a class="v2-contact-tile" href="mailto:<?= htmlspecialchars(EMINCUT_CORREO, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="v2-contact-ico"><i data-lucide="mail"></i></span>
                    <span class="v2-contact-label">Correo</span>
                    <strong><?= htmlspecialchars(EMINCUT_CORREO, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="v2-contact-action">Escribir <i data-lucide="arrow-up-right"></i></span>
                </a>

                <a class="v2-contact-tile v2-contact-tile--search" href="<?= $base ?>publico/buscar.php">
                    <span class="v2-contact-ico"><i data-lucide="scan-search"></i></span>
                    <span class="v2-contact-label">Consulta pública</span>
                    <strong>Buscar un árbol por su código de placa</strong>
                    <span class="v2-contact-action">Buscar ahora <i data-lucide="arrow-right"></i></span>
                </a>
            </div>

            <div class="v2-contact-access" id="acceso">
                <div>
                    <span class="v2-access-kicker">Acceso al sistema</span>
                    <h3>¿Eres parte del equipo de Emincut?</h3>
                    <p>
                        Ingresa con tu usuario para registrar o consultar los árboles.
                        Administración y operarios ven lo que les corresponde.
                    </p>
                </div>
                <div class="v2-access-actions">
                    <a href="<?= $base ?>auth/login.php" class="v2-btn v2-btn-accent">
                        Iniciar sesión
                    </a>
                    <small>Acceso exclusivo para personal autorizado.</small>
                </div>
            </div>
        </div>
    </section>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
