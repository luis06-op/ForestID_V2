<?php
/**
 * ForestID / ForestSRD - Editar Usuario (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$id = $_GET['id'] ?? '';
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM `usuarios` WHERE `id_usuario` = :id");
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    set_flash('error', 'El usuario solicitado no existe.');
    header('Location: ' . BASE_URL . 'admin/usuarios/index.php');
    exit;
}

$error = '';
$roles = $pdo->query("SELECT id_rol, nombre_rol, descripcion FROM `roles` ORDER BY nombre_rol ASC")->fetchAll();
$instituciones = $pdo->query("SELECT id_institucion, nombre_institucion FROM `instituciones` WHERE estado = 1 ORDER BY nombre_institucion ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf     = $_POST['csrf_token'] ?? '';
    $nombres        = sanear($_POST['nombres'] ?? '');
    $apellidos      = sanear($_POST['apellidos'] ?? '');
    $correo         = sanear($_POST['correo'] ?? '');
    $telefono       = sanear($_POST['telefono'] ?? '');
    $id_rol         = $_POST['id_rol'] ?? '';
    $id_institucion = !empty($_POST['id_institucion']) ? $_POST['id_institucion'] : null;
    $estado         = isset($_POST['estado']) ? 1 : 0;
    $password_nueva = $_POST['password_nueva'] ?? '';

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($nombres) || empty($apellidos) || empty($correo) || empty($id_rol)) {
        $error = 'Todos los campos obligatorios (*) deben ser completados.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no tiene un formato válido.';
    } else {
        // Verificar correo único excluyendo al usuario actual
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `usuarios` WHERE correo = :correo AND id_usuario != :id");
        $chk->execute([':correo' => $correo, ':id' => $id]);
        if ((int)$chk->fetchColumn() > 0) {
            $error = 'Otro usuario ya tiene registrado ese correo electrónico.';
        } else {
            // Actualizar datos
            if (!empty($password_nueva)) {
                $hash = password_hash($password_nueva, PASSWORD_BCRYPT);
                $stmtUpd = $pdo->prepare("
                    UPDATE `usuarios` 
                    SET `nombres` = :nombres, `apellidos` = :apellidos, `correo` = :correo, 
                        `telefono` = :telefono, `id_rol` = :id_rol, `id_institucion` = :id_institucion,
                        `estado` = :estado, `password_hash` = :hash
                    WHERE `id_usuario` = :id
                ");
                $stmtUpd->execute([
                    ':nombres' => $nombres, ':apellidos' => $apellidos, ':correo' => $correo,
                    ':telefono' => $telefono, ':id_rol' => $id_rol, ':id_institucion' => $id_institucion,
                    ':estado' => $estado, ':hash' => $hash, ':id' => $id
                ]);
            } else {
                $stmtUpd = $pdo->prepare("
                    UPDATE `usuarios` 
                    SET `nombres` = :nombres, `apellidos` = :apellidos, `correo` = :correo, 
                        `telefono` = :telefono, `id_rol` = :id_rol, `id_institucion` = :id_institucion,
                        `estado` = :estado
                    WHERE `id_usuario` = :id
                ");
                $stmtUpd->execute([
                    ':nombres' => $nombres, ':apellidos' => $apellidos, ':correo' => $correo,
                    ':telefono' => $telefono, ':id_rol' => $id_rol, ':id_institucion' => $id_institucion,
                    ':estado' => $estado, ':id' => $id
                ]);
            }

            registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'EDITAR_USUARIO', 'USUARIOS', [
                'id_usuario_modificado' => $id,
                'correo'                => $correo
            ]);

            set_flash('success', "Usuario {$nombres} {$apellidos} actualizado correctamente.");
            header('Location: ' . BASE_URL . 'admin/usuarios/index.php');
            exit;
        }
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Editar Usuario — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Administración de Personal</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Editar Usuario</h1>
            </div>
            <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="btn-forest-secondary text-xs py-2 px-3">
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
                        <label for="nombres" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombres *</label>
                        <input type="text" name="nombres" id="nombres" required value="<?= htmlspecialchars($usuario['nombres']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="apellidos" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Apellidos *</label>
                        <input type="text" name="apellidos" id="apellidos" required value="<?= htmlspecialchars($usuario['apellidos']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="correo" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Correo Electrónico *</label>
                        <input type="email" name="correo" id="correo" required value="<?= htmlspecialchars($usuario['correo']) ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="telefono" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Teléfono Móvil</label>
                        <input type="text" name="telefono" id="telefono" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="id_rol" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Rol en el Sistema *</label>
                        <select name="id_rol" id="id_rol" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id_rol'] ?>" <?= ($usuario['id_rol'] === $r['id_rol']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['nombre_rol']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="id_institucion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Institución</label>
                        <select name="id_institucion" id="id_institucion" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <option value="">Ninguna / Independiente</option>
                            <?php foreach ($instituciones as $inst): ?>
                                <option value="<?= $inst['id_institucion'] ?>" <?= ($usuario['id_institucion'] === $inst['id_institucion']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($inst['nombre_institucion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="estado" value="1" <?= ($usuario['estado'] == 1) ? 'checked' : '' ?> class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="text-xs font-semibold text-gray-700">Usuario Activo (Permitir acceso al sistema)</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-gray-100 space-y-1">
                    <label for="password_nueva" class="block text-xs font-semibold text-gray-700 uppercase">Cambiar Contraseña (Opcional)</label>
                    <input type="password" name="password_nueva" id="password_nueva" minlength="6" placeholder="Dejar en blanco para mantener la contraseña actual" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                    <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="btn-forest-secondary text-xs py-2.5 px-4">Cancelar</a>
                    <button type="submit" class="btn-forest-primary text-xs py-2.5 px-5">
                        <i data-lucide="save" class="w-4 h-4"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
