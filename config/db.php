<?php
/**
 * ForestID / ForestSRD - Configuración de Base de Datos y Constantes Globales
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

// Zona horaria institucional: no depende del php.ini del hosting.
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('America/Bogota');
}

// Endurecimiento de la sesión (A1): cookies HttpOnly + SameSite, modo estricto
// y nombre propio de sesión. Debe ejecutarse siempre antes de session_start().
if (session_status() === PHP_SESSION_NONE) {
    $sesion_https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443)
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $sesion_https ? '1' : '0');
    ini_set('session.gc_maxlifetime', '1800');

    session_name('FORESTID_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $sesion_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Configuración local de credenciales (archivo NO versionado).
// Copie config/config.local.ejemplo.php como config/config.local.php.
$config_local = __DIR__ . '/config.local.php';
if (is_file($config_local)) {
    require_once $config_local;
}

// Errores: nunca en pantalla, siempre al log del servidor (defensa en
// profundidad además de .user.ini). Para depurar en local: FORESTID_DEBUG=1.
if (getenv('FORESTID_DEBUG') !== '1') {
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    @ini_set('log_errors', '1');
}

// Datos corporativos de Emincut (nombre, NIT, contacto, logo).
require_once __DIR__ . '/empresa.php';

// Prioridad: config.local.php > variables de entorno > valores por defecto de XAMPP.
if (!defined('DB_HOST'))    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME'))    define('DB_NAME', getenv('DB_NAME') ?: 'forestsrd_db');
if (!defined('DB_USER'))    define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS'))    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Constantes de la Aplicación
define('APP_NAME', 'ForestID');
define('APP_FULL_NAME', 'ForestID — Identificación y Gestión del Inventario Forestal');
define('APP_COMPANY', EMINCUT_NOMBRE);
define('APP_LOCATION', EMINCUT_MUNICIPIO . ', ' . EMINCUT_DEPARTAMENTO . ', ' . EMINCUT_PAIS);
define('APP_REGION', 'Bajo Cauca Antioqueño');
define('APP_INITIATIVE', EMINCUT_NOMBRE);
define('APP_VERSION', '1.0.0');

// Parámetros de seguridad (A1/A2). Se pueden sobrescribir en config/config.local.php.
if (!defined('SESSION_IDLE_TIMEOUT'))     define('SESSION_IDLE_TIMEOUT', 1800);      // 30 min de inactividad
if (!defined('LOGIN_MAX_INTENTOS'))       define('LOGIN_MAX_INTENTOS', 5);           // fallos por ventana
if (!defined('LOGIN_VENTANA_MIN'))        define('LOGIN_VENTANA_MIN', 15);           // ventana en minutos
if (!defined('RECUPERACION_MAX_INTENTOS')) define('RECUPERACION_MAX_INTENTOS', 3);

// Token de activación/recuperación (A3/A7)
if (!defined('TOKEN_VIGENCIA_RECUPERACION')) define('TOKEN_VIGENCIA_RECUPERACION', 3600);      // 1 hora
if (!defined('TOKEN_VIGENCIA_ACTIVACION'))   define('TOKEN_VIGENCIA_ACTIVACION', 172800);     // 48 horas

// Créditos del Proyecto
define('TEAM_LEAD', 'Luis Angel Muñoz Márquez (Líder de Arquitectura)');
define('TEAM_BACKEND', 'Gabriel Jaime Correa Correa (Desarrollador Backend)');
define('TEAM_QA_SUPERVISOR', 'Valentina (Supervisor / Verificador de Calidad)');
// In memoriam: Michael Rodríguez Londoño (Desarrollador UI / QA)

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Cálculo dinámico de la URL base
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'))
    ? "https://" : "http://";
$host_raw = $_SERVER['HTTP_HOST'] ?? 'localhost';

// M2: lista blanca de Host para evitar Host Header Injection.
//
// Se aceptan únicamente:
//   1. Los dominios de producción de ForestID (con y sin «www.»).
//   2. Hosts locales, para desarrollo y para el propio servidor.
//   3. El host de APP_URL, que es configuración del servidor, no del cliente.
//   4. Los hosts declarados en FORESTID_HOSTS (separados por coma), para
//      staging u otros dominios legítimos.
//
// Un Host que no esté en la lista NUNCA se refleja en enlaces, QR o correos:
// se sustituye por el APP_URL canónico y, si tampoco existe, se rechaza la
// petición con 400 (salvo en CLI). La IP del cliente no forma parte de la
// lista; solo la IP propia del servidor (SERVER_ADDR), que no controla el
// cliente.
$dominios_produccion = ['forestid.cfmasoft.com', 'www.forestid.cfmasoft.com'];
$hosts_locales = ['localhost', '127.0.0.1', '::1', '[::1]'];

$app_url_canonica = defined('APP_URL')
    ? (string)APP_URL
    : (string)(getenv('APP_URL') ?: '');
$partes_app = $app_url_canonica !== '' ? parse_url($app_url_canonica) : [];
if (!is_array($partes_app)) {
    $partes_app = [];
}

// Normaliza un host: minúsculas, sin espacios, sin puerto y sin punto final.
// Respeta las direcciones IPv6 (p. ej. «::1» o «[::1]:8080»).
$normalizar_host = static function (string $valor): string {
    $valor = strtolower(trim($valor));
    if (preg_match('/^\[([0-9a-f:.]+)\](?::\d{1,5})?$/', $valor, $m)) {
        return $m[1];
    }
    if (substr_count($valor, ':') === 1) {
        $valor = preg_replace('/:\d{1,5}$/', '', $valor) ?? $valor;
    }
    return rtrim($valor, '.');
};

$host_permitidos = array_merge($hosts_locales, $dominios_produccion);
$host_canonico = '';
$host_app = $partes_app['host'] ?? '';
if (is_string($host_app) && $host_app !== '') {
    $host_canonico = $host_app;
    $host_permitidos[] = $host_app;
    if (!empty($partes_app['port'])) {
        // Se conserva el puerto solo para construir BASE_URL; la lista blanca
        // compara sin puerto.
        $host_canonico .= ':' . $partes_app['port'];
    }
}

// La IP del propio servidor no la controla el cliente: permite health checks
// y acceso directo sin abrir la lista a cualquier IP.
if (!empty($_SERVER['SERVER_ADDR'])) {
    $host_permitidos[] = (string)$_SERVER['SERVER_ADDR'];
}

$env_hosts = getenv('FORESTID_HOSTS');
if ($env_hosts !== false && $env_hosts !== '') {
    foreach (explode(',', $env_hosts) as $h) {
        $h = trim($h);
        if ($h !== '') {
            $host_permitidos[] = $h;
        }
    }
}

$host_permitidos = array_values(array_unique(array_filter(
    array_map($normalizar_host, $host_permitidos),
    static fn(string $h): bool => $h !== ''
)));

$host_peticion_plano = $normalizar_host((string)$host_raw);
$host_valido = in_array($host_peticion_plano, $host_permitidos, true);

// Si APP_URL apunta a un host local (config de desarrollo copiada a
// producción), se ignora como canónico cuando la petición trae un dominio
// real. Evita que los enlaces queden en https://localhost/.
$canonico_plano = $normalizar_host($host_canonico);
if ($host_canonico !== ''
    && in_array($canonico_plano, $hosts_locales, true)
    && !in_array($host_peticion_plano, $hosts_locales, true)) {
    $host_canonico = '';
    $canonico_plano = '';
}

if ($host_valido) {
    $host = $host_raw;
} elseif ($host_canonico !== '') {
    $host = $host_canonico;
    if (!empty($partes_app['scheme'])) {
        $protocol = $partes_app['scheme'] . '://';
    }
} else {
    // Ni Host de confianza ni APP_URL: no se puede construir una URL segura.
    if (PHP_SAPI !== 'cli') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        header('Connection: close');
        echo "Solicitud rechazada: dominio no autorizado.\n";
        exit;
    }
    $host = 'localhost';
}
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

// URL canónica para enlaces públicos y contenido de los códigos QR.
// En producción defina APP_URL en config/config.local.php, por ejemplo:
//   define('APP_URL', 'https://forestid.emincut.com/');
// Si no se define, se usa la URL detectada automáticamente (útil en local).
//
// APP_URL_EFECTIVA es solo una red de seguridad: la fuente de verdad es
// APP_URL en config/config.local.php. Si no está definida (o apunta a
// localhost en producción), se usa BASE_URL, que ya se construyó con un host
// de la lista blanca. Nunca se toma un Host no validado.
$app_url_configurada = defined('APP_URL')
    ? (string) APP_URL
    : (string) (getenv('APP_URL') ?: '');

$app_url_efectiva = BASE_URL;
if ($app_url_configurada !== '') {
    $partes_efectiva = parse_url($app_url_configurada);
    $host_efectivo   = strtolower((string) ($partes_efectiva['host'] ?? ''));
    $es_local_efectivo = in_array($host_efectivo, $hosts_locales, true);
    $peticion_es_local = in_array($host_peticion_plano, $hosts_locales, true);

    if (!($es_local_efectivo && !$peticion_es_local)) {
        $app_url_efectiva = rtrim($app_url_configurada, '/') . '/';
    }
}

if (!defined('APP_URL')) {
    define('APP_URL', $app_url_efectiva);
}
define('APP_URL_EFECTIVA', $app_url_efectiva);

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
            // Manejo de error limpio sin revelar credenciales ni detalles internos.
            error_log("Error de conexión a la Base de Datos: " . $e->getMessage());
            $detalle = (getenv('FORESTID_DEBUG') === '1')
                ? "<p><small style='color:#7F1D1D;'>Detalle técnico: " . htmlspecialchars($e->getMessage()) . "</small></p>"
                : "";
            die("<div style='font-family: sans-serif; padding: 2rem; max-width: 600px; margin: 3rem auto; background: #FEF2F2; border: 1px solid #F87171; border-radius: 8px; color: #991B1B;'>
                    <h2 style='margin-top:0;'>Error de Conexión a la Base de Datos</h2>
                    <p>No se pudo establecer conexión con MySQL. Verifique que el servicio esté activo en XAMPP y que la base de datos <strong>" . htmlspecialchars(DB_NAME) . "</strong> haya sido importada.</p>
                    {$detalle}
                 </div>");
        }
    }
    return $pdo;
}
