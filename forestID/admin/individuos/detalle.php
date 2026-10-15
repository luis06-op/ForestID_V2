<?php
/**
 * ForestID / ForestSRD - Detalle Técnico Interno del Individuo Forestal (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$id = $_GET['id'] ?? '';
$pdo = getDBConnection();

// Cargar individuo
$stmt = $pdo->prepare("
    SELECT i.*, z.nombre_zona, z.municipio AS zona_municipio, inst.nombre_institucion,
           e.estado AS estado_salud, e.diagnostico AS diagnostico_salud, e.recomendacion AS recomendacion_salud, e.fecha_evaluacion,
           u_reg.nombres AS reg_nombres, u_reg.apellidos AS reg_apellidos
    FROM `individuos_forestales` i
    INNER JOIN `zonas` z ON i.id_zona = z.id_zona
    INNER JOIN `instituciones` inst ON i.id_institucion = inst.id_institucion
    LEFT JOIN `estados_fitosanitarios` e ON i.id_estado_fitosanitario_actual = e.id_estado
    LEFT JOIN `usuarios` u_reg ON i.registrado_por = u_reg.id_usuario
    WHERE i.id_individuo = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$arbol = $stmt->fetch();

if (!$arbol) {
    set_flash('error', 'El espécimen forestal no fue encontrado.');
    header('Location: ' . BASE_URL . 'admin/individuos/index.php');
    exit;
}

// Procesar registro de nueva evaluación fitosanitaria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_evaluacion'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validar_token_csrf($csrf)) {
        $nuevo_estado = $_POST['estado_nuevo'] ?? 'Sano';
        $diag = sanear($_POST['diagnostico_nuevo'] ?? '');
        $recom = sanear($_POST['recomendacion_nueva'] ?? '');
        $id_nuevo_estado = generar_uuid();
        $id_usuario = $_SESSION['usuario']['id_usuario'];

        $insEval = $pdo->prepare("
            INSERT INTO `estados_fitosanitarios` (
                `id_estado`, `id_individuo`, `estado`, `diagnostico`, `recomendacion`, `evaluado_por`, `fecha_evaluacion`
            ) VALUES (
                :id_est, :id_ind, :estado, :diag, :recom, :eval, NOW()
            )
        ");
        $insEval->execute([
            ':id_est' => $id_nuevo_estado,
            ':id_ind' => $id,
            ':estado' => $nuevo_estado,
            ':diag'   => $diag,
            ':recom'  => $recom,
            ':eval'   => $id_usuario
        ]);

        // Actualizar referencia actual
        $updArbol = $pdo->prepare("UPDATE `individuos_forestales` SET `id_estado_fitosanitario_actual` = :id_est WHERE `id_individuo` = :id_ind");
        $updArbol->execute([':id_est' => $id_nuevo_estado, ':id_ind' => $id]);

        registrar_log($pdo, $id_usuario, 'EVALUACION_FITOSANITARIA', 'FITOSANITARIO', [
            'codigo_placa' => $arbol['codigo_placa'],
            'nuevo_estado' => $nuevo_estado
        ]);

        set_flash('success', 'Evaluación fitosanitaria registrada exitosamente.');
        header('Location: ' . BASE_URL . 'admin/individuos/detalle.php?id=' . $id);
        exit;
    }
}

// Procesar registro de actividad de mantenimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_mantenimiento'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validar_token_csrf($csrf)) {
        $tipo_act = $_POST['tipo_actividad'] ?? 'Poda';
        $desc = sanear($_POST['descripcion_actividad'] ?? '');
        $id_usuario = $_SESSION['usuario']['id_usuario'];

        $foto_evidencia = null;
        if (!empty($_FILES['foto_evidencia']) && $_FILES['foto_evidencia']['error'] !== UPLOAD_ERR_NO_FILE) {
            $sub = subir_imagen_segura($_FILES['foto_evidencia'], 'evidencias');
            if ($sub['ok']) {
                $foto_evidencia = $sub['ruta'];
            }
        }

        $insAct = $pdo->prepare("
            INSERT INTO `actividades_mantenimiento` (
                `id_actividad`, `id_individuo`, `tipo_actividad`, `descripcion`, 
                `evidencia_foto_url`, `realizado_por`, `fecha_actividad`, `fecha_registro`
            ) VALUES (
                UUID(), :id_ind, :tipo, :desc, :foto, :user, NOW(), NOW()
            )
        ");
        $insAct->execute([
            ':id_ind' => $id,
            ':tipo'   => $tipo_act,
            ':desc'   => $desc,
            ':foto'   => $foto_evidencia,
            ':user'   => $id_usuario
        ]);

        registrar_log($pdo, $id_usuario, 'REGISTRO_ACTIVIDAD_MANTENIMIENTO', 'MANTENIMIENTO', [
            'codigo_placa' => $arbol['codigo_placa'],
            'tipo'         => $tipo_act
        ]);

        set_flash('success', 'Actividad de mantenimiento registrada exitosamente.');
        header('Location: ' . BASE_URL . 'admin/individuos/detalle.php?id=' . $id);
        exit;
    }
}

// Cargar historiales
$stmtEvals = $pdo->prepare("
    SELECT e.*, u.nombres, u.apellidos
    FROM `estados_fitosanitarios` e
    INNER JOIN `usuarios` u ON e.evaluado_por = u.id_usuario
    WHERE e.id_individuo = :id
    ORDER BY e.fecha_evaluacion DESC
");
$stmtEvals->execute([':id' => $id]);
$evaluaciones = $stmtEvals->fetchAll();

$stmtActs = $pdo->prepare("
    SELECT a.*, u.nombres, u.apellidos
    FROM `actividades_mantenimiento` a
    LEFT JOIN `usuarios` u ON a.realizado_por = u.id_usuario
    WHERE a.id_individuo = :id
    ORDER BY a.fecha_actividad DESC
");
$stmtActs->execute([':id' => $id]);
$actividades = $stmtActs->fetchAll();

$foto = !empty($arbol['foto_principal_url']) ? BASE_URL . $arbol['foto_principal_url'] : BASE_URL . 'assets/uploads/individuos/guayacan_rosado.svg';
$qr_img = !empty($arbol['qr_url']) ? BASE_URL . $arbol['qr_url'] : BASE_URL . 'assets/qr/qr_' . $arbol['codigo_placa'] . '.svg';

$csrf_token = crear_token_csrf();
$titulo_pagina = "Detalle: {$arbol['codigo_placa']} — " . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <!-- Barra de Navegación Superior de la Ficha -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <span class="font-extrabold text-xs px-2.5 py-1 rounded-md bg-emerald-950 text-white">
                        <?= htmlspecialchars($arbol['codigo_placa']) ?>
                    </span>
                    <?= obtener_badge_estado($arbol['estado_salud'] ?? 'Sano') ?>
                </div>
                <h1 class="font-heading font-extrabold text-2xl sm:text-3xl text-gray-900">
                    <?= htmlspecialchars($arbol['nombre_comun']) ?>
                </h1>
                <p class="text-xs text-gray-500 italic">
                    <?= htmlspecialchars($arbol['nombre_cientifico']) ?> • Familia: <?= htmlspecialchars($arbol['familia_botanica']) ?>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="<?= BASE_URL ?>publico/ficha.php?codigo=<?= urlencode($arbol['codigo_placa']) ?>" target="_blank" class="btn-forest-secondary text-xs py-2 px-3">
                    <i data-lucide="globe" class="w-4 h-4"></i> Ficha Pública
                </a>
                <a href="<?= BASE_URL ?>admin/individuos/editar.php?id=<?= $arbol['id_individuo'] ?>" class="btn-forest-secondary text-xs py-2 px-3">
                    <i data-lucide="edit-3" class="w-4 h-4"></i> Editar
                </a>
                <a href="<?= BASE_URL ?>admin/qr/imprimir.php?codigo=<?= urlencode($arbol['codigo_placa']) ?>" target="_blank" class="btn-forest-primary text-xs py-2 px-3">
                    <i data-lucide="printer" class="w-4 h-4"></i> Placa Física
                </a>
            </div>
        </div>

        <!-- Panel de Información Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Fotografía y QR Oficial -->
            <div class="lg:col-span-4 space-y-4">
                <div class="forest-card overflow-hidden">
                    <div class="h-60 bg-gray-100">
                        <img src="<?= htmlspecialchars($foto) ?>" alt="Foto" class="w-full h-full object-cover">
                    </div>
                    <div class="p-4 bg-emerald-50/60 border-t border-emerald-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white p-1 rounded-lg border border-emerald-300">
                                <img src="<?= htmlspecialchars($qr_img) ?>" alt="QR" class="w-full h-full">
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-gray-900">Placa QR Lista</span>
                                <span class="text-[10px] text-gray-500">Vector SVG HD</span>
                            </div>
                        </div>
                        <a href="<?= htmlspecialchars($qr_img) ?>" download="QR_<?= htmlspecialchars($arbol['codigo_placa']) ?>.svg" class="p-2 bg-white border border-gray-300 rounded-lg text-emerald-800 hover:bg-emerald-100 text-xs">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>

                <!-- Botones de Acción de Campo -->
                <div class="forest-card p-4 space-y-2">
                    <span class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Intervenciones Rápidas</span>
                    <button onclick="abrirModal('modal-evaluacion')" class="w-full btn-forest-primary text-xs py-2 justify-start">
                        <i data-lucide="stethoscope" class="w-4 h-4"></i> Nueva Evaluación Fitosanitaria
                    </button>
                    <button onclick="abrirModal('modal-mantenimiento')" class="w-full btn-forest-secondary text-xs py-2 justify-start">
                        <i data-lucide="wrench" class="w-4 h-4"></i> Registrar Mantenimiento
                    </button>
                </div>
            </div>

            <!-- Métricas Dasométricas y Datos Territoriales -->
            <div class="lg:col-span-8 space-y-6">
                
                <div class="forest-card p-6 space-y-4">
                    <h3 class="font-heading font-bold text-sm text-gray-900 border-b border-gray-100 pb-2">
                        Parámetros Dasométricos Actuales
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <span class="block text-[11px] text-gray-400">DAP (1.30 m)</span>
                            <span class="text-lg font-bold text-gray-900"><?= formatear_numero($arbol['dap_cm'], 1, 'cm') ?></span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <span class="block text-[11px] text-gray-400">Altura Total</span>
                            <span class="text-lg font-bold text-gray-900"><?= formatear_numero($arbol['altura_total_m'], 1, 'm') ?></span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <span class="block text-[11px] text-gray-400">Diámetro Copa</span>
                            <span class="text-lg font-bold text-gray-900"><?= formatear_numero($arbol['diametro_copa_m'], 1, 'm') ?></span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <span class="block text-[11px] text-gray-400">Tipo Individuo</span>
                            <span class="text-xs font-bold text-emerald-800"><?= htmlspecialchars($arbol['tipo_individuo']) ?></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 text-xs text-gray-600">
                        <div>
                            <span class="block font-semibold text-gray-700">Zona Territorial:</span>
                            <span>📍 <?= htmlspecialchars($arbol['nombre_zona']) ?> (<?= htmlspecialchars($arbol['zona_municipio']) ?>)</span>
                        </div>
                        <div>
                            <span class="block font-semibold text-gray-700">Institución Administradora:</span>
                            <span>🏢 <?= htmlspecialchars($arbol['nombre_institucion']) ?></span>
                        </div>
                        <div>
                            <span class="block font-semibold text-gray-700">Coordenadas GPS:</span>
                            <span class="font-mono text-gray-800"><?= $arbol['latitud'] ?>, <?= $arbol['longitud'] ?></span>
                        </div>
                        <div>
                            <span class="block font-semibold text-gray-700">Fecha de Siembra:</span>
                            <span><?= !empty($arbol['fecha_siembra']) ? formatear_fecha($arbol['fecha_siembra']) : 'No registrada' ?></span>
                        </div>
                    </div>

                    <?php if (!empty($arbol['descripcion_general'])): ?>
                        <div class="pt-2 border-t border-gray-100">
                            <span class="block text-xs font-semibold text-gray-700 mb-1">Descripción / Entorno:</span>
                            <p class="text-xs text-gray-600 bg-gray-50 p-3 rounded-lg border border-gray-200"><?= nl2br(htmlspecialchars($arbol['descripcion_general'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Diagnóstico y Estado Actual -->
                <div class="forest-card p-6 bg-emerald-50/70 border border-emerald-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="font-heading font-bold text-sm text-emerald-950 flex items-center gap-2">
                            <i data-lucide="stethoscope" class="w-4 h-4 text-emerald-700"></i>
                            Estado de Salud Fitosanitaria Actual
                        </h3>
                        <span class="text-xs text-emerald-800 font-medium">
                            <?= !empty($arbol['fecha_evaluacion']) ? formatear_fecha($arbol['fecha_evaluacion']) : 'Sin fecha' ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-700 leading-relaxed">
                        <?= !empty($arbol['diagnostico_salud']) ? nl2br(htmlspecialchars($arbol['diagnostico_salud'])) : 'Condición normal sin alertas activas.' ?>
                    </p>
                    <?php if (!empty($arbol['recomendacion_salud'])): ?>
                        <div class="pt-2 border-t border-emerald-200 text-xs text-emerald-950">
                            <strong>Recomendación:</strong> <?= htmlspecialchars($arbol['recomendacion_salud']) ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

        <!-- Historiales Tabulados (Evaluaciones + Mantenimientos) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Historial 1: Evaluaciones Fitosanitarias -->
            <div class="forest-card p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="font-heading font-bold text-sm text-gray-900 flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4 text-emerald-600"></i>
                        Historial de Evaluaciones de Salud
                    </h3>
                    <span class="text-xs bg-gray-100 px-2 py-0.5 rounded-full font-semibold text-gray-600">
                        <?= count($evaluaciones) ?>
                    </span>
                </div>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php if (empty($evaluaciones)): ?>
                        <p class="text-xs text-gray-400 italic">No hay evaluaciones registradas.</p>
                    <?php else: ?>
                        <?php foreach ($evaluaciones as $ev): ?>
                            <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <?= obtener_badge_estado($ev['estado']) ?>
                                    <span class="text-[11px] text-gray-400"><?= formatear_fecha($ev['fecha_evaluacion'], true) ?></span>
                                </div>
                                <p class="text-xs text-gray-700"><?= htmlspecialchars($ev['diagnostico']) ?></p>
                                <?php if (!empty($ev['recomendacion'])): ?>
                                    <span class="block text-[11px] text-emerald-800 font-medium">💡 <?= htmlspecialchars($ev['recomendacion']) ?></span>
                                <?php endif; ?>
                                <span class="block text-[10px] text-gray-400 pt-1">Evaluador: <?= htmlspecialchars($ev['nombres'] . ' ' . $ev['apellidos']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historial 2: Intervenciones y Mantenimientos -->
            <div class="forest-card p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="font-heading font-bold text-sm text-gray-900 flex items-center gap-2">
                        <i data-lucide="wrench" class="w-4 h-4 text-emerald-600"></i>
                        Historial de Mantenimientos
                    </h3>
                    <span class="text-xs bg-gray-100 px-2 py-0.5 rounded-full font-semibold text-gray-600">
                        <?= count($actividades) ?>
                    </span>
                </div>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php if (empty($actividades)): ?>
                        <p class="text-xs text-gray-400 italic">No hay actividades de mantenimiento registradas.</p>
                    <?php else: ?>
                        <?php foreach ($actividades as $act): ?>
                            <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-emerald-800 uppercase tracking-wider"><?= htmlspecialchars($act['tipo_actividad']) ?></span>
                                    <span class="text-[11px] text-gray-400"><?= formatear_fecha($act['fecha_actividad'], true) ?></span>
                                </div>
                                <p class="text-xs text-gray-700"><?= htmlspecialchars($act['descripcion']) ?></p>
                                <span class="block text-[10px] text-gray-400 pt-1">
                                    <?= !empty($act['nombres']) ? 'Realizado por: ' . htmlspecialchars($act['nombres'] . ' ' . $act['apellidos']) : 'Reporte Ciudadano' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<!-- Modal 1: Nueva Evaluación Fitosanitaria -->
<div id="modal-evaluacion" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-gray-200">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="font-heading font-bold text-base text-gray-900 flex items-center gap-2">
                <i data-lucide="stethoscope" class="w-5 h-5 text-emerald-700"></i>
                Registrar Nueva Evaluación Fitosanitaria
            </h3>
            <button onclick="cerrarModal('modal-evaluacion')" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <form method="POST" action="detalle.php?id=<?= urlencode($id) ?>" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="accion_evaluacion" value="1">

            <div>
                <label for="estado_nuevo" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Estado de Salud *</label>
                <select name="estado_nuevo" id="estado_nuevo" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    <option value="Sano" <?= ($arbol['estado_salud'] === 'Sano') ? 'selected' : '' ?>>Sano</option>
                    <option value="En observación" <?= ($arbol['estado_salud'] === 'En observación') ? 'selected' : '' ?>>En observación</option>
                    <option value="Enfermo" <?= ($arbol['estado_salud'] === 'Enfermo') ? 'selected' : '' ?>>Enfermo</option>
                    <option value="Plagado" <?= ($arbol['estado_salud'] === 'Plagado') ? 'selected' : '' ?>>Plagado</option>
                    <option value="En riesgo" <?= ($arbol['estado_salud'] === 'En riesgo') ? 'selected' : '' ?>>En riesgo crítico</option>
                    <option value="Muerto en pie" <?= ($arbol['estado_salud'] === 'Muerto en pie') ? 'selected' : '' ?>>Muerto en pie</option>
                    <option value="Talado" <?= ($arbol['estado_salud'] === 'Talado') ? 'selected' : '' ?>>Talado</option>
                </select>
            </div>

            <div>
                <label for="diagnostico_nuevo" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Diagnóstico Clínico *</label>
                <textarea name="diagnostico_nuevo" id="diagnostico_nuevo" required rows="3" placeholder="Detalla los síntomas, follaje, tronco, raíces..." class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none"></textarea>
            </div>

            <div>
                <label for="recomendacion_nueva" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Recomendación Técnica de Manejo</label>
                <textarea name="recomendacion_nueva" id="recomendacion_nueva" rows="2" placeholder="Acciones a seguir (poda, fertilización, aspersión...)" class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="cerrarModal('modal-evaluacion')" class="btn-forest-secondary text-xs py-2 px-3">Cancelar</button>
                <button type="submit" class="btn-forest-primary text-xs py-2 px-4">Guardar Evaluación</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Nueva Actividad de Mantenimiento -->
<div id="modal-mantenimiento" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-gray-200">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="font-heading font-bold text-base text-gray-900 flex items-center gap-2">
                <i data-lucide="wrench" class="w-5 h-5 text-emerald-700"></i>
                Registrar Actividad de Mantenimiento
            </h3>
            <button onclick="cerrarModal('modal-mantenimiento')" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <form method="POST" action="detalle.php?id=<?= urlencode($id) ?>" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="accion_mantenimiento" value="1">

            <div>
                <label for="tipo_actividad" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tipo de Intervención *</label>
                <select name="tipo_actividad" id="tipo_actividad" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    <option value="Poda">Poda (Formativa / Sanitaria / Realce)</option>
                    <option value="Fumigación">Fumigación / Control Biológico</option>
                    <option value="Fertilización">Fertilización / Enmiendas de suelo</option>
                    <option value="Riego">Riego e Hidratación</option>
                    <option value="Trasplante">Trasplante / Reubicación</option>
                    <option value="Tutoreo">Tutoreo / Enderezamiento</option>
                    <option value="Tala">Tala por Riesgo / Compensación</option>
                    <option value="Otro">Otro mantenimiento</option>
                </select>
            </div>

            <div>
                <label for="descripcion_actividad" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descripción del Trabajo Realizado *</label>
                <textarea name="descripcion_actividad" id="descripcion_actividad" required rows="3" placeholder="Describe insumos utilizados, ramas intervenidas, cuadrilla..." class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none"></textarea>
            </div>

            <div>
                <label for="foto_evidencia" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Evidencia Fotográfica (Opcional)</label>
                <input type="file" name="foto_evidencia" id="foto_evidencia" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="cerrarModal('modal-mantenimiento')" class="btn-forest-secondary text-xs py-2 px-3">Cancelar</button>
                <button type="submit" class="btn-forest-primary text-xs py-2 px-4">Registrar Intervención</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
