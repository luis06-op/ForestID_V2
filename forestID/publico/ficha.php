<?php
/**
 * ForestID / ForestSRD - Ficha Técnica Pública del Individuo Forestal (Mobile-First)
 * Acceso directo mediante escaneo de código QR (sin autenticación requerida)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_check.php';

$codigo = $_GET['codigo'] ?? '';
$id     = $_GET['id'] ?? '';
$codigo = trim($codigo);
$id     = trim($id);

$pdo = getDBConnection();
$arbol = null;
$evaluaciones = [];
$actividades = [];

if (!empty($codigo) || !empty($id)) {
    $sql = "
        SELECT i.*, z.nombre_zona, z.municipio AS zona_municipio, z.departamento AS zona_departamento,
               inst.nombre_institucion, inst.telefono_contacto AS inst_telefono,
               e.estado AS estado_salud, e.diagnostico AS diagnostico_salud, e.recomendacion AS recomendacion_salud, e.fecha_evaluacion,
               u.nombres AS evaluador_nombres, u.apellidos AS evaluador_apellidos
        FROM `individuos_forestales` i
        INNER JOIN `zonas` z ON i.id_zona = z.id_zona
        INNER JOIN `instituciones` inst ON i.id_institucion = inst.id_institucion
        LEFT JOIN `estados_fitosanitarios` e ON i.id_estado_fitosanitario_actual = e.id_estado
        LEFT JOIN `usuarios` u ON e.evaluado_por = u.id_usuario
        WHERE (i.codigo_placa = :codigo OR i.id_individuo = :id) AND i.estado_registro = 1
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':codigo' => $codigo, ':id' => $id]);
    $arbol = $stmt->fetch();

    if ($arbol) {
        // Cargar historial de estados fitosanitarios
        $stmtEval = $pdo->prepare("
            SELECT e.*, u.nombres AS evaluador_nombres, u.apellidos AS evaluador_apellidos
            FROM `estados_fitosanitarios` e
            INNER JOIN `usuarios` u ON e.evaluado_por = u.id_usuario
            WHERE e.id_individuo = :id
            ORDER BY e.fecha_evaluacion DESC
        ");
        $stmtEval->execute([':id' => $arbol['id_individuo']]);
        $evaluaciones = $stmtEval->fetchAll();

        // Cargar historial de actividades de mantenimiento
        $stmtAct = $pdo->prepare("
            SELECT a.*, u.nombres AS responsable_nombres, u.apellidos AS responsable_apellidos
            FROM `actividades_mantenimiento` a
            LEFT JOIN `usuarios` u ON a.realizado_por = u.id_usuario
            WHERE a.id_individuo = :id
            ORDER BY a.fecha_actividad DESC
        ");
        $stmtAct->execute([':id' => $arbol['id_individuo']]);
        $actividades = $stmtAct->fetchAll();
    }
}

$titulo_pagina = $arbol ? "{$arbol['nombre_comun']} ({$arbol['codigo_placa']}) — Ficha ForestID" : "Individuo No Encontrado — ForestID";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <?php if (!$arbol): ?>
        <!-- Mensaje de no encontrado -->
        <div class="forest-card p-10 text-center max-w-lg mx-auto space-y-6">
            <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto">
                <i data-lucide="help-circle" class="w-8 h-8"></i>
            </div>
            <div>
                <h2 class="font-heading font-bold text-2xl text-gray-900">Individuo Forestal No Encontrado</h2>
                <p class="text-sm text-gray-500 mt-2">
                    No se encontró ningún espécimen registrado con el código de placa <strong><?= htmlspecialchars($codigo ?: $id) ?></strong> o el registro se encuentra inactivo.
                </p>
            </div>
            <div class="flex justify-center gap-3">
                <a href="<?= BASE_URL ?>publico/buscar.php" class="btn-forest-secondary text-xs">
                    <i data-lucide="search" class="w-4 h-4"></i> Buscar en el Catálogo
                </a>
                <a href="<?= BASE_URL ?>publico/index.php" class="btn-forest-primary text-xs">
                    <i data-lucide="home" class="w-4 h-4"></i> Ir al Inicio
                </a>
            </div>
        </div>
    <?php else: 
        $foto = !empty($arbol['foto_principal_url']) ? BASE_URL . $arbol['foto_principal_url'] : BASE_URL . 'assets/uploads/individuos/guayacan_rosado.svg';
        $qr_img = !empty($arbol['qr_url']) ? BASE_URL . $arbol['qr_url'] : BASE_URL . 'assets/qr/qr_' . $arbol['codigo_placa'] . '.svg';
    ?>

        <!-- Encabezado de la Ficha Técnica (Tarjeta de Identidad) -->
        <div class="forest-card overflow-hidden mb-6">
            
            <!-- Banner superior verde con metadatos -->
            <div class="bg-gradient-to-r from-emerald-950 via-forest-dark to-emerald-900 text-white p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-md text-xs font-extrabold bg-white text-emerald-900 tracking-wider shadow-sm">
                                <?= htmlspecialchars($arbol['codigo_placa']) ?>
                            </span>
                            <span class="text-xs font-medium text-emerald-200">
                                <?= htmlspecialchars($arbol['tipo_individuo']) ?>
                            </span>
                        </div>
                        <h1 class="font-heading font-extrabold text-2xl sm:text-4xl text-white tracking-tight">
                            <?= htmlspecialchars($arbol['nombre_comun']) ?>
                        </h1>
                        <p class="text-emerald-200/90 text-sm sm:text-base italic font-serif">
                            <?= htmlspecialchars($arbol['nombre_cientifico']) ?>
                        </p>
                    </div>

                    <div class="flex flex-col sm:items-end gap-2">
                        <div>
                            <?= obtener_badge_estado($arbol['estado_salud'] ?? 'Sano') ?>
                        </div>
                        <span class="text-[11px] text-emerald-200/70">
                            Familia: <strong><?= htmlspecialchars($arbol['familia_botanica']) ?></strong>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Cuerpo principal: Foto + Métricas Dasométricas -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 p-6 sm:p-8">
                
                <!-- Columna Izquierda: Fotografía del Espécimen -->
                <div class="md:col-span-5 space-y-4">
                    <div class="h-64 sm:h-80 rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 relative group shadow-sm">
                        <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($arbol['nombre_comun']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>

                    <!-- Código QR físico asociado -->
                    <div class="p-4 bg-emerald-50/60 rounded-xl border border-emerald-200/60 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white p-1 rounded-lg border border-emerald-300 shrink-0 shadow-xs">
                                <img src="<?= htmlspecialchars($qr_img) ?>" alt="QR" class="w-full h-full">
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-emerald-950">Placa QR Oficial</span>
                                <span class="text-[11px] text-emerald-700">Fijada en el fuste</span>
                            </div>
                        </div>
                        <a href="<?= htmlspecialchars($qr_img) ?>" download="QR_<?= htmlspecialchars($arbol['codigo_placa']) ?>.svg" class="p-2 rounded-lg bg-white border border-emerald-300 text-emerald-800 hover:bg-emerald-100 text-xs font-semibold" title="Descargar código QR">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>

                <!-- Columna Derecha: Datos Dasométricos y Diagnóstico -->
                <div class="md:col-span-7 space-y-6">
                    
                    <!-- Tarjetas Dasométricas (DAP, Altura, Copa, Edad) -->
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                            Mediciones Dasométricas
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl">
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-1">
                                    <i data-lucide="circle-dot" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span class="font-medium">DAP (1.30 m)</span>
                                </div>
                                <span class="text-xl font-heading font-extrabold text-gray-900">
                                    <?= formatear_numero($arbol['dap_cm'], 1, 'cm') ?>
                                </span>
                            </div>

                            <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl">
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-1">
                                    <i data-lucide="arrow-up" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span class="font-medium">Altura Total</span>
                                </div>
                                <span class="text-xl font-heading font-extrabold text-gray-900">
                                    <?= formatear_numero($arbol['altura_total_m'], 1, 'm') ?>
                                </span>
                            </div>

                            <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl">
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-1">
                                    <i data-lucide="maximize" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span class="font-medium">Diám. Copa</span>
                                </div>
                                <span class="text-xl font-heading font-extrabold text-gray-900">
                                    <?= formatear_numero($arbol['diametro_copa_m'], 1, 'm') ?>
                                </span>
                            </div>

                            <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl col-span-2 sm:col-span-3">
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-1">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span class="font-medium">Fecha de Siembra / Edad Estimada</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-800">
                                    <?= !empty($arbol['fecha_siembra']) ? formatear_fecha($arbol['fecha_siembra']) : 'No registrada (Árbol nativo preexistente)' ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Diagnóstico Fitosanitario Actual -->
                    <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="font-heading font-bold text-sm text-emerald-950 flex items-center gap-2">
                                <i data-lucide="stethoscope" class="w-4 h-4 text-emerald-700"></i>
                                Diagnóstico de Salud Fitosanitaria
                            </h3>
                            <span class="text-[11px] text-emerald-700 font-medium">
                                <?= !empty($arbol['fecha_evaluacion']) ? formatear_fecha($arbol['fecha_evaluacion']) : 'Sin evaluación reciente' ?>
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-700 leading-relaxed">
                            <?= !empty($arbol['diagnostico_salud']) ? nl2br(htmlspecialchars($arbol['diagnostico_salud'])) : 'El espécimen se encuentra en buenas condiciones estructurales y biológicas generales.' ?>
                        </p>
                        <?php if (!empty($arbol['recomendacion_salud'])): ?>
                            <div class="pt-2 border-t border-emerald-200/60 text-xs text-emerald-900">
                                <strong>Recomendación Técnica:</strong> <?= htmlspecialchars($arbol['recomendacion_salud']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Descripción General -->
                    <?php if (!empty($arbol['descripcion_general'])): ?>
                        <div>
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                Observaciones Dendrológicas
                            </h3>
                            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed bg-gray-50 p-3.5 rounded-xl border border-gray-200">
                                <?= nl2br(htmlspecialchars($arbol['descripcion_general'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Botón de Reporte Ciudadano -->
                    <div class="pt-2">
                        <button onclick="abrirModal('modal-reporte-novedad')" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-md transition-colors">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i> Reportar Novedad sobre este Árbol
                        </button>
                    </div>

                </div>

            </div>

        </div>

        <!-- Ubicación en Mapa y Zona -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">
            
            <div class="md:col-span-7 forest-card p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Ubicación Geográfica</span>
                        <h3 class="font-heading font-bold text-lg text-gray-900">
                            <?= htmlspecialchars($arbol['nombre_zona']) ?>
                        </h3>
                    </div>
                    <span class="text-xs text-gray-500">
                        <?= htmlspecialchars($arbol['zona_municipio']) ?>, <?= htmlspecialchars($arbol['zona_departamento']) ?>
                    </span>
                </div>

                <div id="mapa-individuo" class="w-full h-64 rounded-xl border border-gray-200 z-0"></div>

                <div class="flex items-center justify-between text-xs text-gray-500 pt-1">
                    <span>Lat: <?= htmlspecialchars($arbol['latitud']) ?> | Lng: <?= htmlspecialchars($arbol['longitud']) ?></span>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= $arbol['latitud'] ?>,<?= $arbol['longitud'] ?>" target="_blank" class="text-emerald-700 hover:underline inline-flex items-center gap-1 font-medium">
                        Abrir en Google Maps <i data-lucide="external-link" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>

            <div class="md:col-span-5 forest-card p-5 space-y-4">
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Entidad Administradora</span>
                <h3 class="font-heading font-bold text-lg text-gray-900">
                    <?= htmlspecialchars($arbol['nombre_institucion']) ?>
                </h3>
                <p class="text-xs text-gray-600 leading-relaxed">
                    Institución responsable del cuidado, poda técnica, fertilización y protección legal de este individuo forestal en el municipio de El Bagre.
                </p>
                <div class="pt-3 border-t border-gray-100 space-y-2 text-xs text-gray-600">
                    <div class="flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i>
                        <span>El Bagre, Antioquia — Colombia</span>
                    </div>
                    <?php if (!empty($arbol['inst_telefono'])): ?>
                        <div class="flex items-center gap-2">
                            <i data-lucide="phone" class="w-4 h-4 text-emerald-600"></i>
                            <span><?= htmlspecialchars($arbol['inst_telefono']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Línea de Tiempo de Intervenciones y Mantenimientos -->
        <div class="forest-card p-6 sm:p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Historial de Cuidados</span>
                    <h3 class="font-heading font-bold text-xl text-gray-900">
                        Intervenciones y Mantenimiento
                    </h3>
                </div>
                <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full font-medium">
                    <?= count($actividades) ?> eventos registrados
                </span>
            </div>

            <?php if (empty($actividades)): ?>
                <p class="text-xs text-gray-500 italic py-4">No se han registrado actividades de mantenimiento recientes para este árbol.</p>
            <?php else: ?>
                <div class="space-y-4 border-l-2 border-emerald-300 pl-4 sm:pl-6 ml-2 sm:ml-4">
                    <?php foreach ($actividades as $act): ?>
                        <div class="relative">
                            <span class="absolute -left-[25px] sm:-left-[33px] top-1.5 w-4 h-4 rounded-full bg-forest-primary border-4 border-white shadow-xs"></span>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">
                                        <?= htmlspecialchars($act['tipo_actividad']) ?>
                                    </span>
                                    <span class="text-[11px] text-gray-400">
                                        <?= formatear_fecha($act['fecha_actividad'], true) ?>
                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-gray-700 leading-snug">
                                    <?= htmlspecialchars($act['descripcion']) ?>
                                </p>
                                <?php if (!empty($act['responsable_nombres'])): ?>
                                    <span class="block text-[11px] text-gray-400 pt-1">
                                        Registrado por: <?= htmlspecialchars($act['responsable_nombres'] . ' ' . $act['responsable_apellidos']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<!-- Modal: Reportar Novedad Ciudadana -->
<?php if ($arbol): ?>
<div id="modal-reporte-novedad" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-gray-200">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="font-heading font-bold text-lg text-gray-900 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                Reportar Novedad o Riesgo
            </h3>
            <button onclick="cerrarModal('modal-reporte-novedad')" class="text-gray-400 hover:text-gray-600 p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form action="<?= BASE_URL ?>publico/reportar_novedad.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(crear_token_csrf()) ?>">
            <input type="hidden" name="id_individuo" value="<?= htmlspecialchars($arbol['id_individuo']) ?>">
            <input type="hidden" name="codigo_placa" value="<?= htmlspecialchars($arbol['codigo_placa']) ?>">

            <div>
                <span class="block text-xs font-semibold text-gray-500 uppercase">Espécimen</span>
                <span class="font-bold text-sm text-gray-800"><?= htmlspecialchars($arbol['nombre_comun']) ?> (<?= htmlspecialchars($arbol['codigo_placa']) ?>)</span>
            </div>

            <div>
                <label for="tipo_novedad" class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                    Tipo de Afectación / Observación
                </label>
                <select name="tipo_novedad" id="tipo_novedad" required class="w-full py-2.5 px-3 bg-white border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    <option value="Rama caída o en riesgo de caída">Rama caída o en riesgo de caída</option>
                    <option value="Árbol inclinado con riesgo de volcamiento">Árbol inclinado con riesgo de volcamiento</option>
                    <option value="Presencia de plagas / comején / hongos">Presencia de plagas / comején / hongos</option>
                    <option value="Cables eléctricos interferidos">Cables eléctricos interferidos</option>
                    <option value="Placa QR dañada o ilegible">Placa QR dañada o ilegible</option>
                    <option value="Otro incidente">Otro incidente</option>
                </select>
            </div>

            <div>
                <label for="descripcion_novedad" class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                    Descripción del Reporte
                </label>
                <textarea name="descripcion" id="descripcion_novedad" required rows="3" placeholder="Describe brevemente la situación observada..." class="w-full p-3 bg-white border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                    Evidencia Fotográfica (Opcional)
                </label>
                <input type="file" name="foto_evidencia" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="cerrarModal('modal-reporte-novedad')" class="px-4 py-2 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-xl">
                    Cancelar
                </button>
                <button type="submit" class="btn-forest-primary text-xs py-2 px-4">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i> Enviar Reporte
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Inicialización de mapa individual -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const lat = <?= (float)$arbol['latitud'] ?>;
    const lng = <?= (float)$arbol['longitud'] ?>;

    const mapIndiv = L.map('mapa-individuo').setView([lat, lng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapIndiv);

    L.marker([lat, lng])
        .addTo(mapIndiv)
        .bindPopup("<strong><?= htmlspecialchars($arbol['nombre_comun']) ?></strong><br><?= htmlspecialchars($arbol['codigo_placa']) ?>")
        .openPopup();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
