<?php
/**
 * ForestID / ForestSRD - Menú Lateral para Personal Operativo (OperarioEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

$pagina_actual = basename($_SERVER['PHP_SELF']);
$dir_actual = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="w-64 bg-teal-950 text-teal-100 flex flex-col shrink-0 min-h-[calc(100vh-4rem)] border-r border-teal-900/50 shadow-lg">
    <!-- Encabezado Sidebar Operario -->
    <div class="p-5 border-b border-teal-900/60">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-teal-600/30 border border-teal-500/40 flex items-center justify-center text-teal-300">
                <i data-lucide="wrench" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-sm text-white tracking-wide">Panel de Campo</h3>
                <span class="inline-block text-[11px] font-semibold text-teal-300 bg-teal-900/80 px-2 py-0.5 rounded-md">
                    Técnico Operario
                </span>
            </div>
        </div>
    </div>

    <!-- Navegación Principal Operario -->
    <nav class="flex-1 px-3 py-5 space-y-1.5 overflow-y-auto">
        <a href="<?= BASE_URL ?>operario/dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($pagina_actual === 'dashboard.php') ? 'bg-teal-700 text-white font-semibold shadow-md' : 'text-teal-200/80 hover:bg-teal-900/60 hover:text-white' ?>">
            <i data-lucide="layout-dashboard" class="w-5 h-5 text-teal-400"></i>
            <span>Dashboard Operario</span>
        </a>

        <div class="pt-3 pb-1.5 px-3.5 text-[10px] font-bold uppercase tracking-wider text-teal-400/60">
            Acciones de Campo
        </div>

        <a href="<?= BASE_URL ?>operario/escanear.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all bg-emerald-600/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-600/30 hover:text-white">
            <i data-lucide="camera" class="w-5 h-5 text-emerald-400 animate-pulse"></i>
            <span>Escanear Placa QR</span>
        </a>

        <a href="<?= BASE_URL ?>operario/registrar_individuo.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($pagina_actual === 'registrar_individuo.php') ? 'bg-teal-700 text-white font-semibold shadow-md' : 'text-teal-200/80 hover:bg-teal-900/60 hover:text-white' ?>">
            <i data-lucide="plus-circle" class="w-5 h-5 text-teal-400"></i>
            <span>Alta Rápida en Campo</span>
        </a>

        <a href="<?= BASE_URL ?>operario/individuos/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($dir_actual === 'individuos') ? 'bg-teal-700 text-white font-semibold shadow-md' : 'text-teal-200/80 hover:bg-teal-900/60 hover:text-white' ?>">
            <i data-lucide="trees" class="w-5 h-5 text-teal-400"></i>
            <span>Individuos de Mi Zona</span>
        </a>

        <a href="<?= BASE_URL ?>operario/actividades/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($dir_actual === 'actividades') ? 'bg-teal-700 text-white font-semibold shadow-md' : 'text-teal-200/80 hover:bg-teal-900/60 hover:text-white' ?>">
            <i data-lucide="clipboard-check" class="w-5 h-5 text-teal-400"></i>
            <span>Registro de Mantenimiento</span>
        </a>
    </nav>

    <!-- Enlace al portal público -->
    <div class="p-4 border-t border-teal-900/60 bg-teal-950/40">
        <a href="<?= BASE_URL ?>publico/index.php" target="_blank" class="flex items-center justify-between px-3 py-2 rounded-lg bg-teal-900/50 hover:bg-teal-800 text-xs text-teal-200 hover:text-white transition-colors">
            <span class="flex items-center gap-2">
                <i data-lucide="globe" class="w-4 h-4 text-teal-400"></i>
                <span>Ver Portal Público</span>
            </span>
            <i data-lucide="external-link" class="w-3.5 h-3.5 text-teal-400/80"></i>
        </a>
    </div>
</aside>
