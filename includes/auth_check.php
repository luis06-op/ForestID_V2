<?php
/**
 * ForestID / ForestSRD - Middleware de Autenticación y Control de Roles
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna true si existe una sesión activa y válida.
 * Aplica expiración por inactividad (A1): si se supera SESSION_IDLE_TIMEOUT
 * se limpian los datos de sesión y se marca `sesion_expirada` para avisar.
 */
function esta_autenticado(): bool {
    if (empty($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
        return false;
    }

    $ahora  = time();
    $ultima = (int)($_SESSION['ultima_actividad'] ?? $ahora);

    if (($ahora - $ultima) > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['sesion_expirada'] = true;
        return false;
    }

    $_SESSION['ultima_actividad'] = $ahora;
    return true;
}

/**
 * Retorna los datos del usuario en sesión
 */
function obtener_usuario_actual(): ?array {
    return $_SESSION['usuario'] ?? null;
}

/**
 * Retorna el nombre del rol del usuario autenticado
 */
function obtener_rol_actual(): ?string {
    return $_SESSION['usuario']['nombre_rol'] ?? null;
}

/**
 * Verifica si el usuario actual es Administrador de Emincut
 */
function es_admin(): bool {
    return obtener_rol_actual() === 'AdminEmincut';
}

/**
 * Verifica si el usuario actual es Operario de Campo
 */
function es_operario(): bool {
    return obtener_rol_actual() === 'OperarioEmincut';
}

/**
 * Verifica si el usuario actual es Usuario Público
 */
function es_publico(): bool {
    return obtener_rol_actual() === 'UsuarioPublico';
}

/**
 * Exige inicio de sesión obligatorio
 */
function requerir_autenticacion(): void {
    if (!esta_autenticado()) {
        if (!empty($_SESSION['sesion_expirada'])) {
            unset($_SESSION['sesion_expirada']);
            set_flash('warning', 'Su sesión expiró por inactividad. Vuelva a iniciar sesión.');
        } else {
            set_flash('warning', 'Debe iniciar sesión para acceder a este módulo.');
        }
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

/**
 * Exige que el usuario posea uno de los roles especificados
 */
function requerir_rol(array $roles_permitidos): void {
    requerir_autenticacion();
    
    $rol_actual = obtener_rol_actual();
    if (!in_array($rol_actual, $roles_permitidos, true)) {
        set_flash('error', 'No cuenta con los permisos requeridos para acceder a esta sección.');
        header('Location: ' . BASE_URL . 'error/403.php');
        exit;
    }
}

/**
 * Redirige al dashboard correspondiente según el rol del usuario
 */
function redirigir_segun_rol(): void {
    if (!esta_autenticado()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }

    $rol = obtener_rol_actual();
    switch ($rol) {
        case 'AdminEmincut':
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            exit;
        case 'OperarioEmincut':
            header('Location: ' . BASE_URL . 'operario/dashboard.php');
            exit;
        default:
            header('Location: ' . BASE_URL . 'publico/index.php');
            exit;
    }
}
