<?php
/**
 * ForestID / ForestSRD - Crear Nuevo Usuario (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();
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
    $password       = $_POST['password'] ?? '';

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($nombres) || empty($apellidos) || empty($correo) || empty($id_rol) || empty($password)) {
        $error = 'Todos los campos marcados con (*) son obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico ingresado no tiene un formato válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        // Verificar correo único
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `usuarios` WHERE correo = :correo");
        $chk->execute([':correo' => $correo]);
        if ((int)$chk->fetchColumn() > 0) {
            $error = 'Ya existe un usuario registrado con este correo electrónico.';
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $id_nuevo = generar_uuid();

            $stmt = $pdo->prepare("
                INSERT INTO `usuarios` (
                    `id_usuario`, `id_rol`, `id_institucion`, `nombres`, `apellidos`, 
                    `correo`, `password_hash`, `telefono`, `estado`, `fecha_creacion`
                ) VALUES (
                    :id, :id_rol, :id_institucion, :nombres, :apellidos,
                    :correo, :hash, :telefono, 1, NOW()
                )
            ");
            $stmt->execute([
                ':id'             => $id_nuevo,
                ':id_rol'         => $id_rol,
                ':id_institucion' => $id_institucion,
                ':nombres'        => $nombres,
                ':apellidos'      => $apellidos,
                ':correo'         => $correo,
                ':hash'           => $password_hash,
                ':telefono'       => $telefono
            ]);

            registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'CREAR_USUARIO', 'USUARIOS', [
                'id_usuario_creado' => $id_nuevo,
                'correo'            => $correo
            ]);

            set_flash('success', "Usuario {$nombres} {$apellidos} creado con éxito.");
            header('Location: ' . BASE_URL . 'admin/usuarios/index.php');
            exit;
        }
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Crear Usuario — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Administración de Personal</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Crear Nuevo Usuario</h1>
            </div>
            <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="btn-forest-secondary text-xs py-2 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver a Usuarios
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
                        <label for="nombres" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombres *</label>
                        <input type="text" name="nombres" id="nombres" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="apellidos" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Apellidos *</label>
                        <input type="text" name="apellidos" id="apellidos" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="correo" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Correo Electrónico *</label>
                        <input type="email" name="correo" id="correo" required placeholder="ejemplo@emincut.com" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <div>
                        <label for="telefono" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Teléfono Móvil</label>
                        <input type="text" name="telefono" id="telefono" placeholder="+57 3..." class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="id_rol" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Rol en el Sistema *</label>
                        <select name="id_rol" id="id_rol" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <option value="">Seleccione un rol...</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre_rol']) ?> (<?= htmlspecialchars($r['descripcion']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="id_institucion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Institución Perteneciente</label>
                        <select name="id_institucion" id="id_institucion" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                            <option value="">Ninguna / Independiente</option>
                            <?php foreach ($instituciones as $inst): ?>
                                <option value="<?= $inst['id_institucion'] ?>"><?= htmlspecialchars($inst['nombre_institucion']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Contraseña Inicial *</label>
                    <input type="password" name="password" id="password" required minlength="6" placeholder="Mínimo 6 caracteres" class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                    <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="btn-forest-secondary text-xs py-2.5 px-4">Cancelar</a>
                    <button type="submit" class="btn-forest-primary text-xs py-2.5 px-5">
                        <i data-lucide="check" class="w-4 h-4"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
