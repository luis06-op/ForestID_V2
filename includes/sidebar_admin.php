<?php
/**
 * ForestID / ForestSRD - Menú Lateral para Administrador (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

$pagina_actual = basename($_SERVER['PHP_SELF']);
$dir_actual = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="hidden md:flex w-64 bg-emerald-950 text-emerald-100 flex-col shrink-0 min-h-[calc(100vh-4rem)] border-r border-emerald-900/50 shadow-lg">
    <!-- Encabezado Sidebar -->
    <div class="p-5 border-b border-emerald-900/60">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-600/30 border border-emerald-500/40 flex items-center justify-center text-emerald-300">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-sm text-white tracking-wide">Panel de Control</h3>
                <span class="inline-block text-[11px] font-semibold text-emerald-400 bg-emerald-900/80 px-2 py-0.5 rounded-md">
                    Admin Emincut
                </span>
            </div>
        </div>
    </div>

    <!-- Navegación Principal -->
    <nav class="flex-1 px-3 py-5 space-y-1.5 overflow-y-auto">
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($pagina_actual === 'dashboard.php') ? 'bg-emerald-700 text-white font-semibold shadow-md shadow-emerald-900/20' : 'text-emerald-200/80 hover:bg-emerald-900/60 hover:text-white' ?>">
            <i data-lucide="layout-dashboard" class="w-5 h-5 text-emerald-400"></i>
            <span>Dashboard</span>
        </a>

        <div class="pt-3 pb-1.5 px-3.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400/60">
            Inventario & Territorio
        </div>

        <a href="<?= BASE_URL ?>admin/individuos/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($dir_actual === 'individuos') ? 'bg-emerald-700 text-white font-semibold shadow-md shadow-emerald-900/20' : 'text-emerald-200/80 hover:bg-emerald-900/60 hover:text-white' ?>">
            <i data-lucide="trees" class="w-5 h-5 text-emerald-400"></i>
            <span>Individuos Forestales</span>
        </a>

        <a href="<?= BASE_URL ?>admin/zonas/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($dir_actual === 'zonas') ? 'bg-emerald-700 text-white font-semibold shadow-md shadow-emerald-900/20' : 'text-emerald-200/80 hover:bg-emerald-900/60 hover:text-white' ?>">
            <i data-lucide="map-pin" class="w-5 h-5 text-emerald-400"></i>
            <span>Zonas Territoriales</span>
        </a>

        <a href="<?= BASE_URL ?>admin/qr/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($dir_actual === 'qr') ? 'bg-emerald-700 text-white font-semibold shadow-md shadow-emerald-900/20' : 'text-emerald-200/80 hover:bg-emerald-900/60 hover:text-white' ?>">
            <i data-lucide="qr-code" class="w-5 h-5 text-emerald-400"></i>
            <span>Códigos QR & Placas</span>
        </a>

        <div class="pt-3 pb-1.5 px-3.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400/60">
            Gestión Institucional
        </div>

        <a href="<?= BASE_URL ?>admin/instituciones/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($dir_actual === 'instituciones') ? 'bg-emerald-700 text-white font-semibold shadow-md shadow-emerald-900/20' : 'text-emerald-200/80 hover:bg-emerald-900/60 hover:text-white' ?>">
            <i data-lucide="building-2" class="w-5 h-5 text-emerald-400"></i>
            <span>Instituciones</span>
        </a>

        <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($dir_actual === 'usuarios') ? 'bg-emerald-700 text-white font-semibold shadow-md shadow-emerald-900/20' : 'text-emerald-200/80 hover:bg-emerald-900/60 hover:text-white' ?>">
            <i data-lucide="users" class="w-5 h-5 text-emerald-400"></i>
            <span>Usuarios & Roles</span>
        </a>

        <div class="pt-3 pb-1.5 px-3.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400/60">
            Inteligencia & Auditoría
        </div>

        <a href="<?= BASE_URL ?>admin/reportes/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($dir_actual === 'reportes') ? 'bg-emerald-700 text-white font-semibold shadow-md shadow-emerald-900/20' : 'text-emerald-200/80 hover:bg-emerald-900/60 hover:text-white' ?>">
            <i data-lucide="bar-chart-3" class="w-5 h-5 text-emerald-400"></i>
            <span>Reportes & Métricas</span>
        </a>

        <a href="<?= BASE_URL ?>admin/logs/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($dir_actual === 'logs') ? 'bg-emerald-700 text-white font-semibold shadow-md shadow-emerald-900/20' : 'text-emerald-200/80 hover:bg-emerald-900/60 hover:text-white' ?>">
            <i data-lucide="activity" class="w-5 h-5 text-emerald-400"></i>
            <span>Auditoría & Logs</span>
        </a>
    </nav>

    <!-- Enlace al portal público -->
    <div class="p-4 border-t border-emerald-900/60 bg-emerald-950/40">
        <a href="<?= BASE_URL ?>publico/index.php" target="_blank" class="flex items-center justify-between px-3 py-2 rounded-lg bg-emerald-900/50 hover:bg-emerald-800 text-xs text-emerald-200 hover:text-white transition-colors">
            <span class="flex items-center gap-2">
                <i data-lucide="globe" class="w-4 h-4 text-emerald-400"></i>
                <span>Ver Portal Público</span>
            </span>
            <i data-lucide="external-link" class="w-3.5 h-3.5 text-emerald-400/80"></i>
        </a>
    </div>
</aside>
