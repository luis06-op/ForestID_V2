<?php
/**
 * ForestID - Verificador de despliegue (solo CLI, NO accesible por web)
 *
 * Uso:
 *   php scripts/verificar_despliegue.php
 *
 * Revisa el entorno antes/después de desplegar en Hostinger:
 *   - PHP >= 8.1 (composer.json lo exige) y extensiones requeridas
 *   - Zona horaria date.timezone = America/Bogota
 *   - upload_max_filesize, post_max_size y memory_limit mínimos sugeridos
 *   - Permisos de escritura en assets/uploads/ y assets/qr/
 *   - Conexión a la base de datos (config/config.local.php > variables de entorno > defaults de XAMPP)
 *   - APP_URL definida y con HTTPS
 *
 * Salida: tabla PRUEBA | RESULTADO | DETALLE. Código de salida 0 si todo
 * pasa, 1 si hay al menos una FALLA. No imprime contraseñas.
 */

// --- Guardia: solo línea de comandos ---
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("403 - Este verificador solo se ejecuta por CLI (php scripts/verificar_despliegue.php).\n");
}

// Umbrales sugeridos (ajústelos si Hostinger ofrece otros mínimos).
const VERIFY_MIN_PHP           = '8.1.0';
const VERIFY_MIN_UPLOAD_BYTES  = 2097152;   // 2 MB
const VERIFY_MIN_MEMORY_BYTES  = 134217728; // 128 MB (-1 = sin límite, se acepta)
const VERIFY_APP_TIMEZONE      = 'America/Bogota';
const VERIFY_REQUIRED_EXT      = ['pdo_mysql', 'mbstring', 'fileinfo', 'curl', 'openssl', 'json', 'session', 'gd'];

$root = dirname(__DIR__);
$filas = [];

/** @var list<array{prueba:string, ok:bool, detalle:string}> $filas */
function check(string $prueba, bool $ok, string $detalle = ''): void {
    global $filas;
    $filas[] = ['prueba' => $prueba, 'ok' => $ok, 'detalle' => $detalle];
}

function ini_size_to_bytes(string $value): int {
    $value = trim($value);
    if ($value === '' || $value === '-1') {
        return -1; // sin límite
    }
    $num  = (float) $value;
    $unit = strtolower(substr($value, -1));
    if ($unit === 'g') { $num *= 1024 * 1024 * 1024; }
    elseif ($unit === 'm') { $num *= 1024 * 1024; }
    elseif ($unit === 'k') { $num *= 1024; }
    return (int) $num;
}

// ---------------- 1. PHP y extensiones ----------------
$phpOk = version_compare(PHP_VERSION, VERIFY_MIN_PHP, '>=');
check('Versión de PHP (>= ' . VERIFY_MIN_PHP . ')', $phpOk, 'PHP ' . PHP_VERSION . ($phpOk ? '' : ' - composer.json exige >= 8.1'));

foreach (VERIFY_REQUIRED_EXT as $ext) {
    $cargada = extension_loaded($ext);
    check("Extensión {$ext}", $cargada, $cargada ? 'cargada' : 'FALTA - habilítela en php.ini / panel de Hostinger');
}

// ---------------- 2. Zona horaria ----------------
$tz = ini_get('date.timezone');
if ($tz === false || $tz === '') {
    $tz = date_default_timezone_get();
}
$tzOk = ($tz === VERIFY_APP_TIMEZONE);
$tzDetalle = "date.timezone = '{$tz}'";
if (!$tzOk) {
    // En CLI el php.ini puede diferir del usado por el servidor web: si
    // .user.ini fija la zona, el despliegue web es correcto.
    $userIni = $root . DIRECTORY_SEPARATOR . '.user.ini';
    if (is_file($userIni)) {
        $contenidoIni = (string) file_get_contents($userIni);
        if (preg_match('/^\s*date\.timezone\s*=\s*' . preg_quote(VERIFY_APP_TIMEZONE, '/') . '\s*$/mi', $contenidoIni) === 1) {
            $tzOk = true;
            $tzDetalle .= ' — .user.ini fija ' . VERIFY_APP_TIMEZONE . ' para el servidor web';
        }
    }
}
check('Zona horaria America/Bogota', $tzOk,
    $tzDetalle . ($tzOk ? '' : ' - fíjela en .user.ini o php.ini: date.timezone=America/Bogota'));

// ---------------- 3. Límites de subida y memoria ----------------
$uploadRaw = (string) ini_get('upload_max_filesize');
$postRaw   = (string) ini_get('post_max_size');
$memRaw    = (string) ini_get('memory_limit');
$upload    = ini_size_to_bytes($uploadRaw);
$post      = ini_size_to_bytes($postRaw);
$memory    = ini_size_to_bytes($memRaw);

