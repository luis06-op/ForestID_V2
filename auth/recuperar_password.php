<?php
/**
 * ForestID / ForestSRD - Solicitud de Recuperación de Contraseña
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/notificaciones.php';

$mensaje_exito = '';
$mensaje_error = '';
$enlace_simulado = '';

// El enlace NUNCA se muestra en pantalla salvo que se active explícitamente
// el modo demo local (FORESTID_MODO_DEMO=1). En producción debe quedar apagado.
$modo_demo = (getenv('FORESTID_MODO_DEMO') === '1')
    || (($_SERVER['FORESTID_MODO_DEMO'] ?? '') === '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf = $_POST['csrf_token'] ?? '';
    $correo     = sanear($_POST['correo'] ?? '');

    if (!validar_token_csrf($token_csrf)) {
        $mensaje_error = 'Token de seguridad inválido. Intente nuevamente.';
    } elseif (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = 'Por favor ingrese un correo electrónico válido.';
    } else {
        $pdo = getDBConnection();

        // A2: límite de solicitudes por IP para evitar spam de correos.
        if (recuperacion_bloqueada($pdo)) {
            registrar_log($pdo, null, 'SOLICITUD_RECUPERACION_BLOQUEADA', 'AUTENTICACION', [
                'correo_intentado' => $correo
            ]);
        } else {
            $stmt = $pdo->prepare("SELECT id_usuario, nombres, correo FROM `usuarios` WHERE correo = :correo AND estado = 1");
            $stmt->execute([':correo' => $correo]);
            $usuario = $stmt->fetch();

            // Se registra todo intento válido (exista o no la cuenta) para poder
            // limitar por IP sin revelar qué correos están registrados.
            registrar_log($pdo, $usuario['id_usuario'] ?? null, 'SOLICITUD_RECUPERACION_PASSWORD', 'AUTENTICACION', [
                'correo_intentado' => $correo
            ]);

            if ($usuario) {
                // Generar token único seguro; en BD solo se guarda su hash (A3).
                $token  = bin2hex(random_bytes(32));
                $expira = date('Y-m-d H:i:s', time() + TOKEN_VIGENCIA_RECUPERACION);

                // Invalidar tokens anteriores del mismo usuario.
                $pdo->prepare("UPDATE `tokens_recuperacion` SET `usado` = 1 WHERE `id_usuario` = :id AND `usado` = 0")
                    ->execute([':id' => $usuario['id_usuario']]);

                $ins = $pdo->prepare("
                    INSERT INTO `tokens_recuperacion` (`id_token`, `id_usuario`, `token`, `fecha_expiracion`, `usado`, `fecha_creacion`)
                    VALUES (UUID(), :id_usuario, :token, :expiracion, 0, NOW())
                ");
                $ins->execute([
                    ':id_usuario' => $usuario['id_usuario'],
                    ':token'      => hash_token($token),
                    ':expiracion' => $expira
                ]);

                $enlace = BASE_URL . "auth/restablecer_password.php?token=" . urlencode($token);
                $envio  = enviar_correo_recuperacion($usuario['correo'], $usuario['nombres'], $enlace);
                if (!$envio['ok']) {
                    error_log('[ForestID] No se pudo enviar el enlace de recuperación: ' . $envio['detalle']);
                }

                // Solo en modo demo local se expone el enlace en la interfaz.
                if ($modo_demo) {
                    $enlace_simulado = $enlace;
                }
            }
        }

        // Mensaje genérico en todos los casos: evita revelar si el correo existe.
        $mensaje_exito = 'Si el correo ingresado se encuentra registrado en el sistema, recibirá un enlace para restablecer su contraseña.';
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
                <p><?= htmlspecialchars($mensaje_exito) ?></p>
                <?php if ($modo_demo && !empty($enlace_simulado)): ?>
                    <div class="mt-3 p-3 bg-amber-50 border border-amber-300 rounded-lg space-y-1">
                        <span class="block font-semibold text-[11px] text-amber-800 uppercase tracking-wider">
                            ⚠ Modo demo local activo (FORESTID_MODO_DEMO=1)
                        </span>
                        <a href="<?= htmlspecialchars($enlace_simulado) ?>" class="text-emerald-700 underline font-medium text-xs break-all hover:text-emerald-900">
                            <?= htmlspecialchars($enlace_simulado) ?>
                        </a>
                        <div class="mt-2 text-right">
                            <a href="<?= htmlspecialchars($enlace_simulado) ?>" class="btn-forest-primary text-xs py-1.5 px-3">
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
