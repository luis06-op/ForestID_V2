<?php
/**
 * ForestID / ForestSRD - Editar Institución (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$id = $_GET['id'] ?? '';
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM `instituciones` WHERE `id_institucion` = :id");
$stmt->execute([':id' => $id]);
$inst = $stmt->fetch();

if (!$inst) {
    set_flash('error', 'La institución solicitada no existe.');
    header('Location: ' . BASE_URL . 'admin/instituciones/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf   = $_POST['csrf_token'] ?? '';
    $nombre       = sanear($_POST['nombre_institucion'] ?? '');
    $nit          = sanear($_POST['nit'] ?? '');
    $direccion    = sanear($_POST['direccion'] ?? '');
    $municipio    = sanear($_POST['municipio'] ?? 'El Bagre');
    $departamento = sanear($_POST['departamento'] ?? 'Antioquia');
    $telefono     = sanear($_POST['telefono_contacto'] ?? '');
    $correo       = sanear($_POST['correo_contacto'] ?? '');
    $estado       = isset($_POST['estado']) ? 1 : 0;

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($nombre) || empty($nit)) {
        $error = 'El nombre de la institución y el NIT son obligatorios.';
    } else {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `instituciones` WHERE nit = :nit AND id_institucion != :id");
        $chk->execute([':nit' => $nit, ':id' => $id]);
        if ((int)$chk->fetchColumn() > 0) {
            $error = 'Otra institución ya se encuentra registrada con ese NIT.';
        } else {
            $upd = $pdo->prepare("
                UPDATE `instituciones`
                SET `nombre_institucion` = :nombre, `nit` = :nit, `direccion` = :direccion,
                    `municipio` = :municipio, `departamento` = :depto, `telefono_contacto` = :telefono,
                    `correo_contacto` = :correo, `estado` = :estado
                WHERE `id_institucion` = :id
            ");
            $upd->execute([
                ':nombre'    => $nombre,
                ':nit'       => $nit,
                ':direccion' => $direccion,
                ':municipio' => $municipio,
                ':depto'     => $departamento,
                ':telefono'  => $telefono,
                ':correo'    => $correo,
                ':estado'    => $estado,
                ':id'        => $id
            ]);

            registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'EDITAR_INSTITUCION', 'INSTITUCIONES', [
                'id_institucion' => $id,
                'nombre'         => $nombre
            ]);

            set_flash('success', "Institución {$nombre} actualizada exitosamente.");
            header('Location: ' . BASE_URL . 'admin/instituciones/index.php');
            exit;
        }
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Editar Institución — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Estructura Organizacional</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Editar Institución</h1>
            </div>
            <a href="<?= BASE_URL ?>admin/instituciones/index.php" class="btn-forest-secondary text-xs py-2 px-3">
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

                <div>
                    <label for="nombre_institucion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Razón Social / Nombre *</label>
                    <input type="text" name="nombre_institucion" id="nombre_institucion" required value="<?= htmlspecialchars($inst['nombre_institucion']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nit" class="block text-xs font-semibold text-gray-700 uppercase mb-1">NIT / Identificación Fiscal *</label>
                        <input type="text" name="nit" id="nit" required value="<?= htmlspecialchars($inst['nit']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="telefono_contacto" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Teléfono de Contacto</label>
                        <input type="text" name="telefono_contacto" id="telefono_contacto" value="<?= htmlspecialchars($inst['telefono_contacto'] ?? '') ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="municipio" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Municipio *</label>
                        <input type="text" name="municipio" id="municipio" required value="<?= htmlspecialchars($inst['municipio']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="departamento" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Departamento *</label>
                        <input type="text" name="departamento" id="departamento" required value="<?= htmlspecialchars($inst['departamento']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="direccion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Dirección Física</label>
                        <input type="text" name="direccion" id="direccion" value="<?= htmlspecialchars($inst['direccion'] ?? '') ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="correo_contacto" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Correo Electrónico</label>
                        <input type="email" name="correo_contacto" id="correo_contacto" value="<?= htmlspecialchars($inst['correo_contacto'] ?? '') ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="estado" value="1" <?= ($inst['estado'] == 1) ? 'checked' : '' ?> class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="text-xs font-semibold text-gray-700">Institución Activa</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                    <a href="<?= BASE_URL ?>admin/instituciones/index.php" class="btn-forest-secondary text-xs py-2.5 px-4">Cancelar</a>
                    <button type="submit" class="btn-forest-primary text-xs py-2.5 px-5">
                        <i data-lucide="save" class="w-4 h-4"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
