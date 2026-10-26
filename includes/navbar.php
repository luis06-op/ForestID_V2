<?php
/**
 * ForestID / ForestSRD - Barra de Navegación Superior
 * Identidad visual Emincut: unida visualmente al sidebar de los paneles.
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/auth_check.php';
$usuario_actual = obtener_usuario_actual();
$es_autenticado = esta_autenticado();
?>
<header class="emi-navbar sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">

            <!-- Marca y Logo -->
            <div class="flex items-center gap-6">
                <a href="<?= BASE_URL ?>" class="flex items-center gap-2.5 group">
                    <span class="bg-white rounded-xl p-1.5 shadow-md shadow-black/20 group-hover:scale-105 transition-transform block w-[54px] h-[40px] flex items-center justify-center">
                        <img src="<?= BASE_URL ?>assets/img/logo_emincut_oficial.svg" alt="Emincut" class="w-full h-full object-contain">
                    </span>
                    <div class="flex flex-col">
                        <span class="emi-display font-bold text-xl tracking-wide text-white leading-tight">
                            Forest<span style="color:var(--emi-amarillo)">ID</span>
                        </span>
                        <span class="text-[10px] font-semibold uppercase tracking-widest -mt-0.5" style="color:rgba(255,255,255,.55)">
                            Emincut • El Bagre
                        </span>
                    </div>
                </a>

                <!-- Navegación en Desktop -->
                <nav class="hidden md:flex items-center gap-1 pl-4 border-l" style="border-color:rgba(255,255,255,.15)">
                    <a href="<?= BASE_URL ?>publico/index.php" class="px-3 py-1.5 text-sm font-medium rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        Inicio
                    </a>
                    <a href="<?= BASE_URL ?>publico/buscar.php" class="px-3 py-1.5 text-sm font-medium rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                        Inventario Forestal
                    </a>
                    <?php if ($es_autenticado && es_admin()): ?>
                        <a href="<?= BASE_URL ?>admin/dashboard.php" class="px-3 py-1.5 text-sm font-bold rounded-lg transition-colors" style="background:var(--emi-amarillo); color:var(--emi-verde-noche)">
                            Panel Administrador
                        </a>
                    <?php elseif ($es_autenticado && es_operario()): ?>
                        <a href="<?= BASE_URL ?>operario/dashboard.php" class="px-3 py-1.5 text-sm font-bold rounded-lg transition-colors" style="background:var(--emi-amarillo); color:var(--emi-verde-noche)">
                            Panel Operario
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Zona de Usuario / Accesos -->
            <div class="flex items-center gap-3">
                <?php if ($es_autenticado): ?>
                    <!-- Perfil y Rol -->
                    <div class="hidden sm:flex items-center gap-3 pl-3 pr-2 py-1.5 rounded-full border" style="background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.15)">
                        <div class="flex flex-col text-right">
                            <span class="text-xs font-semibold text-white leading-tight">
                                <?= htmlspecialchars($usuario_actual['nombres'] . ' ' . $usuario_actual['apellidos']) ?>
                            </span>
                            <span class="text-[10px] font-semibold" style="color:var(--emi-amarillo)">
                                <?= htmlspecialchars($usuario_actual['nombre_rol']) ?>
                            </span>
                        </div>
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shadow-sm" style="background:var(--emi-amarillo); color:var(--emi-verde-noche)">
                            <?= htmlspecialchars(strtoupper(substr($usuario_actual['nombres'], 0, 1) . substr($usuario_actual['apellidos'], 0, 1))) ?>
                        </div>
                    </div>

                    <!-- Botón Salir -->
                    <form method="POST" action="<?= BASE_URL ?>auth/logout.php" class="inline-flex">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(crear_token_csrf()) ?>">
                        <button type="submit" title="Cerrar Sesión" class="p-2 rounded-xl transition-colors border" style="color:rgba(255,255,255,.7); border-color:rgba(255,255,255,.2)">
                            <i data-lucide="log-out" class="w-5 h-5"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>publico/buscar.php" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-xl transition-all text-white/85 hover:bg-white/10 border" style="border-color:rgba(255,255,255,.2)">
                        <i data-lucide="search" class="w-4 h-4" style="color:var(--emi-amarillo)"></i> Buscar Árbol
                    </a>
                    <a href="<?= BASE_URL ?>auth/login.php" class="emi-btn emi-btn-sol text-xs sm:text-sm py-2 px-4">
                        <span class="inline-flex items-center gap-1.5"><i data-lucide="lock" class="w-4 h-4"></i> Iniciar Sesión</span>
                    </a>
                <?php endif; ?>

                <!-- Toggle Móvil: abre el drawer del sidebar (si existe) o el menú desplegable -->
                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-xl text-white hover:bg-white/10" aria-label="Abrir menú">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Menú Móvil Desplegable (solo cuando NO hay sidebar en la página) -->
    <div id="mobile-menu" class="hidden md:hidden border-t px-4 pt-3 pb-4 space-y-2" style="border-color:rgba(255,255,255,.1); background:var(--emi-verde-noche)">
        <a href="<?= BASE_URL ?>publico/index.php" class="block px-3 py-2 rounded-lg text-sm font-medium text-white/85 hover:bg-white/10">
            Inicio Público
        </a>
        <a href="<?= BASE_URL ?>publico/buscar.php" class="block px-3 py-2 rounded-lg text-sm font-medium text-white/85 hover:bg-white/10">
            Inventario Forestal
        </a>
        <?php if ($es_autenticado && es_admin()): ?>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="block px-3 py-2 rounded-lg text-sm font-bold" style="background:var(--emi-amarillo); color:var(--emi-verde-noche)">
                Panel Administrador
            </a>
            <a href="<?= BASE_URL ?>admin/individuos/index.php" class="block px-3 py-2 rounded-lg text-sm text-white/75 hover:bg-white/10 pl-6">
                Gestión de Individuos
            </a>
            <a href="<?= BASE_URL ?>admin/qr/index.php" class="block px-3 py-2 rounded-lg text-sm text-white/75 hover:bg-white/10 pl-6">
                Gestor e Impresión QR
            </a>
        <?php elseif ($es_autenticado && es_operario()): ?>
            <a href="<?= BASE_URL ?>operario/dashboard.php" class="block px-3 py-2 rounded-lg text-sm font-bold" style="background:var(--emi-amarillo); color:var(--emi-verde-noche)">
                Panel Operario
            </a>
            <a href="<?= BASE_URL ?>operario/escanear.php" class="block px-3 py-2 rounded-lg text-sm text-white/75 hover:bg-white/10 pl-6">
                Escanear Placa QR (Cámara)
            </a>
            <a href="<?= BASE_URL ?>operario/registrar_individuo.php" class="block px-3 py-2 rounded-lg text-sm text-white/75 hover:bg-white/10 pl-6">
                Registrar en Campo
            </a>
        <?php endif; ?>
    </div>
</header>

<!-- Overlay del drawer + lógica responsive (sidebar unido a la barra superior) -->
<div id="emi-overlay" class="emi-overlay md:hidden"></div>
<script>
(function () {
    // El drawer solo existe en páginas con sidebar (paneles)
    const sidebar = document.querySelector('.emi-sidebar');
    const overlay = document.getElementById('emi-overlay');
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');

    function cerrarDrawer() {
        if (!sidebar) return;
        sidebar.classList.remove('abierta');
        overlay && overlay.classList.remove('visible');
        document.body.style.overflow = '';
    }

    if (btn) {
        btn.addEventListener('click', function () {
            if (sidebar) {
                // Página de panel: abrir/cerrar drawer lateral unido
                const abierta = sidebar.classList.toggle('abierta');
                overlay && overlay.classList.toggle('visible', abierta);
                document.body.style.overflow = abierta ? 'hidden' : '';
            } else {
                // Página sin panel: menú desplegable clásico
                menu && menu.classList.toggle('hidden');
            }
        });
    }
    if (overlay) overlay.addEventListener('click', cerrarDrawer);

    // Cerrar con Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrarDrawer();
    });
    // Cerrar al navegar desde el drawer
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            if (e.target.closest('a')) setTimeout(cerrarDrawer, 120);
        });
    }
})();
</script>
