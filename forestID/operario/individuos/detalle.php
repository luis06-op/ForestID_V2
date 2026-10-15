<?php
/**
 * ForestID / ForestSRD - Detalle y Gestión de Campo del Individuo (OperarioEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['OperarioEmincut', 'AdminEmincut']);

$id = $_GET['id'] ?? '';
$codigo = $_GET['codigo'] ?? '';
$pdo = getDBConnection();

$stmt = $pdo->prepare("
    SELECT i.*, z.nombre_zona, z.municipio AS zona_municipio,
           e.estado AS estado_salud, e.diagnostico AS diagnostico_salud, e.recomendacion AS recomendacion_salud, e.fecha_evaluacion
    FROM `individuos_forestales` i
    INNER JOIN `zonas` z ON i.id_zona = z.id_zona
    LEFT JOIN `estados_fitosanitarios` e ON i.id_estado_fitosanitario_actual = e.id_estado
    WHERE (i.id_individuo = :id OR i.codigo_placa = :cod) AND i.estado_registro = 1
    LIMIT 1
");
$stmt->execute([':id' => $id, ':cod' => $codigo]);
$arbol = $stmt->fetch();

if (!$arbol) {
    set_flash('error', 'El individuo forestal consultado no existe o no se encuentra activo.');
    header('Location: ' . BASE_URL . 'operario/escanear.php');
    exit;
}

$id = $arbol['id_individuo'];

// Procesar Registro de Evaluación Fitosanitaria
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

        $pdo->prepare("UPDATE `individuos_forestales` SET `id_estado_fitosanitario_actual` = :id_est WHERE `id_individuo` = :id_ind")
            ->execute([':id_est' => $id_nuevo_estado, ':id_ind' => $id]);

        registrar_log($pdo, $id_usuario, 'EVALUACION_CAMPO_OPERARIO', 'FITOSANITARIO', [
            'codigo_placa' => $arbol['codigo_placa'],
            'estado'       => $nuevo_estado
        ]);

        set_flash('success', 'Condición fitosanitaria actualizada correctamente.');
        header('Location: ' . BASE_URL . 'operario/individuos/detalle.php?id=' . $id);
        exit;
    }
}

// Procesar Registro de Mantenimiento
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

        registrar_log($pdo, $id_usuario, 'MANTENIMIENTO_CAMPO_OPERARIO', 'MANTENIMIENTO', [
            'codigo_placa' => $arbol['codigo_placa'],
            'tipo'         => $tipo_act
        ]);

        set_flash('success', 'Intervención de mantenimiento registrada.');
        header('Location: ' . BASE_URL . 'operario/individuos/detalle.php?id=' . $id);
        exit;
    }
}

// Historial reciente de actividades
$stmtActs = $pdo->prepare("
    SELECT a.*, u.nombres, u.apellidos
    FROM `actividades_mantenimiento` a
    LEFT JOIN `usuarios` u ON a.realizado_por = u.id_usuario
    WHERE a.id_individuo = :id
    ORDER BY a.fecha_actividad DESC
    LIMIT 10
");
$stmtActs->execute([':id' => $id]);
$actividades = $stmtActs->fetchAll();

$foto = !empty($arbol['foto_principal_url']) ? BASE_URL . $arbol['foto_principal_url'] : BASE_URL . 'assets/uploads/individuos/guayacan_rosado.svg';
$csrf_token = crear_token_csrf();
$titulo_pagina = "Gestión: {$arbol['codigo_placa']} — " . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php 
    if (es_admin()) {
        require_once __DIR__ . '/../../includes/sidebar_admin.php';
    } else {
        require_once __DIR__ . '/../../includes/sidebar_operario.php';
    }
    ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto max-w-4xl">
        
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-teal-700 uppercase tracking-widest">Inspección de Campo</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">
                    <?= htmlspecialchars($arbol['nombre_comun']) ?>
                </h1>
                <span class="font-mono text-xs font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded">
                    <?= htmlspecialchars($arbol['codigo_placa']) ?>
                </span>
            </div>
            <a href="<?= BASE_URL ?>operario/individuos/index.php" class="btn-forest-secondary text-xs py-2 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver
            </a>
        </div>

        <!-- Tarjeta de Diagnóstico y Estado Actual -->
        <div class="forest-card p-6 space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
                <div>
                    <span class="text-xs text-gray-400">Condición Fitosanitaria Actual:</span>
                    <div class="mt-1"><?= obtener_badge_estado($arbol['estado_salud'] ?? 'Sano') ?></div>
                </div>
                <div class="flex gap-2">
                    <button onclick="abrirModal('modal-eval')" class="btn-forest-primary text-xs py-2 px-3">
                        <i data-lucide="stethoscope" class="w-3.5 h-3.5"></i> Actualizar Salud
                    </button>
                    <button onclick="abrirModal('modal-mant')" class="btn-forest-secondary text-xs py-2 px-3">
                        <i data-lucide="wrench" class="w-3.5 h-3.5"></i> Registrar Trabajo
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1 h-36 bg-gray-100 rounded-xl overflow-hidden">
                    <img src="<?= htmlspecialchars($foto) ?>" alt="Foto" class="w-full h-full object-cover">
                </div>
                <div class="sm:col-span-2 space-y-2 text-xs">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="p-2 bg-gray-50 rounded-lg">
                            <span class="text-gray-400 block text-[10px]">DAP</span>
                            <span class="font-bold text-gray-800"><?= formatear_numero($arbol['dap_cm'], 1, 'cm') ?></span>
                        </div>
                        <div class="p-2 bg-gray-50 rounded-lg">
                            <span class="text-gray-400 block text-[10px]">Altura</span>
                            <span class="font-bold text-gray-800"><?= formatear_numero($arbol['altura_total_m'], 1, 'm') ?></span>
                        </div>
                        <div class="p-2 bg-gray-50 rounded-lg">
                            <span class="text-gray-400 block text-[10px]">Copa</span>
                            <span class="font-bold text-gray-800"><?= formatear_numero($arbol['diametro_copa_m'], 1, 'm') ?></span>
                        </div>
                    </div>
                    <div class="p-3 bg-emerald-50/60 rounded-lg text-gray-700">
                        <strong>Diagnóstico reciente:</strong> <?= !empty($arbol['diagnostico_salud']) ? htmlspecialchars($arbol['diagnostico_salud']) : 'Sin observaciones reportadas.' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de Intervenciones -->
        <div class="forest-card p-6 space-y-4">
            <h3 class="font-heading font-bold text-sm text-gray-900 border-b border-gray-100 pb-2">
                Trabajos y Mantenimientos Realizados
            </h3>
            <div class="space-y-3">
                <?php if (empty($actividades)): ?>
                    <p class="text-xs text-gray-400 italic">No hay registros de mantenimiento previos.</p>
                <?php else: ?>
                    <?php foreach ($actividades as $act): ?>
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-1 text-xs">
                            <div class="flex items-center justify-between font-bold">
                                <span class="text-emerald-800 uppercase"><?= htmlspecialchars($act['tipo_actividad']) ?></span>
                                <span class="text-gray-400 font-normal text-[10px]"><?= formatear_fecha($act['fecha_actividad'], true) ?></span>
                            </div>
                            <p class="text-gray-700"><?= htmlspecialchars($act['descripcion']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<!-- Modales para Operario -->
<div id="modal-eval" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
            <h3 class="font-heading font-bold text-sm text-gray-900">Actualizar Estado de Salud</h3>
            <button onclick="cerrarModal('modal-eval')" class="text-gray-400"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>
        <form method="POST" action="detalle.php?id=<?= urlencode($id) ?>" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="accion_evaluacion" value="1">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Estado *</label>
                <select name="estado_nuevo" required class="w-full py-2 px-3 border rounded-xl text-xs">
                    <option value="Sano">Sano</option>
                    <option value="En observación">En observación</option>
                    <option value="Enfermo">Enfermo</option>
                    <option value="Plagado">Plagado</option>
                    <option value="En riesgo">En riesgo</option>
                    <option value="Muerto en pie">Muerto en pie</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Diagnóstico *</label>
                <textarea name="diagnostico_nuevo" required rows="2" class="w-full p-2.5 border rounded-xl text-xs" placeholder="Estado del tronco, ramas, hojas..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Recomendación</label>
                <textarea name="recomendacion_nueva" rows="2" class="w-full p-2.5 border rounded-xl text-xs" placeholder="Trabajo sugerido..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="cerrarModal('modal-eval')" class="btn-forest-secondary text-xs py-1.5 px-3">Cancelar</button>
                <button type="submit" class="btn-forest-primary text-xs py-1.5 px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-mant" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
            <h3 class="font-heading font-bold text-sm text-gray-900">Registrar Mantenimiento</h3>
            <button onclick="cerrarModal('modal-mant')" class="text-gray-400"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>
        <form method="POST" action="detalle.php?id=<?= urlencode($id) ?>" enctype="multipart/form-data" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="accion_mantenimiento" value="1">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tipo de Actividad *</label>
                <select name="tipo_actividad" required class="w-full py-2 px-3 border rounded-xl text-xs">
                    <option value="Poda">Poda</option>
                    <option value="Fumigación">Fumigación</option>
                    <option value="Fertilización">Fertilización</option>
                    <option value="Riego">Riego</option>
                    <option value="Tutoreo">Tutoreo</option>
                    <option value="Tala">Tala</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descripción del Trabajo *</label>
                <textarea name="descripcion_actividad" required rows="2" class="w-full p-2.5 border rounded-xl text-xs" placeholder="Detalle de las acciones realizadas..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Foto de Evidencia (Opcional)</label>
                <input type="file" name="foto_evidencia" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-emerald-50 file:text-emerald-700">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="cerrarModal('modal-mant')" class="btn-forest-secondary text-xs py-1.5 px-3">Cancelar</button>
                <button type="submit" class="btn-forest-primary text-xs py-1.5 px-4">Guardar Intervención</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
