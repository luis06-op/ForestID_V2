<?php
/**
 * ForestID / ForestSRD - Reportes, Estadísticas y Exportación CSV (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();

// Manejo de exportación a CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmtCsv = $pdo->query("
        SELECT i.codigo_placa, i.nombre_comun, i.nombre_cientifico, i.familia_botanica, i.tipo_individuo,
               z.nombre_zona, inst.nombre_institucion, i.latitud, i.longitud,
               i.dap_cm, i.altura_total_m, i.diametro_copa_m, i.fecha_siembra,
               e.estado AS estado_salud, i.fecha_registro
        FROM `individuos_forestales` i
        INNER JOIN `zonas` z ON i.id_zona = z.id_zona
        INNER JOIN `instituciones` inst ON i.id_institucion = inst.id_institucion
        LEFT JOIN `estados_fitosanitarios` e ON i.id_estado_fitosanitario_actual = e.id_estado
        WHERE i.estado_registro = 1
        ORDER BY i.codigo_placa ASC
    ");
    $filas = $stmtCsv->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Inventario_Forestal_Emincut_' . date('Y-m-d') . '.csv');

    $salida = fopen('php://output', 'w');
    // BOM UTF-8 para Excel
    fprintf($salida, chr(0xEF).chr(0xBB).chr(0xBF));

    // Cabecera CSV
    fputcsv($salida, [
        'Código Placa', 'Nombre Común', 'Nombre Científico', 'Familia Botánica', 'Tipo',
        'Zona', 'Institución', 'Latitud', 'Longitud', 'DAP (cm)', 'Altura Total (m)',
        'Diámetro Copa (m)', 'Fecha Siembra', 'Estado Fitosanitario', 'Fecha Registro'
    ]);

    foreach ($filas as $f) {
        fputcsv($salida, [
            $f['codigo_placa'],
            $f['nombre_comun'],
            $f['nombre_cientifico'],
            $f['familia_botanica'],
            $f['tipo_individuo'],
            $f['nombre_zona'],
            $f['nombre_institucion'],
            $f['latitud'],
            $f['longitud'],
            $f['dap_cm'],
            $f['altura_total_m'],
            $f['diametro_copa_m'],
            $f['fecha_siembra'],
            $f['estado_salud'] ?? 'Sano',
            $f['fecha_registro']
        ]);
    }
    fclose($salida);
    exit;
}

// Estadísticas para la vista
$promedios = $pdo->query("
    SELECT AVG(dap_cm) AS prom_dap, AVG(altura_total_m) AS prom_altura, AVG(diametro_copa_m) AS prom_copa, COUNT(*) AS total
    FROM `individuos_forestales` WHERE estado_registro = 1
")->fetch();

// Top 5 Familias Botánicas
$topFamilias = $pdo->query("
    SELECT familia_botanica, COUNT(*) AS total
    FROM `individuos_forestales` WHERE estado_registro = 1
    GROUP BY familia_botanica
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

// Actividades por Tipo
$actividadesPorTipo = $pdo->query("
    SELECT tipo_actividad, COUNT(*) AS total
    FROM `actividades_mantenimiento`
    GROUP BY tipo_actividad
    ORDER BY total DESC
")->fetchAll();

$titulo_pagina = 'Reportes y Métricas — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Inteligencia y Dasometría</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Reportes y Estadísticas Forestales</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Análisis cuantitativo de variables dasométricas, distribución botánica y operaciones de Emincut en El Bagre.
                </p>
            </div>

            <a href="<?= BASE_URL ?>admin/reportes/index.php?export=csv" class="btn-forest-primary text-xs py-2.5 px-4 shadow-sm">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Exportar Datos a CSV (Excel)
            </a>
        </div>

        <!-- Promedios Dasométricos -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="forest-card p-5 border-l-4 border-emerald-600">
                <span class="text-xs font-bold text-gray-500 uppercase">DAP Promedio</span>
                <span class="block text-2xl font-extrabold text-gray-900 font-heading mt-1">
                    <?= formatear_numero($promedios['prom_dap'], 1, 'cm') ?>
                </span>
                <span class="text-[11px] text-emerald-700 font-medium">Diámetro a 1.30 m</span>
            </div>

            <div class="forest-card p-5 border-l-4 border-teal-600">
                <span class="text-xs font-bold text-gray-500 uppercase">Altura Total Promedio</span>
                <span class="block text-2xl font-extrabold text-gray-900 font-heading mt-1">
                    <?= formatear_numero($promedios['prom_altura'], 1, 'm') ?>
                </span>
                <span class="text-[11px] text-teal-700 font-medium">Desarrollo vertical</span>
            </div>

            <div class="forest-card p-5 border-l-4 border-amber-600">
                <span class="text-xs font-bold text-gray-500 uppercase">Diámetro de Copa Promedio</span>
                <span class="block text-2xl font-extrabold text-gray-900 font-heading mt-1">
                    <?= formatear_numero($promedios['prom_copa'], 1, 'm') ?>
                </span>
                <span class="text-[11px] text-amber-700 font-medium">Área de sombra e insolación</span>
            </div>
        </div>

        <!-- Tablas de Distribución -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Familias Botánicas Dominantes -->
            <div class="forest-card p-6 space-y-4">
                <h3 class="font-heading font-bold text-base text-gray-900 flex items-center gap-2 border-b border-gray-100 pb-3">
                    <i data-lucide="trees" class="w-4 h-4 text-emerald-600"></i>
                    Familias Botánicas Más Frecuentes
                </h3>
                <div class="space-y-3">
                    <?php foreach ($topFamilias as $fam): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <span class="font-semibold text-xs text-gray-800"><?= htmlspecialchars($fam['familia_botanica']) ?></span>
                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-md text-xs font-bold"><?= $fam['total'] ?> individuos</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Intervenciones Realizadas -->
            <div class="forest-card p-6 space-y-4">
                <h3 class="font-heading font-bold text-base text-gray-900 flex items-center gap-2 border-b border-gray-100 pb-3">
                    <i data-lucide="wrench" class="w-4 h-4 text-emerald-600"></i>
                    Histórico de Actividades por Tipo
                </h3>
                <div class="space-y-3">
                    <?php foreach ($actividadesPorTipo as $act): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <span class="font-semibold text-xs text-gray-800"><?= htmlspecialchars($act['tipo_actividad']) ?></span>
                            <span class="px-2.5 py-1 bg-teal-100 text-teal-800 rounded-md text-xs font-bold"><?= $act['total'] ?> registros</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
