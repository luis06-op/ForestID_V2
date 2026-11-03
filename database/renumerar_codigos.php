<?php
/**
 * ForestID / ForestSRD - Migración de códigos de placa a la serie numérica
 *
 * Convierte los códigos antiguos (ej. EBG-NECH-003) en una serie consecutiva
 * de números (001, 002, ... 448) compartida por individuos registrados y
 * placas QR vacías, y regenera los archivos SVG de los códigos QR.
 *
 * Uso (CLI, desde la raíz del proyecto):
 *   php database/renumerar_codigos.php "http://localhost/ForestID_V2-qr-masivo-vacio/"
 *
 * El segundo argumento es la URL pública que se codificará dentro de los QR
 * (debe ser la misma APP_URL del entorno). Guarda un respaldo del mapeo en la
 * carpeta temporal del sistema antes de modificar nada.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo puede ejecutarse por línea de comandos.\n");
}

$app_url = rtrim((string) ($argv[1] ?? ''), '/');
if ($app_url === '') {
    fwrite(STDERR, "Indique la URL pública del proyecto, por ejemplo:\n  php database/renumerar_codigos.php \"http://localhost/ForestID_V2-qr-masivo-vacio/\"\n");
    exit(1);
}
if (!defined('APP_URL')) {
    define('APP_URL', $app_url . '/');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/qrcode_helper.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "APP_URL para los QR: " . APP_URL . PHP_EOL;

$inds = $pdo->query("SELECT id_individuo, codigo_placa FROM `individuos_forestales` ORDER BY fecha_registro ASC, id_individuo ASC")->fetchAll(PDO::FETCH_ASSOC);
$pend = $pdo->query("SELECT id_placa, codigo_placa FROM `placas_qr` WHERE estado = 'pendiente' ORDER BY fecha_generacion ASC, id_placa ASC")->fetchAll(PDO::FETCH_ASSOC);
$asig = $pdo->query("SELECT id_placa, codigo_placa, asignado_a FROM `placas_qr` WHERE estado = 'asignada'")->fetchAll(PDO::FETCH_ASSOC);

if (!$inds && !$pend && !$asig) {
    echo "No hay placas ni individuos que reasignar.\n";
    exit(0);
}

// 1) Calcular el mapeo definitivo: individuos primero y luego placas pendientes.
$numero = 1;
$map_ind = [];
$map_placa = [];
$map_asignada = [];

foreach ($inds as $ind) {
    $map_ind[$ind['id_individuo']] = formatear_codigo_placa($numero++);
}
foreach ($pend as $placa) {
    $map_placa[$placa['id_placa']] = formatear_codigo_placa($numero++);
}
foreach ($asig as $placa) {
    if (isset($map_ind[$placa['asignado_a']])) {
        $map_asignada[$placa['id_placa']] = $map_ind[$placa['asignado_a']];
    }
}

// Respaldo del mapeo antes de tocar la base de datos.
$respaldo = [
    'fecha' => date('c'),
    'app_url' => APP_URL,
    'individuos' => [],
    'placas_pendientes' => [],
    'placas_asignadas' => [],
];
foreach ($inds as $ind) {
    $respaldo['individuos'][$ind['id_individuo']] = [
        'anterior' => $ind['codigo_placa'],
        'nuevo' => $map_ind[$ind['id_individuo']],
    ];
}
foreach ($pend as $placa) {
    $respaldo['placas_pendientes'][$placa['id_placa']] = [
        'anterior' => $placa['codigo_placa'],
        'nuevo' => $map_placa[$placa['id_placa']],
    ];
}
foreach ($asig as $placa) {
    if (isset($map_asignada[$placa['id_placa']])) {
        $respaldo['placas_asignadas'][$placa['id_placa']] = [
            'anterior' => $placa['codigo_placa'],
            'nuevo' => $map_asignada[$placa['id_placa']],
        ];
    }
}
$ruta_respaldo = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'forestid_codigos_' . date('Ymd_His') . '.json';
file_put_contents($ruta_respaldo, json_encode($respaldo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "Respaldo del mapeo: {$ruta_respaldo}" . PHP_EOL;

/**
 * Ruta del archivo SVG de un código de placa.
 */
