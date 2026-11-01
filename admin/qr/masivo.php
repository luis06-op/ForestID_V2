<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/placas_helper.php';

requerir_rol(['AdminEmincut', 'OperarioEmincut']);
$pdo = getDBConnection();
$paso = 1;
$error = '';
$cantidad = 0;
$tamano = 'mediana';
$codigos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_token_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Token de seguridad inválido. Actualice la página e inténtelo de nuevo.';
    } elseif (($_POST['accion'] ?? '') === 'previsualizar') {
        $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $tamano = (string) ($_POST['tamano'] ?? '');
        if ($cantidad < 1 || $cantidad > 500 || !in_array($tamano, ['pequena', 'mediana', 'grande'], true)) {
            $error = 'Indique una cantidad de 1 a 500 y un tamaño válido.';
        } else {
            try {
                $codigos = PlacasHelper::generarCodigosEnMemoria($pdo, $cantidad);
                $_SESSION['lote_qr_preview'] = ['cantidad' => $cantidad, 'tamano' => $tamano, 'codigos' => $codigos];
                $paso = 2;
            } catch (Throwable $e) {
                error_log('[ForestID] Vista previa de placas: ' . $e->getMessage());
                $error = 'No fue posible preparar la vista previa.';
            }
        }
    } elseif (($_POST['accion'] ?? '') === 'generar') {
        $preview = $_SESSION['lote_qr_preview'] ?? null;
        if (!is_array($preview) || !isset($preview['cantidad'], $preview['tamano'])) {
            $error = 'La vista previa expiró. Inicie el proceso nuevamente.';
        } else {
            $idLote = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
            try {
                $pdo->beginTransaction();
                $generados = PlacasHelper::generarLote($pdo, (int) $preview['cantidad'], $idLote, (string) $preview['tamano'], $preview['codigos']);
                registrar_log($pdo, $_SESSION['usuario']['id_usuario'] ?? null, 'GENERAR_QR_MASIVO_VACIO', 'QR', [
                    'id_lote' => $idLote, 'cantidad' => count($generados), 'tamano' => $preview['tamano'],
                ]);
                $pdo->commit();
                unset($_SESSION['lote_qr_preview']);
                header('Location: ' . BASE_URL . 'admin/qr/imprimir_masivo.php?lote=' . rawurlencode($idLote));
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('[ForestID] Generación de placas masivas: ' . $e->getMessage());
                $error = 'No se pudo generar el lote. Revise los permisos de la carpeta QR e inténtelo de nuevo.';
                $paso = 2;
                $cantidad = (int) $preview['cantidad'];
                $tamano = (string) $preview['tamano'];
                $codigos = is_array($preview['codigos'] ?? null) ? $preview['codigos'] : [];
            }
        }
    }
}
if ($paso === 2 && !$codigos && isset($_SESSION['lote_qr_preview']['codigos'])) {
    $codigos = $_SESSION['lote_qr_preview']['codigos'];
}
$csrf = crear_token_csrf();
function placa_e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Generar placas QR vacías</title>
<style>body{font:16px Arial,sans-serif;background:#f3f6f4;color:#19352a;margin:0;padding:2rem}.panel{max-width:720px;margin:auto;background:white;border-radius:14px;padding:2rem;box-shadow:0 4px 20px #183d2418}h1{margin-top:0}.pasos{display:flex;gap:1rem;margin:1.5rem 0}.pasos span{padding:.6rem 1rem;border-radius:2rem;background:#e5eee8}.pasos .actual{background:#176b45;color:white}label{display:block;font-weight:bold;margin:1rem 0 .4rem}input,select,button{font:inherit;padding:.7rem;border:1px solid #aabbb0;border-radius:7px}button{background:#176b45;color:white;border:0;cursor:pointer;margin-top:1rem}.error{color:#9b1c1c}.codigos{display:flex;gap:1rem;flex-wrap:wrap}.codigo{padding:1rem;background:#edf5ef;border-radius:8px;font-size:1.2rem;font-weight:bold}</style></head>
<body><main class="panel"><h1>Generar placas QR vacías</h1><div class="pasos"><span class="<?= $paso === 1 ? 'actual' : '' ?>">1. Configurar</span><span class="<?= $paso === 2 ? 'actual' : '' ?>">2. Confirmar</span><span>3. Generar e imprimir</span></div>
<?php if ($error !== ''): ?><p class="error"><?= placa_e($error) ?></p><?php endif; ?>
<?php if ($paso === 1): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= placa_e($csrf) ?>"><input type="hidden" name="accion" value="previsualizar"><label for="cantidad">Cantidad (1 a 500)</label><input id="cantidad" name="cantidad" type="number" min="1" max="500" required value="<?= $cantidad ?: '' ?>"><label for="tamano">Tamaño de placa</label><select id="tamano" name="tamano"><option value="pequena">Pequeña (40 mm)</option><option value="mediana" selected>Mediana (60 mm)</option><option value="grande">Grande (80 mm)</option></select><button type="submit">Continuar a vista previa</button></form>
<?php else: ?><p>Se prepararán <strong><?= (int) $cantidad ?></strong> placas de tamaño <strong><?= placa_e($tamano) ?></strong>. Estos códigos son una vista previa en memoria; aún no se ha guardado el lote.</p><h2>Vista previa (primeros 3)</h2><div class="codigos"><?php foreach (array_slice($codigos, 0, 3) as $codigo): ?><span class="codigo"><?= placa_e($codigo) ?></span><?php endforeach; ?></div><form method="post"><input type="hidden" name="csrf_token" value="<?= placa_e($csrf) ?>"><input type="hidden" name="accion" value="generar"><button type="submit">Confirmar y generar lote</button></form><form method="get"><button type="submit">Cancelar</button></form><?php endif; ?></main></body></html>
