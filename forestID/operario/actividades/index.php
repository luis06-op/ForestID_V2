<?php
/**
 * ForestID / ForestSRD - Listado de Actividades de Mantenimiento (OperarioEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['OperarioEmincut', 'AdminEmincut']);

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT a.*, i.codigo_placa, i.nombre_comun, z.nombre_zona, u.nombres, u.apellidos
    FROM `actividades_mantenimiento` a
    INNER JOIN `individuos_forestales` i ON a.id_individuo = i.id_individuo
    INNER JOIN `zonas` z ON i.id_zona = z.id_zona
    LEFT JOIN `usuarios` u ON a.realizado_por = u.id_usuario
    ORDER BY a.fecha_actividad DESC
");
$actividades = $stmt->fetchAll();

$titulo_pagina = 'Registro de Actividades — ' . APP_NAME;
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
                <span class="text-xs font-bold text-teal-700 uppercase tracking-widest">Trazabilidad Operativa</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Historial de Mantenimientos</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Registro de todas las intervenciones silviculturales y labores de conservación en El Bagre.
                </p>
            </div>
            
            <a href="<?= BASE_URL ?>operario/actividades/crear.php" class="btn-forest-primary text-xs py-2.5 px-4 shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Registrar Intervención
            </a>
        </div>

        <div class="forest-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 uppercase font-semibold">
                        <tr>
                            <th class="p-4">Fecha</th>
                            <th class="p-4">Placa / Espécimen</th>
                            <th class="p-4">Zona</th>
                            <th class="p-4">Tipo de Labor</th>
                            <th class="p-4">Descripción</th>
                            <th class="p-4">Operario / Responsable</th>
                            <th class="p-4 text-right">Ficha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($actividades)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-400 italic">No hay intervenciones registradas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($actividades as $act): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-4 whitespace-nowrap text-gray-500 font-medium">
                                        <?= formatear_fecha($act['fecha_actividad'], true) ?>
                                    </td>
                                    <td class="p-4 font-semibold text-gray-900">
                                        <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-[11px] font-bold"><?= htmlspecialchars($act['codigo_placa']) ?></span>
                                        <div class="text-xs text-gray-700 mt-0.5"><?= htmlspecialchars($act['nombre_comun']) ?></div>
                                    </td>
                                    <td class="p-4"><?= htmlspecialchars($act['nombre_zona']) ?></td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                            <?= htmlspecialchars($act['tipo_actividad']) ?>
                                        </span>
                                    </td>
                                    <td class="p-4 max-w-xs truncate text-gray-700" title="<?= htmlspecialchars($act['descripcion']) ?>">
                                        <?= htmlspecialchars($act['descripcion']) ?>
                                    </td>
                                    <td class="p-4">
                                        <?= !empty($act['nombres']) ? htmlspecialchars($act['nombres'] . ' ' . $act['apellidos']) : '<span class="text-amber-700 font-medium italic">Reporte Ciudadano</span>' ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <a href="<?= BASE_URL ?>operario/individuos/detalle.php?id=<?= $act['id_individuo'] ?>" class="p-1.5 bg-gray-100 hover:bg-emerald-100 text-gray-600 hover:text-emerald-800 rounded-lg inline-flex" title="Ver Árbol">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
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
