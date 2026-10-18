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

<!-- Identidad visual Emincut — variante "Sol": amarillo protagonista + verde institucional -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/emincut.css">

<style>
    /* ================= Fondo del hero: amanecer dorado sobre colinas ================= */
    .hero-sol {
        position: relative;
        background-color: #E9A22E;
        background-image:
            radial-gradient(60% 55% at 76% 26%, rgba(242,182,50,.98) 0%, rgba(242,182,50,0) 62%),
            radial-gradient(85% 70% at 20% 100%, rgba(91,124,58,.95) 0%, rgba(91,124,58,0) 65%),
            radial-gradient(110% 80% at 60% 125%, rgba(34,55,28,1) 0%, rgba(34,55,28,0) 70%),
            linear-gradient(160deg, #F2B632 0%, #E9A22E 45%, #C9861D 100%);
    }

    /* ---- Sol animado con rayos girando ---- */
    .sol-rayos {
        position: absolute;
        top: 6%; right: 8%;
        width: 340px; height: 340px;
        pointer-events: none;
        opacity: .55;
    }
    .sol-rayos .disco {
        position: absolute; inset: 32%;
        border-radius: 50%;
        background: radial-gradient(circle, #FFF3CE 0%, #F2B632 65%, rgba(242,182,50,0) 72%);
        animation: pulso-sol 5s ease-in-out infinite;
    }
    .sol-rayos .rayo {
        position: absolute; top: 50%; left: 50%;
        width: 10px; height: 46%;
        margin-left: -5px;
        border-radius: 999px;
        background: linear-gradient(to top, rgba(255,243,206,.9), rgba(255,243,206,0));
        transform-origin: 50% 0%;
    }
    .sol-rayos .girar {
        position: absolute; inset: 0;
        animation: girar-sol 36s linear infinite;
    }
    @keyframes girar-sol { to { transform: rotate(360deg); } }
    @keyframes pulso-sol {
        0%, 100% { transform: scale(1); filter: blur(0); }
        50%      { transform: scale(1.12); filter: blur(2px); }
    }
    @media (max-width: 640px) { .sol-rayos { width: 200px; height: 200px; opacity: .4; } }

    /* ---- Hojas flotantes ---- */
    .hoja {
        position: absolute;
        color: rgba(53, 80, 43, .5);
        animation-name: caer;
        animation-timing-function: linear;
        animation-iteration-count: infinite;
    }
    @keyframes caer {
        0%   { transform: translateY(-8%) rotate(0deg); opacity: 0; }
        10%  { opacity: .8; }
        90%  { opacity: .5; }
        100% { transform: translateY(560px) rotate(300deg); opacity: 0; }
    }

    /* ---- Reveal al hacer scroll ---- */
    .al-scroll {
        opacity: 0;
        transform: translateY(34px);
        transition: opacity .8s cubic-bezier(.22,.68,.32,1), transform .8s cubic-bezier(.22,.68,.32,1);
        will-change: opacity, transform;
    }
    .al-scroll.visible { opacity: 1; transform: translateY(0); }
    .al-scroll.d1 { transition-delay: .12s; }
    .al-scroll.d2 { transition-delay: .24s; }
    .al-scroll.d3 { transition-delay: .36s; }

    /* ---- Contador animado ---- */
    .cifra { font-variant-numeric: tabular-nums; }

    /* ---- Subrayado que se dibuja en los títulos ---- */
    .linea-sol {
        height: 5px;
        width: 88px;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--emi-amarillo), var(--emi-ambar));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .9s cubic-bezier(.22,.68,.32,1) .2s;
    }
    .visible .linea-sol, .linea-sol.visible { transform: scaleX(1); }

    /* ---- Numerales de pasos ---- */
    .paso-num {
        font-family: 'Chakra Petch', sans-serif;
        background-image: linear-gradient(135deg, var(--emi-ambar) 10%, var(--emi-amarillo) 60%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        transition: transform .45s cubic-bezier(.34,1.56,.64,1);
    }
    .emi-card:hover .paso-num { transform: scale(1.18) rotate(-3deg); }

    /* ---- Brillo que barre el botón CTA ---- */
    .barra-brillo { position: relative; overflow: hidden; }
    .barra-brillo::after {
        content: '';
        position: absolute; top: 0; left: -80%;
        width: 55%; height: 100%;
        background: linear-gradient(105deg, transparent, rgba(255,255,255,.55), transparent);
        transform: skewX(-20deg);
        animation: brillo 4.5s ease-in-out infinite;
    }
    @keyframes brillo {
        0%, 55%  { left: -80%; }
        75%, 100% { left: 130%; }
    }

    @media (prefers-reduced-motion: reduce) {
        .sol-rayos .girar, .sol-rayos .disco, .hoja, .barra-brillo::after { animation: none !important; }
        .al-scroll { opacity: 1; transform: none; transition: none; }
        .linea-sol { transform: scaleX(1); transition: none; }
    }
</style>

<!-- HERO -->
<section class="hero-sol relative text-white block overflow-hidden">
    <div class="absolute inset-0 z-0">
        <img src="<?= BASE_URL ?>assets/img/hero-bosque.jpg" alt="" class="w-full h-full object-cover opacity-35 mix-blend-multiply" onerror="this.remove()">
        <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(26,43,21,.85) 0%, rgba(34,55,28,.25) 45%, rgba(233,162,46,.08) 100%)"></div>
    </div>

    <!-- Sol con rayos girando -->
    <div class="sol-rayos z-0" aria-hidden="true">
        <div class="girar">
            <span class="rayo" style="transform:rotate(0deg)"></span>
            <span class="rayo" style="transform:rotate(30deg)"></span>
            <span class="rayo" style="transform:rotate(60deg)"></span>
            <span class="rayo" style="transform:rotate(90deg)"></span>
            <span class="rayo" style="transform:rotate(120deg)"></span>
            <span class="rayo" style="transform:rotate(150deg)"></span>
            <span class="rayo" style="transform:rotate(180deg)"></span>
            <span class="rayo" style="transform:rotate(210deg)"></span>
            <span class="rayo" style="transform:rotate(240deg)"></span>
            <span class="rayo" style="transform:rotate(270deg)"></span>
            <span class="rayo" style="transform:rotate(300deg)"></span>
            <span class="rayo" style="transform:rotate(330deg)"></span>
        </div>
        <div class="disco"></div>
    </div>

    <!-- Hojas cayendo -->
    <div class="absolute inset-0 z-0 pointer-events-none" aria-hidden="true">
        <i data-lucide="leaf" class="hoja w-5 h-5" style="left:12%; animation-duration:11s; animation-delay:0s"></i>
        <i data-lucide="leaf" class="hoja w-4 h-4" style="left:30%; animation-duration:14s; animation-delay:3s"></i>
        <i data-lucide="leaf" class="hoja w-6 h-6" style="left:52%; animation-duration:12s; animation-delay:6s"></i>
        <i data-lucide="leaf" class="hoja w-4 h-4" style="left:68%; animation-duration:16s; animation-delay:1.5s"></i>
        <i data-lucide="leaf" class="hoja w-5 h-5" style="left:85%; animation-duration:13s; animation-delay:5s"></i>
    </div>

    <!-- Barra superior: logo oficial Emincut y acceso -->
    <header class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between">
        <a href="<?= BASE_URL ?>" class="flex items-center gap-3 group">
            <span class="bg-white rounded-2xl p-2 shadow-lg shadow-black/20 group-hover:scale-105 transition-transform block w-[78px] h-[56px]">
                <img src="<?= BASE_URL ?>assets/img/logo_emincut_oficial.svg" alt="Emincut" class="w-full h-full object-contain">
            </span>
            <span class="leading-tight">
                <span class="emi-display block font-bold text-xl tracking-wide text-[#22371C]">Forest<span style="color:#fff">ID</span></span>
                <span class="block text-xs text-[#22371C]/70">Emincut · El Bagre</span>
            </span>
        </a>
        <a href="<?= BASE_URL ?>auth/login.php" class="emi-btn rounded-full px-5 py-2.5 text-sm shadow-lg" style="background:#22371C; color:var(--emi-amarillo)">
            <span class="inline-flex items-center gap-2"><i data-lucide="lock" class="w-4 h-4"></i> Iniciar sesión</span>
        </a>
    </header>

    <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <p class="emi-rise emi-d1 text-sm sm:text-base font-semibold mb-4 emi-display uppercase tracking-[.18em] text-[#22371C]">
            Sistema de control de restauración forestal posminería
        </p>

        <h1 class="emi-rise emi-d2 font-heading font-extrabold text-4xl sm:text-6xl lg:text-7xl leading-[1.08] tracking-tight max-w-4xl text-[#22371C]">
            Cada árbol sembrado,<br>contado y <span class="text-white" style="text-shadow:0 4px 18px rgba(34,55,28,.35)">verificado</span>.
        </h1>

        <p class="emi-rise emi-d3 mt-6 text-lg max-w-2xl leading-relaxed text-[#22371C]/85">
            ForestID le permite a Emincut llevar el registro de los árboles que siembra y de las hectáreas forestadas después de la minería, con evidencia para sustentar el cumplimiento de la Ley 2173 de 2021.
        </p>

        <div class="mt-8 flex flex-wrap items-center gap-4 emi-rise emi-d4">
            <a href="<?= BASE_URL ?>auth/login.php" class="emi-btn barra-brillo rounded-full px-8 py-3.5 text-sm shadow-xl" style="background:#22371C; color:var(--emi-amarillo)">
                <span class="inline-flex items-center gap-2 relative z-10"><i data-lucide="log-in" class="w-4 h-4"></i> Ingresar al sistema</span>
            </a>
            <a href="https://www.minambiente.gov.co/wp-content/uploads/2024/07/LEY-2173-DEL-30-DE-DICIEMBRE-DE-2021.pdf" target="_blank" rel="noopener noreferrer" class="emi-btn rounded-full px-8 py-3.5 text-sm border-2 border-[#22371C]/40 text-[#22371C] hover:bg-[#22371C]/10 transition-colors">
                <span class="inline-flex items-center gap-2"><i data-lucide="file-text" class="w-4 h-4"></i> Leer la Ley 2173 de 2021</span>
            </a>
        </div>
        <p class="mt-4 text-xs text-[#22371C]/65 emi-rise emi-d4">Acceso solo para personal autorizado de Emincut.</p>
    </div>

    <!-- Franja de cifras con contadores animados -->
    <div class="relative z-10 border-t border-[#22371C]/20" style="background:rgba(34,55,28,.55); backdrop-filter:blur(6px)">
        <dl class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4 divide-x divide-white/10">
            <div class="px-4 sm:px-6 lg:px-8 py-5">
                <dt class="emi-display font-bold text-3xl cifra" style="color:var(--emi-amarillo)"><span data-contador="1250">0</span>+</dt>
                <dd class="text-sm text-white/75">árboles registrados</dd>
            </div>
            <div class="px-4 sm:px-6 lg:px-8 py-5">
                <dt class="emi-display font-bold text-3xl cifra" style="color:var(--emi-amarillo)"><span data-contador="18">0</span></dt>
                <dd class="text-sm text-white/75">hectáreas forestadas</dd>
            </div>
            <div class="px-4 sm:px-6 lg:px-8 py-5">
                <dt class="emi-display font-bold text-3xl cifra" style="color:var(--emi-amarillo)"><span data-contador="42">0</span></dt>
                <dd class="text-sm text-white/75">especies catalogadas</dd>
            </div>
            <div class="px-4 sm:px-6 lg:px-8 py-5">
                <dt class="emi-display font-bold text-3xl cifra" style="color:var(--emi-amarillo)">100<span class="text-xl">%</span></dt>
                <dd class="text-sm text-white/75">trazabilidad verificable</dd>
            </div>
        </dl>
    </div>
