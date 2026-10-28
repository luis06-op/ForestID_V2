<?php
/**
 * ForestID / ForestSRD - Cierre de Sesión Seguro
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

// A6: el cierre de sesión solo se acepta por POST con token CSRF.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'publico/index.php');
    exit;
}

if (!validar_token_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Token de seguridad inválido: la sesión no se cerró.');
    header('Location: ' . BASE_URL . 'publico/index.php');
    exit;
}

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
cerrar_sesion_actual();

// Iniciar nueva sesión limpia para el mensaje de despedida
session_start();
set_flash('info', 'Ha cerrado su sesión de manera segura.');
header('Location: ' . BASE_URL . 'publico/index.php');
exit;
