<?php
/**
 * ForestID / ForestSRD - Auditoría y Registro de Logs (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();

$filtro_modulo = sanear($_GET['modulo'] ?? '');
$where = "1=1";
$params = [];

if (!empty($filtro_modulo)) {
    $where = "l.modulo_afectado = :mod";
    $params[':mod'] = $filtro_modulo;
}

$stmt = $pdo->prepare("
    SELECT l.*, u.nombres, u.apellidos, u.correo
    FROM `logs_sistema` l
    LEFT JOIN `usuarios` u ON l.id_usuario = u.id_usuario
    WHERE {$where}
    ORDER BY l.fecha_registro DESC
    LIMIT 100
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$modulos = $pdo->query("SELECT DISTINCT modulo_afectado FROM `logs_sistema` ORDER BY modulo_afectado ASC")->fetchAll(PDO::FETCH_COLUMN);

$titulo_pagina = 'Auditoría y Logs — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Seguridad y Trazabilidad</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Bitácora de Auditoría y Logs</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Historial cronológico de eventos, accesos y modificaciones registradas en el sistema.
                </p>
            </div>
        </div>

        <!-- Filtro por Módulo -->
        <div class="forest-card p-4">
            <form method="GET" action="index.php" class="flex flex-col sm:flex-row items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Módulo del Sistema</label>
                    <select name="modulo" class="w-full py-2 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                        <option value="">Todos los módulos</option>
                        <?php foreach ($modulos as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= ($filtro_modulo === $m) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-forest-primary text-xs py-2 px-4">Filtrar</button>
                <?php if (!empty($filtro_modulo)): ?>
                    <a href="<?= BASE_URL ?>admin/logs/index.php" class="btn-forest-secondary text-xs py-2 px-3">Ver Todos</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabla de Logs -->
        <div class="forest-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 uppercase font-semibold">
                        <tr>
                            <th class="p-4">Fecha y Hora</th>
                            <th class="p-4">Usuario</th>
                            <th class="p-4">Acción</th>
                            <th class="p-4">Módulo</th>
                            <th class="p-4">Detalle JSON</th>
                            <th class="p-4">IP Origen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-mono text-[11px]">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-400 italic font-sans">No hay registros de auditoría para mostrar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-4 whitespace-nowrap text-gray-500 font-sans">
                                        <?= formatear_fecha($log['fecha_registro'], true) ?>
                                    </td>
                                    <td class="p-4 font-sans font-medium text-gray-900">
                                        <?= !empty($log['nombres']) ? htmlspecialchars($log['nombres'] . ' ' . $log['apellidos']) : '<span class="text-gray-400 italic">Anónimo / Sistema</span>' ?>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 font-bold">
                                            <?= htmlspecialchars($log['accion']) ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-gray-700 font-sans">
                                        <?= htmlspecialchars($log['modulo_afectado']) ?>
                                    </td>
                                    <td class="p-4 text-gray-600 max-w-xs truncate" title="<?= htmlspecialchars($log['detalle'] ?? '') ?>">
                                        <?= htmlspecialchars($log['detalle'] ?? '{}') ?>
                                    </td>
                                    <td class="p-4 text-gray-400">
                                        <?= htmlspecialchars($log['ip_origen'] ?? '127.0.0.1') ?>
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