</section>

<!-- QUÉ HACE -->
<section id="que-hace" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <div class="max-w-2xl al-scroll">
        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl leading-tight" style="color:var(--emi-tinta)">Lo que antes se contaba a mano, ahora queda registrado</h2>
        <div class="linea-sol mt-3"></div>
        <p class="mt-4" style="color:var(--emi-tinta-suave)">Después de la minería, Emincut debe poder demostrar cuánto sembró y cómo está. ForestID reúne esa información en un solo lugar.</p>
    </div>

    <ul class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <li class="emi-card p-6 al-scroll">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-amarillo-suave); color:var(--emi-ambar)">
                <i data-lucide="tree-pine" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Registro de cada árbol</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Especie, fecha de siembra, diámetro, altura y fotos. Cada individuo tiene su ficha y su placa con código QR.</p>
        </li>
        <li class="emi-card p-6 al-scroll d1">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-verde-suave); color:var(--emi-verde)">
                <i data-lucide="layers" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Hectáreas forestadas</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Los árboles se agrupan por zona, para saber qué área ya está sembrada y cuál falta.</p>
        </li>
        <li class="emi-card p-6 al-scroll d2">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-amarillo-suave); color:var(--emi-ambar)">
                <i data-lucide="map-pinned" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Ubicación exacta</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Cada árbol queda georreferenciado en el mapa, con sus coordenadas.</p>
        </li>
        <li class="emi-card p-6 al-scroll">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-verde-suave); color:var(--emi-verde)">
                <i data-lucide="clipboard-check" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Verificación en campo</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Los operarios revisan el estado de salud y registran riegos, podas y fertilizaciones. Así se sabe cuántos árboles siguen vivos.</p>
        </li>
        <li class="emi-card p-6 al-scroll d1">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-amarillo-suave); color:var(--emi-ambar)">
                <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Reportes de avance</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Gráficos y cifras del total sembrado, el estado de los árboles y las zonas, listos para presentar.</p>
        </li>
        <li class="emi-card p-6 al-scroll d2">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:var(--emi-verde-suave); color:var(--emi-verde)">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">Información privada</h3>
            <p class="mt-2 text-sm leading-relaxed" style="color:var(--emi-tinta-suave)">Solo entra personal autorizado, con usuario y contraseña. Administración y operarios ven lo que les corresponde.</p>
        </li>
    </ul>
