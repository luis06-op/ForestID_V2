<?php
/**
 * ForestID / ForestSRD - Crear Nuevo Usuario (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/notificaciones.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();
$error = '';

// Resultado del envío del enlace de activación (correo / WhatsApp)
$envio = null;
$usuario_nuevo = null;

// Vigencia del enlace de activación en horas (definida en config/db.php).
$horas_activacion = max(1, (int)round(TOKEN_VIGENCIA_ACTIVACION / 3600));

$roles = $pdo->query("SELECT id_rol, nombre_rol, descripcion FROM `roles` ORDER BY nombre_rol ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf     = $_POST['csrf_token'] ?? '';
    $nombres        = sanear($_POST['nombres'] ?? '');
    $apellidos      = sanear($_POST['apellidos'] ?? '');
    $correo         = sanear($_POST['correo'] ?? '');
    $telefono       = sanear($_POST['telefono'] ?? '');
    $id_rol         = $_POST['id_rol'] ?? '';

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($nombres) || empty($apellidos) || empty($correo) || empty($id_rol)) {
        $error = 'Todos los campos marcados con (*) son obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico ingresado no tiene un formato válido.';
    } else {
        // Verificar correo único
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `usuarios` WHERE correo = :correo");
        $chk->execute([':correo' => $correo]);
        if ((int)$chk->fetchColumn() > 0) {
            $error = 'Ya existe un usuario registrado con este correo electrónico.';
        } else {
            // A7: el administrador no define la contraseña. Se guarda un hash
            // aleatorio inutilizable y el usuario crea la suya con un enlace.
            $password_hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
            $id_nuevo = generar_uuid();
            $token_activacion = bin2hex(random_bytes(32));
            $expira_activacion = date('Y-m-d H:i:s', time() + TOKEN_VIGENCIA_ACTIVACION);
            $enlace_activacion = APP_URL . 'auth/restablecer_password.php?token=' . urlencode($token_activacion) . '&nuevo=1';

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO `usuarios` (
                        `id_usuario`, `id_rol`, `nombres`, `apellidos`, 
                        `correo`, `password_hash`, `telefono`, `estado`, `fecha_creacion`
                    ) VALUES (
                        :id, :id_rol, :nombres, :apellidos,
                        :correo, :hash, :telefono, 1, NOW()
                    )
                ");
                $stmt->execute([
                    ':id'             => $id_nuevo,
                    ':id_rol'         => $id_rol,
                    ':nombres'        => $nombres,
                    ':apellidos'      => $apellidos,
                    ':correo'         => $correo,
                    ':hash'           => $password_hash,
                    ':telefono'       => $telefono
                ]);

                // Token de activación de un solo uso (en BD solo el hash).
                $insToken = $pdo->prepare("
                    INSERT INTO `tokens_recuperacion` (`id_token`, `id_usuario`, `token`, `fecha_expiracion`, `usado`, `fecha_creacion`)
                    VALUES (UUID(), :id_usuario, :token, :expiracion, 0, NOW())
                ");
                $insToken->execute([
                    ':id_usuario' => $id_nuevo,
                    ':token'      => hash_token($token_activacion),
                    ':expiracion' => $expira_activacion
                ]);

                registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'CREAR_USUARIO', 'USUARIOS', [
                    'id_usuario_creado' => $id_nuevo,
                    'correo'            => $correo
                ]);

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('[ForestID] Error al crear usuario: ' . $e->getMessage());
                $error = 'No se pudo crear el usuario. Intente nuevamente.';
            }

            if (empty($error)) {
                // Nombre del rol seleccionado (para la notificación)
                $nombre_rol = '';
                foreach ($roles as $r) {
                    if ((string)$r['id_rol'] === (string)$id_rol) { $nombre_rol = $r['nombre_rol']; break; }
                }

                // --- Envío del enlace de activación por correo y WhatsApp (A7) ---
                $res_correo = enviar_correo_activacion($correo, $nombres, $apellidos, $enlace_activacion, $horas_activacion);
                $res_wa = enviar_whatsapp_activacion($telefono, $nombres, $apellidos, $enlace_activacion, $horas_activacion);
                $enlace_wa = enlace_whatsapp_activacion($telefono, $nombres, $apellidos, $enlace_activacion, $horas_activacion);

                registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'ENVIAR_ACTIVACION', 'USUARIOS', [
                    'id_usuario_creado' => $id_nuevo,
                    'correo_ok'         => $res_correo['ok'],
                    'whatsapp_ok'       => $res_wa['ok']
                ]);

                $envio = [
                    'correo'            => $res_correo,
                    'whatsapp'          => $res_wa,
                    'enlace_wa'         => $enlace_wa,
                    'enlace_activacion' => $enlace_activacion,
                    'enlace_mail'       => 'mailto:' . $correo . '?subject=' . rawurlencode('ForestID — Activa tu cuenta') . '&body=' . rawurlencode(texto_activacion($nombres, $apellidos, $enlace_activacion, $horas_activacion)),
                ];
                $usuario_nuevo = [
                    'nombres'           => $nombres,
                    'apellidos'         => $apellidos,
                    'correo'            => $correo,
                    'rol'               => $nombre_rol,
                    'telefono'          => $telefono,
                    'enlace_activacion' => $enlace_activacion,
                ];
                // No redirige: muestra el panel de estado de envío con los botones de respaldo
            }
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

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto" style="background:var(--emi-hueso)">
        
        <div class="flex items-center justify-between">
            <div>
                <span class="emi-display text-xs font-bold uppercase tracking-widest" style="color:var(--emi-ambar)">Administración de Personal</span>
                <h1 class="font-heading font-extrabold text-2xl" style="color:var(--emi-tinta)">Crear Nuevo Usuario</h1>
                <div class="linea-sol mt-2"></div>
            </div>
            <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="emi-btn text-xs py-2 px-3" style="background:#fff; color:var(--emi-verde-oscuro); border:1px solid #CBD9B8">
                <span class="inline-flex items-center gap-2"><i data-lucide="arrow-left" class="w-4 h-4"></i> Volver a Usuarios</span>
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="border-l-4 p-4 rounded-xl text-xs flex items-center gap-2 bg-red-50 border-red-500 text-red-800">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($usuario_nuevo !== null && $envio !== null): ?>
        <!-- ===== Panel de resultado: envío del enlace de activación ===== -->
        <div class="emi-card p-6 sm:p-8 max-w-2xl al-scroll">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:var(--emi-verde-suave); color:var(--emi-verde)">
                    <i data-lucide="user-check" class="w-6 h-6"></i>
                </div>
                <div>
                    <h2 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">
                        <?= htmlspecialchars($usuario_nuevo['nombres'] . ' ' . $usuario_nuevo['apellidos']) ?> creado
                    </h2>
                    <p class="text-xs" style="color:var(--emi-tinta-suave)">Rol: <?= htmlspecialchars($usuario_nuevo['rol']) ?></p>
                </div>
            </div>

            <div class="linea-sol mt-3 mb-5"></div>

            <!-- Enlace de activación -->
            <div class="rounded-xl p-4 border mb-5" style="background:var(--emi-amarillo-suave); border-color:#F2DCAC">
                <span class="emi-label block mb-2">Enlace de activación (un solo uso)</span>
                <p class="text-sm" style="color:var(--emi-tinta)">
                    <strong>Correo:</strong> <?= htmlspecialchars($usuario_nuevo['correo']) ?>
                </p>
                <a href="<?= htmlspecialchars($usuario_nuevo['enlace_activacion']) ?>" class="block mt-2 text-xs break-all font-medium" style="color:var(--emi-verde)">
                    <?= htmlspecialchars($usuario_nuevo['enlace_activacion']) ?>
                </a>
                <p class="text-[11px] mt-2" style="color:var(--emi-tinta-suave)">
                    El usuario debe abrir este enlace para crear su propia contraseña. Caduca en <?= $horas_activacion ?> horas.
                </p>
            </div>

            <!-- Estado de los envíos -->
            <div class="space-y-3 mb-6">
                <!-- Correo -->
                <div class="flex items-start gap-3 p-3.5 rounded-xl border" style="background:<?= $envio['correo']['ok'] ? 'var(--emi-verde-suave)' : 'var(--emi-hueso)' ?>; border-color:#CBD9B8">
                    <i data-lucide="<?= $envio['correo']['ok'] ? 'check-circle' : 'alert-triangle' ?>" class="w-5 h-5 shrink-0 mt-0.5" style="color:<?= $envio['correo']['ok'] ? 'var(--emi-verde)' : 'var(--emi-ambar)' ?>"></i>
                    <div class="text-xs" style="color:var(--emi-tinta)">
                        <span class="font-bold block">Correo electrónico</span>
                        <span style="color:var(--emi-tinta-suave)"><?= htmlspecialchars($envio['correo']['detalle']) ?></span>
                        <?php if (!$envio['correo']['ok']): ?>
                            <a href="<?= htmlspecialchars($envio['enlace_mail']) ?>" class="block mt-1.5 font-semibold" style="color:var(--emi-verde)">Abrir en el cliente de correo →</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- WhatsApp -->
                <div class="flex items-start gap-3 p-3.5 rounded-xl border" style="background:<?= $envio['whatsapp']['ok'] ? 'var(--emi-verde-suave)' : 'var(--emi-hueso)' ?>; border-color:#CBD9B8">
                    <i data-lucide="<?= $envio['whatsapp']['ok'] ? 'check-circle' : 'message-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5" style="color:<?= $envio['whatsapp']['ok'] ? 'var(--emi-verde)' : 'var(--emi-ambar)' ?>"></i>
                    <div class="text-xs" style="color:var(--emi-tinta)">
                        <span class="font-bold block">WhatsApp</span>
                        <span style="color:var(--emi-tinta-suave)"><?= htmlspecialchars($envio['whatsapp']['detalle']) ?></span>
                        <?php if (!$envio['whatsapp']['ok'] && !empty($envio['enlace_wa'])): ?>
                            <a href="<?= htmlspecialchars($envio['enlace_wa']) ?>" target="_blank" rel="noopener" class="emi-btn emi-btn-sol text-[11px] py-2 px-3 mt-2 inline-flex">
                                <span class="inline-flex items-center gap-1.5 relative z-10"><i data-lucide="send" class="w-3.5 h-3.5"></i> Enviar enlace por WhatsApp</span>
                            </a>
                        <?php endif; ?>
                        <?php if (empty($envio['enlace_wa'])): ?>
                            <span class="block mt-1 italic" style="color:var(--emi-tinta-suave)">Sin teléfono registrado: agregue un número al usuario para poder enviar por WhatsApp.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-[#E7EADD] flex justify-end gap-2">
                <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="emi-btn emi-btn-verde text-xs py-2.5 px-5">
                    <span class="inline-flex items-center gap-2"><i data-lucide="users" class="w-4 h-4"></i> Ir a Usuarios</span>
                </a>
                <a href="crear.php" class="emi-btn text-xs py-2.5 px-5" style="background:#fff; color:var(--emi-verde-oscuro); border:1px solid #CBD9B8">
                    Crear otro usuario
                </a>
            </div>
        </div>

        <?php else: ?>
        <!-- ===== Formulario de creación ===== -->
        <div class="emi-card p-6 sm:p-8 max-w-2xl al-scroll">
            <form method="POST" action="crear.php" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nombres" class="emi-label block mb-1">Nombres *</label>
                        <input type="text" name="nombres" id="nombres" required class="emi-input" style="padding-left:.75rem">
                    </div>
                    <div>
                        <label for="apellidos" class="emi-label block mb-1">Apellidos *</label>
                        <input type="text" name="apellidos" id="apellidos" required class="emi-input" style="padding-left:.75rem">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="correo" class="emi-label block mb-1">Correo Electrónico *</label>
                        <input type="email" name="correo" id="correo" required placeholder="ejemplo@emincut.com" class="emi-input" style="padding-left:.75rem">
                    </div>
                    <div>
                        <label for="telefono" class="emi-label block mb-1">Teléfono Móvil (WhatsApp)</label>
                        <input type="text" name="telefono" id="telefono" placeholder="+57 3..." class="emi-input" style="padding-left:.75rem">
                    </div>
                </div>

                <div>
                    <label for="id_rol" class="emi-label block mb-1">Rol en el Sistema *</label>
                    <select name="id_rol" id="id_rol" required class="emi-input" style="padding-left:.75rem">
                        <option value="">Seleccione un rol...</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre_rol']) ?> (<?= htmlspecialchars($r['descripcion']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="emi-label block mb-1">Activación de la cuenta</label>
                    <p class="text-[11px]" style="color:var(--emi-tinta-suave)">
                        <i data-lucide="info" class="w-3 h-3 inline"></i> Al guardar se envía al usuario un enlace de un solo uso (<?= $horas_activacion ?> horas) para que cree su propia contraseña. El administrador nunca define ni ve la contraseña.
                    </p>
                </div>

                <div class="pt-4 border-t border-[#E7EADD] flex justify-end gap-2">
                    <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="emi-btn text-xs py-2.5 px-4" style="background:#fff; color:var(--emi-verde-oscuro); border:1px solid #CBD9B8">Cancelar</a>
                    <button type="submit" class="emi-btn emi-btn-sol text-xs py-2.5 px-5">
                        <span class="inline-flex items-center gap-2 relative z-10"><i data-lucide="check" class="w-4 h-4"></i> Guardar y Enviar Enlace</span>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
