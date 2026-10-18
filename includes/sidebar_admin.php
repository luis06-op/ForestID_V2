<?php
/**
 * ForestID / ForestSRD - Menú Lateral para Administrador (AdminEmincut)
 * Identidad visual Emincut: unido a la barra superior (verde noche + ámbar).
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

$pagina_actual = basename($_SERVER['PHP_SELF']);
$dir_actual = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="emi-sidebar w-64 text-white flex flex-col shrink-0 md:min-h-[calc(100vh-4rem)]">
    <!-- Encabezado Sidebar -->
    <div class="p-5 border-b" style="border-color:rgba(255,255,255,.1)">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(242,182,50,.15); border:1px solid rgba(242,182,50,.35); color:var(--emi-amarillo)">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="emi-display font-bold text-sm text-white tracking-wide">Panel de Control</h3>
                <span class="inline-block text-[11px] font-bold px-2 py-0.5 rounded-md" style="background:var(--emi-amarillo); color:var(--emi-verde-noche)">
                    Admin Emincut
                </span>
            </div>
        </div>
    </div>

    <!-- Navegación Principal -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="emi-enlace <?= ($pagina_actual === 'dashboard.php') ? 'activo' : '' ?>">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            <span>Dashboard</span>
        </a>

        <div class="emi-seccion">Inventario & Territorio</div>

        <a href="<?= BASE_URL ?>admin/individuos/index.php" class="emi-enlace <?= ($dir_actual === 'individuos') ? 'activo' : '' ?>">
            <i data-lucide="trees" class="w-5 h-5"></i>
            <span>Individuos Forestales</span>
        </a>

        <a href="<?= BASE_URL ?>admin/zonas/index.php" class="emi-enlace <?= ($dir_actual === 'zonas') ? 'activo' : '' ?>">
            <i data-lucide="map-pin" class="w-5 h-5"></i>
            <span>Zonas Territoriales</span>
        </a>

        <a href="<?= BASE_URL ?>admin/qr/index.php" class="emi-enlace <?= ($dir_actual === 'qr') ? 'activo' : '' ?>">
            <i data-lucide="qr-code" class="w-5 h-5"></i>
            <span>Códigos QR & Placas</span>
        </a>

        <div class="emi-seccion">Gestión</div>

        <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="emi-enlace <?= ($dir_actual === 'usuarios') ? 'activo' : '' ?>">
            <i data-lucide="users" class="w-5 h-5"></i>
            <span>Usuarios & Roles</span>
        </a>

        <div class="emi-seccion">Inteligencia & Auditoría</div>

        <a href="<?= BASE_URL ?>admin/reportes/index.php" class="emi-enlace <?= ($dir_actual === 'reportes') ? 'activo' : '' ?>">
            <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
            <span>Reportes & Métricas</span>
        </a>

        <a href="<?= BASE_URL ?>admin/logs/index.php" class="emi-enlace <?= ($dir_actual === 'logs') ? 'activo' : '' ?>">
            <i data-lucide="activity" class="w-5 h-5"></i>
            <span>Auditoría & Logs</span>
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
