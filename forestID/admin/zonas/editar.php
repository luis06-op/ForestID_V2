<?php
/**
 * ForestID / ForestSRD - Editar Zona Territorial (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$id = $_GET['id'] ?? '';
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM `zonas` WHERE `id_zona` = :id");
$stmt->execute([':id' => $id]);
$zona = $stmt->fetch();

if (!$zona) {
    set_flash('error', 'La zona solicitada no existe.');
    header('Location: ' . BASE_URL . 'admin/zonas/index.php');
    exit;
}

$error = '';
$instituciones = $pdo->query("SELECT id_institucion, nombre_institucion FROM `instituciones` WHERE estado = 1 ORDER BY nombre_institucion ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf     = $_POST['csrf_token'] ?? '';
    $id_institucion = $_POST['id_institucion'] ?? '';
    $nombre_zona    = sanear($_POST['nombre_zona'] ?? '');
    $descripcion    = sanear($_POST['descripcion'] ?? '');
    $municipio      = sanear($_POST['municipio'] ?? 'El Bagre');
    $departamento   = sanear($_POST['departamento'] ?? 'Antioquia');
    $latitud        = !empty($_POST['latitud']) ? (float)$_POST['latitud'] : null;
    $longitud       = !empty($_POST['longitud']) ? (float)$_POST['longitud'] : null;
    $estado         = isset($_POST['estado']) ? 1 : 0;

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($nombre_zona) || empty($id_institucion)) {
        $error = 'El nombre de la zona y la institución son obligatorios.';
    } else {
        $upd = $pdo->prepare("
            UPDATE `zonas`
            SET `id_institucion` = :id_inst, `nombre_zona` = :nombre, `descripcion` = :desc,
                `municipio` = :muni, `departamento` = :depto, `latitud_referencia` = :lat,
                `longitud_referencia` = :lng, `estado` = :estado
            WHERE `id_zona` = :id
        ");
        $upd->execute([
            ':id_inst' => $id_institucion,
            ':nombre'  => $nombre_zona,
            ':desc'    => $descripcion,
            ':muni'    => $municipio,
            ':depto'   => $departamento,
            ':lat'     => $latitud,
            ':lng'     => $longitud,
            ':estado'  => $estado,
            ':id'      => $id
        ]);

        registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'EDITAR_ZONA', 'ZONAS', [
            'id_zona' => $id,
            'nombre'  => $nombre_zona
        ]);

        set_flash('success', "Zona {$nombre_zona} actualizada exitosamente.");
        header('Location: ' . BASE_URL . 'admin/zonas/index.php');
        exit;
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Editar Zona — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Territorio y Cobertura</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Editar Zona Territorial</h1>
            </div>
            <a href="<?= BASE_URL ?>admin/zonas/index.php" class="btn-forest-secondary text-xs py-2 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl text-xs text-rose-800 flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="forest-card p-6 sm:p-8 max-w-2xl">
            <form method="POST" action="editar.php?id=<?= urlencode($id) ?>" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nombre_zona" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre de la Zona *</label>
                        <input type="text" name="nombre_zona" id="nombre_zona" required value="<?= htmlspecialchars($zona['nombre_zona']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="id_institucion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Institución Responsable *</label>
                        <select name="id_institucion" id="id_institucion" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <?php foreach ($instituciones as $inst): ?>
                                <option value="<?= $inst['id_institucion'] ?>" <?= ($zona['id_institucion'] === $inst['id_institucion']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($inst['nombre_institucion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="descripcion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descripción / Delimitación</label>
                    <textarea name="descripcion" id="descripcion" rows="2" class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none"><?= htmlspecialchars($zona['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="municipio" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Municipio *</label>
                        <input type="text" name="municipio" id="municipio" required value="<?= htmlspecialchars($zona['municipio']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="departamento" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Departamento *</label>
                        <input type="text" name="departamento" id="departamento" required value="<?= htmlspecialchars($zona['departamento']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="latitud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Latitud de Referencia</label>
                        <input type="number" step="0.0000001" name="latitud" id="latitud" value="<?= htmlspecialchars($zona['latitud_referencia'] ?? '') ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none font-mono">
                    </div>
                    <div>
                        <label for="longitud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Longitud de Referencia</label>
                        <input type="number" step="0.0000001" name="longitud" id="longitud" value="<?= htmlspecialchars($zona['longitud_referencia'] ?? '') ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none font-mono">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="estado" value="1" <?= ($zona['estado'] == 1) ? 'checked' : '' ?> class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="text-xs font-semibold text-gray-700">Zona Activa</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                    <a href="<?= BASE_URL ?>admin/zonas/index.php" class="btn-forest-secondary text-xs py-2.5 px-4">Cancelar</a>
                    <button type="submit" class="btn-forest-primary text-xs py-2.5 px-5">
                        <i data-lucide="save" class="w-4 h-4"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
