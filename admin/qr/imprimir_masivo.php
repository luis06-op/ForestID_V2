<?php
/**
 * ForestID / ForestSRD - Impresión de un lote de placas QR vacías
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut', 'OperarioEmincut']);

$lote = trim((string) ($_GET['lote'] ?? ''));
$pdo = getDBConnection();
$placas = [];
if ($lote !== '') {
    $stmt = $pdo->prepare('SELECT codigo_placa, tamano, estado FROM `placas_qr` WHERE id_lote = :lote ORDER BY CAST(codigo_placa AS UNSIGNED), codigo_placa');
    $stmt->execute([':lote' => $lote]);
    $placas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$tamano = $placas[0]['tamano'] ?? 'mediana';
$medidas = ['pequena' => '40mm', 'mediana' => '60mm', 'grande' => '80mm'];
$pendientes = count(array_filter($placas, static fn(array $p): bool => ($p['estado'] ?? '') === 'pendiente'));

function imprimir_e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lote de placas QR — <?= imprimir_e(substr($lote, 0, 8)) ?> — ForestID</title>
    <style>
        :root { --verde: #0E3B2E; --verde-medio: #176B45; --amarillo: #F2B632; --tinta: #182B22; }
        * { box-sizing: border-box; }
        body { font-family: Inter, "Segoe UI", Arial, sans-serif; margin: 0; background: #F6F7F4; color: var(--tinta); }
        .barra { position: sticky; top: 0; z-index: 5; background: var(--verde); color: #fff; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .barra h1 { font-size: 16px; margin: 0; letter-spacing: .01em; }
        .barra p { margin: 2px 0 0; font-size: 12px; color: rgba(255,255,255,.72); }
        .barra .acciones { display: flex; gap: 8px; align-items: center; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; border: 0; cursor: pointer; }
        .btn-sol { background: var(--amarillo); color: var(--verde); }
        .btn-ghost { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.25); }
        .contenido { max-width: 1200px; margin: 0 auto; padding: 22px 20px 40px; }
        .aviso { background: #fff; border: 1px solid #E3E7DF; border-left: 4px solid var(--amarillo); border-radius: 12px; padding: 12px 16px; font-size: 12px; color: #55635C; margin-bottom: 18px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(<?= imprimir_e($medidas[$tamano] ?? '60mm') ?>, 1fr)); gap: 8mm; }
        .celda { background: #fff; border: 1.5px solid #1F2A24; border-radius: 8px; min-height: 42mm; padding: 4mm 3mm; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2mm; break-inside: avoid; page-break-inside: avoid; }
        .celda svg { width: 24mm; height: 24mm; max-width: 100%; }
        .codigo { font-size: 22pt; line-height: 1; font-weight: 800; letter-spacing: .08em; color: var(--verde); text-align: center; font-variant-numeric: tabular-nums; }
        .marca { font-size: 6.5pt; letter-spacing: .16em; text-transform: uppercase; color: #6B7B72; font-weight: 700; }
        .vacio { background: #fff; border: 1px dashed #C9D2CB; border-radius: 14px; padding: 40px; text-align: center; color: #6B7B72; font-size: 14px; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .contenido { max-width: none; padding: 0; }
            .aviso { display: none; }
            .grid { gap: 5mm; }
            .celda { border: 1px solid #000; border-radius: 0; }
        }
    </style>
</head>
<body>

<header class="barra no-print">
    <div>
        <h1>Placas QR — Lote <?= imprimir_e(substr($lote, 0, 8)) ?><?= $lote !== '' ? '…' : '' ?></h1>
        <p><?= count($placas) ?> placas · <?= imprimir_e($medidas[$tamano] ?? '60mm') ?> · <?= $pendientes ?> pendientes de asignar</p>
    </div>
    <div class="acciones">
        <a href="<?= BASE_URL ?>admin/qr/masivo.php" class="btn btn-ghost">Volver</a>
        <button type="button" class="btn btn-sol" onclick="window.print()">Imprimir</button>
    </div>
</header>

<main class="contenido">
    <?php if (!$placas): ?>
        <div class="vacio">
            <p><strong>No se encontraron placas para este lote.</strong></p>
            <p>Genere un lote nuevo desde el gestor de placas QR vacías.</p>
            <p><a href="<?= BASE_URL ?>admin/qr/masivo.php" class="btn btn-sol">Generar placas</a></p>
        </div>
    <?php else: ?>
        <div class="aviso no-print">
            Recorte cada placa por el borde y fíjela en el fuste. El número impreso es el identificador visible del árbol;
            el QR abre la ficha pública y permite al operario activar la placa tras iniciar sesión.
        </div>
        <div class="grid">
            <?php foreach ($placas as $placa): ?>
                <?php
                $codigo = (string) $placa['codigo_placa'];
                $archivo = ROOT_PATH . 'assets/qr/qr_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $codigo) . '.svg';
                ?>
                <article class="celda">
                    <?php if (is_file($archivo)): ?>
                        <?= file_get_contents($archivo) ?>
                    <?php else: ?>
                        <span class="marca">QR no disponible</span>
                    <?php endif; ?>
                    <div class="codigo"><?= imprimir_e($codigo) ?></div>
                    <div class="marca">ForestID · Emincut</div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
