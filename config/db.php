<?php
/**
 * ForestID / ForestSRD - Configuración de Base de Datos y Constantes Globales
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 * Marco: SENA Regional Antioquia – iRed Innovacción
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la Base de Datos MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'forestsrd_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Constantes de la Aplicación
define('APP_NAME', 'ForestID');
define('APP_FULL_NAME', 'ForestID — Identificación y Gestión del Inventario Forestal');
define('APP_COMPANY', 'Emincut S.A.S.');
define('APP_LOCATION', 'El Bagre, Antioquia, Colombia');
define('APP_REGION', 'Bajo Cauca Antioqueño');
define('APP_INITIATIVE', 'SENA Regional Antioquia – iRed Innovacción');
define('APP_VERSION', '1.0.0');

// Créditos del Proyecto
define('TEAM_LEAD', 'Luis Angel Muñoz Márquez (Líder de Arquitectura)');
define('TEAM_BACKEND', 'Gabriel Jaime Correa Correa (Desarrollador Backend)');
define('TEAM_UI_QA', 'Michael Rodríguez Londoño (Desarrollador UI / QA)');
define('TEAM_QA_SUPERVISOR', 'Valentina (Supervisor / Verificador de Calidad)');

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Cálculo dinámico de la URL base
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

$base_path = '';

// 1. Intentar deducir la ruta relativa desde DOCUMENT_ROOT
$doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$app_root = str_replace('\\', '/', realpath(ROOT_PATH) ?: '');

if (!empty($doc_root) && !empty($app_root) && strpos($app_root, $doc_root) === 0) {
    $base_path = substr($app_root, strlen($doc_root));
} else {
    // 2. Fallback: Deducir cortando subdirectorios conocidos de la aplicación
    $subdirs = ['/admin', '/operario', '/publico', '/auth', '/includes', '/config', '/error'];
    $found = false;
    foreach ($subdirs as $dir) {
        if (($pos = strpos($script_name, $dir)) !== false) {
            $base_path = substr($script_name, 0, $pos);
            $found = true;
            break;
        }
    }
    if (!$found) {
        $base_path = dirname($script_name);
    }
}

$base_path = '/' . trim(str_replace('\\', '/', $base_path), '/');
if ($base_path === '/') {
    $base_path = '';
}

define('BASE_URL', $protocol . $host . $base_path . '/');

// Función Singleton para obtener conexión PDO
function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_spanish_ci"
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Manejo de error limpio sin revelar credenciales
            error_log("Error de conexión a la Base de Datos: " . $e->getMessage());
            die("<div style='font-family: sans-serif; padding: 2rem; max-width: 600px; margin: 3rem auto; background: #FEF2F2; border: 1px solid #F87171; border-radius: 8px; color: #991B1B;'>
                    <h2 style='margin-top:0;'>Error de Conexión a la Base de Datos</h2>
                    <p>No se pudo establecer conexión con MySQL. Verifique que el servicio esté activo en XAMPP y que la base de datos <strong>" . htmlspecialchars(DB_NAME) . "</strong> haya sido importada.</p>
                    <p><small style='color:#7F1D1D;'>Detalle técnico: " . htmlspecialchars($e->getMessage()) . "</small></p>
                 </div>");
        }
    }
    return $pdo;
}
