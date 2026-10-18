<?php
/**
 * ForestID — Página de entrada (diseño v3, sistema PRIVADO de Emincut)
 * Emincut | El Bagre, Antioquia
 * Desarrollado por aprendices del SENA – Centro de Formación Minero Ambiental, El Bagre
 *
 * Esta página no consulta la base de datos ni muestra información de árboles:
 * solo presenta el sistema y lleva al login. Si ya hay sesión, redirige al panel.
 *
 * Imágenes opcionales en assets/img/ (si faltan, se ve un fondo verde):
 *   hero-bosque.jpg  -> fondo del hero, horizontal, mín. 1600px (ideal: área reforestada de Emincut)
 *   emincut.jpg      -> foto de la sección del equipo
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (esta_autenticado()) {
    redirigir_segun_rol();
    exit;
}

$titulo_pagina = 'ForestID — Control de siembra y hectáreas forestadas | Emincut';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Identidad visual Emincut: blanco, verde (principal) y amarillo -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/emincut.css">

<style>
    /* Ajustes propios de la portada pública */
    .hero-emincut {
        position: relative;
        background-color: var(--emi-verde-oscuro);
        background-image:
            radial-gradient(48% 55% at 82% 14%, rgba(242,182,50,.50) 0%, transparent 60%),
            radial-gradient(30% 38% at 84% 12%, rgba(233,162,46,.40) 0%, transparent 55%),
            radial-gradient(65% 55% at 12% 90%, rgba(91,124,58,.80) 0%, transparent 68%),
            radial-gradient(95% 75% at 55% 115%, rgba(34,55,28,.95) 0%, transparent 70%),
            linear-gradient(165deg, #35502B 0%, #22371C 55%, #1A2B15 100%);
    }
    .paso-num {
        font-family: 'Chakra Petch', sans-serif;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        background-image: linear-gradient(135deg, var(--emi-verde) 20%, var(--emi-amarillo) 90%);
    }
</style>

<!-- HERO -->
<section class="hero-emincut emi-sunline relative text-white block">
    <div class="absolute inset-0 z-0">
        <img src="<?= BASE_URL ?>assets/img/hero-bosque.jpg" alt="" class="w-full h-full object-cover opacity-60" onerror="this.remove()">
        <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(26,43,21,.92) 0%, rgba(26,43,21,.55) 55%, rgba(26,43,21,.25) 100%)"></div>
    </div>

    <!-- Barra superior: logo oficial Emincut y acceso -->
    <header class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between">
        <a href="<?= BASE_URL ?>" class="flex items-center gap-3 group">
            <span class="bg-white rounded-2xl p-2 shadow-lg shadow-black/25 group-hover:scale-105 transition-transform block w-[78px] h-[56px]">
                <img src="<?= BASE_URL ?>assets/img/logo_emincut_oficial.svg" alt="Emincut" class="w-full h-full object-contain">
            </span>
            <span class="leading-tight">
                <span class="emi-display block font-bold text-xl tracking-wide">Forest<span style="color:var(--emi-amarillo)">ID</span></span>
                <span class="block text-xs text-white/60">Emincut · El Bagre</span>
            </span>
        </a>
        <a href="<?= BASE_URL ?>auth/login.php" class="emi-btn emi-btn-sol text-sm px-5 py-2.5 rounded-full">
            <span class="inline-flex items-center gap-2"><i data-lucide="lock" class="w-4 h-4"></i> Iniciar sesión</span>
        </a>
    </header>

    <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <p class="emi-rise emi-d1 text-sm sm:text-base font-medium mb-4 emi-display uppercase tracking-[.18em]" style="color:var(--emi-amarillo)">
            Sistema de control de restauración forestal posminería
        </p>

        <h1 class="emi-rise emi-d2 font-heading font-extrabold text-4xl sm:text-6xl lg:text-7xl leading-[1.1] tracking-tight max-w-4xl">
            Cada árbol sembrado,<br>contado y <span style="color:var(--emi-amarillo)">verificado</span>.
        </h1>

        <p class="emi-rise emi-d3 mt-6 text-lg text-white/80 max-w-2xl leading-relaxed">
            ForestID le permite a Emincut llevar el registro de los árboles que siembra y de las hectáreas forestadas después de la minería, con evidencia para sustentar el cumplimiento de la Ley 2173 de 2021.
        </p>

        <div class="mt-8 flex flex-wrap items-center gap-4 emi-rise emi-d4">
            <a href="<?= BASE_URL ?>auth/login.php" class="emi-btn emi-btn-sol rounded-full px-8 py-3.5 text-sm">
                <span class="inline-flex items-center gap-2"><i data-lucide="log-in" class="w-4 h-4"></i> Ingresar al sistema</span>
            </a>
            <a href="https://www.minambiente.gov.co/wp-content/uploads/2024/07/LEY-2173-DEL-30-DE-DICIEMBRE-DE-2021.pdf" target="_blank" rel="noopener noreferrer" class="emi-btn emi-btn-fantasma rounded-full px-8 py-3.5 text-sm">
                <span class="inline-flex items-center gap-2"><i data-lucide="file-text" class="w-4 h-4"></i> Leer la Ley 2173 de 2021</span>
            </a>
        </div>
        <p class="mt-4 text-xs text-white/55 emi-rise emi-d4">Acceso solo para personal autorizado de Emincut.</p>
    </div>

    <!-- Franja con lo que se controla -->
    <div class="relative z-10 border-t border-white/15" style="background:rgba(20,32,16,.45); backdrop-filter:blur(6px)">
        <dl class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4 divide-x divide-white/10">
            <div class="px-4 sm:px-6 lg:px-8 py-5"><dt class="emi-display font-bold text-lg" style="color:var(--emi-amarillo)">Conteo</dt><dd class="text-sm text-white/70">árbol por árbol</dd></div>
            <div class="px-4 sm:px-6 lg:px-8 py-5"><dt class="emi-display font-bold text-lg" style="color:var(--emi-amarillo)">Hectáreas</dt><dd class="text-sm text-white/70">forestadas por zona</dd></div>
            <div class="px-4 sm:px-6 lg:px-8 py-5"><dt class="emi-display font-bold text-lg" style="color:var(--emi-amarillo)">Ubicación</dt><dd class="text-sm text-white/70">de cada individuo en el mapa</dd></div>
            <div class="px-4 sm:px-6 lg:px-8 py-5"><dt class="emi-display font-bold text-lg" style="color:var(--emi-amarillo)">Verificación</dt><dd class="text-sm text-white/70">del estado en campo</dd></div>
        </dl>
    </div>
