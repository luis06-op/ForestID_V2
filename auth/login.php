<?php
/**
 * ForestID / ForestSRD - Inicio de Sesión
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Si ya está autenticado, redirigir a su dashboard correspondiente
if (esta_autenticado()) {
    redirigir_segun_rol();
}

$error_login = '';
$correo_previo = '';

// Aviso si se llegó aquí por expiración de la sesión.
if (!empty($_SESSION['sesion_expirada'])) {
    unset($_SESSION['sesion_expirada']);
    $error_login = 'Su sesión expiró por inactividad. Vuelva a iniciar sesión.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf = $_POST['csrf_token'] ?? '';
    $correo     = sanear($_POST['correo'] ?? '');
    $password   = $_POST['password'] ?? '';
    $correo_previo = $correo;

    if (!validar_token_csrf($token_csrf)) {
        $error_login = 'Token de seguridad inválido o expirado. Intente nuevamente.';
    } elseif (empty($correo) || empty($password)) {
        $error_login = 'Por favor ingrese su correo electrónico y contraseña.';
    } else {
        $pdo = getDBConnection();

        // A2: bloqueo temporal tras varios fallos por IP o por correo.
        if (login_bloqueado($pdo, $correo)) {
            $error_login = 'Demasiados intentos fallidos. Espere ' . LOGIN_VENTANA_MIN . ' minutos e intente nuevamente.';
            registrar_log($pdo, null, 'LOGIN_BLOQUEADO', 'AUTENTICACION', [
                'correo_intentado' => $correo
            ]);
        } else {
            $fallos_previos = intentos_login_fallidos($pdo, $correo);
            retardo_progresivo($fallos_previos);

            $stmt = $pdo->prepare("
                SELECT u.*, r.nombre_rol, r.descripcion AS descripcion_rol
                FROM `usuarios` u
                INNER JOIN `roles` r ON u.id_rol = r.id_rol
                WHERE u.correo = :correo AND u.estado = 1
                LIMIT 1
            ");
            $stmt->execute([':correo' => $correo]);
            $usuario = $stmt->fetch();

            // Validar contraseña exclusivamente contra el hash almacenado.
            // No se permiten credenciales en texto plano ni rehasheos automáticos:
            // los usuarios de prueba deben crearse con password_hash() real.
            $password_valida = $usuario && password_verify($password, $usuario['password_hash']);

            if ($usuario && $password_valida) {
                // A1: nuevo ID de sesión tras autenticar (evita fijación de sesión).
                session_regenerate_id(true);
                $_SESSION['ultima_actividad'] = time();
                $_SESSION['iniciada_en']      = time();
                unset($_SESSION['sesion_expirada']);

                // Iniciar sesión y guardar datos mínimos necesarios
                $_SESSION['usuario'] = [
                    'id_usuario'         => $usuario['id_usuario'],
                    'nombres'            => $usuario['nombres'],
                    'apellidos'          => $usuario['apellidos'],
                    'correo'             => $usuario['correo'],
                    'id_rol'             => $usuario['id_rol'],
                    'nombre_rol'         => $usuario['nombre_rol']
                ];

                // Registrar acceso en BD y logs
                $updAcceso = $pdo->prepare("UPDATE `usuarios` SET `ultimo_acceso` = NOW() WHERE `id_usuario` = :id");
                $updAcceso->execute([':id' => $usuario['id_usuario']]);

                registrar_log($pdo, $usuario['id_usuario'], 'INICIO_SESION', 'AUTENTICACION', [
                    'rol'    => $usuario['nombre_rol'],
                    'correo' => $usuario['correo']
                ]);

                set_flash('success', '¡Bienvenido(a), ' . $usuario['nombres'] . '!');
                redirigir_segun_rol();
            } else {
                $error_login = 'Credenciales incorrectas o cuenta de usuario inactiva.';
                registrar_log($pdo, null, 'INTENTO_FALLIDO_LOGIN', 'AUTENTICACION', [
                    'correo_intentado' => $correo
                ]);
            }
        }
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Iniciar Sesión — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-stretch bg-[#FBFAF6]">

    <!-- Columna Izquierda: Branding Institucional Emincut -->
    <div class="hidden lg:flex lg:w-1/2 emi-sky emi-sunline p-12 text-white flex-col justify-between relative overflow-hidden">

        <!-- Encabezado Superior Izquierdo: logo oficial Emincut -->
        <div class="relative z-10 emi-rise emi-d1">
            <a href="<?= BASE_URL ?>publico/index.php" class="inline-flex items-center gap-4 group">
                <div class="bg-white rounded-2xl p-2.5 shadow-lg shadow-black/20 group-hover:scale-105 transition-transform w-[86px] h-[64px] flex items-center justify-center">
                    <img src="<?= BASE_URL ?>assets/img/logo_emincut_oficial.svg" alt="Emincut" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="emi-display font-bold text-2xl tracking-wide text-white leading-none">
                        Forest<span style="color:var(--emi-amarillo)">ID</span>
                    </h1>
                    <span class="text-xs text-white/65 font-medium mt-1 block">Emincut • El Bagre, Antioquia</span>
                </div>
            </a>
        </div>

        <!-- Mensaje Central -->
        <div class="relative z-10 my-auto py-12 max-w-lg">
            <div class="emi-rise emi-d2 inline-flex items-center gap-2 px-3 py-1 rounded-full border text-xs font-semibold uppercase tracking-wider mb-6"
                 style="background:rgba(242,182,50,.14); border-color:rgba(242,182,50,.35); color:var(--emi-amarillo)">
                <i data-lucide="sun" class="w-3.5 h-3.5"></i> En camino con la minería responsable
            </div>
            <h2 class="emi-display emi-rise emi-d3 font-bold text-3xl sm:text-4xl tracking-wide leading-tight">
                Gestión inteligente del patrimonio forestal
            </h2>
            <p class="mt-4 text-white/75 text-sm sm:text-base leading-relaxed emi-rise emi-d3">
                Sistema centralizado para el monitoreo fitosanitario, trazabilidad de intervenciones y catalogación con placas QR de la arborización urbana y rural del Bajo Cauca antioqueño.
            </p>

            <div class="mt-8 grid grid-cols-2 gap-4 pt-6 border-t border-white/10 emi-rise emi-d4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(91,124,58,.55); color:var(--emi-amarillo)">
                        <i data-lucide="qr-code" class="w-4 h-4"></i>
                    </div>
                    <span class="text-xs text-white/85 font-medium">Fichas QR Físicas</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(91,124,58,.55); color:var(--emi-amarillo)">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <span class="text-xs text-white/85 font-medium">Auditoría Dasométrica</span>
                </div>
            </div>
        </div>

        <!-- Pie de Branding -->
        <div class="relative z-10 text-xs text-white/55 flex items-center justify-between border-t border-white/10 pt-6 emi-rise emi-d5">
            <span>&copy; <?= date('Y') ?> Emincut S.A.S.</span>
            <span>El Bagre — Antioquia</span>
        </div>
    </div>

    <!-- Columna Derecha: Formulario de Acceso -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md space-y-8">

            <div class="text-center sm:text-left">
                <div class="lg:hidden flex items-center justify-center mb-6">
                    <img src="<?= BASE_URL ?>assets/img/logo_emincut_oficial.svg" alt="Emincut" class="h-16">
                </div>
                <h2 class="emi-display font-bold text-2xl tracking-wide" style="color:var(--emi-tinta)">
                    Acceso al Sistema
                </h2>
                <p class="mt-1 text-sm" style="color:var(--emi-tinta-suave)">
                    Ingrese sus credenciales de funcionario o técnico de campo.
                </p>
            </div>

            <?php if (!empty($error_login)): ?>
                <div class="border-l-4 p-4 rounded-xl flex items-center gap-3 text-sm bg-red-50 border-red-500 text-red-800">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
                    <span><?= htmlspecialchars($error_login) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div>
                    <label for="correo" class="emi-label block mb-1.5">Correo Electrónico</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none" style="color:var(--emi-verde)">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                        </div>
                        <input type="email" id="correo" name="correo" required value="<?= htmlspecialchars($correo_previo) ?>"
                               placeholder="nombre@emincut.com" class="emi-input">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="emi-label">Contraseña</label>
                        <a href="<?= BASE_URL ?>auth/recuperar_password.php" class="text-xs font-semibold transition-colors" style="color:var(--emi-verde)">
                            ¿Olvidó su contraseña?
                        </a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none" style="color:var(--emi-verde)">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                               placeholder="••••••••••••" class="emi-input">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full emi-btn emi-btn-sol py-3.5 text-sm">
                        <span class="inline-flex items-center gap-2"><i data-lucide="log-in" class="w-4 h-4"></i> Ingresar al Panel</span>
                    </button>
                </div>
            </form>

            <!-- Regresar al portal -->
            <div class="text-center pt-2">
                <a href="<?= BASE_URL ?>publico/index.php" class="inline-flex items-center gap-1.5 text-xs font-semibold transition-colors" style="color:var(--emi-tinta-suave)">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Volver al Portal Ciudadano Público
                </a>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