</section>

<!-- CÓMO SE TRABAJA -->
<section class="py-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden" style="background:#22371C">
    <!-- brillo de fondo -->
    <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full blur-3xl pointer-events-none" style="background:radial-gradient(circle, rgba(242,182,50,.28) 0%, transparent 70%)"></div>
    <div class="max-w-7xl mx-auto relative">
        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl max-w-2xl leading-tight text-white al-scroll">Del vivero a la verificación</h2>
        <div class="linea-sol mt-3"></div>
        <ol class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <li class="p-8 rounded-3xl border border-white/10 al-scroll" style="background:rgba(255,255,255,.05)">
                <span class="paso-num font-bold text-6xl">01</span>
                <h3 class="font-heading font-bold text-xl mt-3 text-white">Se siembra y se registra</h3>
                <p class="mt-2 text-sm leading-relaxed text-white/70">El operario registra el árbol en campo: especie, ubicación y medidas iniciales.</p>
            </li>
            <li class="p-8 rounded-3xl border border-white/10 al-scroll d1" style="background:rgba(255,255,255,.05)">
                <span class="paso-num font-bold text-6xl">02</span>
                <h3 class="font-heading font-bold text-xl mt-3 text-white">Se marca con su placa</h3>
                <p class="mt-2 text-sm leading-relaxed text-white/70">El árbol recibe una placa con código QR para identificarlo en los recorridos de seguimiento.</p>
            </li>
            <li class="p-8 rounded-3xl border border-white/10 al-scroll d2" style="background:rgba(255,255,255,.05)">
                <span class="paso-num font-bold text-6xl">03</span>
                <h3 class="font-heading font-bold text-xl mt-3 text-white">Se verifica y se reporta</h3>
                <p class="mt-2 text-sm leading-relaxed text-white/70">Administración revisa conteos, hectáreas y estado de salud, y genera los reportes de avance.</p>
            </li>
        </ol>
    </div>
