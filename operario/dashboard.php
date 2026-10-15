<?php
/**
 * ForestID / ForestSRD - Panel de Control Operativo en Campo (OperarioEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_check.php';

requerir_rol(['OperarioEmincut', 'AdminEmincut']);

$pdo = getDBConnection();
$usuario_id = $_SESSION['usuario']['id_usuario'];

// Estadísticas del operario
$mis_actividades_stmt = $pdo->prepare("SELECT COUNT(*) FROM `actividades_mantenimiento` WHERE realizado_por = :u");
$mis_actividades_stmt->execute([':u' => $usuario_id]);
$total_mis_actividades = (int)$mis_actividades_stmt->fetchColumn();

// Individuos pendientes de revisión en la zona
$stmtPendientes = $pdo->query("
    SELECT i.id_individuo, i.codigo_placa, i.nombre_comun, z.nombre_zona, e.estado, e.fecha_evaluacion
    FROM `individuos_forestales` i
    INNER JOIN `zonas` z ON i.id_zona = z.id_zona
    LEFT JOIN `estados_fitosanitarios` e ON i.id_estado_fitosanitario_actual = e.id_estado
    WHERE i.estado_registro = 1 AND (e.estado IN ('En observación', 'Enfermo', 'Plagado', 'En riesgo') OR e.fecha_evaluacion < DATE_SUB(NOW(), INTERVAL 90 DAY))
    ORDER BY e.fecha_evaluacion ASC
    LIMIT 6
");
$pendientes = $stmtPendientes->fetchAll();

// Mis últimos mantenimientos
$stmtHist = $pdo->prepare("
    SELECT a.*, i.codigo_placa, i.nombre_comun
    FROM `actividades_mantenimiento` a
    INNER JOIN `individuos_forestales` i ON a.id_individuo = i.id_individuo
    WHERE a.realizado_por = :u
    ORDER BY a.fecha_actividad DESC
    LIMIT 5
");
$stmtHist->execute([':u' => $usuario_id]);
$ultimos_mantenimientos = $stmtHist->fetchAll();

$titulo_pagina = 'Dashboard Operario — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../includes/sidebar_operario.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-teal-700 uppercase tracking-widest">Trabajo de Campo • Emincut</span>
                <h1 class="font-heading font-extrabold text-2xl sm:text-3xl text-gray-900">
                    Panel Técnico Operario
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Bienvenido(a), <?= htmlspecialchars($_SESSION['usuario']['nombres']) ?>. Gestione inspecciones y actividades en campo.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>operario/escanear.php" class="btn-forest-primary text-xs py-2.5 px-4 shadow-md bg-emerald-700 hover:bg-emerald-800">
                    <i data-lucide="camera" class="w-4 h-4 animate-pulse"></i> Escanear Placa QR
                </a>
            </div>
        </div>

        <!-- Accesos Rápidos de Campo (Mobile-First) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="<?= BASE_URL ?>operario/escanear.php" class="forest-card p-6 border-l-4 border-l-emerald-600 hover:bg-emerald-50/50 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="camera" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="block text-base font-bold text-gray-900 font-heading">Escanear Placa QR</span>
                    <span class="text-xs text-gray-500">Abrir escáner con cámara</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>operario/registrar_individuo.php" class="forest-card p-6 border-l-4 border-l-teal-600 hover:bg-teal-50/50 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="plus-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="block text-base font-bold text-gray-900 font-heading">Alta Rápida en Campo</span>
                    <span class="text-xs text-gray-500">Registrar árbol nuevo con GPS</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>operario/actividades/crear.php" class="forest-card p-6 border-l-4 border-l-blue-600 hover:bg-blue-50/50 transition-all flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-800 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="wrench" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="block text-base font-bold text-gray-900 font-heading">Nueva Intervención</span>
                    <span class="text-xs text-gray-500">Podas, abonos y tratamientos</span>
                </div>
            </a>
        </div>

        <!-- Tareas Prioritarias de Inspección -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <div class="lg:col-span-7 forest-card p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="font-heading font-bold text-base text-gray-900 flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                        Especímenes Prioritarios para Inspección
                    </h3>
                    <span class="text-xs text-gray-500">El Bagre</span>
                </div>

                <div class="space-y-3">
                    <?php if (empty($pendientes)): ?>
                        <p class="text-xs text-gray-400 italic">No hay alertas de inspección pendientes.</p>
                    <?php else: ?>
                        <?php foreach ($pendientes as $p): ?>
                            <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs text-gray-900"><?= htmlspecialchars($p['codigo_placa']) ?></span>
                                        <?= obtener_badge_estado($p['estado'] ?? 'Sano') ?>
                                    </div>
                                    <h4 class="font-semibold text-xs text-gray-800 mt-0.5"><?= htmlspecialchars($p['nombre_comun']) ?></h4>
                                    <span class="block text-[11px] text-gray-500">📍 <?= htmlspecialchars($p['nombre_zona']) ?></span>
                                </div>
                                <a href="<?= BASE_URL ?>operario/individuos/detalle.php?id=<?= $p['id_individuo'] ?>" class="btn-forest-secondary text-xs py-1.5 px-3">
                                    Inspeccionar
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mantenimientos Registrados por el Operario -->
            <div class="lg:col-span-5 forest-card p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="font-heading font-bold text-base text-gray-900 flex items-center gap-2">
                        <i data-lucide="history" class="w-5 h-5 text-teal-600"></i>
                        Mis Intervenciones Recientes
                    </h3>
                    <span class="text-xs text-teal-800 font-bold bg-teal-100 px-2 py-0.5 rounded-full">
                        <?= $total_mis_actividades ?> total
                    </span>
                </div>

                <div class="space-y-3">
                    <?php if (empty($ultimos_mantenimientos)): ?>
                        <p class="text-xs text-gray-400 italic">No has registrado intervenciones aún.</p>
                    <?php else: ?>
                        <?php foreach ($ultimos_mantenimientos as $m): ?>
                            <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-emerald-800"><?= htmlspecialchars($m['tipo_actividad']) ?></span>
                                    <span class="text-[10px] text-gray-400"><?= formatear_fecha($m['fecha_actividad']) ?></span>
                                </div>
                                <p class="text-xs text-gray-700"><?= htmlspecialchars($m['descripcion']) ?></p>
                                <span class="block text-[10px] text-gray-500 font-mono"><?= htmlspecialchars($m['codigo_placa']) ?> (<?= htmlspecialchars($m['nombre_comun']) ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
