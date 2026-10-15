<?php
/**
 * ForestID / ForestSRD - Buscador y Catálogo Público del Inventario Forestal
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = getDBConnection();

// Filtros
$q            = sanear($_GET['q'] ?? '');
$filtro_zona  = sanear($_GET['zona'] ?? '');
$filtro_tipo  = sanear($_GET['tipo'] ?? '');
$filtro_salud = sanear($_GET['salud'] ?? '');

// Cargar listas para selects
$zonas = $pdo->query("SELECT id_zona, nombre_zona FROM `zonas` WHERE estado = 1 ORDER BY nombre_zona ASC")->fetchAll();

// Construcción de consulta dinámica con prepared statements
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

if (!empty($filtro_tipo)) {
    $where[] = "i.tipo_individuo = :tipo";
    $params[':tipo'] = $filtro_tipo;
}

if (!empty($filtro_salud)) {
    $where[] = "e.estado = :salud";
    $params[':salud'] = $filtro_salud;
}

$where_sql = implode(' AND ', $where);

$sql = "
    SELECT i.*, z.nombre_zona, e.estado AS estado_salud
    FROM `individuos_forestales` i
    INNER JOIN `zonas` z ON i.id_zona = z.id_zona
    LEFT JOIN `estados_fitosanitarios` e ON i.id_estado_fitosanitario_actual = e.id_estado
    WHERE {$where_sql}
    ORDER BY i.nombre_comun ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$individuos = $stmt->fetchAll();

$titulo_pagina = 'Catálogo e Inventario Forestal — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Encabezado de Sección -->
    <div class="mb-8 space-y-2">
        <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Inventario Abierto</span>
        <h1 class="font-heading font-extrabold text-3xl sm:text-4xl text-gray-900">
            Explorador del Patrimonio Forestal
        </h1>
        <p class="text-sm text-gray-500 max-w-2xl">
            Consulta el censo de árboles, palmas y plantas ornamentales del municipio de El Bagre, Antioquia gestionados por Emincut.
        </p>
    </div>

    <!-- Barra de Filtros -->
    <div class="forest-card p-5 mb-8">
        <form method="GET" action="buscar.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            
            <!-- Búsqueda textual -->
            <div class="lg:col-span-4">
                <label for="q" class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                    Buscar por Nombre o Placa
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Guayacán, Ceiba, EBG-..."
                           class="w-full pl-9 pr-3 py-2 bg-white border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                </div>
            </div>

            <!-- Filtro Zona -->
            <div class="lg:col-span-3">
                <label for="zona" class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                    Zona / Sector
                </label>
                <select name="zona" id="zona" class="w-full py-2 px-3 bg-white border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    <option value="">Todas las zonas</option>
                    <?php foreach ($zonas as $z): ?>
                        <option value="<?= $z['id_zona'] ?>" <?= ($filtro_zona === $z['id_zona']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($z['nombre_zona']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro Tipo -->
            <div class="lg:col-span-2">
                <label for="tipo" class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                    Tipo de Planta
                </label>
                <select name="tipo" id="tipo" class="w-full py-2 px-3 bg-white border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    <option value="">Todos los tipos</option>
                    <option value="Árbol" <?= ($filtro_tipo === 'Árbol') ? 'selected' : '' ?>>Árbol</option>
                    <option value="Palma" <?= ($filtro_tipo === 'Palma') ? 'selected' : '' ?>>Palma</option>
                    <option value="Arbusto" <?= ($filtro_tipo === 'Arbusto') ? 'selected' : '' ?>>Arbusto</option>
                    <option value="Planta ornamental" <?= ($filtro_tipo === 'Planta ornamental') ? 'selected' : '' ?>>Planta ornamental</option>
                </select>
            </div>

            <!-- Filtro Estado Fitosanitario -->
            <div class="lg:col-span-2">
                <label for="salud" class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                    Estado de Salud
                </label>
                <select name="salud" id="salud" class="w-full py-2 px-3 bg-white border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                    <option value="">Todos los estados</option>
                    <option value="Sano" <?= ($filtro_salud === 'Sano') ? 'selected' : '' ?>>Sano</option>
                    <option value="En observación" <?= ($filtro_salud === 'En observación') ? 'selected' : '' ?>>En observación</option>
                    <option value="Enfermo" <?= ($filtro_salud === 'Enfermo') ? 'selected' : '' ?>>Enfermo</option>
                    <option value="Plagado" <?= ($filtro_salud === 'Plagado') ? 'selected' : '' ?>>Plagado</option>
                    <option value="En riesgo" <?= ($filtro_salud === 'En riesgo') ? 'selected' : '' ?>>En riesgo</option>
                </select>
            </div>

            <!-- Botones Filtrar y Limpiar -->
            <div class="lg:col-span-1 flex gap-1">
                <button type="submit" class="w-full btn-forest-primary text-xs py-2 px-2" title="Aplicar Filtros">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                </button>
                <a href="<?= BASE_URL ?>publico/buscar.php" class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs flex items-center justify-center" title="Limpiar Filtros">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- Contador de Resultados -->
    <div class="flex items-center justify-between mb-6 text-xs text-gray-500 font-medium">
        <span>Se encontraron <strong><?= count($individuos) ?></strong> individuos registrados</span>
        <?php if (!empty($q) || !empty($filtro_zona) || !empty($filtro_tipo) || !empty($filtro_salud)): ?>
            <span class="text-emerald-700 font-semibold flex items-center gap-1">
                <i data-lucide="check" class="w-3.5 h-3.5"></i> Filtros aplicados
            </span>
        <?php endif; ?>
    </div>

    <!-- Grilla de Resultados -->
    <?php if (empty($individuos)): ?>
        <div class="forest-card p-12 text-center max-w-md mx-auto space-y-4">
            <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto">
                <i data-lucide="search-x" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading font-bold text-lg text-gray-800">No se encontraron resultados</h3>
            <p class="text-xs text-gray-500">Prueba ajustando los términos de búsqueda o limpiando los filtros seleccionados.</p>
            <a href="<?= BASE_URL ?>publico/buscar.php" class="btn-forest-secondary text-xs inline-flex">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Restablecer Búsqueda
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($individuos as $arbol): 
                $foto = !empty($arbol['foto_principal_url']) ? BASE_URL . $arbol['foto_principal_url'] : BASE_URL . 'assets/uploads/individuos/guayacan_rosado.svg';
            ?>
                <div class="forest-card overflow-hidden flex flex-col group">
                    <div class="h-44 overflow-hidden relative bg-gray-100">
                        <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($arbol['nombre_comun']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute top-2.5 left-2.5">
                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-white/95 text-gray-900 shadow-sm backdrop-blur">
                                <?= htmlspecialchars($arbol['codigo_placa']) ?>
                            </span>
                        </div>
                        <div class="absolute top-2.5 right-2.5">
                            <?= obtener_badge_estado($arbol['estado_salud'] ?? 'Sano') ?>
                        </div>
                    </div>

                    <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                        <div>
                            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">
                                <?= htmlspecialchars($arbol['familia_botanica']) ?> • <?= htmlspecialchars($arbol['tipo_individuo']) ?>
                            </span>
                            <h3 class="font-heading font-bold text-base text-gray-900 mt-0.5">
                                <?= htmlspecialchars($arbol['nombre_comun']) ?>
                            </h3>
                            <p class="text-xs text-gray-500 italic">
                                <?= htmlspecialchars($arbol['nombre_cientifico']) ?>
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-2 py-2 border-y border-gray-100 text-center text-xs">
                            <div>
                                <span class="block text-gray-400 text-[10px]">DAP</span>
                                <span class="font-bold text-gray-800"><?= formatear_numero($arbol['dap_cm'], 1, 'cm') ?></span>
                            </div>
                            <div>
                                <span class="block text-gray-400 text-[10px]">Altura</span>
                                <span class="font-bold text-gray-800"><?= formatear_numero($arbol['altura_total_m'], 1, 'm') ?></span>
                            </div>
                            <div>
                                <span class="block text-gray-400 text-[10px]">Copa</span>
                                <span class="font-bold text-gray-800"><?= formatear_numero($arbol['diametro_copa_m'], 1, 'm') ?></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <span class="text-xs text-gray-500 flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-600"></i> <?= htmlspecialchars($arbol['nombre_zona']) ?>
                            </span>
                            <a href="<?= BASE_URL ?>publico/ficha.php?codigo=<?= urlencode($arbol['codigo_placa']) ?>" class="btn-forest-primary text-xs py-1.5 px-3">
                                Ficha QR <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
