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

<style>
    .hero-copas {
        background:
            radial-gradient(55% 80% at 15% 25%, #1f7a46 0%, transparent 60%),
            radial-gradient(45% 70% at 85% 15%, #166534 0%, transparent 65%),
            radial-gradient(70% 60% at 60% 95%, #0b3d24 0%, transparent 70%),
            #072e1c;
    }
    .foto-respaldo { background: linear-gradient(135deg, #15803d, #052e1b); }
</style>

<!-- HERO -->
<section class="hero-copas relative text-white block">
    <div class="absolute inset-0 z-0">
        <img src="<?= BASE_URL ?>assets/img/hero-bosque.jpg" alt="" class="w-full h-full object-cover" onerror="this.remove()">
        <div class="absolute inset-0 bg-gradient-to-t from-[#04231a] via-[#04231a]/65 to-[#04231a]/30"></div>
    </div>

    <!-- Barra superior mínima: solo marca y acceso -->
    <header class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between">
        <a href="<?= BASE_URL ?>" class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-xl bg-emerald-600 flex items-center justify-center">
                <i data-lucide="trees" class="w-6 h-6 text-white"></i>
            </span>
            <span class="leading-tight">
                <span class="block font-heading font-extrabold text-xl">ForestID</span>
                <span class="block text-xs text-emerald-200">Emincut · El Bagre</span>
            </span>
        </a>
        <a href="<?= BASE_URL ?>auth/login.php" class="inline-flex items-center gap-2 rounded-full bg-white text-emerald-900 font-semibold text-sm px-5 py-2.5 hover:bg-emerald-100 transition-colors">
            <i data-lucide="lock" class="w-4 h-4"></i> Iniciar sesión
        </a>
    </header>

    <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <p class="text-sm sm:text-base text-emerald-200 font-medium mb-4">Sistema de control de restauración forestal posminería</p>

        <h1 class="font-heading font-extrabold text-4xl sm:text-6xl lg:text-7xl leading-[1.1] tracking-tight max-w-4xl">
            Cada árbol sembrado, contado y verificado.
        </h1>

        <p class="mt-6 text-lg text-emerald-50/90 max-w-2xl leading-relaxed">
            ForestID le permite a Emincut llevar el registro de los árboles que siembra y de las hectáreas forestadas después de la minería, con evidencia para sustentar el cumplimiento de la Ley 2173 de 2021.
        </p>

        <div class="mt-8 flex flex-wrap items-center gap-4">
            <a href="<?= BASE_URL ?>auth/login.php" class="btn-forest-primary rounded-full px-8 py-3.5 text-sm font-semibold">
                <i data-lucide="log-in" class="w-4 h-4"></i> Ingresar al sistema
            </a>
            <a href="https://www.minambiente.gov.co/wp-content/uploads/2024/07/LEY-2173-DEL-30-DE-DICIEMBRE-DE-2021.pdf" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/40 px-8 py-3.5 text-sm font-semibold hover:bg-white/10 transition-colors inline-flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4"></i> Leer la Ley 2173 de 2021
            </a>
        </div>
        <p class="mt-4 text-xs text-emerald-100/70">Acceso solo para personal autorizado de Emincut.</p>
    </div>

    <!-- Franja con lo que se controla -->
    <div class="relative z-10 border-t border-white/20 bg-black/25 backdrop-blur-sm">
        <dl class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4 divide-x divide-white/15">
            <div class="px-4 sm:px-6 lg:px-8 py-5"><dt class="font-heading font-bold text-lg">Conteo</dt><dd class="text-sm text-emerald-100/80">árbol por árbol</dd></div>
            <div class="px-4 sm:px-6 lg:px-8 py-5"><dt class="font-heading font-bold text-lg">Hectáreas</dt><dd class="text-sm text-emerald-100/80">forestadas por zona</dd></div>
            <div class="px-4 sm:px-6 lg:px-8 py-5"><dt class="font-heading font-bold text-lg">Ubicación</dt><dd class="text-sm text-emerald-100/80">de cada individuo en el mapa</dd></div>
            <div class="px-4 sm:px-6 lg:px-8 py-5"><dt class="font-heading font-bold text-lg">Verificación</dt><dd class="text-sm text-emerald-100/80">del estado en campo</dd></div>
        </dl>
    </div>
</section>

<!-- QUÉ HACE -->
<section id="que-hace" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <div class="max-w-2xl">
        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl text-gray-900 leading-tight">Lo que antes se contaba a mano, ahora queda registrado</h2>
        <p class="text-gray-600 mt-3">Después de la minería, Emincut debe poder demostrar cuánto sembró y cómo está. ForestID reúne esa información en un solo lugar.</p>
    </div>

    <ul class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-x-14 gap-y-10">
        <li class="flex gap-4">
            <i data-lucide="tree-pine" class="w-7 h-7 text-emerald-700 shrink-0 mt-1"></i>
            <div>
                <h3 class="font-heading font-bold text-xl text-gray-900">Registro de cada árbol</h3>
                <p class="text-gray-600 mt-1">Especie, fecha de siembra, diámetro, altura y fotos. Cada individuo tiene su ficha y su placa con código QR.</p>
            </div>
        </li>
        <li class="flex gap-4">
            <i data-lucide="layers" class="w-7 h-7 text-emerald-700 shrink-0 mt-1"></i>
            <div>
                <h3 class="font-heading font-bold text-xl text-gray-900">Hectáreas forestadas</h3>
                <p class="text-gray-600 mt-1">Los árboles se agrupan por zona, para saber qué área ya está sembrada y cuál falta.</p>
            </div>
        </li>
        <li class="flex gap-4">
            <i data-lucide="map-pinned" class="w-7 h-7 text-emerald-700 shrink-0 mt-1"></i>
            <div>
                <h3 class="font-heading font-bold text-xl text-gray-900">Ubicación exacta</h3>
                <p class="text-gray-600 mt-1">Cada árbol queda georreferenciado en el mapa, con sus coordenadas.</p>
            </div>
        </li>
        <li class="flex gap-4">
            <i data-lucide="clipboard-check" class="w-7 h-7 text-emerald-700 shrink-0 mt-1"></i>
            <div>
                <h3 class="font-heading font-bold text-xl text-gray-900">Verificación en campo</h3>
                <p class="text-gray-600 mt-1">Los operarios revisan el estado de salud y registran riegos, podas y fertilizaciones. Así se sabe cuántos árboles siguen vivos.</p>
            </div>
        </li>
        <li class="flex gap-4">
            <i data-lucide="bar-chart-3" class="w-7 h-7 text-emerald-700 shrink-0 mt-1"></i>
            <div>
                <h3 class="font-heading font-bold text-xl text-gray-900">Reportes de avance</h3>
                <p class="text-gray-600 mt-1">Gráficos y cifras del total sembrado, el estado de los árboles y las zonas, listos para presentar.</p>
            </div>
        </li>
        <li class="flex gap-4">
            <i data-lucide="shield-check" class="w-7 h-7 text-emerald-700 shrink-0 mt-1"></i>
            <div>
                <h3 class="font-heading font-bold text-xl text-gray-900">Información privada</h3>
                <p class="text-gray-600 mt-1">Solo entra personal autorizado, con usuario y contraseña. Administración y operarios ven lo que les corresponde.</p>
            </div>
        </li>
    </ul>
</section>

<!-- CÓMO SE TRABAJA -->
<section class="bg-[#EEF5EA] py-20 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <h2 class="font-heading font-extrabold text-3xl sm:text-4xl text-gray-900 max-w-2xl leading-tight">Del vivero a la verificación</h2>
        <ol class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-10 md:gap-0 md:divide-x md:divide-emerald-900/15">
            <li class="md:pr-10">
                <span class="font-heading font-extrabold text-6xl text-emerald-300">1</span>
                <h3 class="font-heading font-bold text-xl text-gray-900 mt-2">Se siembra y se registra</h3>
                <p class="text-gray-700 mt-2">El operario registra el árbol en campo: especie, ubicación y medidas iniciales.</p>
            </li>
            <li class="md:px-10">
                <span class="font-heading font-extrabold text-6xl text-emerald-300">2</span>
                <h3 class="font-heading font-bold text-xl text-gray-900 mt-2">Se marca con su placa</h3>
                <p class="text-gray-700 mt-2">El árbol recibe una placa con código QR para identificarlo en los recorridos de seguimiento.</p>
            </li>
            <li class="md:pl-10">
                <span class="font-heading font-extrabold text-6xl text-emerald-300">3</span>
                <h3 class="font-heading font-bold text-xl text-gray-900 mt-2">Se verifica y se reporta</h3>
                <p class="text-gray-700 mt-2">Administración revisa conteos, hectáreas y estado de salud, y genera los reportes de avance.</p>
            </li>
        </ol>
    </div>
</section>

<!-- EQUIPO -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div class="foto-respaldo relative h-80 lg:h-[420px] rounded-3xl overflow-hidden flex items-center justify-center">
            <i data-lucide="trees" class="w-24 h-24 text-emerald-200/50"></i>
            <img src="<?= BASE_URL ?>assets/img/emincut.jpg" alt="Equipo de desarrollo de ForestID" class="absolute inset-0 w-full h-full object-cover" onerror="this.remove()">
        </div>

        <div class="space-y-5">
            <h2 class="font-heading font-extrabold text-3xl sm:text-4xl text-gray-900 leading-tight">Hecho a la medida de Emincut</h2>
            <p class="text-gray-700 leading-relaxed">
                Emincut es una empresa minera legal y certificada de El Bagre, Antioquia. Su reto era contar, sembrar y verificar las hectáreas forestadas después de la minería. ForestID se construyó para resolverlo.
            </p>
            <p class="text-gray-700 leading-relaxed">
                Lo desarrollamos <strong>Luis Ángel Muñoz, Gabriel Correa y Valentina Hernández</strong>, aprendices del SENA, Centro de Formación Minero Ambiental de El Bagre, durante nuestra etapa productiva en las instalaciones de Emincut.
            </p>
        </div>
    </div>
</section>

<!-- LLAMADO FINAL -->
<section class="bg-emerald-950 text-white py-16 px-4 text-center">
    <h2 class="font-heading font-extrabold text-3xl sm:text-4xl">¿Eres parte del equipo de Emincut?</h2>
    <p class="text-emerald-100/80 mt-3">Ingresa con tu usuario para registrar o consultar los árboles.</p>
    <a href="<?= BASE_URL ?>auth/login.php" class="btn-forest-primary rounded-full px-8 py-3.5 text-sm font-semibold mt-8 inline-flex">
        <i data-lucide="log-in" class="w-4 h-4"></i> Iniciar sesión
    </a>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>