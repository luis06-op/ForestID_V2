<?php
/**
 * ForestID / ForestSRD - Pie de Página Institucional
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */
?>
    <!-- Pie de página Institucional — fondo sólido, paleta Emincut (verde / amarillo / blanco) -->
    <footer class="mt-auto border-t-4" style="background-color:#22371C; border-color:#E9A22E; color:#FFFFFF">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Columna 1: Marca y Propósito -->
                <div class="md:col-span-2 space-y-4">
                    <!-- Logo oficial Emincut -->
                    <div class="bg-white rounded-2xl p-2.5 shadow-lg shadow-black/20 w-[150px] h-[74px] flex items-center justify-center">
                        <img src="<?= BASE_URL ?>assets/img/logo_emincut_oficial.svg" alt="Emincut" class="w-full h-full object-contain">
                    </div>
                    <p class="text-sm leading-relaxed text-white/75 max-w-md">
                        <strong style="color:#F2B632">ForestID</strong> es la aplicación web de <strong class="text-white">Emincut S.A.S.</strong> para gestionar el inventario forestal de la empresa —árboles y plantas ornamentales— en <strong class="text-white">Antioquia, Colombia</strong>.
                    </p>
                    <p class="text-sm leading-relaxed text-white/75 max-w-md">
                        Cada árbol cuenta con una placa física con <strong style="color:#F2B632">código QR</strong> que cualquier persona puede escanear para consultar su ficha, <span class="text-white/90">sin necesidad de iniciar sesión</span>.
                    </p>
                    <div class="flex items-center gap-2 text-xs text-white/60 font-medium">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5" style="color:#F2B632"></i>
                        <span>El Bagre, Antioquia, Colombia — Sede Principal Emincut</span>
                    </div>
                </div>

                <!-- Columna 2: Equipo de Desarrollo y Memoria -->
                <div class="space-y-3">
                    <h4 class="font-heading font-semibold text-sm uppercase tracking-wider" style="color:#F2B632">Equipo de Proyecto</h4>
                    <ul class="text-xs text-white/75 space-y-1">
                        <li>• <strong class="text-white">Luis Angel Muñoz M.</strong> (Arquitectura)</li>
                        <li>• <strong class="text-white">Gabriel Jaime Correa C.</strong> (Backend)</li>
                        <li>• <strong class="text-white">Valentina</strong> (Verificación Calidad)</li>
                    </ul>
                    <p class="text-[11px] text-white/45 leading-relaxed">
                        Desarrollado por aprendices del SENA, Centro de Formación Minero Ambiental de El Bagre, en etapa productiva con Emincut.
                    </p>

                    <!-- Conmemoración: Michael Rodríguez L. -->
                    <div class="mt-3 pt-3 border-t space-y-1.5" style="border-color:rgba(255,255,255,.15)">
                        <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider" style="color:#F2B632">
                            <i data-lucide="star" class="w-3 h-3"></i> In memoriam
                        </div>
                        <p class="text-xs leading-relaxed text-white/85">
                            <strong class="text-white">Michael Rodríguez L.</strong> (UI / QA)<br>
                            <span style="color:#E9A22E">2008 &ndash; 2026</span>
                        </p>
                        <p class="text-[11px] italic text-white/50">
                            Parte de este equipo para siempre. En agradecido recuerdo.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Copyright inferior -->
            <div class="mt-8 pt-6 border-t flex flex-col sm:flex-row items-center justify-between text-xs text-white/55 gap-3" style="border-color:rgba(255,255,255,.15)">
                <div>
                    &copy; <?= date('Y') ?> <strong class="text-white">Emincut S.A.S.</strong> — Todos los derechos reservados.
                </div>
                <div class="flex items-center gap-4">
                    <a href="<?= BASE_URL ?>publico/index.php" class="hover:opacity-80 transition-opacity" style="color:#F2B632">Inicio Público</a>
                    <span class="text-white/30">•</span>
                    <a href="<?= BASE_URL ?>publico/buscar.php" class="hover:opacity-80 transition-opacity" style="color:#F2B632">Buscador QR</a>
                    <span class="text-white/30">•</span>
                    <a href="<?= BASE_URL ?>auth/login.php" class="hover:opacity-80 transition-opacity" style="color:#F2B632">Acceso Restringido</a>
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
