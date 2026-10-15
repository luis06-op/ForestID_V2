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

// Acción de regenerar QR individual
if (isset($_GET['regenerar_id'])) {
    $reg_id = $_GET['regenerar_id'];
    $csrf = $_GET['csrf'] ?? '';

    if (validar_token_csrf($csrf)) {
        $stmt = $pdo->prepare("SELECT codigo_placa FROM `individuos_forestales` WHERE id_individuo = :id");
        $stmt->execute([':id' => $reg_id]);
        $ind = $stmt->fetch();

        if ($ind) {
            $urlFicha = BASE_URL . "publico/ficha.php?codigo=" . urlencode($ind['codigo_placa']);
            $nueva_ruta = QRCodeGenerator::guardarQR($ind['codigo_placa'], $urlFicha);
            
            $upd = $pdo->prepare("UPDATE `individuos_forestales` SET `qr_url` = :qr WHERE id_individuo = :id");
            $upd->execute([':qr' => $nueva_ruta, ':id' => $reg_id]);

            registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'REGENERAR_QR', 'QR', ['codigo_placa' => $ind['codigo_placa']]);
            set_flash('success', "Código QR para la placa {$ind['codigo_placa']} regenerado correctamente.");
        }
    }
    header('Location: ' . BASE_URL . 'admin/qr/index.php');
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
                    Generación, previsualización y reimpresión de placas físicas con códigos QR para el arbolado de El Bagre.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>admin/qr/imprimir.php<?= !empty($filtro_zona) ? '?zona=' . urlencode($filtro_zona) : '' ?>" target="_blank" class="btn-forest-primary text-xs py-2.5 px-4 shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i> Imprimir Placas de Zona
                </a>
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
            <?php foreach ($individuos as $arbol): 
                $qr_img = !empty($arbol['qr_url']) ? BASE_URL . $arbol['qr_url'] : BASE_URL . 'assets/qr/qr_' . $arbol['codigo_placa'] . '.svg';
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
                    <div class="w-36 h-36 mx-auto bg-white p-2 border-2 border-dashed border-emerald-300 rounded-xl flex items-center justify-center">
                        <img src="<?= htmlspecialchars($qr_img) ?>" alt="QR <?= $arbol['codigo_placa'] ?>" class="w-full h-full object-contain">
                    </div>

                    <div class="text-[11px] text-gray-400 text-center">
                        📍 <?= htmlspecialchars($arbol['nombre_zona']) ?>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100">
                        <a href="<?= BASE_URL ?>admin/qr/imprimir.php?codigo=<?= urlencode($arbol['codigo_placa']) ?>" target="_blank" class="btn-forest-secondary text-[11px] py-1.5 justify-center">
                            <i data-lucide="printer" class="w-3 h-3"></i> Imprimir
                        </a>
                        <a href="<?= BASE_URL ?>admin/qr/index.php?regenerar_id=<?= $arbol['id_individuo'] ?>&csrf=<?= $csrf_token ?>" class="p-1.5 bg-gray-100 hover:bg-emerald-100 text-gray-600 hover:text-emerald-800 rounded-lg text-[11px] font-medium flex items-center justify-center gap-1" title="Regenerar archivo QR">
                            <i data-lucide="rotate-ccw" class="w-3 h-3"></i> Regenerar
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
