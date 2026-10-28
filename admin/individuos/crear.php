<?php
/**
 * ForestID / ForestSRD - Registrar Nuevo Individuo Forestal (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/qrcode_helper.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();
$error = '';

$zonas = $pdo->query("SELECT id_zona, nombre_zona FROM `zonas` WHERE estado = 1 ORDER BY nombre_zona ASC")->fetchAll();

// Código de placa sugerido automático
$codigo_sugerido = generar_codigo_placa($pdo, 'EBG');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf         = $_POST['csrf_token'] ?? '';
    $codigo_placa       = strtoupper(sanear($_POST['codigo_placa'] ?? ''));
    $id_zona            = $_POST['id_zona'] ?? '';
    $nombre_comun       = sanear($_POST['nombre_comun'] ?? '');
    $nombre_cientifico  = sanear($_POST['nombre_cientifico'] ?? '');
    $familia_botanica   = sanear($_POST['familia_botanica'] ?? '');
    $tipo_individuo     = $_POST['tipo_individuo'] ?? 'Árbol';
    $fecha_siembra      = !empty($_POST['fecha_siembra']) ? $_POST['fecha_siembra'] : null;
    $latitud            = !empty($_POST['latitud']) ? (float)$_POST['latitud'] : 7.60432;
    $longitud           = !empty($_POST['longitud']) ? (float)$_POST['longitud'] : -74.80915;
    $dap_cm             = (float)($_POST['dap_cm'] ?? 0);
    $altura_total_m     = (float)($_POST['altura_total_m'] ?? 0);
    $diametro_copa_m    = (float)($_POST['diametro_copa_m'] ?? 0);
    $descripcion        = sanear($_POST['descripcion_general'] ?? '');
    
    // Datos de estado fitosanitario inicial
    $estado_salud       = $_POST['estado_salud'] ?? 'Sano';
    $diagnostico        = sanear($_POST['diagnostico'] ?? 'Espécimen en condiciones normales.');
    $recomendacion      = sanear($_POST['recomendacion'] ?? 'Continuar con monitoreo semestral regular.');

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($codigo_placa) || empty($id_zona) || empty($nombre_comun) || empty($nombre_cientifico)) {
        $error = 'Todos los campos obligatorios (*) deben ser completados.';
    } elseif (!zona_activa($pdo, (string)$id_zona)) {
        $error = 'La zona seleccionada no es válida o está inactiva.';
    } else {
        // M3: normalizar enums contra el catálogo del esquema.
        $tipo_individuo = normalizar_enum($tipo_individuo, TIPOS_INDIVIDUO_VALIDOS, 'Árbol');
        $estado_salud   = normalizar_enum($estado_salud, ESTADOS_FITOSANITARIOS_VALIDOS, 'Sano');

        // Verificar código de placa único
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `individuos_forestales` WHERE codigo_placa = :cod");
        $chk->execute([':cod' => $codigo_placa]);
        if ((int)$chk->fetchColumn() > 0) {
            $error = 'El código de placa ingresado ya está en uso por otro individuo forestal.';
        } else {
            // Manejo de foto principal
            $foto_url = null;
            if (!empty($_FILES['foto_principal']) && $_FILES['foto_principal']['error'] !== UPLOAD_ERR_NO_FILE) {
                $subida = subir_imagen_segura($_FILES['foto_principal'], 'individuos');
                if ($subida['ok']) {
                    $foto_url = $subida['ruta'];
                } else {
                    $error = $subida['error'];
                }
            }

            if (empty($error)) {
                $id_individuo = generar_uuid();
                $id_estado    = generar_uuid();
                $id_usuario   = $_SESSION['usuario']['id_usuario'];
                $qr_relativo  = null;

                // 1. Generar archivo QR SVG apuntando a la ficha pública
                $urlPublicaFicha = QRCodeGenerator::urlFicha($codigo_placa);
                try {
                    $qr_relativo = QRCodeGenerator::guardarQR($codigo_placa, $urlPublicaFicha);
                } catch (Throwable $e) {
                    error_log('[ForestID] Error generando QR: ' . $e->getMessage());
                    $error = 'No se pudo generar el código QR. Verifique los permisos de assets/qr e intente nuevamente.';
                }

                if (empty($error)) {
                    try {
                        // A5: individuo + estado + referencia + log en una sola transacción.
                        $pdo->beginTransaction();

                        // 2. Insertar Individuo Forestal
                        $insIndiv = $pdo->prepare("
                            INSERT INTO `individuos_forestales` (
                                `id_individuo`, `codigo_placa`, `id_zona`, 
                                `nombre_comun`, `nombre_cientifico`, `familia_botanica`, `tipo_individuo`, 
                                `fecha_siembra`, `latitud`, `longitud`, `dap_cm`, `altura_total_m`, `diametro_copa_m`, 
                                `foto_principal_url`, `descripcion_general`, `qr_url`, `registrado_por`, `estado_registro`, `fecha_registro`
                            ) VALUES (
                                :id, :codigo, :id_zona,
                                :nom_com, :nom_cie, :familia, :tipo,
                                :siembra, :lat, :lng, :dap, :alt, :copa,
                                :foto, :desc, :qr, :user, 1, NOW()
                            )
                        ");
                        $insIndiv->execute([
                            ':id'      => $id_individuo,
                            ':codigo'  => $codigo_placa,
                            ':id_zona' => $id_zona,
                            ':nom_com' => $nombre_comun,
                            ':nom_cie' => $nombre_cientifico,
                            ':familia' => $familia_botanica,
                            ':tipo'    => $tipo_individuo,
                            ':siembra' => $fecha_siembra,
                            ':lat'     => $latitud,
                            ':lng'     => $longitud,
                            ':dap'     => $dap_cm,
                            ':alt'     => $altura_total_m,
                            ':copa'    => $diametro_copa_m,
                            ':foto'    => $foto_url,
                            ':desc'    => $descripcion,
                            ':qr'      => $qr_relativo,
                            ':user'    => $id_usuario
                        ]);

                        // 3. Insertar Estado Fitosanitario Inicial
                        $insSalud = $pdo->prepare("
                            INSERT INTO `estados_fitosanitarios` (
                                `id_estado`, `id_individuo`, `estado`, `diagnostico`, `recomendacion`, `evaluado_por`, `fecha_evaluacion`
                            ) VALUES (
                                :id_est, :id_ind, :estado, :diag, :recom, :eval, NOW()
                            )
                        ");
                        $insSalud->execute([
                            ':id_est' => $id_estado,
                            ':id_ind' => $id_individuo,
                            ':estado' => $estado_salud,
                            ':diag'   => $diagnostico,
                            ':recom'  => $recomendacion,
                            ':eval'   => $id_usuario
                        ]);

                        // 4. Actualizar referencia de estado actual
                        $updRef = $pdo->prepare("UPDATE `individuos_forestales` SET `id_estado_fitosanitario_actual` = :id_est WHERE `id_individuo` = :id_ind");
                        $updRef->execute([':id_est' => $id_estado, ':id_ind' => $id_individuo]);

                        // 5. Registrar log de auditoría
                        registrar_log($pdo, $id_usuario, 'CREAR_INDIVIDUO', 'INDIVIDUOS_FORESTALES', [
                            'codigo_placa' => $codigo_placa,
                            'nombre_comun' => $nombre_comun,
                            'zona_id'      => $id_zona
                        ]);

                        $pdo->commit();

                        set_flash('success', "Individuo {$nombre_comun} ({$codigo_placa}) registrado con éxito y código QR generado.");
                        header('Location: ' . BASE_URL . 'admin/individuos/detalle.php?id=' . $id_individuo);
                        exit;
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        error_log('[ForestID] Error al registrar individuo (admin): ' . $e->getMessage());

                        // Limpiar archivos que ya no tendrán registro en la BD.
                        foreach ([$foto_url, $qr_relativo] as $archivo) {
                            if (!empty($archivo)) {
                                $ruta = ROOT_PATH . str_replace('/', DIRECTORY_SEPARATOR, $archivo);
                                if (is_file($ruta)) {
                                    @unlink($ruta);
                                }
                            }
                        }

                        $error = 'No se pudo registrar el individuo forestal. Intente nuevamente.';
                    }
                }
            }
        }
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Registrar Individuo Forestal — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Censo y Alta Botánica</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Registrar Individuo Forestal</h1>
            </div>
            <a href="<?= BASE_URL ?>admin/individuos/index.php" class="btn-forest-secondary text-xs py-2 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver al Listado
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl text-xs text-rose-800 flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="crear.php" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- 1. Identificación y Ubicación -->
            <div class="forest-card p-6 space-y-4">
                <h3 class="font-heading font-bold text-sm text-emerald-950 flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i data-lucide="qr-code" class="w-4 h-4 text-emerald-700"></i>
                    1. Identificación y Ubicación Territorial
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="codigo_placa" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Código de Placa QR *</label>
                        <input type="text" name="codigo_placa" id="codigo_placa" required value="<?= htmlspecialchars($codigo_sugerido) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs font-bold uppercase focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="id_zona" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Zona Territorial *</label>
                        <select name="id_zona" id="id_zona" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <option value="">Seleccione una zona...</option>
                            <?php foreach ($zonas as $z): ?>
                                <option value="<?= $z['id_zona'] ?>"><?= htmlspecialchars($z['nombre_zona']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="latitud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Latitud GPS *</label>
                        <input type="number" step="0.0000001" name="latitud" id="latitud" required value="7.6043200" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="longitud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Longitud GPS *</label>
                        <input type="number" step="0.0000001" name="longitud" id="longitud" required value="-74.8091500" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>
            </div>

            <!-- 2. Información Botánica y Dasométrica -->
            <div class="forest-card p-6 space-y-4">
                <h3 class="font-heading font-bold text-sm text-emerald-950 flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i data-lucide="trees" class="w-4 h-4 text-emerald-700"></i>
                    2. Datos Botánicos y Mediciones Dasométricas
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="nombre_comun" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre Común *</label>
                        <input type="text" name="nombre_comun" id="nombre_comun" required placeholder="ej. Guayacán Amarillo" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="nombre_cientifico" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre Científico *</label>
                        <input type="text" name="nombre_cientifico" id="nombre_cientifico" required placeholder="ej. Handroanthus chrysanthus" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs italic focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="familia_botanica" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Familia Botánica *</label>
                        <input type="text" name="familia_botanica" id="familia_botanica" required placeholder="ej. Bignoniaceae" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label for="tipo_individuo" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tipo *</label>
                        <select name="tipo_individuo" id="tipo_individuo" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <option value="Árbol">Árbol</option>
                            <option value="Palma">Palma</option>
                            <option value="Arbusto">Arbusto</option>
                            <option value="Planta ornamental">Planta ornamental</option>
                        </select>
                    </div>
                    <div>
                        <label for="dap_cm" class="block text-xs font-semibold text-gray-700 uppercase mb-1">DAP (cm) *</label>
                        <input type="number" step="0.1" name="dap_cm" id="dap_cm" required placeholder="ej. 35.5" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="altura_total_m" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Altura Total (m) *</label>
                        <input type="number" step="0.1" name="altura_total_m" id="altura_total_m" required placeholder="ej. 12.0" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="diametro_copa_m" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Diám. Copa (m) *</label>
                        <input type="number" step="0.1" name="diametro_copa_m" id="diametro_copa_m" required placeholder="ej. 8.5" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="fecha_siembra" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Fecha de Siembra (Opcional)</label>
                        <input type="date" name="fecha_siembra" id="fecha_siembra" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="foto_principal" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Fotografía Principal del Espécimen</label>
                        <input type="file" name="foto_principal" id="foto_principal" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                </div>

                <div>
                    <label for="descripcion_general" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descripción General / Entorno</label>
                    <textarea name="descripcion_general" id="descripcion_general" rows="2" placeholder="Observaciones de desarrollo, cercanía a redes o andenes..." class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none"></textarea>
                </div>
            </div>

            <!-- 3. Evaluación Fitosanitaria Inicial -->
            <div class="forest-card p-6 space-y-4">
                <h3 class="font-heading font-bold text-sm text-emerald-950 flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i data-lucide="stethoscope" class="w-4 h-4 text-emerald-700"></i>
                    3. Estado Fitosanitario Inicial
                </h3>

                <div>
                    <label for="estado_salud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Condición de Salud *</label>
                    <select name="estado_salud" id="estado_salud" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                        <option value="Sano" selected>Sano</option>
                        <option value="En observación">En observación</option>
                        <option value="Enfermo">Enfermo</option>
                        <option value="Plagado">Plagado</option>
                        <option value="En riesgo">En riesgo</option>
                        <option value="Muerto en pie">Muerto en pie</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="diagnostico" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Diagnóstico Inicial</label>
                        <textarea name="diagnostico" id="diagnostico" rows="2" class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">Follaje y fuste en óptimas condiciones fitosanitarias.</textarea>
                    </div>
                    <div>
                        <label for="recomendacion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Recomendación Técnica</label>
                        <textarea name="recomendacion" id="recomendacion" rows="2" class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">Monitoreo rutinario y riego en temporada seca.</textarea>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-2 pt-2">
                <a href="<?= BASE_URL ?>admin/individuos/index.php" class="btn-forest-secondary text-xs py-2.5 px-4">Cancelar</a>
                <button type="submit" class="btn-forest-primary text-xs py-2.5 px-6 shadow-md">
                    <i data-lucide="check" class="w-4 h-4"></i> Guardar y Generar Placa QR
                </button>
            </div>

        </form>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
