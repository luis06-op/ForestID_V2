<?php
/**
 * ForestID / ForestSRD - Gestión de Zonas Territoriales (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT z.*, i.nombre_institucion,
           COUNT(ind.id_individuo) AS total_arboles
    FROM `zonas` z
    INNER JOIN `instituciones` i ON z.id_institucion = i.id_institucion
    LEFT JOIN `individuos_forestales` ind ON z.id_zona = ind.id_zona AND ind.estado_registro = 1
    GROUP BY z.id_zona
    ORDER BY z.fecha_creacion DESC
");
$zonas = $stmt->fetchAll();

$titulo_pagina = 'Zonas Territoriales — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Territorio y Cobertura</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Zonas Territoriales</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Sectores geográficos, parques y corredores ecológicos censados en El Bagre, Antioquia.
                </p>
            </div>
            <a href="<?= BASE_URL ?>admin/zonas/crear.php" class="btn-forest-primary text-xs py-2.5 px-4 shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Registrar Nueva Zona
            </a>
        </div>

        <div class="forest-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 uppercase font-semibold">
                        <tr>
                            <th class="p-4">Zona / Sector</th>
                            <th class="p-4">Institución</th>
                            <th class="p-4">Municipio</th>
                            <th class="p-4">Coordenadas Ref.</th>
                            <th class="p-4">Árboles Censados</th>
                            <th class="p-4">Estado</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($zonas)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-400 italic">No hay zonas territoriales registradas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($zonas as $z): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-4 font-semibold text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600"></i>
                                            <div>
                                                <span><?= htmlspecialchars($z['nombre_zona']) ?></span>
                                                <?php if (!empty($z['descripcion'])): ?>
                                                    <span class="block text-[11px] text-gray-400 font-normal truncate max-w-xs"><?= htmlspecialchars($z['descripcion']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4"><?= htmlspecialchars($z['nombre_institucion']) ?></td>
                                    <td class="p-4"><?= htmlspecialchars($z['municipio']) ?>, <?= htmlspecialchars($z['departamento']) ?></td>
                                    <td class="p-4 font-mono text-[11px] text-gray-500">
                                        <?= !empty($z['latitud_referencia']) ? "{$z['latitud_referencia']}, {$z['longitud_referencia']}" : 'Sin fijar' ?>
                                    </td>
                                    <td class="p-4 font-bold text-emerald-700"><?= $z['total_arboles'] ?></td>
                                    <td class="p-4">
                                        <?php if ($z['estado'] == 1): ?>
                                            <span class="text-emerald-700 font-semibold flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Activa</span>
                                        <?php else: ?>
                                            <span class="text-rose-700 font-semibold flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <a href="<?= BASE_URL ?>admin/zonas/editar.php?id=<?= $z['id_zona'] ?>" class="p-1.5 rounded-lg bg-gray-100 hover:bg-emerald-100 text-gray-600 hover:text-emerald-800 inline-flex" title="Editar">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
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