</section>

<!-- EQUIPO -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div class="al-scroll relative h-80 lg:h-[420px] rounded-3xl overflow-hidden flex items-center justify-center emi-card"
             style="background:linear-gradient(135deg, var(--emi-amarillo) 0%, var(--emi-ambar) 55%, var(--emi-verde) 130%)">
            <i data-lucide="trees" class="w-24 h-24" style="color:rgba(255,255,255,.5)"></i>
            <img src="<?= BASE_URL ?>assets/img/emincut.jpg" alt="Equipo de desarrollo de ForestID" class="absolute inset-0 w-full h-full object-cover" onerror="this.remove()">
        </div>

        <div class="space-y-5 al-scroll d1">
            <h2 class="font-heading font-extrabold text-3xl sm:text-4xl leading-tight" style="color:var(--emi-tinta)">Hecho a la medida de Emincut</h2>
            <div class="linea-sol visible"></div>
            <p class="leading-relaxed" style="color:var(--emi-tinta-suave)">
                Emincut es una empresa minera legal y certificada de El Bagre, Antioquia. Su reto era contar, sembrar y verificar las hectáreas forestadas después de la minería. ForestID se construyó para resolverlo.
            </p>
            <p class="leading-relaxed" style="color:var(--emi-tinta-suave)">
                Lo desarrollamos <strong style="color:var(--emi-ambar)">Luis Ángel Muñoz, Gabriel Correa y Valentina Hernández</strong>, aprendices del SENA, Centro de Formación Minero Ambiental de El Bagre, durante nuestra etapa productiva en las instalaciones de Emincut.
            </p>
        </div>
    </div>
