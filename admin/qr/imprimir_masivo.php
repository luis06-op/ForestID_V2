<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut', 'OperarioEmincut']);
$lote = (string) ($_GET['lote'] ?? '');
$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT codigo_placa, tamano FROM placas_qr WHERE id_lote = :lote ORDER BY codigo_placa');
$stmt->execute([':lote' => $lote]);
$placas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$tamano = $placas[0]['tamano'] ?? 'mediana';
$medidas = ['pequena' => '40mm', 'mediana' => '60mm', 'grande' => '80mm'];
function imprimir_e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Imprimir lote de placas QR</title>
<style>body{font:16px Arial,sans-serif;margin:1rem;color:#111}.controles{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(<?= imprimir_e($medidas[$tamano] ?? '60mm') ?>,1fr));gap:8mm}.celda{border:1px solid #777;min-height:35mm;padding:5mm;display:flex;flex-direction:column;align-items:center;justify-content:center;page-break-inside:avoid;break-inside:avoid}.celda svg{width:25mm;height:25mm;max-width:100%;height:auto}.codigo{font-size:20pt;font-weight:bold;text-align:center;margin-top:3mm;letter-spacing:.04em}.vacio{text-align:center;color:#555}@media print{.no-print{display:none!important}body{margin:5mm}.grid{gap:5mm}.celda{border:1px solid #222}}</style></head>
<body><header class="controles no-print"><div><h1>Lote de placas QR</h1><p><?= count($placas) ?> placas · <?= imprimir_e($medidas[$tamano] ?? '60mm') ?></p></div><button type="button" onclick="window.print()">Imprimir</button></header>
<?php if (!$placas): ?><p class="vacio">No se encontraron placas para este lote.</p><?php else: ?><main class="grid"><?php foreach ($placas as $placa): $codigo = (string) $placa['codigo_placa']; $archivo = ROOT_PATH . 'assets/qr/qr_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $codigo) . '.svg'; ?><article class="celda"><?php if (is_file($archivo)): ?><?= file_get_contents($archivo) ?><?php else: ?><span>QR no disponible</span><?php endif; ?><div class="codigo"><?= imprimir_e($codigo) ?></div></article><?php endforeach; ?></main><?php endif; ?></body></html>
