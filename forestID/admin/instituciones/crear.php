<?php
/**
 * ForestID / ForestSRD - Crear Institución (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();
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

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($nombre) || empty($nit)) {
        $error = 'El nombre de la institución y el NIT son obligatorios.';
    } else {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `instituciones` WHERE nit = :nit");
        $chk->execute([':nit' => $nit]);
        if ((int)$chk->fetchColumn() > 0) {
            $error = 'Ya existe una institución registrada con este NIT.';
        } else {
            $id_nuevo = generar_uuid();
            $stmt = $pdo->prepare("
                INSERT INTO `instituciones` (
                    `id_institucion`, `nombre_institucion`, `nit`, `direccion`, `municipio`, 
                    `departamento`, `telefono_contacto`, `correo_contacto`, `estado`, `fecha_registro`
                ) VALUES (
                    :id, :nombre, :nit, :direccion, :municipio,
                    :depto, :telefono, :correo, 1, NOW()
                )
            ");
            $stmt->execute([
                ':id'        => $id_nuevo,
                ':nombre'    => $nombre,
                ':nit'       => $nit,
                ':direccion' => $direccion,
                ':municipio' => $municipio,
                ':depto'     => $departamento,
                ':telefono'  => $telefono,
                ':correo'    => $correo
            ]);

            registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'CREAR_INSTITUCION', 'INSTITUCIONES', [
                'id_institucion' => $id_nuevo,
                'nombre'         => $nombre
            ]);

            set_flash('success', "Institución {$nombre} registrada exitosamente.");
            header('Location: ' . BASE_URL . 'admin/instituciones/index.php');
            exit;
        }
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Nueva Institución — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Estructura Organizacional</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Registrar Nueva Institución</h1>
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
            <form method="POST" action="crear.php" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div>
                    <label for="nombre_institucion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Razón Social / Nombre *</label>
                    <input type="text" name="nombre_institucion" id="nombre_institucion" required placeholder="ej. Alcaldía de El Bagre" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nit" class="block text-xs font-semibold text-gray-700 uppercase mb-1">NIT / Identificación Fiscal *</label>
                        <input type="text" name="nit" id="nit" required placeholder="ej. 890.980.123-1" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="telefono_contacto" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Teléfono de Contacto</label>
                        <input type="text" name="telefono_contacto" id="telefono_contacto" placeholder="+57 3..." class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
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
                        <label for="direccion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Dirección Física</label>
                        <input type="text" name="direccion" id="direccion" placeholder="Calle / Carrera / Sector" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="correo_contacto" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Correo Electrónico</label>
                        <input type="email" name="correo_contacto" id="correo_contacto" placeholder="contacto@entidad.gov.co" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                    <a href="<?= BASE_URL ?>admin/instituciones/index.php" class="btn-forest-secondary text-xs py-2.5 px-4">Cancelar</a>
                    <button type="submit" class="btn-forest-primary text-xs py-2.5 px-5">
                        <i data-lucide="check" class="w-4 h-4"></i> Guardar Institución
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
