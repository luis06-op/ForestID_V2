<?php
/**
 * ForestID / ForestSRD - Editar Individuo Forestal (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$id = $_GET['id'] ?? '';
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM `individuos_forestales` WHERE `id_individuo` = :id");
$stmt->execute([':id' => $id]);
$arbol = $stmt->fetch();

if (!$arbol) {
    set_flash('error', 'El individuo forestal no existe.');
    header('Location: ' . BASE_URL . 'admin/individuos/index.php');
    exit;
}

$error = '';
$zonas = $pdo->query("SELECT id_zona, nombre_zona FROM `zonas` WHERE estado = 1 ORDER BY nombre_zona ASC")->fetchAll();
$instituciones = $pdo->query("SELECT id_institucion, nombre_institucion FROM `instituciones` WHERE estado = 1 ORDER BY nombre_institucion ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf         = $_POST['csrf_token'] ?? '';
    $id_zona            = $_POST['id_zona'] ?? '';
    $id_institucion     = $_POST['id_institucion'] ?? '';
    $nombre_comun       = sanear($_POST['nombre_comun'] ?? '');
    $nombre_cientifico  = sanear($_POST['nombre_cientifico'] ?? '');
    $familia_botanica   = sanear($_POST['familia_botanica'] ?? '');
    $tipo_individuo     = $_POST['tipo_individuo'] ?? 'Árbol';
    $fecha_siembra      = !empty($_POST['fecha_siembra']) ? $_POST['fecha_siembra'] : null;
    $latitud            = !empty($_POST['latitud']) ? (float)$_POST['latitud'] : $arbol['latitud'];
    $longitud           = !empty($_POST['longitud']) ? (float)$_POST['longitud'] : $arbol['longitud'];
    $dap_cm             = (float)($_POST['dap_cm'] ?? $arbol['dap_cm']);
    $altura_total_m     = (float)($_POST['altura_total_m'] ?? $arbol['altura_total_m']);
    $diametro_copa_m    = (float)($_POST['diametro_copa_m'] ?? $arbol['diametro_copa_m']);
    $descripcion        = sanear($_POST['descripcion_general'] ?? '');

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($id_zona) || empty($id_institucion) || empty($nombre_comun) || empty($nombre_cientifico)) {
        $error = 'Todos los campos obligatorios (*) deben ser completados.';
    } else {
        // Manejo de nueva foto opcional
        $foto_url = $arbol['foto_principal_url'];
        if (!empty($_FILES['foto_principal']) && $_FILES['foto_principal']['error'] !== UPLOAD_ERR_NO_FILE) {
            $subida = subir_imagen_segura($_FILES['foto_principal'], 'individuos');
            if ($subida['ok']) {
                $foto_url = $subida['ruta'];
            } else {
                $error = $subida['error'];
            }
        }

        if (empty($error)) {
            $stmtUpd = $pdo->prepare("
                UPDATE `individuos_forestales`
                SET `id_zona` = :id_zona, `id_institucion` = :id_inst,
                    `nombre_comun` = :nom_com, `nombre_cientifico` = :nom_cie,
                    `familia_botanica` = :familia, `tipo_individuo` = :tipo,
                    `fecha_siembra` = :siembra, `latitud` = :lat, `longitud` = :lng,
                    `dap_cm` = :dap, `altura_total_m` = :alt, `diametro_copa_m` = :copa,
                    `foto_principal_url` = :foto, `descripcion_general` = :desc
                WHERE `id_individuo` = :id
            ");
            $stmtUpd->execute([
                ':id_zona'  => $id_zona,
                ':id_inst'  => $id_institucion,
                ':nom_com'  => $nombre_comun,
                ':nom_cie'  => $nombre_cientifico,
                ':familia'  => $familia_botanica,
                ':tipo'     => $tipo_individuo,
                ':siembra'  => $fecha_siembra,
                ':lat'      => $latitud,
                ':lng'      => $longitud,
                ':dap'      => $dap_cm,
                ':alt'      => $altura_total_m,
                ':copa'     => $diametro_copa_m,
                ':foto'     => $foto_url,
                ':desc'     => $descripcion,
                ':id'       => $id
            ]);

            registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'EDITAR_INDIVIDUO', 'INDIVIDUOS_FORESTALES', [
                'id_individuo' => $id,
                'codigo_placa' => $arbol['codigo_placa']
            ]);

            set_flash('success', "Individuo {$nombre_comun} ({$arbol['codigo_placa']}) actualizado correctamente.");
            header('Location: ' . BASE_URL . 'admin/individuos/detalle.php?id=' . $id);
            exit;
        }
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Editar Individuo Forestal — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Edición de Censo</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">
                    Editar <?= htmlspecialchars($arbol['nombre_comun']) ?> (<?= htmlspecialchars($arbol['codigo_placa']) ?>)
                </h1>
            </div>
            <a href="<?= BASE_URL ?>admin/individuos/detalle.php?id=<?= $arbol['id_individuo'] ?>" class="btn-forest-secondary text-xs py-2 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver al Detalle
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl text-xs text-rose-800 flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="editar.php?id=<?= urlencode($id) ?>" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="forest-card p-6 space-y-4">
                <h3 class="font-heading font-bold text-sm text-emerald-950 flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i data-lucide="qr-code" class="w-4 h-4 text-emerald-700"></i>
                    Ubicación y Placa
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Código de Placa (Inmutable)</label>
                        <input type="text" disabled value="<?= htmlspecialchars($arbol['codigo_placa']) ?>" class="w-full py-2.5 px-3 bg-gray-100 border border-gray-300 rounded-xl text-xs font-bold text-gray-700">
                    </div>
                    <div>
                        <label for="id_zona" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Zona Territorial *</label>
                        <select name="id_zona" id="id_zona" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <?php foreach ($zonas as $z): ?>
                                <option value="<?= $z['id_zona'] ?>" <?= ($arbol['id_zona'] === $z['id_zona']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($z['nombre_zona']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="id_institucion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Institución *</label>
                        <select name="id_institucion" id="id_institucion" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <?php foreach ($instituciones as $inst): ?>
                                <option value="<?= $inst['id_institucion'] ?>" <?= ($arbol['id_institucion'] === $inst['id_institucion']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($inst['nombre_institucion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="latitud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Latitud GPS *</label>
                        <input type="number" step="0.0000001" name="latitud" id="latitud" required value="<?= htmlspecialchars($arbol['latitud']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="longitud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Longitud GPS *</label>
                        <input type="number" step="0.0000001" name="longitud" id="longitud" required value="<?= htmlspecialchars($arbol['longitud']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>
            </div>

            <div class="forest-card p-6 space-y-4">
                <h3 class="font-heading font-bold text-sm text-emerald-950 flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i data-lucide="trees" class="w-4 h-4 text-emerald-700"></i>
                    Datos Botánicos y Dasometría
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="nombre_comun" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre Común *</label>
                        <input type="text" name="nombre_comun" id="nombre_comun" required value="<?= htmlspecialchars($arbol['nombre_comun']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="nombre_cientifico" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre Científico *</label>
                        <input type="text" name="nombre_cientifico" id="nombre_cientifico" required value="<?= htmlspecialchars($arbol['nombre_cientifico']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs italic focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="familia_botanica" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Familia Botánica *</label>
                        <input type="text" name="familia_botanica" id="familia_botanica" required value="<?= htmlspecialchars($arbol['familia_botanica']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label for="tipo_individuo" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tipo *</label>
                        <select name="tipo_individuo" id="tipo_individuo" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <option value="Árbol" <?= ($arbol['tipo_individuo'] === 'Árbol') ? 'selected' : '' ?>>Árbol</option>
                            <option value="Palma" <?= ($arbol['tipo_individuo'] === 'Palma') ? 'selected' : '' ?>>Palma</option>
                            <option value="Arbusto" <?= ($arbol['tipo_individuo'] === 'Arbusto') ? 'selected' : '' ?>>Arbusto</option>
                            <option value="Planta ornamental" <?= ($arbol['tipo_individuo'] === 'Planta ornamental') ? 'selected' : '' ?>>Planta ornamental</option>
                        </select>
                    </div>
                    <div>
                        <label for="dap_cm" class="block text-xs font-semibold text-gray-700 uppercase mb-1">DAP (cm) *</label>
                        <input type="number" step="0.1" name="dap_cm" id="dap_cm" required value="<?= htmlspecialchars($arbol['dap_cm']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="altura_total_m" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Altura (m) *</label>
                        <input type="number" step="0.1" name="altura_total_m" id="altura_total_m" required value="<?= htmlspecialchars($arbol['altura_total_m']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="diametro_copa_m" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Copa (m) *</label>
                        <input type="number" step="0.1" name="diametro_copa_m" id="diametro_copa_m" required value="<?= htmlspecialchars($arbol['diametro_copa_m']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="fecha_siembra" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Fecha de Siembra</label>
                        <input type="date" name="fecha_siembra" id="fecha_siembra" value="<?= htmlspecialchars($arbol['fecha_siembra'] ?? '') ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="foto_principal" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Reemplazar Fotografía</label>
                        <input type="file" name="foto_principal" id="foto_principal" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                </div>

                <div>
                    <label for="descripcion_general" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descripción / Entorno</label>
                    <textarea name="descripcion_general" id="descripcion_general" rows="2" class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none"><?= htmlspecialchars($arbol['descripcion_general'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <a href="<?= BASE_URL ?>admin/individuos/detalle.php?id=<?= $arbol['id_individuo'] ?>" class="btn-forest-secondary text-xs py-2.5 px-4">Cancelar</a>
                <button type="submit" class="btn-forest-primary text-xs py-2.5 px-6 shadow-md">
                    <i data-lucide="save" class="w-4 h-4"></i> Guardar Cambios
                </button>
            </div>
        </form>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
