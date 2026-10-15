<?php
/**
 * ForestID / ForestSRD - Gestión de Usuarios y Roles (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();

// Acción de activar/desactivar usuario vía GET con CSRF
if (isset($_GET['toggle_id'])) {
    $toggle_id = $_GET['toggle_id'];
    $csrf = $_GET['csrf'] ?? '';
    
    if (validar_token_csrf($csrf)) {
        // Evitar que el admin se desactive a sí mismo
        if ($toggle_id === $_SESSION['usuario']['id_usuario']) {
            set_flash('error', 'No puede desactivar su propia cuenta de administrador.');
        } else {
            $stmt = $pdo->prepare("UPDATE `usuarios` SET `estado` = IF(estado = 1, 0, 1) WHERE `id_usuario` = :id");
            $stmt->execute([':id' => $toggle_id]);
            registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'TOGGLE_ESTADO_USUARIO', 'USUARIOS', ['id_usuario_afectado' => $toggle_id]);
            set_flash('success', 'Estado del usuario actualizado correctamente.');
        }
    } else {
        set_flash('error', 'Token de seguridad inválido.');
    }
    header('Location: ' . BASE_URL . 'admin/usuarios/index.php');
    exit;
}

$q = sanear($_GET['q'] ?? '');
$params = [];
$where = "1=1";

if (!empty($q)) {
    $where = "(u.nombres LIKE :q OR u.apellidos LIKE :q OR u.correo LIKE :q OR r.nombre_rol LIKE :q)";
    $params[':q'] = "%{$q}%";
}

$stmt = $pdo->prepare("
    SELECT u.*, r.nombre_rol, i.nombre_institucion
    FROM `usuarios` u
    INNER JOIN `roles` r ON u.id_rol = r.id_rol
    LEFT JOIN `instituciones` i ON u.id_institucion = i.id_institucion
    WHERE {$where}
    ORDER BY u.fecha_creacion DESC
");
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Gestión de Usuarios — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Seguridad y Accesos</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">
                    Usuarios del Sistema
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Administración de cuentas, asignación de roles y control de estado de acceso.
                </p>
            </div>

            <a href="<?= BASE_URL ?>admin/usuarios/crear.php" class="btn-forest-primary text-xs py-2.5 px-4 shadow-sm">
                <i data-lucide="user-plus" class="w-4 h-4"></i> Crear Nuevo Usuario
            </a>
        </div>

        <!-- Buscador -->
        <div class="forest-card p-4">
            <form method="GET" action="index.php" class="flex gap-2">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre, correo o rol..."
                           class="w-full pl-9 pr-4 py-2 bg-white border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                </div>
                <button type="submit" class="btn-forest-primary text-xs py-2 px-4">
                    Buscar
                </button>
                <?php if (!empty($q)): ?>
                    <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="btn-forest-secondary text-xs py-2 px-3">
                        Limpiar
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="forest-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 uppercase font-semibold">
                        <tr>
                            <th class="p-4">Usuario</th>
                            <th class="p-4">Correo</th>
                            <th class="p-4">Rol</th>
                            <th class="p-4">Institución</th>
                            <th class="p-4">Estado</th>
                            <th class="p-4">Último Acceso</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-400 italic">
                                    No se encontraron usuarios registrados con los criterios indicados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-4 font-semibold text-gray-900">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-[11px]">
                                                <?= strtoupper(substr($u['nombres'], 0, 1) . substr($u['apellidos'], 0, 1)) ?>
                                            </div>
                                            <span><?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos']) ?></span>
                                        </div>
                                    </td>
                                    <td class="p-4"><?= htmlspecialchars($u['correo']) ?></td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-bold 
                                            <?= ($u['nombre_rol'] === 'AdminEmincut') ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : (($u['nombre_rol'] === 'OperarioEmincut') ? 'bg-teal-100 text-teal-800 border border-teal-300' : 'bg-gray-100 text-gray-700') ?>">
                                            <?= htmlspecialchars($u['nombre_rol']) ?>
                                        </span>
                                    </td>
                                    <td class="p-4"><?= htmlspecialchars($u['nombre_institucion'] ?? 'Emincut S.A.S.') ?></td>
                                    <td class="p-4">
                                        <?php if ($u['estado'] == 1): ?>
                                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 text-rose-700 font-semibold">
                                                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-gray-400">
                                        <?= !empty($u['ultimo_acceso']) ? formatear_fecha($u['ultimo_acceso'], true) : 'Nunca' ?>
                                    </td>
                                    <td class="p-4 text-right space-x-1">
                                        <a href="<?= BASE_URL ?>admin/usuarios/editar.php?id=<?= $u['id_usuario'] ?>" class="p-1.5 rounded-lg bg-gray-100 hover:bg-emerald-100 text-gray-600 hover:text-emerald-800 inline-flex" title="Editar">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </a>
                                        <?php if ($u['id_usuario'] !== $_SESSION['usuario']['id_usuario']): ?>
                                            <a href="<?= BASE_URL ?>admin/usuarios/index.php?toggle_id=<?= $u['id_usuario'] ?>&csrf=<?= $csrf_token ?>" 
                                               data-confirm="¿Está seguro de cambiar el estado de este usuario?"
                                               class="p-1.5 rounded-lg <?= ($u['estado'] == 1) ? 'bg-rose-50 text-rose-600 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' ?> inline-flex" 
                                               title="<?= ($u['estado'] == 1) ? 'Desactivar' : 'Activar' ?>">
                                                <i data-lucide="<?= ($u['estado'] == 1) ? 'user-x' : 'user-check' ?>" class="w-4 h-4"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
