<?php
/**
 * ForestID / ForestSRD - Crear Zona Territorial (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();
$error = '';
$instituciones = $pdo->query("SELECT id_institucion, nombre_institucion FROM `instituciones` WHERE estado = 1 ORDER BY nombre_institucion ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf     = $_POST['csrf_token'] ?? '';
    $id_institucion = $_POST['id_institucion'] ?? '';
    $nombre_zona    = sanear($_POST['nombre_zona'] ?? '');
    $descripcion    = sanear($_POST['descripcion'] ?? '');
    $municipio      = sanear($_POST['municipio'] ?? 'El Bagre');
    $departamento   = sanear($_POST['departamento'] ?? 'Antioquia');
    $latitud        = !empty($_POST['latitud']) ? (float)$_POST['latitud'] : 7.60432;
    $longitud       = !empty($_POST['longitud']) ? (float)$_POST['longitud'] : -74.80915;

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($nombre_zona) || empty($id_institucion)) {
        $error = 'El nombre de la zona y la institución son obligatorios.';
    } else {
        $id_nuevo = generar_uuid();
        $stmt = $pdo->prepare("
            INSERT INTO `zonas` (
                `id_zona`, `id_institucion`, `nombre_zona`, `descripcion`, 
                `municipio`, `departamento`, `latitud_referencia`, `longitud_referencia`, `estado`, `fecha_creacion`
            ) VALUES (
                :id, :id_inst, :nombre, :desc,
                :muni, :depto, :lat, :lng, 1, NOW()
            )
        ");
        $stmt->execute([
            ':id'      => $id_nuevo,
            ':id_inst' => $id_institucion,
            ':nombre'  => $nombre_zona,
            ':desc'    => $descripcion,
            ':muni'    => $municipio,
            ':depto'   => $departamento,
            ':lat'     => $latitud,
            ':lng'     => $longitud
        ]);

        registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'CREAR_ZONA', 'ZONAS', [
            'id_zona' => $id_nuevo,
            'nombre'  => $nombre_zona
        ]);

        set_flash('success', "Zona {$nombre_zona} registrada exitosamente.");
        header('Location: ' . BASE_URL . 'admin/zonas/index.php');
        exit;
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Nueva Zona Territorial — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Territorio y Cobertura</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Registrar Zona Territorial</h1>
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
            <form method="POST" action="crear.php" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nombre_zona" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre de la Zona / Parque / Sector *</label>
                        <input type="text" name="nombre_zona" id="nombre_zona" required placeholder="ej. Parque Lineal Los Mangos" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="id_institucion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Institución Responsable *</label>
                        <select name="id_institucion" id="id_institucion" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <option value="">Seleccione una institución...</option>
                            <?php foreach ($instituciones as $inst): ?>
                                <option value="<?= $inst['id_institucion'] ?>"><?= htmlspecialchars($inst['nombre_institucion']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="descripcion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descripción / Delimitación</label>
                    <textarea name="descripcion" id="descripcion" rows="2" placeholder="Detalles de linderos, tipo de suelo, acceso..." class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="municipio" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Municipio *</label>
                        <input type="text" name="municipio" id="municipio" required value="El Bagre" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="departamento" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Departamento *</label>
                        <input type="text" name="departamento" id="departamento" required value="Antioquia" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="latitud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Latitud de Referencia</label>
                        <input type="number" step="0.0000001" name="latitud" id="latitud" value="7.6043200" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none font-mono">
                    </div>
                    <div>
                        <label for="longitud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Longitud de Referencia</label>
                        <input type="number" step="0.0000001" name="longitud" id="longitud" value="-74.8091500" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none font-mono">
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                    <a href="<?= BASE_URL ?>admin/zonas/index.php" class="btn-forest-secondary text-xs py-2.5 px-4">Cancelar</a>
                    <button type="submit" class="btn-forest-primary text-xs py-2.5 px-5">
                        <i data-lucide="check" class="w-4 h-4"></i> Guardar Zona
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
