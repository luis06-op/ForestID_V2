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

                <!-- Toggle Móvil -->
                <button id="mobile-menu-btn" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="md:hidden p-2 rounded-xl text-gray-600 hover:bg-gray-100">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Menú Móvil Desplegable -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-gray-100 bg-white px-4 pt-3 pb-4 space-y-2">
        <a href="<?= BASE_URL ?>publico/index.php" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-emerald-50">
            Inicio Público
        </a>
        <a href="<?= BASE_URL ?>publico/buscar.php" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-emerald-50">
            Inventario Forestal
        </a>
        <?php if ($es_autenticado && es_admin()): ?>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-emerald-800 bg-emerald-50">
                Panel Administrador
            </a>
            <a href="<?= BASE_URL ?>admin/individuos/index.php" class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-emerald-50 pl-6">
                Gestión de Individuos
            </a>
            <a href="<?= BASE_URL ?>admin/qr/index.php" class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-emerald-50 pl-6">
                Gestor e Impresión QR
            </a>
        <?php elseif ($es_autenticado && es_operario()): ?>
            <a href="<?= BASE_URL ?>operario/dashboard.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-emerald-800 bg-emerald-50">
                Panel Operario
            </a>
            <a href="<?= BASE_URL ?>operario/escanear.php" class="block px-3 py-2 rounded-lg text-sm text-emerald-700 hover:bg-emerald-50 pl-6">
                Escanear Placa QR (Cámara)
            </a>
            <a href="<?= BASE_URL ?>operario/registrar_individuo.php" class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-emerald-50 pl-6">
                Registrar en Campo
            </a>
        <?php endif; ?>
    </div>
</header>
