<?php
/**
 * ForestID / ForestSRD - Gestión de Instituciones y Clientes (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT i.*, 
           COUNT(DISTINCT z.id_zona) AS total_zonas,
           COUNT(DISTINCT ind.id_individuo) AS total_individuos
    FROM `instituciones` i
    LEFT JOIN `zonas` z ON i.id_institucion = z.id_institucion
    LEFT JOIN `individuos_forestales` ind ON i.id_institucion = ind.id_institucion AND ind.estado_registro = 1
    GROUP BY i.id_institucion
    ORDER BY i.fecha_registro DESC
");
$instituciones = $stmt->fetchAll();

$titulo_pagina = 'Instituciones y Clientes — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Estructura Organizacional</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Instituciones y Clientes</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Entidades que administran zonas territoriales y arborización bajo la plataforma ForestID.
                </p>
            </div>
            <a href="<?= BASE_URL ?>admin/instituciones/crear.php" class="btn-forest-primary text-xs py-2.5 px-4 shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Nueva Institución
            </a>
        </div>

        <div class="forest-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 uppercase font-semibold">
                        <tr>
                            <th class="p-4">Institución</th>
                            <th class="p-4">NIT</th>
                            <th class="p-4">Ubicación</th>
                            <th class="p-4">Contacto</th>
                            <th class="p-4">Zonas</th>
                            <th class="p-4">Árboles</th>
                            <th class="p-4">Estado</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($instituciones as $inst): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-4 font-semibold text-gray-900 flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">
                                        <i data-lucide="building-2" class="w-4 h-4"></i>
                                    </div>
                                    <span><?= htmlspecialchars($inst['nombre_institucion']) ?></span>
                                </td>
                                <td class="p-4"><?= htmlspecialchars($inst['nit']) ?></td>
                                <td class="p-4"><?= htmlspecialchars($inst['municipio']) ?>, <?= htmlspecialchars($inst['departamento']) ?></td>
                                <td class="p-4">
                                    <div><?= htmlspecialchars($inst['correo_contacto'] ?? 'N/A') ?></div>
                                    <span class="text-[11px] text-gray-400"><?= htmlspecialchars($inst['telefono_contacto'] ?? '') ?></span>
                                </td>
                                <td class="p-4 font-bold text-gray-800"><?= $inst['total_zonas'] ?></td>
                                <td class="p-4 font-bold text-emerald-700"><?= $inst['total_individuos'] ?></td>
                                <td class="p-4">
                                    <?php if ($inst['estado'] == 1): ?>
                                        <span class="text-emerald-700 font-semibold flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Activa</span>
                                    <?php else: ?>
                                        <span class="text-rose-700 font-semibold flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-right">
                                    <a href="<?= BASE_URL ?>admin/instituciones/editar.php?id=<?= $inst['id_institucion'] ?>" class="p-1.5 rounded-lg bg-gray-100 hover:bg-emerald-100 text-gray-600 hover:text-emerald-800 inline-flex" title="Editar">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
