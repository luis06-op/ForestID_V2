<?php
/**
 * ForestID / ForestSRD - Pie de Página Institucional
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */
?>
    <!-- Pie de página Institucional con margen inferior para Bottom Bar en móviles -->
    <footer class="mt-auto pb-20 md:pb-0 bg-gradient-to-b from-[#FAFAF7] to-emerald-950/90 text-gray-200 border-t border-emerald-900/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Columna 1: Marca y Propósito -->
                <div class="md:col-span-2 space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white shadow-md">
                            <i data-lucide="trees" class="w-5 h-5"></i>
                        </div>
                        <span class="font-heading font-extrabold text-xl tracking-tight text-white">Forest<span class="text-emerald-400">ID</span></span>
                    </div>
                    <p class="text-xs sm:text-sm text-emerald-100/80 max-w-md leading-relaxed">
                        Plataforma de gestión dendrológica, trazabilidad fitosanitaria e identificación inteligente de arborización con tecnología de códigos QR físicos para el municipio de <strong>El Bagre, Antioquia</strong> y el Bajo Cauca antioqueño.
                    </p>
                    <div class="flex items-center gap-2 text-xs text-emerald-300/90 font-medium">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span>El Bagre, Antioquia, Colombia — Sede Principal Emincut</span>
                    </div>
                </div>

                <!-- Columna 2: Marco Institucional -->
                <div class="space-y-2">
                    <h4 class="font-heading font-semibold text-sm text-emerald-300 uppercase tracking-wider">Marco Institucional</h4>
                    <p class="text-xs text-emerald-100/75 leading-relaxed">
                        Iniciativa desarrollada en el marco del programa <strong class="text-white">SENA Regional Antioquia – iRed Innovacción</strong> para el fomento de soluciones tecnológicas con impacto ambiental y productivo.
                    </p>
                    <div class="pt-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-medium bg-emerald-900/80 text-emerald-200 border border-emerald-700/50">
                            <i data-lucide="award" class="w-3 h-3 text-emerald-400"></i> Semillero iRed SENA
                        </span>
                    </div>
                </div>

                <!-- Columna 3: Equipo de Desarrollo -->
                <div class="space-y-2">
                    <h4 class="font-heading font-semibold text-sm text-emerald-300 uppercase tracking-wider">Equipo de Proyecto</h4>
                    <ul class="text-xs text-emerald-100/75 space-y-1">
                        <li>• <strong class="text-white">Luis Angel Muñoz M.</strong> (Arquitectura)</li>
                        <li>• <strong class="text-white">Gabriel Jaime Correa C.</strong> (Backend)</li>
                        <li>• <strong class="text-white">Michael Rodríguez L.</strong> (UI / QA)</li>
                        <li>• <strong class="text-white">Valentina</strong> (Verificación Calidad)</li>
                    </ul>
                </div>
            </div>

            <!-- Copyright inferior -->
            <div class="mt-8 pt-6 border-t border-emerald-900/60 flex flex-col sm:flex-row items-center justify-between text-xs text-emerald-200/60 gap-3">
                <div>
                    &copy; <?= date('Y') ?> <strong>Emincut S.A.S.</strong> — Todos los derechos reservados.
                </div>
                <div class="flex items-center gap-4">
                    <a href="<?= BASE_URL ?>publico/index.php" class="hover:text-emerald-300 transition-colors">Inicio Público</a>
                    <span>•</span>
                    <a href="<?= BASE_URL ?>publico/buscar.php" class="hover:text-emerald-300 transition-colors">Buscador</a>
                    <span>•</span>
                    <a href="<?= BASE_URL ?>auth/login.php" class="hover:text-emerald-300 transition-colors">Acceso Restringido</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Barra de Navegación Inferior Fija para Móviles (Bottom Tab Bar) -->
    <?php
    $actual_uri = $_SERVER['REQUEST_URI'] ?? '';
    ?>
    <nav aria-label="Navegación Móvil Rápida" class="fixed bottom-0 inset-x-0 z-50 bg-white/95 backdrop-blur-md border-t border-emerald-100 md:hidden flex justify-around items-center h-16 px-1 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
        <?php if (esta_autenticado() && es_admin()): ?>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'dashboard') !== false) ? 'text-emerald-700 font-extrabold' : 'text-gray-500 hover:text-emerald-700' ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5 mb-0.5"></i>
                <span>Inicio</span>
            </a>
            <a href="<?= BASE_URL ?>admin/individuos/index.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'individuos') !== false) ? 'text-emerald-700 font-extrabold' : 'text-gray-500 hover:text-emerald-700' ?>">
                <i data-lucide="trees" class="w-5 h-5 mb-0.5"></i>
                <span>Árboles</span>
            </a>
            <a href="<?= BASE_URL ?>admin/zonas/index.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'zonas') !== false) ? 'text-emerald-700 font-extrabold' : 'text-gray-500 hover:text-emerald-700' ?>">
                <i data-lucide="map-pin" class="w-5 h-5 mb-0.5"></i>
                <span>Zonas</span>
            </a>
            <a href="<?= BASE_URL ?>admin/qr/index.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'qr') !== false) ? 'text-emerald-700 font-extrabold' : 'text-gray-500 hover:text-emerald-700' ?>">
                <i data-lucide="qr-code" class="w-5 h-5 mb-0.5"></i>
                <span>QR</span>
            </a>
            <button type="button" onclick="const m=document.getElementById('mobile-menu'); if(m){ m.classList.toggle('hidden'); window.scrollTo({top:0, behavior:'smooth'}); }" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold text-gray-500 hover:text-emerald-700">
                <i data-lucide="menu" class="w-5 h-5 mb-0.5"></i>
                <span>Menú</span>
            </button>
        <?php elseif (esta_autenticado() && es_operario()): ?>
            <a href="<?= BASE_URL ?>operario/dashboard.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'dashboard') !== false) ? 'text-teal-700 font-extrabold' : 'text-gray-500 hover:text-teal-700' ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5 mb-0.5"></i>
                <span>Inicio</span>
            </a>
            <a href="<?= BASE_URL ?>operario/individuos/index.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'individuos') !== false) ? 'text-teal-700 font-extrabold' : 'text-gray-500 hover:text-teal-700' ?>">
                <i data-lucide="trees" class="w-5 h-5 mb-0.5"></i>
                <span>Mi Zona</span>
            </a>
            <div class="flex-1 flex justify-center -mt-5">
                <a href="<?= BASE_URL ?>operario/escanear.php" aria-label="Escanear Placa QR" class="w-14 h-14 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white flex flex-col items-center justify-center shadow-lg border-4 border-white active:scale-95 transition-transform">
                    <i data-lucide="camera" class="w-6 h-6"></i>
                </a>
            </div>
            <a href="<?= BASE_URL ?>operario/registrar_individuo.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'registrar_individuo') !== false) ? 'text-teal-700 font-extrabold' : 'text-gray-500 hover:text-teal-700' ?>">
                <i data-lucide="plus-circle" class="w-5 h-5 mb-0.5"></i>
                <span>Alta GPS</span>
            </a>
            <a href="<?= BASE_URL ?>operario/actividades/index.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'actividades') !== false) ? 'text-teal-700 font-extrabold' : 'text-gray-500 hover:text-teal-700' ?>">
                <i data-lucide="clipboard-check" class="w-5 h-5 mb-0.5"></i>
                <span>Labores</span>
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>publico/index.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'publico/index.php') !== false) ? 'text-emerald-700 font-extrabold' : 'text-gray-500 hover:text-emerald-700' ?>">
                <i data-lucide="home" class="w-5 h-5 mb-0.5"></i>
                <span>Inicio</span>
            </a>
            <a href="<?= BASE_URL ?>publico/buscar.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'buscar') !== false) ? 'text-emerald-700 font-extrabold' : 'text-gray-500 hover:text-emerald-700' ?>">
                <i data-lucide="search" class="w-5 h-5 mb-0.5"></i>
                <span>Explorar</span>
            </a>
            <a href="<?= BASE_URL ?>auth/login.php" class="flex-1 min-w-[48px] min-h-[48px] flex flex-col items-center justify-center text-[10px] font-bold <?= (strpos($actual_uri, 'login') !== false) ? 'text-emerald-700 font-extrabold' : 'text-gray-500 hover:text-emerald-700' ?>">
                <i data-lucide="lock" class="w-5 h-5 mb-0.5"></i>
                <span>Acceso</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Scripts globales -->
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
