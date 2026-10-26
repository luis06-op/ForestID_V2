<?php
/**
 * ForestID / ForestSRD - Restablecimiento de Contraseña
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$token = trim($token);

// Un enlace de activación (?nuevo=1) usa el mismo formulario que la recuperación.
$es_activacion = (($_GET['nuevo'] ?? $_POST['nuevo'] ?? '') === '1');

$error_token = '';
$error_form = '';
$token_valido = false;
$usuario_encontrado = null;

$pdo = getDBConnection();

if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error_token = 'No se proporcionó un token de recuperación válido.';
} else {
    // Validar token en la BD comparando su hash SHA-256 (A3).
    $stmt = $pdo->prepare("
        SELECT tr.*, u.id_usuario, u.nombres, u.apellidos, u.correo
        FROM `tokens_recuperacion` tr
        INNER JOIN `usuarios` u ON tr.id_usuario = u.id_usuario
        WHERE tr.token = :token AND tr.usado = 0 AND tr.fecha_expiracion > NOW()
        LIMIT 1
    ");
    $stmt->execute([':token' => hash_token($token)]);
    $usuario_encontrado = $stmt->fetch();

    if ($usuario_encontrado) {
        $token_valido = true;
    } else {
        $error_token = $es_activacion
            ? 'El enlace de activación es inválido, ya fue utilizado o ha expirado (validez de 48 horas).'
            : 'El enlace de recuperación es inválido, ya fue utilizado o ha expirado (validez de 1 hora).';
    }
}

// Procesar formulario de nueva contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $token_csrf = $_POST['csrf_token'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $error_password = validar_password_fuerte($password_nueva);

    if (!validar_token_csrf($token_csrf)) {
        $error_form = 'Token de seguridad inválido. Intente nuevamente.';
    } elseif ($error_password !== null) {
        $error_form = $error_password;
    } elseif ($password_nueva !== $password_confirm) {
        $error_form = 'Las contraseñas no coinciden.';
    } else {
        // Hashear nueva contraseña
        $nuevo_hash = password_hash($password_nueva, PASSWORD_BCRYPT);

        // Actualizar usuario
        $updUser = $pdo->prepare("UPDATE `usuarios` SET `password_hash` = :hash WHERE `id_usuario` = :id");
        $updUser->execute([
            ':hash' => $nuevo_hash,
            ':id'   => $usuario_encontrado['id_usuario']
        ]);

        // Invalidar todos los tokens pendientes del usuario.
        $updToken = $pdo->prepare("UPDATE `tokens_recuperacion` SET `usado` = 1 WHERE `id_usuario` = :id_usuario");
        $updToken->execute([':id_usuario' => $usuario_encontrado['id_usuario']]);

        registrar_log($pdo, $usuario_encontrado['id_usuario'], 'RESTABLECIMIENTO_PASSWORD', 'AUTENTICACION', [
            'correo' => $usuario_encontrado['correo']
        ]);

        set_flash('success', $es_activacion
            ? '¡Su contraseña ha sido creada con éxito! Ya puede iniciar sesión.'
            : '¡Su contraseña ha sido actualizada con éxito! Ya puede iniciar sesión.');
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = ($es_activacion ? 'Activar Cuenta' : 'Restablecer Contraseña') . ' — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-[#FAFAF7]">
    <div class="w-full max-w-md bg-white border border-gray-200 rounded-2xl shadow-xl p-8 space-y-6">
        
        <div class="text-center">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <h2 class="font-heading font-bold text-2xl text-gray-900">
                <?= $es_activacion ? 'Activa tu cuenta' : 'Nueva Contraseña' ?>
            </h2>
            <p class="text-xs text-gray-500 mt-1">
                Emincut • Sistema ForestID
            </p>
        </div>

        <?php if (!$token_valido): ?>
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl text-xs text-rose-800 space-y-2">
                <p class="font-semibold">Enlace No Válido</p>
                <p><?= htmlspecialchars($error_token) ?></p>
            </div>
            <div class="text-center pt-3">
                <a href="<?= BASE_URL ?>auth/recuperar_password.php" class="btn-forest-primary text-xs py-2 px-4">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Solicitar Nuevo Enlace
                </a>
            </div>
        <?php else: ?>

            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-xs text-emerald-800">
                <?= $es_activacion ? 'Creando acceso para' : 'Restableciendo contraseña para' ?>: <strong><?= htmlspecialchars($usuario_encontrado['nombres'] . ' ' . $usuario_encontrado['apellidos']) ?></strong> (<?= htmlspecialchars($usuario_encontrado['correo']) ?>)
            </div>

            <?php if (!empty($error_form)): ?>
                <div class="bg-rose-50 border-l-4 border-rose-500 p-3 rounded-xl text-xs text-rose-800 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                    <span><?= htmlspecialchars($error_form) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="restablecer_password.php" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="nuevo" value="<?= $es_activacion ? '1' : '0' ?>">

                <div>
                    <label for="password_nueva" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Nueva Contraseña
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <input type="password" id="password_nueva" name="password_nueva" required minlength="10"
                               placeholder="Mínimo 10 caracteres"
                               class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 outline-none">
                    </div>
                    <p class="text-[11px] mt-1.5 text-gray-500">
                        Debe incluir al menos una mayúscula, una minúscula y un número.
                    </p>
                </div>

                <div>
                    <label for="password_confirm" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Confirmar Nueva Contraseña
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="check" class="w-4 h-4"></i>
                        </div>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="10"
                               placeholder="Repita la contraseña"
                               class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 outline-none">
                    </div>
                </div>

                <button type="submit" class="w-full btn-forest-primary py-2.5 text-sm font-semibold rounded-xl shadow-md">
                    <i data-lucide="save" class="w-4 h-4"></i> Guardar Nueva Contraseña
                </button>
            </form>

        <?php endif; ?>

        <div class="text-center pt-2 border-t border-gray-100">
            <a href="<?= BASE_URL ?>auth/login.php" class="inline-flex items-center gap-1.5 text-xs text-emerald-700 hover:text-emerald-900 font-medium">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Volver al Inicio de Sesión
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
