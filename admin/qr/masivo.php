<?php
/**
 * ForestID / ForestSRD - Generación masiva de placas QR vacías
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 *
 * Las placas se crean sin individuo asignado. El operario las activa en campo
 * escaneando el QR e iniciando sesión en el alta rápida.
 */

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_eliminar'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validar_token_csrf($csrf)) {
        set_flash('error', 'Token de seguridad inválido.');
    } else {
        try {
            PlacasHelper::eliminarLote($pdo, (string) $_POST['accion_eliminar']);
            set_flash('success', 'Lote eliminado correctamente.');
            header('Location: ' . BASE_URL . 'admin/qr/masivo.php');
            exit;
        } catch (RuntimeException $e) {
            set_flash('error', $e->getMessage());
            header('Location: ' . BASE_URL . 'admin/qr/masivo.php');
            exit;
        } catch (Throwable $e) {
            error_log('[ForestID] Error eliminando lote: ' . $e->getMessage());
            set_flash('error', 'No se pudo eliminar el lote. Revise los permisos e inténtelo de nuevo.');
            header('Location: ' . BASE_URL . 'admin/qr/masivo.php');
            exit;
        }
    }
}

if ($paso === 2 && !$codigos && isset($_SESSION['lote_qr_preview']['codigos'])) {
    $codigos = $_SESSION['lote_qr_preview']['codigos'];
}

$total_pendientes = (int) $pdo->query("SELECT COUNT(*) FROM `placas_qr` WHERE estado = 'pendiente'")->fetchColumn();
$total_asignadas   = (int) $pdo->query("SELECT COUNT(*) FROM `placas_qr` WHERE estado = 'asignada'")->fetchColumn();
$siguiente_codigo  = reservar_codigos_placa($pdo, 1)[0];

