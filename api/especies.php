<?php
/**
 * Catálogo de especies para autocompletado.
 * Solo expone datos por GET; no realiza escrituras.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function responderJson(array $datos, int $estado = 200): never
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    responderJson(['error' => 'Método no permitido'], 405);
}

/** Normaliza texto para mostrar y comparar: minúsculas y sin diacríticos. */
function normalizarNombre(string $valor): string
{
    $valor = trim($valor);
    $minusculas = function_exists('mb_strtolower')
        ? mb_strtolower($valor, 'UTF-8')
        : strtolower($valor);

    return strtr($minusculas, [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n',
    ]);
}

function agregarEspecie(array &$especies, array $fila): void
{
    $comun = normalizarNombre((string)($fila['comun'] ?? $fila['nombre_comun'] ?? ''));
    if ($comun === '') {
        return;
    }

    $especies[$comun] = [
        'comun' => $comun,
        'cientifico' => normalizarNombre((string)($fila['cientifico'] ?? $fila['nombre_cientifico'] ?? '')),
        'familia' => normalizarNombre((string)($fila['familia'] ?? $fila['familia_botanica'] ?? '')),
    ];
}

$especies = [];
$rutaCatalogo = __DIR__ . '/../includes/catalogo_especies.php';
if (is_file($rutaCatalogo)) {
    $catalogo = require $rutaCatalogo;
    if (!is_array($catalogo)) {
        $catalogo = $catalogo_especies ?? $catalogoEspecies ?? [];
    }
    foreach ($catalogo as $fila) {
        if (is_array($fila)) {
            agregarEspecie($especies, $fila);
        }
    }
}

try {
    require_once __DIR__ . '/../config/db.php';
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $consulta = $pdo->query(
        'SELECT nombre_comun, nombre_cientifico, familia_botanica
         FROM individuos_forestales
         WHERE estado_registro = 1'
    );
    foreach ($consulta->fetchAll() as $fila) {
        agregarEspecie($especies, $fila);
    }
} catch (Throwable $e) {
    error_log('Error al cargar el catálogo de especies: ' . $e->getMessage());
    responderJson(['error' => 'No fue posible cargar el catálogo de especies'], 500);
}

$busqueda = normalizarNombre((string)($_GET['q'] ?? ''));
$resultado = array_values(array_filter($especies, static function (array $especie) use ($busqueda): bool {
    if ($busqueda === '') {
        return true;
    }

    return str_contains($especie['comun'], $busqueda)
        || str_contains($especie['cientifico'], $busqueda)
        || str_contains($especie['familia'], $busqueda);
}));

usort($resultado, static fn(array $a, array $b): int => strcmp($a['comun'], $b['comun']));
responderJson($resultado);
