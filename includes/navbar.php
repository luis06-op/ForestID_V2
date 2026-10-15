<?php
/**
 * ForestID / ForestSRD - Barra de Navegación Superior
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/auth_check.php';
$usuario_actual = obtener_usuario_actual();
$es_autenticado = esta_autenticado();
?>
<header class="bg-white/95 backdrop-blur border-b border-emerald-100 sticky top-0 z-40 shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            
            <!-- Marca y Logo -->
            <div class="flex items-center gap-6">
                <a href="<?= BASE_URL ?>index.php" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 rounded-xl bg-forest-dark flex items-center justify-center text-white shadow-md shadow-emerald-900/10 group-hover:scale-105 transition-transform">
                        <i data-lucide="trees" class="w-6 h-6 text-emerald-300"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-heading font-extrabold text-xl tracking-tight text-forest-dark leading-tight">
                            Forest<span class="text-forest-primary">ID</span>
                        </span>
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest -mt-0.5">
                            Emincut • El Bagre
                        </span>
                    </div>
                </a>

                <!-- Navegación Pública en Desktop -->
                <nav class="hidden md:flex items-center gap-1 pl-4 border-l border-gray-200">
                    <a href="<?= BASE_URL ?>publico/index.php" class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-600 hover:text-forest-dark hover:bg-forest-light transition-colors">
                        Inicio
                    </a>
                    <a href="<?= BASE_URL ?>publico/buscar.php" class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-600 hover:text-forest-dark hover:bg-forest-light transition-colors">
                        Inventario Forestal
                    </a>
                    <?php if ($es_autenticado && es_admin()): ?>
                        <a href="<?= BASE_URL ?>admin/dashboard.php" class="px-3 py-1.5 text-sm font-semibold rounded-lg text-forest-dark bg-emerald-100/60 border border-emerald-200 hover:bg-emerald-100 transition-colors">
                            Panel Administrador
                        </a>
                    <?php elseif ($es_autenticado && es_operario()): ?>
                        <a href="<?= BASE_URL ?>operario/dashboard.php" class="px-3 py-1.5 text-sm font-semibold rounded-lg text-forest-dark bg-emerald-100/60 border border-emerald-200 hover:bg-emerald-100 transition-colors">
                            Panel Operario
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Zona de Usuario / Accesos -->
            <div class="flex items-center gap-3">
                <?php if ($es_autenticado): ?>
                    <!-- Perfil y Rol -->
                    <div class="hidden sm:flex items-center gap-3 pl-3 pr-2 py-1.5 bg-gray-50 border border-gray-200 rounded-full">
                        <div class="flex flex-col text-right">
                            <span class="text-xs font-semibold text-gray-800 leading-tight">
                                <?= htmlspecialchars($usuario_actual['nombres'] . ' ' . $usuario_actual['apellidos']) ?>
                            </span>
                            <span class="text-[10px] font-medium text-emerald-700">
                                <?= htmlspecialchars($usuario_actual['nombre_rol']) ?>
                            </span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-forest-primary text-white flex items-center justify-center text-xs font-bold shadow-sm">
                            <?= strtoupper(substr($usuario_actual['nombres'], 0, 1) . substr($usuario_actual['apellidos'], 0, 1)) ?>
                        </div>
                    </div>

                    <!-- Botón Salir -->
                    <a href="<?= BASE_URL ?>auth/logout.php" title="Cerrar Sesión" class="p-2 rounded-xl text-gray-500 hover:text-rose-600 hover:bg-rose-50 transition-colors border border-gray-200">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>publico/buscar.php" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium text-gray-700 hover:text-forest-dark bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                        <i data-lucide="search" class="w-4 h-4 text-emerald-600"></i> Buscar Árbol
                    </a>
                    <a href="<?= BASE_URL ?>auth/login.php" class="btn-forest-primary text-xs sm:text-sm py-2 px-4 shadow-sm">
                        <i data-lucide="lock" class="w-4 h-4"></i> Iniciar Sesión
                    </a>
                <?php endif; ?>

                <!-- Toggle Móvil (Área táctil >= 48px) -->
                <button id="mobile-menu-btn" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" 
                        aria-label="Abrir Menú de Navegación"
                        class="md:hidden w-12 h-12 flex items-center justify-center rounded-xl text-gray-700 hover:bg-emerald-50 active:bg-emerald-100 transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Menú Móvil Desplegable (Drawer Accesible) -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-emerald-100 bg-white/98 backdrop-blur-md px-4 pt-3 pb-6 space-y-2 shadow-xl max-h-[calc(100vh-5rem)] overflow-y-auto">
        <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider px-3 pt-1">Navegación General</div>
        <a href="<?= BASE_URL ?>publico/index.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-semibold text-gray-700 hover:bg-emerald-50 active:bg-emerald-100">
            <i data-lucide="home" class="w-5 h-5 text-emerald-600"></i>
            <span>Inicio Público</span>
        </a>
        <a href="<?= BASE_URL ?>publico/buscar.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-semibold text-gray-700 hover:bg-emerald-50 active:bg-emerald-100">
            <i data-lucide="search" class="w-5 h-5 text-emerald-600"></i>
            <span>Explorador del Inventario</span>
        </a>

        <?php if ($es_autenticado && es_admin()): ?>
            <div class="pt-3 pb-1 border-t border-gray-100 text-[11px] font-bold text-emerald-800 uppercase tracking-wider px-3">
                Administración General
            </div>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-emerald-900 bg-emerald-50">
                <i data-lucide="layout-dashboard" class="w-5 h-5 text-emerald-700"></i>
                <span>Dashboard Principal</span>
            </a>
            <a href="<?= BASE_URL ?>admin/individuos/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-emerald-50">
                <i data-lucide="trees" class="w-5 h-5 text-emerald-600"></i>
                <span>Individuos Forestales</span>
            </a>
            <a href="<?= BASE_URL ?>admin/zonas/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-emerald-50">
                <i data-lucide="map-pin" class="w-5 h-5 text-emerald-600"></i>
                <span>Zonas Territoriales</span>
            </a>
            <a href="<?= BASE_URL ?>admin/qr/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-emerald-50">
                <i data-lucide="qr-code" class="w-5 h-5 text-emerald-600"></i>
                <span>Códigos QR & Placas</span>
            </a>
            <a href="<?= BASE_URL ?>admin/instituciones/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-emerald-50">
                <i data-lucide="building-2" class="w-5 h-5 text-emerald-600"></i>
                <span>Instituciones</span>
            </a>
            <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-emerald-50">
                <i data-lucide="users" class="w-5 h-5 text-emerald-600"></i>
                <span>Usuarios & Roles</span>
            </a>
            <a href="<?= BASE_URL ?>admin/reportes/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-emerald-50">
                <i data-lucide="bar-chart-3" class="w-5 h-5 text-emerald-600"></i>
                <span>Reportes & Métricas</span>
            </a>
            <a href="<?= BASE_URL ?>admin/logs/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-emerald-50">
                <i data-lucide="activity" class="w-5 h-5 text-emerald-600"></i>
                <span>Auditoría & Logs</span>
            </a>
        <?php elseif ($es_autenticado && es_operario()): ?>
            <div class="pt-3 pb-1 border-t border-gray-100 text-[11px] font-bold text-teal-800 uppercase tracking-wider px-3">
                Operaciones en Campo
            </div>
            <a href="<?= BASE_URL ?>operario/dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-teal-900 bg-teal-50">
                <i data-lucide="layout-dashboard" class="w-5 h-5 text-teal-700"></i>
                <span>Dashboard Operario</span>
            </a>
            <a href="<?= BASE_URL ?>operario/escanear.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-bold text-emerald-800 bg-emerald-100/70">
                <i data-lucide="camera" class="w-5 h-5 text-emerald-700"></i>
                <span>Escanear Placa QR (Cámara)</span>
            </a>
            <a href="<?= BASE_URL ?>operario/registrar_individuo.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-teal-50">
                <i data-lucide="plus-circle" class="w-5 h-5 text-teal-600"></i>
                <span>Alta Rápida en Campo</span>
            </a>
            <a href="<?= BASE_URL ?>operario/individuos/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-teal-50">
                <i data-lucide="trees" class="w-5 h-5 text-teal-600"></i>
                <span>Individuos de Mi Zona</span>
            </a>
            <a href="<?= BASE_URL ?>operario/actividades/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-teal-50">
                <i data-lucide="clipboard-check" class="w-5 h-5 text-teal-600"></i>
                <span>Registro de Mantenimiento</span>
            </a>
        <?php endif; ?>

        <?php if ($es_autenticado): ?>
            <div class="pt-3 border-t border-gray-100">
                <a href="<?= BASE_URL ?>auth/logout.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-semibold text-rose-600 hover:bg-rose-50">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</header>