function archivo_qr(string $codigo): string {
    return ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR . 'qr' . DIRECTORY_SEPARATOR
        . 'qr_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $codigo) . '.svg';
}

// 2) Generar primero los QR nuevos (si algo falla, la base de datos aún no cambia).
$generados = 0;
$pendientes_borrado = [];

foreach ($inds as $ind) {
    $anterior = (string) $ind['codigo_placa'];
    $nuevo = $map_ind[$ind['id_individuo']];
    if ($anterior === $nuevo) {
        continue;
    }
    QRCodeGenerator::guardarQR($nuevo, QRCodeGenerator::urlFicha($nuevo));
    $generados++;
    $pendientes_borrado[$anterior] = true;
}

foreach ($pend as $placa) {
    $anterior = (string) $placa['codigo_placa'];
    $nuevo = $map_placa[$placa['id_placa']];
    if ($anterior === $nuevo) {
        continue;
    }
    QRCodeGenerator::guardarQR($nuevo, QRCodeGenerator::urlFicha($nuevo));
    $generados++;
    $pendientes_borrado[$anterior] = true;
}

// 3) Actualizar la base de datos en dos pasos (temporal -> definitivo).
$pdo->beginTransaction();
try {
    $updInd = $pdo->prepare("UPDATE `individuos_forestales` SET `codigo_placa` = :codigo WHERE `id_individuo` = :id");
    $updPlaca = $pdo->prepare("UPDATE `placas_qr` SET `codigo_placa` = :codigo WHERE `id_placa` = :id");

    foreach ($map_ind as $id => $nuevo) {
        $updInd->execute([':codigo' => 'T' . bin2hex(random_bytes(8)), ':id' => $id]);
    }
    foreach (array_merge(array_keys($map_placa), array_keys($map_asignada)) as $id) {
        $updPlaca->execute([':codigo' => 'T' . bin2hex(random_bytes(8)), ':id' => $id]);
    }

    $updQr = $pdo->prepare("UPDATE `individuos_forestales` SET `codigo_placa` = :codigo, `qr_url` = :qr WHERE `id_individuo` = :id");
    foreach ($map_ind as $id => $nuevo) {
        $updQr->execute([
            ':codigo' => $nuevo,
            ':qr' => 'assets/qr/qr_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $nuevo) . '.svg',
            ':id' => $id,
        ]);
    }
    foreach ($map_placa as $id => $nuevo) {
        $updPlaca->execute([':codigo' => $nuevo, ':id' => $id]);
    }
    foreach ($map_asignada as $id => $nuevo) {
        $updPlaca->execute([':codigo' => $nuevo, ':id' => $id]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Error al actualizar la base de datos: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// 4) Eliminar los SVG antiguos que ya no están referenciados.
$borrados = 0;
foreach (array_keys($pendientes_borrado) as $anterior) {
    $archivo = archivo_qr((string) $anterior);
    if (is_file($archivo)) {
        @unlink($archivo);
        $borrados++;
    }
}

echo "Individuos renumerados: " . count($map_ind) . PHP_EOL;
echo "Placas pendientes renumeradas: " . count($map_placa) . PHP_EOL;
echo "Placas asignadas actualizadas: " . count($map_asignada) . PHP_EOL;
echo "QR generados: {$generados} | SVG antiguos eliminados: {$borrados}" . PHP_EOL;

$serie_individuos = array_values($map_ind);
echo "Serie resultante en individuos: " . ($serie_individuos[0] ?? '—') . " … " . (end($serie_individuos) ?: '—') . PHP_EOL;
echo "Listo. El siguiente código disponible es " . reservar_codigos_placa($pdo, 1)[0] . "." . PHP_EOL;
