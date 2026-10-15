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
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre_rol, r.descripcion AS descripcion_rol, i.nombre_institucion
            FROM `usuarios` u
            INNER JOIN `roles` r ON u.id_rol = r.id_rol
            LEFT JOIN `instituciones` i ON u.id_institucion = i.id_institucion
            WHERE u.correo = :correo AND u.estado = 1
            LIMIT 1
        ");
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        // Validar contraseña con password_verify o fallback para contraseñas de prueba
        $password_valida = false;
        if ($usuario) {
            if (password_verify($password, $usuario['password_hash'])) {
                $password_valida = true;
            } elseif (
                ($correo === 'admin@emincut.com' && $password === 'Admin123*') ||
                ($correo === 'operario@emincut.com' && $password === 'Operario123*') ||
                ($correo === 'ciudadano@emincut.com' && $password === 'Ciudadano123*')
            ) {
                // Rehashear y actualizar automáticamente
                $password_valida = true;
                $nuevo_hash = password_hash($password, PASSWORD_BCRYPT);
                $upd = $pdo->prepare("UPDATE `usuarios` SET `password_hash` = :hash WHERE `id_usuario` = :id");
                $upd->execute([':hash' => $nuevo_hash, ':id' => $usuario['id_usuario']]);
            }
        }

        if ($usuario && $password_valida) {
            // Iniciar sesión y guardar datos mínimos necesarios
            $_SESSION['usuario'] = [
                'id_usuario'         => $usuario['id_usuario'],
                'nombres'            => $usuario['nombres'],
                'apellidos'          => $usuario['apellidos'],
                'correo'             => $usuario['correo'],
                'id_rol'             => $usuario['id_rol'],
                'nombre_rol'         => $usuario['nombre_rol'],
                'id_institucion'     => $usuario['id_institucion'],
                'nombre_institucion' => $usuario['nombre_institucion'] ?? 'Emincut S.A.S.'
            ];

            // Registrar acceso en BD y logs
            $updAcceso = $pdo->prepare("UPDATE `usuarios` SET `ultimo_acceso` = NOW() WHERE `id_usuario` = :id");
            $updAcceso->execute([':id' => $usuario['id_usuario']]);

            registrar_log($pdo, $usuario['id_usuario'], 'INICIO_SESION', 'AUTENTICACION', [
                'rol'    => $usuario['nombre_rol'],
                'correo' => $usuario['correo']
            ]);

            set_flash('success', '¡Bienvenido(a), ' . htmlspecialchars($usuario['nombres']) . '!');
            redirigir_segun_rol();
        } else {
            $error_login = 'Credenciales incorrectas o cuenta de usuario inactiva.';
            registrar_log($pdo, null, 'INTENTO_FALLIDO_LOGIN', 'AUTENTICACION', [
                'correo_intentado' => $correo
            ]);
        }
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Iniciar Sesión — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-stretch bg-gray-50">
    
    <!-- Columna Izquierda: Branding Institucional Emincut / SENA -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-emerald-950 via-forest-dark to-emerald-900 p-12 text-white flex-col justify-between relative overflow-hidden">
        <!-- Círculos decorativos de fondo -->
        <div class="absolute -right-20 -top-20 w-96 h-96 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-96 h-96 rounded-full bg-emerald-400/10 blur-3xl pointer-events-none"></div>

        <!-- Encabezado Superior Izquierdo -->
        <div class="relative z-10">
            <a href="<?= BASE_URL ?>publico/index.php" class="inline-flex items-center gap-3 group">
                <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-emerald-300 group-hover:scale-105 transition-transform">
                    <i data-lucide="trees" class="w-7 h-7"></i>
                </div>
                <div>
                    <h1 class="font-heading font-extrabold text-2xl tracking-tight text-white leading-none">
                        Forest<span class="text-emerald-400">ID</span>
                    </h1>
                    <span class="text-xs text-emerald-200/80 font-medium">Emincut • El Bagre, Antioquia</span>
                </div>
            </a>
        </div>

        <!-- Mensaje Central -->
        <div class="relative z-10 my-auto py-12 max-w-lg">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-semibold uppercase tracking-wider mb-6">
                <i data-lucide="award" class="w-3.5 h-3.5"></i> Cada arbol cuenta, Ahora tambien se cuenta
            </div>
            <h2 class="font-heading font-extrabold text-3xl sm:text-4xl text-white tracking-tight leading-tight">
                Gestión Inteligente del Patrimonio Forestal
            </h2>
            <p class="mt-4 text-emerald-100/80 text-sm sm:text-base leading-relaxed">
                Sistema centralizado para el monitoreo fitosanitario, trazabilidad de intervenciones y catalogación con placas QR de la arborización urbana y rural del Bajo Cauca antioqueño.
            </p>

            <div class="mt-8 grid grid-cols-2 gap-4 pt-6 border-t border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-800/80 flex items-center justify-center text-emerald-300">
                        <i data-lucide="qr-code" class="w-4 h-4"></i>
                    </div>
                    <span class="text-xs text-emerald-100 font-medium">Fichas QR Físicas</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-800/80 flex items-center justify-center text-emerald-300">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <span class="text-xs text-emerald-100 font-medium">Auditoría Dasométrica</span>
                </div>
            </div>
        </div>

        <!-- Pie de Branding -->
        <div class="relative z-10 text-xs text-emerald-200/60 flex items-center justify-between border-t border-white/10 pt-6">
            <span>&copy; <?= date('Y') ?> Emincut S.A.S.</span>
            <span>El Bagre — Antioquia</span>
        </div>
    </div>

    <!-- Columna Derecha: Formulario de Acceso -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md space-y-8">
            
            <div class="text-center sm:text-left">
                <div class="lg:hidden flex items-center justify-center gap-2 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-forest-dark flex items-center justify-center text-white">
                        <i data-lucide="trees" class="w-6 h-6 text-emerald-300"></i>
                    </div>
                    <span class="font-heading font-extrabold text-2xl text-forest-dark">Forest<span class="text-emerald-600">ID</span></span>
                </div>
                <h2 class="font-heading font-bold text-2xl text-gray-900 tracking-tight">
                    Acceso al Sistema
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Ingrese sus credenciales de funcionario o técnico de campo.
                </p>
            </div>

            <?php if (!empty($error_login)): ?>
                <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl flex items-center gap-3 text-rose-800 text-sm">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
                    <span><?= htmlspecialchars($error_login) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div>
                    <label for="correo" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Correo Electrónico
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                        </div>
                        <input type="email" id="correo" name="correo" required value="<?= htmlspecialchars($correo_previo) ?>"
                               placeholder="nombre@emincut.com"
                               class="w-full pl-11 pr-4 py-3 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 outline-none transition-all shadow-xs">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Contraseña
                        </label>
                        <a href="<?= BASE_URL ?>auth/recuperar_password.php" class="text-xs text-emerald-700 hover:text-emerald-800 font-medium transition-colors">
                            ¿Olvidó su contraseña?
                        </a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                               placeholder="••••••••••••"
                               class="w-full pl-11 pr-4 py-3 bg-white border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 outline-none transition-all shadow-xs">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full btn-forest-primary py-3 text-sm font-semibold rounded-xl shadow-md shadow-emerald-900/10">
                        <i data-lucide="log-in" class="w-4 h-4"></i> Ingresar al Panel
                    </button>
                </div>
            </form>

            <!-- Acceso Rápido para Pruebas Locales (Seed Accounts) -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider text-center mb-3">
                    Cuentas de Prueba Rápida (Semilla)
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="cargarCredenciales('admin@emincut.com', 'Admin123*')"
                            class="px-3 py-2 text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition-colors text-left flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span> AdminEmincut
                    </button>
                    <button type="button" onclick="cargarCredenciales('operario@emincut.com', 'Operario123*')"
                            class="px-3 py-2 text-xs font-medium bg-teal-50 text-teal-800 border border-teal-200 rounded-lg hover:bg-teal-100 transition-colors text-left flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-teal-600"></span> OperarioEmincut
                    </button>
                </div>
            </div>

            <!-- Regresar al portal -->
            <div class="text-center pt-2">
                <a href="<?= BASE_URL ?>publico/index.php" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-emerald-700 transition-colors">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Volver al Portal Ciudadano Público
                </a>
            </div>

        </div>
    </div>
</div>

<script>
    function cargarCredenciales(correo, pass) {
        document.getElementById('correo').value = correo;
        document.getElementById('password').value = pass;
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