$lotes = $pdo->query("
    SELECT id_lote,
           COUNT(*) AS total,
           SUM(estado = 'pendiente') AS pendientes,
           SUM(estado = 'asignada') AS asignadas,
           MAX(fecha_generacion) AS fecha
      FROM `placas_qr`
     GROUP BY id_lote
     ORDER BY fecha DESC
     LIMIT 6
")->fetchAll();

$csrf = crear_token_csrf();
$titulo_pagina = 'Placas QR Vacías — ' . APP_NAME;

function placa_e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$tamanos = [
    'pequena' => ['Placa pequeña', '40 mm'],
    'mediana' => ['Placa mediana', '60 mm'],
    'grande'  => ['Placa grande', '80 mm'],
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php
    if (es_admin()) {
        require_once __DIR__ . '/../../includes/sidebar_admin.php';
    } else {
        require_once __DIR__ . '/../../includes/sidebar_operario.php';
    }
    ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">

        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Identificación Física</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Placas QR Vacías</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Genere placas sin individuo, imprímalas y actívelas en campo cuando el operario registre el árbol.
                </p>
            </div>
            <a href="<?= BASE_URL ?>admin/qr/index.php" class="btn-forest-secondary text-xs py-2.5 px-4">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver al gestor QR
            </a>
        </div>

        <?php $tab_qr = 'vacias'; require __DIR__ . '/../../includes/qr_tabs.php'; ?>

        <!-- Resumen -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="forest-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Placas pendientes</span>
                <p class="text-2xl font-extrabold text-amber-600 mt-1"><?= $total_pendientes ?></p>
                <p class="text-[11px] text-gray-400">Listas para asignar en campo</p>
            </div>
            <div class="forest-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Placas asignadas</span>
                <p class="text-2xl font-extrabold text-emerald-700 mt-1"><?= $total_asignadas ?></p>
                <p class="text-[11px] text-gray-400">Vinculadas a un individuo</p>
            </div>
            <div class="forest-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Siguiente código</span>
                <p class="text-2xl font-extrabold tracking-wider text-gray-900 mt-1"><?= placa_e($siguiente_codigo) ?></p>
                <p class="text-[11px] text-gray-400">Serie numérica consecutiva</p>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl text-xs text-rose-800 flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span><?= placa_e($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Asistente de generación -->
        <div class="forest-card p-6 sm:p-8 space-y-6 max-w-3xl">

            <!-- Pasos -->
            <ol class="flex items-center gap-2 sm:gap-3 text-[11px] sm:text-xs font-semibold">
                <?php
                $pasos = [
                    1 => 'Configurar',
                    2 => 'Confirmar',
                    3 => 'Imprimir',
                ];
                foreach ($pasos as $n => $etiqueta):
                    $estado = $n < $paso ? 'hecho' : ($n === $paso ? 'actual' : 'pendiente');
                ?>
                    <li class="flex items-center gap-2 <?= $estado === 'pendiente' ? 'text-gray-400' : 'text-gray-800' ?>">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center <?= $estado === 'actual' ? 'text-white' : '' ?>"
                              style="<?= $estado === 'actual' ? 'background:var(--emi-verde-oscuro)' : ($estado === 'hecho' ? 'background:#DCFCE7;color:#15803D' : 'background:#F3F4F6') ?>">
                            <?= $n < $paso ? '✓' : $n ?>
                        </span>
                        <span><?= $etiqueta ?></span>
                    </li>
                    <?php if ($n < 3): ?><li class="h-px flex-1 bg-gray-200" aria-hidden="true"></li><?php endif; ?>
                <?php endforeach; ?>
            </ol>

            <?php if ($paso === 1): ?>
                <form method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= placa_e($csrf) ?>">
                    <input type="hidden" name="accion" value="previsualizar">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="cantidad" class="block text-xs font-semibold text-gray-700 uppercase mb-1.5">Cantidad de placas</label>
                            <input id="cantidad" name="cantidad" type="number" min="1" max="500" required
                                   value="<?= $cantidad ?: '' ?>" placeholder="Ej. 100"
                                   class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-600 outline-none">
                            <p class="text-[11px] text-gray-400 mt-1">Entre 1 y 500 placas por lote.</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-700 uppercase mb-1.5">Tamaño de placa</span>
                            <div class="grid grid-cols-3 gap-2">
                                <?php foreach ($tamanos as $valor => [$nombre, $medida]): ?>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="tamano" value="<?= placa_e($valor) ?>" class="peer sr-only" <?= $tamano === $valor ? 'checked' : '' ?>>
                                        <span class="block rounded-xl border border-gray-200 p-2.5 text-center transition-colors peer-checked:border-emerald-600 peer-checked:bg-emerald-50 hover:border-emerald-300">
                                            <span class="block text-[11px] font-bold text-gray-800"><?= placa_e($nombre) ?></span>
                                            <span class="block text-[10px] text-gray-500"><?= placa_e($medida) ?></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-emerald-50/70 border border-emerald-200 p-4 text-xs text-emerald-900 flex items-start gap-2">
                        <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0"></i>
                        <span>Los códigos se reservan de la serie numérica de Emincut (<?= placa_e($siguiente_codigo) ?>, <?= placa_e(formatear_codigo_placa((int) $siguiente_codigo + 1)) ?>…) y no se repiten con individuos ya registrados.</span>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <a href="<?= BASE_URL ?>admin/qr/index.php" class="btn-forest-secondary text-xs py-2.5 px-4">Cancelar</a>
                        <button type="submit" class="btn-forest-primary text-xs py-2.5 px-5">
                            <i data-lucide="eye" class="w-4 h-4"></i> Continuar a vista previa
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <div class="space-y-5">
                    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-xs text-amber-900">
                        Se prepararán <strong><?= (int) $cantidad ?></strong> placas de tamaño <strong><?= placa_e($tamanos[$tamano][0] ?? $tamano) ?></strong>.
                        Esta vista previa aún no guarda nada en la base de datos.
                    </div>

                    <div>
                        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Códigos reservados</h2>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (array_slice($codigos, 0, 24) as $codigo): ?>
                                <span class="px-3 py-1.5 rounded-lg bg-emerald-950 text-white font-mono font-bold text-sm tracking-wider"><?= placa_e($codigo) ?></span>
                            <?php endforeach; ?>
                            <?php if (count($codigos) > 24): ?>
                                <span class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 font-semibold text-xs self-center">
                                    +<?= count($codigos) - 24 ?> más
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2 pt-4 border-t border-gray-100">
                        <a href="<?= BASE_URL ?>admin/qr/masivo.php" class="btn-forest-secondary text-xs py-2.5 px-4 justify-center">Cancelar</a>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= placa_e($csrf) ?>">
                            <input type="hidden" name="accion" value="generar">
                            <button type="submit" class="btn-forest-primary text-xs py-2.5 px-5 w-full justify-center">
                                <i data-lucide="check" class="w-4 h-4"></i> Confirmar y generar lote
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lotes recientes -->
        <div class="forest-card p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Historial</span>
                    <h2 class="font-heading font-bold text-lg text-gray-900">Lotes recientes</h2>
                </div>
                <a href="<?= BASE_URL ?>admin/qr/index.php" class="text-xs font-semibold text-emerald-700 hover:underline">Ver placas asignadas</a>
            </div>

            <?php if (empty($lotes)): ?>
                <p class="text-xs text-gray-400 italic py-3">Todavía no se han generado lotes de placas vacías.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-[11px] uppercase tracking-wider text-gray-400 border-b border-gray-100">
                                <th class="py-2 pr-4 font-bold">Lote</th>
                                <th class="py-2 pr-4 font-bold">Fecha</th>
                                <th class="py-2 pr-4 font-bold text-center">Total</th>
                                <th class="py-2 pr-4 font-bold text-center">Pendientes</th>
                                <th class="py-2 pr-4 font-bold text-center">Asignadas</th>
                                <th class="py-2 font-bold text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($lotes as $lote): ?>
                                <tr class="hover:bg-gray-50/70">
                                    <td class="py-2.5 pr-4 font-mono font-semibold text-emerald-900"><?= placa_e(substr((string) $lote['id_lote'], 0, 8)) ?>…</td>
                                    <td class="py-2.5 pr-4 text-gray-500"><?= placa_e(date('d/m/Y H:i', strtotime((string) $lote['fecha']))) ?></td>
                                    <td class="py-2.5 pr-4 text-center font-bold text-gray-800"><?= (int) $lote['total'] ?></td>
                                    <td class="py-2.5 pr-4 text-center text-amber-700 font-semibold"><?= (int) $lote['pendientes'] ?></td>
                                    <td class="py-2.5 pr-4 text-center text-emerald-700 font-semibold"><?= (int) $lote['asignadas'] ?></td>
                                    <td class="py-2.5 text-right">
                                        <a href="<?= BASE_URL ?>admin/qr/imprimir_masivo.php?lote=<?= rawurlencode((string) $lote['id_lote']) ?>" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-gray-100 hover:bg-emerald-100 text-gray-700 hover:text-emerald-800 font-semibold">
                                            <i data-lucide="printer" class="w-3.5 h-3.5"></i> Imprimir
                                        </a>
                                        <form method="post" style="display:inline" onsubmit="return confirm('¿Seguro que deseas eliminar este lote de <?= (int) $lote['total'] ?> placa(s)?');">
                                            <input type="hidden" name="csrf_token" value="<?= placa_e($csrf) ?>">
                                            <input type="hidden" name="accion_eliminar" value="<?= rawurlencode((string) $lote['id_lote']) ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Borrar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
