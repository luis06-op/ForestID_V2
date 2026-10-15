<?php
/**
 * ForestID / ForestSRD - Panel de Control Administrador (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Protección de acceso: solo Administradores
requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();

// Cargar métricas principales
$total_arboles = (int)$pdo->query("SELECT COUNT(*) FROM `individuos_forestales` WHERE `estado_registro` = 1")->fetchColumn();
$total_zonas = (int)$pdo->query("SELECT COUNT(*) FROM `zonas` WHERE `estado` = 1")->fetchColumn();
$total_usuarios = (int)$pdo->query("SELECT COUNT(*) FROM `usuarios` WHERE `estado` = 1")->fetchColumn();
$total_actividades = (int)$pdo->query("SELECT COUNT(*) FROM `actividades_mantenimiento`")->fetchColumn();

// Distribución por estado fitosanitario
$stmtSalud = $pdo->query("
    SELECT e.estado, COUNT(i.id_individuo) AS cantidad
    FROM `individuos_forestales` i
    INNER JOIN `estados_fitosanitarios` e ON i.id_estado_fitosanitario_actual = e.id_estado
    WHERE i.estado_registro = 1
    GROUP BY e.estado
");
$distribucion_salud = $stmtSalud->fetchAll();

// Individuos por zona
$stmtZonas = $pdo->query("
    SELECT z.nombre_zona, COUNT(i.id_individuo) AS total_arboles
    FROM `zonas` z
    LEFT JOIN `individuos_forestales` i ON z.id_zona = i.id_zona AND i.estado_registro = 1
    WHERE z.estado = 1
    GROUP BY z.id_zona, z.nombre_zona
    ORDER BY total_arboles DESC
");
$individuos_por_zona = $stmtZonas->fetchAll();

// Árboles en observación o riesgo crítico
$stmtAlertas = $pdo->query("
    SELECT i.id_individuo, i.codigo_placa, i.nombre_comun, i.nombre_cientifico, z.nombre_zona, e.estado, e.diagnostico, e.fecha_evaluacion
    FROM `individuos_forestales` i
    INNER JOIN `zonas` z ON i.id_zona = z.id_zona
    INNER JOIN `estados_fitosanitarios` e ON i.id_estado_fitosanitario_actual = e.id_estado
    WHERE i.estado_registro = 1 AND e.estado IN ('En observación', 'Enfermo', 'Plagado', 'En riesgo')
    ORDER BY e.fecha_evaluacion DESC
    LIMIT 5
");
$alertas_fitosanitarias = $stmtAlertas->fetchAll();

// Últimas actividades registradas
$stmtUltAct = $pdo->query("
    SELECT a.*, i.codigo_placa, i.nombre_comun, u.nombres, u.apellidos
    FROM `actividades_mantenimiento` a
    INNER JOIN `individuos_forestales` i ON a.id_individuo = i.id_individuo
    LEFT JOIN `usuarios` u ON a.realizado_por = u.id_usuario
    ORDER BY a.fecha_actividad DESC
    LIMIT 6
");
$ultimas_actividades = $stmtUltAct->fetchAll();

$titulo_pagina = 'Dashboard Administrador — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <!-- Sidebar Admin -->
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <!-- Contenido Principal -->
    <main class="flex-1 p-6 sm:p-8 space-y-8 overflow-y-auto">
        
        <!-- Cabecera de Bienvenida y Acciones Rápidas -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Emincut • El Bagre, Antioquia</span>
                <h1 class="font-heading font-extrabold text-2xl sm:text-3xl text-gray-900">
                    Panel de Administración General
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                    Resumen del inventario dendrológico, salud fitosanitaria y registro de intervenciones en campo.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>admin/individuos/crear.php" class="btn-forest-primary text-xs py-2.5 px-4 shadow-sm">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i> Nuevo Individuo
                </a>
                <a href="<?= BASE_URL ?>admin/qr/imprimir.php" class="btn-forest-secondary text-xs py-2.5 px-4">
                    <i data-lucide="printer" class="w-4 h-4"></i> Imprimir Placas
                </a>
            </div>
        </div>

        <!-- Tarjetas de Métricas Principales (KPIs) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="forest-card p-5 border-l-4 border-l-emerald-600">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-gray-500 uppercase">Individuos Forestales</span>
                    <i data-lucide="trees" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <span class="text-3xl font-extrabold font-heading text-gray-900"><?= $total_arboles ?></span>
                <span class="block text-[11px] text-emerald-700 font-medium mt-1">Censo activo</span>
            </div>

            <div class="forest-card p-5 border-l-4 border-l-teal-600">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-gray-500 uppercase">Zonas Territoriales</span>
                    <i data-lucide="map-pin" class="w-5 h-5 text-teal-600"></i>
                </div>
                <span class="text-3xl font-extrabold font-heading text-gray-900"><?= $total_zonas ?></span>
                <span class="block text-[11px] text-teal-700 font-medium mt-1">Sectores en El Bagre</span>
            </div>

            <div class="forest-card p-5 border-l-4 border-l-blue-600">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-gray-500 uppercase">Intervenciones</span>
                    <i data-lucide="clipboard-check" class="w-5 h-5 text-blue-600"></i>
                </div>
                <span class="text-3xl font-extrabold font-heading text-gray-900"><?= $total_actividades ?></span>
                <span class="block text-[11px] text-blue-700 font-medium mt-1">Mantenimientos y podas</span>
            </div>

            <div class="forest-card p-5 border-l-4 border-l-purple-600">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-gray-500 uppercase">Personal y Usuarios</span>
                    <i data-lucide="users" class="w-5 h-5 text-purple-600"></i>
                </div>
                <span class="text-3xl font-extrabold font-heading text-gray-900"><?= $total_usuarios ?></span>
                <span class="block text-[11px] text-purple-700 font-medium mt-1">Cuentas activas</span>
            </div>
        </div>

        <!-- Gráficos de Estadísticas con Chart.js -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Gráfico 1: Estado Fitosanitario (Doughnut) -->
            <div class="lg:col-span-5 forest-card p-6 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-heading font-bold text-base text-gray-900">
                        Distribución Fitosanitaria
                    </h3>
                    <span class="text-xs text-gray-400">Salud actual</span>
                </div>
                <div class="relative h-64 flex items-center justify-center">
                    <canvas id="chart-fitosanitario"></canvas>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-100 grid grid-cols-2 gap-2 text-xs">
                    <?php foreach ($distribucion_salud as $ds): ?>
                        <div class="flex items-center justify-between text-gray-600">
                            <span><?= htmlspecialchars($ds['estado']) ?>:</span>
                            <span class="font-bold text-gray-900"><?= $ds['cantidad'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Gráfico 2: Árboles por Zona (Bar Chart) -->
            <div class="lg:col-span-7 forest-card p-6 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-heading font-bold text-base text-gray-900">
                        Cobertura Arbórea por Zona
                    </h3>
                    <span class="text-xs text-gray-400">El Bagre</span>
                </div>
                <div class="relative h-64">
                    <canvas id="chart-zonas"></canvas>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                    <span>Zona con mayor censo: <strong><?= !empty($individuos_por_zona[0]['nombre_zona']) ? htmlspecialchars($individuos_por_zona[0]['nombre_zona']) : 'N/A' ?></strong></span>
                    <a href="<?= BASE_URL ?>admin/zonas/index.php" class="text-emerald-700 font-semibold hover:underline">Ver Zonas</a>
                </div>
            </div>

        </div>

        <!-- Sección de Alertas y Tareas Pendientes -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Tabla: Especímenes en Observación / Riesgo -->
            <div class="lg:col-span-6 forest-card p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                        <h3 class="font-heading font-bold text-base text-gray-900">Atención Fitosanitaria Prioritaria</h3>
                    </div>
                    <span class="text-xs bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full">
                        <?= count($alertas_fitosanitarias) ?> alertas
                    </span>
                </div>

                <?php if (empty($alertas_fitosanitarias)): ?>
                    <p class="text-xs text-gray-400 italic py-4">No hay individuos registrados con estado crítico o en observación.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($alertas_fitosanitarias as $alerta): ?>
                            <div class="p-3.5 bg-amber-50/50 border border-amber-200/70 rounded-xl flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs text-gray-900"><?= htmlspecialchars($alerta['codigo_placa']) ?></span>
                                        <?= obtener_badge_estado($alerta['estado']) ?>
                                    </div>
                                    <h4 class="font-heading font-semibold text-sm text-gray-800"><?= htmlspecialchars($alerta['nombre_comun']) ?></h4>
                                    <p class="text-xs text-gray-600 leading-snug line-clamp-2"><?= htmlspecialchars($alerta['diagnostico']) ?></p>
                                    <span class="block text-[10px] text-gray-400">📍 <?= htmlspecialchars($alerta['nombre_zona']) ?></span>
                                </div>
                                <a href="<?= BASE_URL ?>admin/individuos/detalle.php?id=<?= $alerta['id_individuo'] ?>" class="btn-forest-secondary text-[11px] py-1.5 px-2.5 shrink-0">
                                    Inspeccionar
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tabla: Registro Reciente de Actividades en Campo -->
            <div class="lg:col-span-6 forest-card p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="history" class="w-5 h-5 text-emerald-600"></i>
                        <h3 class="font-heading font-bold text-base text-gray-900">Últimas Intervenciones Registradas</h3>
                    </div>
                    <a href="<?= BASE_URL ?>admin/reportes/index.php" class="text-xs text-emerald-700 font-semibold hover:underline">
                        Ver todas
                    </a>
                </div>

                <?php if (empty($ultimas_actividades)): ?>
                    <p class="text-xs text-gray-400 italic py-4">Aún no se han registrado actividades de mantenimiento.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($ultimas_actividades as $act): ?>
                            <div class="p-3 bg-gray-50 border border-gray-200/80 rounded-xl flex items-center justify-between gap-3">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-emerald-800"><?= htmlspecialchars($act['tipo_actividad']) ?></span>
                                        <span class="text-[11px] text-gray-500">• <?= htmlspecialchars($act['codigo_placa']) ?> (<?= htmlspecialchars($act['nombre_comun']) ?>)</span>
                                    </div>
                                    <p class="text-xs text-gray-600 truncate max-w-sm"><?= htmlspecialchars($act['descripcion']) ?></p>
                                    <span class="block text-[10px] text-gray-400">
                                        <?= formatear_fecha($act['fecha_actividad'], true) ?>
                                        <?= !empty($act['nombres']) ? '— ' . htmlspecialchars($act['nombres'] . ' ' . $act['apellidos']) : '— Reporte Ciudadano' ?>
                                    </span>
                                </div>
                                <a href="<?= BASE_URL ?>publico/ficha.php?codigo=<?= urlencode($act['codigo_placa']) ?>" target="_blank" class="p-2 text-gray-400 hover:text-emerald-700">
                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </main>
</div>

<!-- Scripts de Inicialización de Gráficos Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Gráfico Doughnut: Estado Fitosanitario
    const datosSalud = <?= json_encode($distribucion_salud) ?>;
    const etiquetasSalud = datosSalud.map(d => d.estado);
    const valoresSalud = datosSalud.map(d => parseInt(d.cantidad));

    const colorMap = {
        'Sano': '#16A34A',
        'En observación': '#D97706',
        'Enfermo': '#E11D48',
        'Plagado': '#9333EA',
        'En riesgo': '#DC2626',
        'Muerto en pie': '#78716C',
        'Talado': '#52525B'
    };

    const colores = etiquetasSalud.map(e => colorMap[e] || '#10B981');

    new Chart(document.getElementById('chart-fitosanitario'), {
        type: 'doughnut',
        data: {
            labels: etiquetasSalud,
            datasets: [{
                data: valoresSalud,
                backgroundColor: colores,
                borderWidth: 2,
                borderColor: '#FFFFFF'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                }
            },
            cutout: '65%'
        }
    });

    // 2. Gráfico Bar: Individuos por Zona
    const datosZonas = <?= json_encode($individuos_por_zona) ?>;
    const etiquetasZonas = datosZonas.map(z => z.nombre_zona);
    const valoresZonas = datosZonas.map(z => parseInt(z.total_arboles));

    new Chart(document.getElementById('chart-zonas'), {
        type: 'bar',
        data: {
            labels: etiquetasZonas,
            datasets: [{
                label: 'Individuos Forestales',
                data: valoresZonas,
                backgroundColor: '#2E7D32',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, font: { size: 11 } },
                    grid: { color: '#F3F4F6' }
                },
                x: {
                    ticks: { font: { size: 10 } },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
