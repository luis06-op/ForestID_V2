<?php
/**
 * ForestID / ForestSRD - Menú Lateral para Personal Operativo (OperarioEmincut)
 * Identidad visual Emincut: unido a la barra superior (verde noche + ámbar).
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

$pagina_actual = basename($_SERVER['PHP_SELF']);
$dir_actual = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="emi-sidebar w-64 text-white flex flex-col shrink-0 md:min-h-[calc(100vh-4rem)]">
    <!-- Encabezado Sidebar Operario -->
    <div class="p-5 border-b" style="border-color:rgba(255,255,255,.1)">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(242,182,50,.15); border:1px solid rgba(242,182,50,.35); color:var(--emi-amarillo)">
                <i data-lucide="wrench" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="emi-display font-bold text-sm text-white tracking-wide">Panel de Campo</h3>
                <span class="inline-block text-[11px] font-bold px-2 py-0.5 rounded-md" style="background:var(--emi-amarillo); color:var(--emi-verde-noche)">
                    Técnico Operario
                </span>
            </div>
        </div>
    </div>

    <!-- Navegación Principal Operario -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <a href="<?= BASE_URL ?>operario/dashboard.php" class="emi-enlace <?= ($pagina_actual === 'dashboard.php') ? 'activo' : '' ?>">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            <span>Dashboard Operario</span>
        </a>

        <div class="emi-seccion">Acciones de Campo</div>

        <a href="<?= BASE_URL ?>operario/escanear.php" class="emi-enlace <?= ($pagina_actual === 'escanear.php') ? 'activo' : '' ?>">
            <i data-lucide="camera" class="w-5 h-5"></i>
            <span>Escanear Placa QR</span>
        </a>

        <a href="<?= BASE_URL ?>operario/registrar_individuo.php" class="emi-enlace <?= ($pagina_actual === 'registrar_individuo.php') ? 'activo' : '' ?>">
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
            <span>Alta Rápida en Campo</span>
        </a>

        <a href="<?= BASE_URL ?>operario/individuos/index.php" class="emi-enlace <?= ($dir_actual === 'individuos') ? 'activo' : '' ?>">
            <i data-lucide="trees" class="w-5 h-5"></i>
            <span>Individuos de Mi Zona</span>
        </a>

        <a href="<?= BASE_URL ?>operario/actividades/index.php" class="emi-enlace <?= ($dir_actual === 'actividades') ? 'activo' : '' ?>">
            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
            <span>Registro de Mantenimiento</span>
        </a>
    </nav>

    <!-- Enlace al portal público -->
    <div class="p-4 border-t" style="border-color:rgba(255,255,255,.1)">
        <a href="<?= BASE_URL ?>publico/index.php" target="_blank" class="emi-enlace justify-between text-xs" style="background:rgba(255,255,255,.06)">
            <span class="flex items-center gap-2">
                <i data-lucide="globe" class="w-4 h-4" style="color:var(--emi-amarillo)"></i>
                <span>Ver Portal Público</span>
            </span>
            <i data-lucide="external-link" class="w-3.5 h-3.5 opacity-70"></i>
        </a>
    </div>
</aside>