</section>

<!-- LLAMADO FINAL -->
<section class="relative overflow-hidden py-20 px-4 text-center"
         style="background:linear-gradient(135deg, var(--emi-amarillo) 0%, var(--emi-ambar) 100%)">
    <!-- rayos tenues de fondo -->
    <div class="absolute inset-0 opacity-25 pointer-events-none" style="background:repeating-conic-gradient(from 0deg at 50% 120%, rgba(255,255,255,.5) 0deg 6deg, transparent 6deg 18deg)"></div>
    <h2 class="font-heading font-extrabold text-3xl sm:text-4xl relative z-10 text-[#22371C]">¿Eres parte del equipo de Emincut?</h2>
    <p class="mt-3 relative z-10 text-[#22371C]/80">Ingresa con tu usuario para registrar o consultar los árboles.</p>
    <a href="<?= BASE_URL ?>auth/login.php" class="emi-btn barra-brillo rounded-full px-8 py-3.5 text-sm mt-8 inline-flex relative z-10 shadow-xl" style="background:#22371C; color:var(--emi-amarillo)">
        <span class="inline-flex items-center gap-2 relative z-10"><i data-lucide="log-in" class="w-4 h-4"></i> Iniciar sesión</span>
    </a>
</section>

<script>
    // Reveal de secciones al hacer scroll
    (function () {
        const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const elementos = document.querySelectorAll('.al-scroll, .linea-sol');
        if (reducir || !('IntersectionObserver' in window)) {
            elementos.forEach(el => el.classList.add('visible'));
        } else {
            const io = new IntersectionObserver((entradas) => {
                entradas.forEach(e => {
                    if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
                });
            }, { threshold: .18 });
            elementos.forEach(el => io.observe(el));
        }

        // Contadores animados de la franja de cifras
        const contadores = document.querySelectorAll('[data-contador]');
        const animar = (el) => {
            const meta = parseInt(el.dataset.contador, 10);
            const dur = 1400, inicio = performance.now();
            const paso = (ahora) => {
                const p = Math.min((ahora - inicio) / dur, 1);
                const suave = 1 - Math.pow(1 - p, 3); // ease-out cúbico
                el.textContent = Math.round(meta * suave).toLocaleString('es-CO');
                if (p < 1) requestAnimationFrame(paso);
            };
            requestAnimationFrame(paso);
        };
        if (reducir || !('IntersectionObserver' in window)) {
            contadores.forEach(el => el.textContent = parseInt(el.dataset.contador, 10).toLocaleString('es-CO'));
        } else {
            const io2 = new IntersectionObserver((entradas) => {
                entradas.forEach(e => { if (e.isIntersecting) { animar(e.target); io2.unobserve(e.target); } });
            }, { threshold: .6 });
            contadores.forEach(el => io2.observe(el));
        }
    })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>