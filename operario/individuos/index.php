<?php
/**
 * ForestID / ForestSRD - Listado de Individuos Forestales (OperarioEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['OperarioEmincut', 'AdminEmincut']);

$pdo = getDBConnection();
$q = sanear($_GET['q'] ?? '');
$filtro_zona = sanear($_GET['zona'] ?? '');

$zonas = $pdo->query("SELECT id_zona, nombre_zona FROM `zonas` WHERE estado = 1 ORDER BY nombre_zona ASC")->fetchAll();

$where = ["i.estado_registro = 1"];
$params = [];

if (!empty($q)) {
    $where[] = "(i.codigo_placa LIKE :q1 OR i.nombre_comun LIKE :q2 OR i.nombre_cientifico LIKE :q3)";
    $params[':q1'] = $params[':q2'] = $params[':q3'] = "%{$q}%";
}
if (!empty($filtro_zona)) {
    $where[] = "i.id_zona = :zona";
    $params[':zona'] = $filtro_zona;
}

$where_sql = implode(' AND ', $where);
$stmt = $pdo->prepare("
    SELECT i.*, z.nombre_zona, e.estado AS estado_salud
    FROM `individuos_forestales` i
    INNER JOIN `zonas` z ON i.id_zona = z.id_zona
    LEFT JOIN `estados_fitosanitarios` e ON i.id_estado_fitosanitario_actual = e.id_estado
    WHERE {$where_sql}
    ORDER BY i.codigo_placa ASC
");
$stmt->execute($params);
$individuos = $stmt->fetchAll();

$titulo_pagina = 'Individuos Forestales — ' . APP_NAME;
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
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-teal-700 uppercase tracking-widest">Inspección en Campo</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Individuos Forestales</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Seleccione un espécimen para registrar podas, mantenimientos o actualizar su condición fitosanitaria.
                </p>
            </div>
            
            <a href="<?= BASE_URL ?>operario/registrar_individuo.php" class="btn-forest-primary text-xs py-2.5 px-4">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Alta Rápida
            </a>
        </div>

        <!-- Buscador -->
        <div class="forest-card p-4">
            <form method="GET" action="index.php" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por placa o especie..." class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-xl text-xs outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
                <select name="zona" class="py-2 px-3 border border-gray-300 rounded-xl text-xs outline-none">
                    <option value="">Todas las zonas</option>
                    <?php foreach ($zonas as $z): ?>
                        <option value="<?= $z['id_zona'] ?>" <?= ($filtro_zona === $z['id_zona']) ? 'selected' : '' ?>><?= htmlspecialchars($z['nombre_zona']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-forest-primary text-xs py-2 px-4">Buscar</button>
            </form>
        </div>

        <!-- Grilla de Individuos para Operario -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($individuos as $arbol): 
                $foto = !empty($arbol['foto_principal_url']) ? BASE_URL . $arbol['foto_principal_url'] : BASE_URL . 'assets/uploads/individuos/guayacan_rosado.svg';
            ?>
                <div class="forest-card overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="h-40 bg-gray-100 relative">
                            <img src="<?= htmlspecialchars($foto) ?>" alt="Foto" class="w-full h-full object-cover">
                            <div class="absolute top-2 left-2">
                                <span class="bg-white/95 px-2 py-0.5 rounded text-[11px] font-mono font-bold shadow-xs">
                                    <?= htmlspecialchars($arbol['codigo_placa']) ?>
                                </span>
                            </div>
                            <div class="absolute top-2 right-2">
                                <?= obtener_badge_estado($arbol['estado_salud'] ?? 'Sano') ?>
                            </div>
                        </div>

                        <div class="p-4 space-y-1.5">
                            <h3 class="font-heading font-bold text-sm text-gray-900 leading-tight">
                                <?= htmlspecialchars($arbol['nombre_comun']) ?>
                            </h3>
                            <p class="text-xs text-gray-500 italic"><?= htmlspecialchars($arbol['nombre_cientifico']) ?></p>
                            <span class="block text-[11px] text-gray-500">📍 <?= htmlspecialchars($arbol['nombre_zona']) ?></span>
                        </div>
                    </div>

                    <div class="p-4 pt-0">
                        <a href="<?= BASE_URL ?>operario/individuos/detalle.php?id=<?= $arbol['id_individuo'] ?>" class="btn-forest-primary text-xs py-2 w-full justify-center">
                            <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i> Gestionar en Campo
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