</section>

<!-- QUÉ HACE -->
<section id="que-hace" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <div class="max-w-2xl">
        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl leading-tight" style="color:var(--emi-tinta)">Lo que antes se contaba a mano, ahora queda registrado</h2>
        <div class="mt-3 h-1 w-20 rounded-full" style="background:linear-gradient(90deg, var(--emi-verde), var(--emi-amarillo))"></div>
        <p class="mt-4" style="color:var(--emi-tinta-suave)">Después de la minería, Emincut debe poder demostrar cuánto sembró y cómo está. ForestID reúne esa información en un solo lugar.</p>
    </div>

    <ul class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <li class="emi-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-verde-suave); color:var(--emi-verde)">
                <i data-lucide="tree-pine" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Registro de cada árbol</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Especie, fecha de siembra, diámetro, altura y fotos. Cada individuo tiene su ficha y su placa con código QR.</p>
        </li>
        <li class="emi-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-amarillo-suave); color:var(--emi-ambar)">
                <i data-lucide="layers" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Hectáreas forestadas</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Los árboles se agrupan por zona, para saber qué área ya está sembrada y cuál falta.</p>
        </li>
        <li class="emi-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-verde-suave); color:var(--emi-verde)">
                <i data-lucide="map-pinned" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Ubicación exacta</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Cada árbol queda georreferenciado en el mapa, con sus coordenadas.</p>
        </li>
        <li class="emi-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-amarillo-suave); color:var(--emi-ambar)">
                <i data-lucide="clipboard-check" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Verificación en campo</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Los operarios revisan el estado de salud y registran riegos, podas y fertilizaciones. Así se sabe cuántos árboles siguen vivos.</p>
        </li>
        <li class="emi-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-verde-suave); color:var(--emi-verde)">
                <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Reportes de avance</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Gráficos y cifras del total sembrado, el estado de los árboles y las zonas, listos para presentar.</p>
        </li>
        <li class="emi-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-amarillo-suave); color:var(--emi-ambar)">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Información privada</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Solo entra personal autorizado, con usuario y contraseña. Administración y operarios ven lo que les corresponde.</p>
        </li>
    </ul>
