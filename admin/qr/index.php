<?php
/**
 * ForestID / ForestSRD - Gestor y Generador de Códigos QR y Placas (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/qrcode_helper.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();

// Acción de regenerar QR individual (A6: solo POST con CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerar_id'])) {
    $reg_id = $_POST['regenerar_id'];
    $csrf = $_POST['csrf_token'] ?? '';

    if (validar_token_csrf($csrf)) {
        $stmt = $pdo->prepare("SELECT codigo_placa FROM `individuos_forestales` WHERE id_individuo = :id");
        $stmt->execute([':id' => $reg_id]);
        $ind = $stmt->fetch();

        if ($ind) {
            try {
                $nueva_ruta = QRCodeGenerator::guardarQR(
                    $ind['codigo_placa'],
                    QRCodeGenerator::urlFicha($ind['codigo_placa'])
                );

                $upd = $pdo->prepare("UPDATE `individuos_forestales` SET `qr_url` = :qr WHERE id_individuo = :id");
                $upd->execute([':qr' => $nueva_ruta, ':id' => $reg_id]);

                registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'REGENERAR_QR', 'QR', ['codigo_placa' => $ind['codigo_placa']]);
                set_flash('success', "Código QR para la placa {$ind['codigo_placa']} regenerado correctamente.");
            } catch (Throwable $e) {
                error_log('[ForestID] Error regenerando QR: ' . $e->getMessage());
                set_flash('error', 'No se pudo regenerar el código QR. Verifique los permisos de assets/qr.');
            }
        }
    }
    header('Location: ' . BASE_URL . 'admin/qr/index.php');
    exit;
}

// Acción de regenerar QR de forma masiva (todas las placas o las de una zona)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'regenerar_todos') {
    $csrf = $_POST['csrf_token'] ?? '';
    $zona_bulk = sanear($_POST['zona'] ?? '');

    if (!validar_token_csrf($csrf)) {
        set_flash('error', 'Token de seguridad inválido. Intente nuevamente.');
    } else {
        $sql = "SELECT id_individuo, codigo_placa FROM `individuos_forestales` WHERE estado_registro = 1";
        $params = [];
        if ($zona_bulk !== '') {
            $sql .= " AND id_zona = :zona";
            $params[':zona'] = $zona_bulk;
        }
        $sql .= " ORDER BY codigo_placa ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $todos = $stmt->fetchAll();

        $upd = $pdo->prepare("UPDATE `individuos_forestales` SET `qr_url` = :qr WHERE id_individuo = :id");
        $ok = 0;
        $fallos = 0;

        foreach ($todos as $ind) {
            try {
                $ruta = QRCodeGenerator::guardarQR(
                    $ind['codigo_placa'],
                    QRCodeGenerator::urlFicha($ind['codigo_placa'])
                );
                $upd->execute([':qr' => $ruta, ':id' => $ind['id_individuo']]);
                $ok++;
            } catch (Throwable $e) {
                $fallos++;
                error_log('[ForestID] Error regenerando QR masivo (' . $ind['codigo_placa'] . '): ' . $e->getMessage());
            }
        }

        registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'REGENERAR_QR_MASIVO', 'QR', [
            'zona'   => $zona_bulk ?: 'Todas',
            'ok'     => $ok,
            'fallos' => $fallos
        ]);

        if ($fallos > 0) {
            set_flash('warning', "Se regeneraron {$ok} códigos QR y fallaron {$fallos}. Revise los permisos de assets/qr y el registro de errores.");
        } else {
            set_flash('success', "Se regeneraron {$ok} códigos QR correctamente.");
        }
    }

    $destino = BASE_URL . 'admin/qr/index.php';
    if ($zona_bulk !== '') {
        $destino .= '?zona=' . urlencode($zona_bulk);
    }
    header('Location: ' . $destino);
    exit;
}

$filtro_zona = sanear($_GET['zona'] ?? '');
$zonas = $pdo->query("SELECT id_zona, nombre_zona FROM `zonas` WHERE estado = 1 ORDER BY nombre_zona ASC")->fetchAll();

$where = ["i.estado_registro = 1"];
$params = [];

if (!empty($filtro_zona)) {
    $where[] = "i.id_zona = :zona";
    $params[':zona'] = $filtro_zona;
}

$where_sql = implode(' AND ', $where);
$stmt = $pdo->prepare("
    SELECT i.*, z.nombre_zona
    FROM `individuos_forestales` i
    INNER JOIN `zonas` z ON i.id_zona = z.id_zona
    WHERE {$where_sql}
    ORDER BY i.codigo_placa ASC
");
$stmt->execute($params);
$individuos = $stmt->fetchAll();

// Diagnóstico de archivos QR disponibles en assets/qr
$total_qr = count($individuos);
$qr_ok = 0;
$qr_faltantes = 0;
foreach ($individuos as $arbol) {
    $ruta_qr = !empty($arbol['qr_url']) ? $arbol['qr_url'] : 'assets/qr/qr_' . $arbol['codigo_placa'] . '.svg';
    if (is_file(ROOT_PATH . $ruta_qr)) {
        $qr_ok++;
    } else {
        $qr_faltantes++;
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Gestor de Códigos QR & Placas — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Identificación Física</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Gestor de Códigos QR y Placas</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Generación, previsualización y reimpresión de placas físicas para el arbolado de Emincut en El Bagre.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="index.php" class="inline" onsubmit="return confirm('¿Regenerar los códigos QR de <?= $filtro_zona ? 'esta zona' : 'todo el inventario' ?>? Los archivos actuales serán reemplazados.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="accion" value="regenerar_todos">
                    <input type="hidden" name="zona" value="<?= htmlspecialchars($filtro_zona) ?>">
                    <button type="submit" class="emi-btn text-xs py-2.5 px-4" style="background:#fff; color:var(--emi-verde-oscuro); border:1px solid #CBD9B8">
                        <span class="inline-flex items-center gap-2"><i data-lucide="refresh-cw" class="w-4 h-4"></i> Regenerar QR</span>
                    </button>
                </form>
                <a href="<?= BASE_URL ?>admin/qr/imprimir.php<?= !empty($filtro_zona) ? '?zona=' . urlencode($filtro_zona) : '' ?>" target="_blank" class="btn-forest-primary text-xs py-2.5 px-4 shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i> Imprimir Placas
                </a>
            </div>
        </div>

        <!-- Resumen de estado -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="forest-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Individuos activos</span>
                <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= $total_qr ?></p>
            </div>
            <div class="forest-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">QR disponibles</span>
                <p class="text-2xl font-extrabold text-emerald-700 mt-1"><?= $qr_ok ?></p>
            </div>
            <div class="forest-card p-4">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">QR faltantes</span>
                <p class="text-2xl font-extrabold <?= $qr_faltantes > 0 ? 'text-amber-600' : 'text-gray-900' ?> mt-1"><?= $qr_faltantes ?></p>
            </div>
        </div>

        <!-- URL canónica de los QR -->
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 text-xs text-emerald-900 flex items-start gap-2">
            <i data-lucide="link" class="w-4 h-4 mt-0.5 shrink-0"></i>
            <div>
                <span class="font-bold block">Dominio canónico de los QR</span>
                <code class="break-all"><?= htmlspecialchars(APP_URL) ?>publico/ficha.php?codigo=...</code>
            </div>
        </div>

        <!-- Filtro por Zona -->
        <div class="forest-card p-4">
            <form method="GET" action="index.php" class="flex flex-col sm:flex-row items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Filtrar por Zona Territorial</label>
                    <select name="zona" class="w-full py-2 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                        <option value="">Todas las zonas</option>
                        <?php foreach ($zonas as $z): ?>
                            <option value="<?= $z['id_zona'] ?>" <?= ($filtro_zona === $z['id_zona']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($z['nombre_zona']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-forest-primary text-xs py-2 px-4">
                    Filtrar
                </button>
                <?php if (!empty($filtro_zona)): ?>
                    <a href="<?= BASE_URL ?>admin/qr/index.php" class="btn-forest-secondary text-xs py-2 px-3">
                        Ver Todos
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Galería de Placas QR -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (empty($individuos)): ?>
                <div class="forest-card p-8 text-center text-gray-400 italic col-span-full">
                    No hay individuos activos para los filtros seleccionados.
                </div>
            <?php endif; ?>
            <?php foreach ($individuos as $arbol): 
                $ruta_qr = !empty($arbol['qr_url']) ? $arbol['qr_url'] : 'assets/qr/qr_' . $arbol['codigo_placa'] . '.svg';
                $qr_existe = is_file(ROOT_PATH . $ruta_qr);
                $qr_img = BASE_URL . $ruta_qr;
            ?>
                <div class="forest-card p-5 flex flex-col justify-between space-y-4 hover:border-emerald-300 transition-all">
                    
                    <div class="text-center space-y-2">
                        <span class="inline-block px-3 py-1 rounded-md text-xs font-mono font-extrabold bg-emerald-950 text-white">
                            <?= htmlspecialchars($arbol['codigo_placa']) ?>
                        </span>
                        <h4 class="font-heading font-bold text-sm text-gray-900 line-clamp-1">
                            <?= htmlspecialchars($arbol['nombre_comun']) ?>
                        </h4>
                        <p class="text-[11px] text-gray-500 italic line-clamp-1">
                            <?= htmlspecialchars($arbol['nombre_cientifico']) ?>
                        </p>
                    </div>

                    <!-- Código QR Vectorial -->
                    <div class="w-36 h-36 mx-auto bg-white p-2 border-2 border-dashed <?= $qr_existe ? 'border-emerald-300' : 'border-amber-300' ?> rounded-xl flex items-center justify-center">
                        <?php if ($qr_existe): ?>
                            <img src="<?= htmlspecialchars($qr_img) ?>" alt="QR <?= htmlspecialchars($arbol['codigo_placa']) ?>" class="w-full h-full object-contain">
                        <?php else: ?>
                            <div class="text-center text-amber-600">
                                <i data-lucide="qr-code" class="w-8 h-8 mx-auto mb-1"></i>
                                <span class="text-[10px] font-bold uppercase">Sin QR</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="text-[11px] text-gray-400 text-center">
                        📍 <?= htmlspecialchars($arbol['nombre_zona']) ?>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100">
                        <?php if ($qr_existe): ?>
                            <a href="<?= BASE_URL ?>admin/qr/imprimir.php?codigo=<?= urlencode($arbol['codigo_placa']) ?>" target="_blank" class="btn-forest-secondary text-[11px] py-1.5 justify-center">
                                <i data-lucide="printer" class="w-3 h-3"></i> Imprimir
                            </a>
                        <?php else: ?>
                            <span class="btn-forest-secondary text-[11px] py-1.5 justify-center opacity-50 cursor-not-allowed" title="Genere el QR primero">
                                <i data-lucide="printer" class="w-3 h-3"></i> Imprimir
                            </span>
                        <?php endif; ?>
                        <form method="POST" action="<?= BASE_URL ?>admin/qr/index.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="regenerar_id" value="<?= htmlspecialchars($arbol['id_individuo']) ?>">
                            <button type="submit" class="p-1.5 bg-gray-100 hover:bg-emerald-100 text-gray-600 hover:text-emerald-800 rounded-lg text-[11px] font-medium flex items-center justify-center gap-1" title="Regenerar archivo QR">
                                <i data-lucide="rotate-ccw" class="w-3 h-3"></i> Regenerar
                            </button>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
