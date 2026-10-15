<?php
/**
 * ForestID / ForestSRD - Alta Rápida de Individuo en Campo con GPS (OperarioEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/qrcode_helper.php';

requerir_rol(['OperarioEmincut', 'AdminEmincut']);

$pdo = getDBConnection();
$error = '';

$zonas = $pdo->query("SELECT id_zona, id_institucion, nombre_zona FROM `zonas` WHERE estado = 1 ORDER BY nombre_zona ASC")->fetchAll();
$instituciones = $pdo->query("SELECT id_institucion, nombre_institucion FROM `instituciones` WHERE estado = 1 ORDER BY nombre_institucion ASC")->fetchAll();

$codigo_sugerido = generar_codigo_placa($pdo, 'EBG');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf         = $_POST['csrf_token'] ?? '';
    $codigo_placa       = strtoupper(sanear($_POST['codigo_placa'] ?? ''));
    $id_zona            = $_POST['id_zona'] ?? '';
    $id_institucion     = $_POST['id_institucion'] ?? '';
    $nombre_comun       = sanear($_POST['nombre_comun'] ?? '');
    $nombre_cientifico  = sanear($_POST['nombre_cientifico'] ?? '');
    $familia_botanica   = sanear($_POST['familia_botanica'] ?? '');
    $tipo_individuo     = $_POST['tipo_individuo'] ?? 'Árbol';
    $latitud            = !empty($_POST['latitud']) ? (float)$_POST['latitud'] : 7.60432;
    $longitud           = !empty($_POST['longitud']) ? (float)$_POST['longitud'] : -74.80915;
    $dap_cm             = (float)($_POST['dap_cm'] ?? 0);
    $altura_total_m     = (float)($_POST['altura_total_m'] ?? 0);
    $diametro_copa_m    = (float)($_POST['diametro_copa_m'] ?? 0);
    $descripcion        = sanear($_POST['descripcion_general'] ?? '');
    $estado_salud       = $_POST['estado_salud'] ?? 'Sano';

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($codigo_placa) || empty($id_zona) || empty($id_institucion) || empty($nombre_comun)) {
        $error = 'Todos los campos obligatorios (*) son requeridos.';
    } elseif ($dap_cm <= 0 || $altura_total_m <= 0 || $diametro_copa_m <= 0) {
        $error = 'Las medidas dasométricas (DAP, Altura y Diámetro de Copa) deben ser valores numéricos mayores a cero.';
    } else {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `individuos_forestales` WHERE codigo_placa = :cod");
        $chk->execute([':cod' => $codigo_placa]);
        if ((int)$chk->fetchColumn() > 0) {
            $error = 'La placa ingresada ya existe en el censo.';
        } else {
            $foto_url = null;
            if (!empty($_FILES['foto_principal']) && $_FILES['foto_principal']['error'] !== UPLOAD_ERR_NO_FILE) {
                $sub = subir_imagen_segura($_FILES['foto_principal'], 'individuos');
                if ($sub['ok']) {
                    $foto_url = $sub['ruta'];
                }
            }

            $id_individuo = generar_uuid();
            $id_estado = generar_uuid();
            $id_usuario = $_SESSION['usuario']['id_usuario'];

            // Generar código QR SVG
            $urlFicha = BASE_URL . "publico/ficha.php?codigo=" . urlencode($codigo_placa);
            $qr_relativo = QRCodeGenerator::guardarQR($codigo_placa, $urlFicha);

            // Insertar espécimen
            $ins = $pdo->prepare("
                INSERT INTO `individuos_forestales` (
                    `id_individuo`, `codigo_placa`, `id_zona`, `id_institucion`,
                    `nombre_comun`, `nombre_cientifico`, `familia_botanica`, `tipo_individuo`,
                    `latitud`, `longitud`, `dap_cm`, `altura_total_m`, `diametro_copa_m`,
                    `foto_principal_url`, `descripcion_general`, `qr_url`, `registrado_por`, `estado_registro`, `fecha_registro`
                ) VALUES (
                    :id, :cod, :zona, :inst,
                    :com, :cie, :fam, :tipo,
                    :lat, :lng, :dap, :alt, :copa,
                    :foto, :desc, :qr, :user, 1, NOW()
                )
            ");
            $ins->execute([
                ':id'   => $id_individuo,
                ':cod'  => $codigo_placa,
                ':zona' => $id_zona,
                ':inst' => $id_institucion,
                ':com'  => $nombre_comun,
                ':cie'  => $nombre_cientifico ?: $nombre_comun,
                ':fam'  => $familia_botanica ?: 'No especificada',
                ':tipo' => $tipo_individuo,
                ':lat'  => $latitud,
                ':lng'  => $longitud,
                ':dap'  => $dap_cm,
                ':alt'  => $altura_total_m,
                ':copa' => $diametro_copa_m,
                ':foto' => $foto_url,
                ':desc' => $descripcion,
                ':qr'   => $qr_relativo,
                ':user' => $id_usuario
            ]);

            // Insertar estado
            $insEst = $pdo->prepare("
                INSERT INTO `estados_fitosanitarios` (
                    `id_estado`, `id_individuo`, `estado`, `diagnostico`, `recomendacion`, `evaluado_por`, `fecha_evaluacion`
                ) VALUES (
                    :id_est, :id_ind, :estado, 'Alta en campo por operario', 'Seguimiento programado', :user, NOW()
                )
            ");
            $insEst->execute([
                ':id_est' => $id_estado,
                ':id_ind' => $id_individuo,
                ':estado' => $estado_salud,
                ':user'   => $id_usuario
            ]);

            $pdo->prepare("UPDATE `individuos_forestales` SET `id_estado_fitosanitario_actual` = :id_est WHERE `id_individuo` = :id_ind")
                ->execute([':id_est' => $id_estado, ':id_ind' => $id_individuo]);

            registrar_log($pdo, $id_usuario, 'ALTA_CAMPO_OPERARIO', 'INDIVIDUOS_FORESTALES', [
                'codigo_placa' => $codigo_placa,
                'nombre_comun' => $nombre_comun
            ]);

            set_flash('success', "Árbol {$nombre_comun} ({$codigo_placa}) registrado exitosamente.");
            header('Location: ' . BASE_URL . 'operario/individuos/detalle.php?id=' . $id_individuo);
            exit;
        }
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Alta Rápida en Campo — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php 
    if (es_admin()) {
        require_once __DIR__ . '/../includes/sidebar_admin.php';
    } else {
        require_once __DIR__ . '/../includes/sidebar_operario.php';
    }
    ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-teal-700 uppercase tracking-widest">Trabajo de Campo • El Bagre</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Alta Rápida de Individuo</h1>
            </div>
            <a href="<?= BASE_URL ?>operario/dashboard.php" class="btn-forest-secondary text-xs py-2 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl text-xs text-rose-800 flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="registrar_individuo.php" enctype="multipart/form-data" class="space-y-6 max-w-2xl" onsubmit="const b=this.querySelector('button[type=submit]'); if(b && this.checkValidity()){ b.disabled=true; b.innerHTML='<span class=\'inline-block animate-spin mr-2\'>⏳</span> Guardando...'; }">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- Tarjeta 1: Placa y Geolocalización -->
            <div class="forest-card p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <h3 class="font-heading font-bold text-sm text-gray-900">Placa y Ubicación GPS</h3>
                    <button type="button" onclick="capturarGPS()" class="btn-forest-secondary text-xs py-1.5 px-2.5">
                        <i data-lucide="crosshair" class="w-3.5 h-3.5 text-emerald-600"></i> Capturar Mi GPS
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="codigo_placa" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Código de Placa *</label>
                        <input type="text" name="codigo_placa" id="codigo_placa" required value="<?= htmlspecialchars($codigo_sugerido) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs font-bold uppercase focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="id_zona" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Zona Territorial *</label>
                        <select name="id_zona" id="id_zona" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <option value="">Seleccione zona...</option>
                            <?php foreach ($zonas as $z): ?>
                                <option value="<?= $z['id_zona'] ?>"><?= htmlspecialchars($z['nombre_zona']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="id_institucion" value="<?= !empty($instituciones[0]['id_institucion']) ? $instituciones[0]['id_institucion'] : '22222222-2222-2222-2222-222222222201' ?>">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="latitud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Latitud GPS</label>
                        <input type="number" step="0.0000001" name="latitud" id="latitud" required value="7.6043200" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs font-mono">
                    </div>
                    <div>
                        <label for="longitud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Longitud GPS</label>
                        <input type="number" step="0.0000001" name="longitud" id="longitud" required value="-74.8091500" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs font-mono">
                    </div>
                </div>
            </div>

            <!-- Tarjeta 2: Datos del Árbol y Dasometría -->
            <div class="forest-card p-6 space-y-4">
                <h3 class="font-heading font-bold text-sm text-gray-900 border-b border-gray-100 pb-2">Identificación Botánica y Mediciones</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nombre_comun" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre Común *</label>
                        <input type="text" name="nombre_comun" id="nombre_comun" required placeholder="ej. Guayacán Rosado" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="nombre_cientifico" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre Científico</label>
                        <input type="text" name="nombre_cientifico" id="nombre_cientifico" placeholder="ej. Handroanthus chrysanthus" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs italic focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="dap_cm" class="block text-xs font-semibold text-gray-700 uppercase mb-1">DAP (cm) *</label>
                        <input type="number" step="0.1" min="0.1" name="dap_cm" id="dap_cm" required placeholder="ej. 25.0" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label for="altura_total_m" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Altura (m) *</label>
                        <input type="number" step="0.1" min="0.1" name="altura_total_m" id="altura_total_m" required placeholder="ej. 9.5" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label for="diametro_copa_m" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Copa (m) *</label>
                        <input type="number" step="0.1" min="0.1" name="diametro_copa_m" id="diametro_copa_m" required placeholder="ej. 6.0" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="estado_salud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Salud Fitosanitaria *</label>
                        <select name="estado_salud" id="estado_salud" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <option value="Sano" selected>Sano</option>
                            <option value="En observación">En observación</option>
                            <option value="Enfermo">Enfermo</option>
                            <option value="Plagado">Plagado</option>
                            <option value="En riesgo">En riesgo</option>
                        </select>
                    </div>
                    <div>
                        <label for="foto_principal" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Foto con Cámara</label>
                        <input type="file" name="foto_principal" id="foto_principal" accept="image/*" capture="environment" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                <a href="<?= BASE_URL ?>operario/dashboard.php" class="btn-forest-secondary text-sm min-h-[48px] py-3 px-5 justify-center">Cancelar</a>
                <button type="submit" class="btn-forest-primary text-sm min-h-[48px] py-3 px-6 shadow-md justify-center">
                    <i data-lucide="check" class="w-4 h-4"></i> Guardar en Campo
                </button>
            </div>
        </form>

    </main>
</div>

<script>
function capturarGPS() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => {
                document.getElementById('latitud').value = pos.coords.latitude.toFixed(7);
                document.getElementById('longitud').value = pos.coords.longitude.toFixed(7);
                alert("Coordenadas GPS capturadas con éxito: " + pos.coords.latitude.toFixed(5) + ", " + pos.coords.longitude.toFixed(5));
            },
            err => {
                alert("No se pudo obtener la ubicación GPS (" + err.message + "). Ingrese manualmente.");
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    } else {
        alert("Su dispositivo o navegador no soporta geolocalización.");
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
