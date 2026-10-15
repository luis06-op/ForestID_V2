<?php
/**
 * ForestID / ForestSRD - Listado y Gestión de Individuos Forestales (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();

$q            = sanear($_GET['q'] ?? '');
$filtro_zona  = sanear($_GET['zona'] ?? '');
$filtro_salud = sanear($_GET['salud'] ?? '');

$zonas = $pdo->query("SELECT id_zona, nombre_zona FROM `zonas` WHERE estado = 1 ORDER BY nombre_zona ASC")->fetchAll();

$where = ["i.estado_registro = 1"];
$params = [];

if (!empty($q)) {
    $where[] = "(i.codigo_placa LIKE :q1 OR i.nombre_comun LIKE :q2 OR i.nombre_cientifico LIKE :q3 OR i.familia_botanica LIKE :q4)";
    $term = "%{$q}%";
    $params[':q1'] = $term;
    $params[':q2'] = $term;
    $params[':q3'] = $term;
    $params[':q4'] = $term;
}

if (!empty($filtro_zona)) {
    $where[] = "i.id_zona = :zona";
    $params[':zona'] = $filtro_zona;
}

if (!empty($filtro_salud)) {
    $where[] = "e.estado = :salud";
    $params[':salud'] = $filtro_salud;
}

$where_sql = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT i.*, z.nombre_zona, e.estado AS estado_salud
    FROM `individuos_forestales` i
    INNER JOIN `zonas` z ON i.id_zona = z.id_zona
    LEFT JOIN `estados_fitosanitarios` e ON i.id_estado_fitosanitario_actual = e.id_estado
    WHERE {$where_sql}
    ORDER BY i.fecha_registro DESC
");
$stmt->execute($params);
$individuos = $stmt->fetchAll();

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Individuos Forestales — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Inventario Dendrológico</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Individuos Forestales</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Registro de árboles, palmas y flora con placas QR activas en El Bagre, Antioquia.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>admin/individuos/crear.php" class="btn-forest-primary text-xs py-2.5 px-4 shadow-sm">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i> Nuevo Individuo
                </a>
                <a href="<?= BASE_URL ?>admin/qr/imprimir.php" class="btn-forest-secondary text-xs py-2.5 px-3">
                    <i data-lucide="printer" class="w-4 h-4"></i> Imprimir QR
                </a>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="forest-card p-4">
            <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                <div class="sm:col-span-5">
                    <label class="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Búsqueda rápida</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Placa, especie, nombre común..."
                               class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Zona</label>
                    <select name="zona" class="w-full py-2 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                        <option value="">Todas las zonas</option>
                        <?php foreach ($zonas as $z): ?>
                            <option value="<?= $z['id_zona'] ?>" <?= ($filtro_zona === $z['id_zona']) ? 'selected' : '' ?>><?= htmlspecialchars($z['nombre_zona']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Estado Fitosanitario</label>
                    <select name="salud" class="w-full py-2 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                        <option value="">Todos los estados</option>
                        <option value="Sano" <?= ($filtro_salud === 'Sano') ? 'selected' : '' ?>>Sano</option>
                        <option value="En observación" <?= ($filtro_salud === 'En observación') ? 'selected' : '' ?>>En observación</option>
                        <option value="Enfermo" <?= ($filtro_salud === 'Enfermo') ? 'selected' : '' ?>>Enfermo</option>
                        <option value="Plagado" <?= ($filtro_salud === 'Plagado') ? 'selected' : '' ?>>Plagado</option>
                        <option value="En riesgo" <?= ($filtro_salud === 'En riesgo') ? 'selected' : '' ?>>En riesgo</option>
                    </select>
                </div>

                <div class="sm:col-span-1 flex gap-1">
                    <button type="submit" class="w-full btn-forest-primary text-xs py-2 px-2" title="Filtrar">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    </button>
                    <?php if (!empty($q) || !empty($filtro_zona) || !empty($filtro_salud)): ?>
                        <a href="<?= BASE_URL ?>admin/individuos/index.php" class="p-2 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 flex items-center justify-center" title="Limpiar">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabla de Individuos -->
        <div class="forest-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 uppercase font-semibold">
                        <tr>
                            <th class="p-4">Placa / Foto</th>
                            <th class="p-4">Nombre Común / Científico</th>
                            <th class="p-4">Zona</th>
                            <th class="p-4">DAP</th>
                            <th class="p-4">Altura</th>
                            <th class="p-4">Salud</th>
                            <th class="p-4">QR</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($individuos)): ?>
                            <tr>
                                <td colspan="8" class="p-12 text-center">
                                    <div class="max-w-sm mx-auto space-y-3">
                                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto border border-emerald-100">
                                            <i data-lucide="sprout" class="w-7 h-7"></i>
                                        </div>
                                        <h3 class="font-heading font-bold text-base text-gray-800">No se encontraron especímenes</h3>
                                        <p class="text-xs text-gray-500 leading-relaxed">
                                            No hay registros forestales que coincidan con los criterios seleccionados. Puedes registrar un nuevo individuo o restablecer los filtros.
                                        </p>
                                        <div class="flex justify-center gap-2 pt-2">
                                            <a href="<?= BASE_URL ?>admin/individuos/crear.php" class="btn-forest-primary text-xs min-h-[44px] py-2.5 px-4">
                                                <i data-lucide="plus-circle" class="w-4 h-4"></i> Registrar Espécimen
                                            </a>
                                            <?php if (!empty($q) || !empty($filtro_zona) || !empty($filtro_salud)): ?>
                                                <a href="<?= BASE_URL ?>admin/individuos/index.php" class="btn-forest-secondary text-xs min-h-[44px] py-2.5 px-4">
                                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Limpiar Filtros
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($individuos as $arbol): 
                                $foto = obtener_url_foto($arbol['foto_principal_url'] ?? null);
                                $qr_img = !empty($arbol['qr_url']) ? BASE_URL . $arbol['qr_url'] : BASE_URL . 'assets/qr/qr_' . $arbol['codigo_placa'] . '.svg';
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 shrink-0">
                                                <img src="<?= htmlspecialchars($foto) ?>" alt="Foto" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/img/tree-placeholder.svg';">
                                            </div>
                                            <span class="font-extrabold text-xs text-gray-900 bg-gray-100 px-2 py-1 rounded-md border border-gray-200 font-mono">
                                                <?= htmlspecialchars($arbol['codigo_placa']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-4 font-semibold text-gray-900">
                                        <div><?= htmlspecialchars($arbol['nombre_comun']) ?></div>
                                        <span class="text-[11px] text-gray-400 font-normal italic"><?= htmlspecialchars($arbol['nombre_cientifico']) ?></span>
                                    </td>
                                    <td class="p-4"><?= htmlspecialchars($arbol['nombre_zona']) ?></td>
                                    <td class="p-4 font-bold text-gray-800"><?= formatear_numero($arbol['dap_cm'], 1, 'cm') ?></td>
                                    <td class="p-4 font-bold text-gray-800"><?= formatear_numero($arbol['altura_total_m'], 1, 'm') ?></td>
                                    <td class="p-4">
                                        <?= obtener_badge_estado($arbol['estado_salud'] ?? 'Sano') ?>
                                    </td>
                                    <td class="p-4">
                                        <a href="<?= htmlspecialchars($qr_img) ?>" target="_blank" 
                                           class="w-12 h-12 min-w-[48px] min-h-[48px] bg-emerald-50 border border-emerald-300 p-1.5 rounded-xl inline-flex items-center justify-center hover:scale-105 hover:bg-emerald-100 transition-all shadow-xs" 
                                           title="Ver Código QR">
                                            <img src="<?= htmlspecialchars($qr_img) ?>" alt="QR" class="w-full h-full object-contain">
                                        </a>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="<?= BASE_URL ?>admin/individuos/detalle.php?id=<?= $arbol['id_individuo'] ?>" 
                                               class="w-12 h-12 min-w-[48px] min-h-[48px] rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 inline-flex items-center justify-center transition-colors shadow-xs" 
                                               title="Detalle Técnico">
                                                <i data-lucide="eye" class="w-5 h-5"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>admin/individuos/editar.php?id=<?= $arbol['id_individuo'] ?>" 
                                               class="w-12 h-12 min-w-[48px] min-h-[48px] rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 inline-flex items-center justify-center transition-colors shadow-xs" 
                                               title="Editar Espécimen">
                                                <i data-lucide="edit-3" class="w-5 h-5"></i>
                                            </a>
                                            <form method="POST" action="<?= BASE_URL ?>admin/individuos/eliminar.php" class="inline m-0 p-0">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $arbol['id_individuo'] ?>">
                                                <button type="submit" data-confirm="¿Está seguro de dar de baja lógica a este espécimen forestal?"
                                                        class="w-12 h-12 min-w-[48px] min-h-[48px] rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 inline-flex items-center justify-center transition-colors shadow-xs" 
                                                        title="Dar de baja">
                                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
