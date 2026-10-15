<?php
/**
 * ForestID / ForestSRD - Cierre de Sesión Seguro
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['usuario']['id_usuario'])) {
    $pdo = getDBConnection();
    registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'CIERRE_SESION', 'AUTENTICACION', [
        'correo' => $_SESSION['usuario']['correo'] ?? ''
    ]);
}

// Limpiar y destruir la sesión
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();

// Iniciar nueva sesión limpia para el mensaje de despedida
session_start();
set_flash('info', 'Ha cerrado su sesión de manera segura.');
header('Location: ' . BASE_URL . 'publico/index.php');
exit;
