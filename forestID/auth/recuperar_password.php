<?php
/**
 * ForestID / ForestSRD - Solicitud de Recuperación de Contraseña
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

$mensaje_exito = '';
$mensaje_error = '';
$enlace_simulado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf = $_POST['csrf_token'] ?? '';
    $correo     = sanear($_POST['correo'] ?? '');

    if (!validar_token_csrf($token_csrf)) {
        $mensaje_error = 'Token de seguridad inválido. Intente nuevamente.';
    } elseif (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = 'Por favor ingrese un correo electrónico válido.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id_usuario, nombres, correo FROM `usuarios` WHERE correo = :correo AND estado = 1");
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // Generar token único seguro
            $token = bin2hex(random_bytes(32));
            // Expiración en 1 hora
            $expiracion = date('Y-m-d H:i:s', time() + 3600);

            // Guardar en tabla tokens_recuperacion
            $ins = $pdo->prepare("
                INSERT INTO `tokens_recuperacion` (`id_token`, `id_usuario`, `token`, `fecha_expiracion`, `usado`, `fecha_creacion`)
                VALUES (UUID(), :id_usuario, :token, :expiracion, 0, NOW())
            ");
            $ins->execute([
                ':id_usuario'  => $usuario['id_usuario'],
                ':token'       => $token,
                ':expiracion'  => $expiracion
            ]);

            registrar_log($pdo, $usuario['id_usuario'], 'SOLICITUD_RECUPERACION_PASSWORD', 'AUTENTICACION', [
                'correo' => $correo
            ]);

            $enlace_simulado = BASE_URL . "auth/restablecer_password.php?token=" . urlencode($token);
            $mensaje_exito = "Se han generado las instrucciones de restablecimiento para el usuario <strong>" . htmlspecialchars($usuario['nombres']) . "</strong>.";
        } else {
            // Por seguridad, no revelar si el correo existe o no
            $mensaje_exito = "Si el correo ingresado se encuentra registrado en el sistema, recibirá un enlace para restablecer su contraseña.";
        }
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Recuperar Contraseña — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-[#FAFAF7]">
    <div class="w-full max-w-md bg-white border border-gray-200 rounded-2xl shadow-xl p-8 space-y-6">
        
        <div class="text-center">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="key-round" class="w-6 h-6"></i>
            </div>
            <h2 class="font-heading font-bold text-2xl text-gray-900">
                Recuperar Contraseña
            </h2>
            <p class="text-xs text-gray-500 mt-1">
                Emincut • Sistema ForestID
            </p>
        </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl text-xs text-rose-800 flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span><?= htmlspecialchars($mensaje_error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje_exito)): ?>
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl text-xs text-emerald-900 space-y-2">
                <p><?= $mensaje_exito ?></p>
                <?php if (!empty($enlace_simulado)): ?>
                    <div class="mt-3 p-3 bg-white border border-emerald-200 rounded-lg">
                        <span class="block font-semibold text-[11px] text-emerald-800 mb-1">
                            Simulación de Correo en Entorno Local (XAMPP):
                        </span>
                        <a href="<?= $enlace_simulado ?>" class="text-emerald-600 underline font-medium text-xs break-all hover:text-emerald-800">
                            <?= $enlace_simulado ?>
                        </a>
                        <div class="mt-2 text-right">
                            <a href="<?= $enlace_simulado ?>" class="btn-forest-primary text-xs py-1.5 px-3">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Abrir Enlace Ahora
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="recuperar_password.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div>
                <label for="correo" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                    Correo Registrado
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </div>
                    <input type="email" id="correo" name="correo" required placeholder="ej. admin@emincut.com"
                           class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 outline-none">
                </div>
            </div>

            <button type="submit" class="w-full btn-forest-primary py-2.5 text-sm font-semibold rounded-xl shadow-md">
                <i data-lucide="send" class="w-4 h-4"></i> Generar Enlace de Recuperación
            </button>
        </form>

        <div class="text-center pt-2 border-t border-gray-100">
            <a href="<?= BASE_URL ?>auth/login.php" class="inline-flex items-center gap-1.5 text-xs text-emerald-700 hover:text-emerald-900 font-medium">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Volver al Inicio de Sesión
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