check('upload_max_filesize (>= 2M)', $upload === -1 || $upload >= VERIFY_MIN_UPLOAD_BYTES, "upload_max_filesize = {$uploadRaw}");
check('post_max_size (>= upload_max_filesize)', $post === -1 || $post >= $upload, "post_max_size = {$postRaw}" . (($post !== -1 && $upload !== -1 && $post < $upload) ? ' - es MENOR que upload_max_filesize' : ''));
check('memory_limit (>= 128M o -1)', $memory === -1 || $memory >= VERIFY_MIN_MEMORY_BYTES, "memory_limit = {$memRaw}");

// ---------------- 3.b Errores fuera de pantalla ----------------
$displayErrors = strtolower((string) ini_get('display_errors'));
$logErrors     = strtolower((string) ini_get('log_errors'));
check('display_errors desactivado', in_array($displayErrors, ['0', 'off', ''], true),
    "display_errors = '{$displayErrors}'" . (in_array($displayErrors, ['0', 'off', ''], true) ? '' : ' - debe ser Off'));
check('log_errors activado', in_array($logErrors, ['1', 'on'], true),
    "log_errors = '{$logErrors}'");

// ---------------- 3.c Protección de carpetas sensibles ----------------
foreach (['config', 'includes', 'vendor', 'database'] as $dirSensible) {
    $rutaDir = $root . DIRECTORY_SEPARATOR . $dirSensible;
    if (is_dir($rutaDir)) {
        $ht = is_file($rutaDir . DIRECTORY_SEPARATOR . '.htaccess');
        check(".htaccess en {$dirSensible}/", $ht, $ht ? 'presente' : 'FALTA - cree el .htaccess de bloqueo');
    }
}

// ---------------- 4. Permisos de escritura ----------------
foreach (['assets/uploads', 'assets/qr'] as $rutaRelativa) {
    $ruta = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
    $existe    = is_dir($ruta);
    $escribible = $existe && is_writable($ruta);
    check("Escribible {$rutaRelativa}/", $escribible,
        !$existe ? 'el directorio NO existe' : ($escribible ? 'permisos OK' : 'sin permiso de escritura para el usuario del servidor'));
}

// ---------------- 5. Conexión a la base de datos ----------------
$configLocal = $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.local.php';
$fuenteDb = 'defaults de XAMPP (sin config/config.local.php ni variables de entorno)';
if (is_file($configLocal)) {
    require_once $configLocal;
    $fuenteDb = 'config/config.local.php';
} else {
    $envHost = getenv('DB_HOST');
    if ($envHost !== false && $envHost !== '') {
        $fuenteDb = 'variables de entorno';
    }
}
if (!defined('DB_HOST'))    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME'))    define('DB_NAME', getenv('DB_NAME') ?: 'forestsrd_db');
if (!defined('DB_USER'))    define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS'))    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4;connect_timeout=5';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    check('Conexión a BD', true, 'OK - ' . $version . ' (fuente: ' . $fuenteDb . ')');
    $pdo = null;
} catch (PDOException $e) {
    check('Conexión a BD', false, 'FALLA - no se pudo conectar (fuente: ' . $fuenteDb . '). Detalle en el log del servidor; no se muestran credenciales.');
}

// ---------------- 6. APP_URL (opcional: si falta se detecta del dominio) ----------------
$appUrl = defined('APP_URL') ? (string) APP_URL : (string) (getenv('APP_URL') ?: '');
if ($appUrl === '') {
    check('APP_URL', true, 'no definida - se detecta automáticamente del dominio de la petición');
} else {
    $scheme = strtolower((string) (parse_url($appUrl, PHP_URL_SCHEME) ?: ''));
    $host   = (string) (parse_url($appUrl, PHP_URL_HOST) ?: '');
    $ok     = $scheme === 'https' && $host !== '';
    check('APP_URL con HTTPS', $ok, $appUrl . ($ok ? ' - OK' : ' - debe ser https://'));
}

// ---------------- Salida: tabla ----------------
$anchoPrueba = 44;
$anchoEstado = 8;
$separador   = str_repeat('-', $anchoPrueba + $anchoEstado + 60);

echo "\nForestID - Verificación de despliegue (CLI)\n";
echo $separador . "\n";
printf("%-{$anchoPrueba}s %-{$anchoEstado}s %s\n", 'PRUEBA', 'RESULTADO', 'DETALLE');
echo $separador . "\n";

$fallos = 0;
foreach ($filas as $fila) {
    if (!$fila['ok']) {
        $fallos++;
    }
    printf("%-{$anchoPrueba}s %-{$anchoEstado}s %s\n",
        $fila['prueba'],
        $fila['ok'] ? 'OK' : 'FALLA',
        $fila['detalle']);
}
echo $separador . "\n";
printf("Total: %d pruebas, %d FALLA(s)\n\n", count($filas), $fallos);

exit($fallos === 0 ? 0 : 1);
