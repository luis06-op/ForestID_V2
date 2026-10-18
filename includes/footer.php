<?php
/**
 * ForestID / ForestSRD - Pie de Página Institucional
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */
?>
    <!-- Pie de página Institucional -->
    <footer class="mt-auto bg-gradient-to-b from-[#FAFAF7] to-emerald-950/90 text-gray-200 border-t border-emerald-900/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
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

                <!-- Columna 2: Equipo de Desarrollo y Memoria -->
                <div class="space-y-3">
                    <h4 class="font-heading font-semibold text-sm text-emerald-300 uppercase tracking-wider">Equipo de Proyecto</h4>
                    <ul class="text-xs text-emerald-100/75 space-y-1">
                        <li>• <strong class="text-white">Luis Angel Muñoz M.</strong> (Arquitectura)</li>
                        <li>• <strong class="text-white">Gabriel Jaime Correa C.</strong> (Backend)</li>
                        <li>• <strong class="text-white">Valentina</strong> (Verificación Calidad)</li>
                    </ul>

                    <!-- Conmemoración: Michael Rodríguez L. (100% Emincut: este proyecto es solo para Emincut) -->
                    <div class="mt-3 pt-3 border-t border-emerald-800/60 space-y-1.5">
                        <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-amber-300/90">
                            <i data-lucide="star" class="w-3 h-3"></i> In memoriam
                        </div>
                        <p class="text-xs leading-relaxed text-emerald-100/85">
                            <strong class="text-white">Michael Rodríguez L.</strong> (UI / QA)<br>
                            <span class="text-amber-200/80">2008 &ndash; 2026</span>
                        </p>
                        <p class="text-[11px] italic text-emerald-200/60">
                            Parte de este equipo para siempre. En agradecido recuerdo.
                        </p>
                    </div>
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

    <!-- Scripts globales -->
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    <script>
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