</section>

<!-- CÓMO SE TRABAJA -->
<section class="py-20 px-4 sm:px-6 lg:px-8" style="background:var(--emi-verde-suave)">
    <div class="max-w-7xl mx-auto">
        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl max-w-2xl leading-tight" style="color:var(--emi-tinta)">Del vivero a la verificación</h2>
        <div class="mt-3 h-1 w-20 rounded-full" style="background:linear-gradient(90deg, var(--emi-verde), var(--emi-amarillo))"></div>
        <ol class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <li class="emi-card p-8">
                <span class="paso-num font-bold text-6xl">01</span>
                <h3 class="font-heading font-bold text-xl mt-3" style="color:var(--emi-tinta)">Se siembra y se registra</h3>
                <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">El operario registra el árbol en campo: especie, ubicación y medidas iniciales.</p>
            </li>
            <li class="emi-card p-8">
                <span class="paso-num font-bold text-6xl">02</span>
                <h3 class="font-heading font-bold text-xl mt-3" style="color:var(--emi-tinta)">Se marca con su placa</h3>
                <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">El árbol recibe una placa con código QR para identificarlo en los recorridos de seguimiento.</p>
            </li>
            <li class="emi-card p-8">
                <span class="paso-num font-bold text-6xl">03</span>
                <h3 class="font-heading font-bold text-xl mt-3" style="color:var(--emi-tinta)">Se verifica y se reporta</h3>
                <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Administración revisa conteos, hectáreas y estado de salud, y genera los reportes de avance.</p>
            </li>
        </ol>
    </div>
</section>

<!-- EQUIPO -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div class="relative h-80 lg:h-[420px] rounded-3xl overflow-hidden flex items-center justify-center emi-card"
             style="background:linear-gradient(135deg, var(--emi-verde) 0%, var(--emi-verde-oscuro) 100%)">
            <i data-lucide="trees" class="w-24 h-24" style="color:rgba(242,182,50,.45)"></i>
            <img src="<?= BASE_URL ?>assets/img/emincut.jpg" alt="Equipo de desarrollo de ForestID" class="absolute inset-0 w-full h-full object-cover" onerror="this.remove()">
        </div>

        <div class="space-y-5">
            <h2 class="font-heading font-extrabold text-3xl sm:text-4xl leading-tight" style="color:var(--emi-tinta)">Hecho a la medida de Emincut</h2>
            <div class="h-1 w-20 rounded-full" style="background:linear-gradient(90deg, var(--emi-verde), var(--emi-amarillo))"></div>
            <p class="leading-relaxed" style="color:var(--emi-tinta-suave)">
                Emincut es una empresa minera legal y certificada de El Bagre, Antioquia. Su reto era contar, sembrar y verificar las hectáreas forestadas después de la minería. ForestID se construyó para resolverlo.
            </p>
            <p class="leading-relaxed" style="color:var(--emi-tinta-suave)">
                Lo desarrollamos <strong style="color:var(--emi-verde-oscuro)">Luis Ángel Muñoz, Gabriel Correa y Valentina Hernández</strong>, aprendices del SENA, Centro de Formación Minero Ambiental de El Bagre, durante nuestra etapa productiva en las instalaciones de Emincut.
            </p>
        </div>
    </div>
</section>

<!-- LLAMADO FINAL -->
<section class="emi-sky text-white py-20 px-4 text-center relative overflow-hidden">
    <h2 class="font-heading font-extrabold text-3xl sm:text-4xl relative z-10">¿Eres parte del equipo de Emincut?</h2>
    <p class="mt-3 text-white/75 relative z-10">Ingresa con tu usuario para registrar o consultar los árboles.</p>
    <a href="<?= BASE_URL ?>auth/login.php" class="emi-btn emi-btn-sol rounded-full px-8 py-3.5 text-sm mt-8 inline-flex relative z-10">
        <span class="inline-flex items-center gap-2"><i data-lucide="log-in" class="w-4 h-4"></i> Iniciar sesión</span>
    </a>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>