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
    <main class="flex-1 p-6 sm:p-8 space-y-8 overflow-y-auto" style="background:var(--emi-hueso)">

        <!-- Cabecera de Bienvenida y Acciones Rápidas -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 al-scroll">
            <div>
                <span class="emi-display text-xs font-bold uppercase tracking-widest" style="color:var(--emi-ambar)">Emincut • El Bagre, Antioquia</span>
                <h1 class="font-heading font-extrabold text-2xl sm:text-3xl" style="color:var(--emi-tinta)">
                    Panel de Administración General
                </h1>
                <div class="linea-sol mt-2"></div>
                <p class="text-xs sm:text-sm mt-2" style="color:var(--emi-tinta-suave)">
                    Resumen del inventario, salud fitosanitaria y registro de intervenciones en campo.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>admin/individuos/crear.php" class="emi-btn emi-btn-verde text-xs py-2.5 px-4">
                    <span class="inline-flex items-center gap-2"><i data-lucide="plus-circle" class="w-4 h-4"></i> Nuevo Individuo</span>
                </a>
                <a href="<?= BASE_URL ?>admin/qr/imprimir.php" class="emi-btn text-xs py-2.5 px-4 border" style="background:#fff; color:var(--emi-verde-oscuro); border-color:#CBD9B8">
                    <span class="inline-flex items-center gap-2"><i data-lucide="printer" class="w-4 h-4"></i> Imprimir Placas</span>
                </a>
            </div>
        </div>

        <!-- Tarjetas de Métricas Principales (KPIs) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="emi-kpi al-scroll">
                <div class="flex items-center justify-between mb-2">
                    <span class="emi-label">Individuos Forestales</span>
                    <i data-lucide="trees" class="w-5 h-5" style="color:var(--emi-verde)"></i>
                </div>
                <span class="text-3xl font-extrabold font-heading cifra" style="color:var(--emi-tinta)" data-contador="<?= (int)$total_arboles ?>">0</span>
                <span class="block text-[11px] font-semibold mt-1" style="color:var(--emi-verde)">Censo activo</span>
            </div>

            <div class="emi-kpi al-scroll d1">
                <div class="flex items-center justify-between mb-2">
                    <span class="emi-label">Zonas Territoriales</span>
                    <i data-lucide="map-pin" class="w-5 h-5" style="color:var(--emi-ambar)"></i>
                </div>
                <span class="text-3xl font-extrabold font-heading cifra" style="color:var(--emi-tinta)" data-contador="<?= (int)$total_zonas ?>">0</span>
                <span class="block text-[11px] font-semibold mt-1" style="color:var(--emi-ambar)">Sectores en El Bagre</span>
            </div>

            <div class="emi-kpi al-scroll d2">
                <div class="flex items-center justify-between mb-2">
                    <span class="emi-label">Intervenciones</span>
                    <i data-lucide="clipboard-check" class="w-5 h-5" style="color:var(--emi-verde)"></i>
                </div>
                <span class="text-3xl font-extrabold font-heading cifra" style="color:var(--emi-tinta)" data-contador="<?= (int)$total_actividades ?>">0</span>
                <span class="block text-[11px] font-semibold mt-1" style="color:var(--emi-verde)">Mantenimientos y podas</span>
            </div>

            <div class="emi-kpi al-scroll d3">
                <div class="flex items-center justify-between mb-2">
                    <span class="emi-label">Personal y Usuarios</span>
                    <i data-lucide="users" class="w-5 h-5" style="color:var(--emi-ambar)"></i>
                </div>
                <span class="text-3xl font-extrabold font-heading cifra" style="color:var(--emi-tinta)" data-contador="<?= (int)$total_usuarios ?>">0</span>
                <span class="block text-[11px] font-semibold mt-1" style="color:var(--emi-ambar)">Cuentas activas</span>
            </div>
        </div>

        <!-- Gráficos de Estadísticas con Chart.js -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Gráfico 1: Estado Fitosanitario (Doughnut) -->
            <div class="lg:col-span-5 emi-card p-6 flex flex-col justify-between al-scroll">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-heading font-bold text-base" style="color:var(--emi-tinta)">
                        Distribución Fitosanitaria
                    </h3>
                    <span class="text-xs" style="color:var(--emi-tinta-suave)">Salud actual</span>
                </div>
                <div class="relative h-64 flex items-center justify-center">
                    <canvas id="chart-fitosanitario"></canvas>
                </div>
                <div class="mt-4 pt-3 border-t border-[#E7EADD] grid grid-cols-2 gap-2 text-xs">
                    <?php foreach ($distribucion_salud as $ds): ?>
                        <div class="flex items-center justify-between" style="color:var(--emi-tinta-suave)">
                            <span><?= htmlspecialchars($ds['estado']) ?>:</span>
                            <span class="font-bold" style="color:var(--emi-tinta)"><?= $ds['cantidad'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Gráfico 2: Árboles por Zona (Bar Chart) -->
            <div class="lg:col-span-7 emi-card p-6 flex flex-col justify-between al-scroll d1">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-heading font-bold text-base" style="color:var(--emi-tinta)">
                        Cobertura Arbórea por Zona
                    </h3>
                    <span class="text-xs" style="color:var(--emi-tinta-suave)">El Bagre</span>
                </div>
                <div class="relative h-64">
                    <canvas id="chart-zonas"></canvas>
                </div>
                <div class="mt-4 pt-3 border-t border-[#E7EADD] flex items-center justify-between text-xs" style="color:var(--emi-tinta-suave)">
                    <span>Zona con mayor censo: <strong style="color:var(--emi-tinta)"><?= !empty($individuos_por_zona[0]['nombre_zona']) ? htmlspecialchars($individuos_por_zona[0]['nombre_zona']) : 'N/A' ?></strong></span>
                    <a href="<?= BASE_URL ?>admin/zonas/index.php" class="font-semibold hover:underline" style="color:var(--emi-verde)">Ver Zonas</a>
                </div>
            </div>

        </div>

        <!-- Sección de Alertas y Tareas Pendientes -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Tabla: Especímenes en Observación / Riesgo -->
            <div class="lg:col-span-6 emi-card p-6 space-y-4 al-scroll">
                <div class="flex items-center justify-between border-b border-[#E7EADD] pb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-5 h-5" style="color:var(--emi-ambar)"></i>
                        <h3 class="font-heading font-bold text-base" style="color:var(--emi-tinta)">Atención Fitosanitaria Prioritaria</h3>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full" style="background:var(--emi-amarillo-suave); color:#8A5E10">
                        <?= count($alertas_fitosanitarias) ?> alertas
                    </span>
                </div>

                <?php if (empty($alertas_fitosanitarias)): ?>
                    <p class="text-xs italic py-4" style="color:var(--emi-tinta-suave)">No hay individuos registrados con estado crítico o en observación.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($alertas_fitosanitarias as $alerta): ?>
                            <div class="p-3.5 rounded-xl flex items-start justify-between gap-3 border" style="background:var(--emi-amarillo-suave); border-color:#F2DCAC">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs" style="color:var(--emi-tinta)"><?= htmlspecialchars($alerta['codigo_placa']) ?></span>
                                        <?= obtener_badge_estado($alerta['estado']) ?>
                                    </div>
                                    <h4 class="font-heading font-semibold text-sm" style="color:var(--emi-tinta)"><?= htmlspecialchars($alerta['nombre_comun']) ?></h4>
                                    <p class="text-xs leading-snug line-clamp-2" style="color:var(--emi-tinta-suave)"><?= htmlspecialchars($alerta['diagnostico']) ?></p>
                                    <span class="block text-[10px]" style="color:var(--emi-tinta-suave)">📍 <?= htmlspecialchars($alerta['nombre_zona']) ?></span>
                                </div>
                                <a href="<?= BASE_URL ?>admin/individuos/detalle.php?id=<?= $alerta['id_individuo'] ?>" class="emi-btn text-[11px] py-1.5 px-2.5 shrink-0" style="background:#fff; color:var(--emi-verde-oscuro); border:1px solid #CBD9B8">
                                    Inspeccionar
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tabla: Registro Reciente de Actividades en Campo -->
            <div class="lg:col-span-6 emi-card p-6 space-y-4 al-scroll d1">
                <div class="flex items-center justify-between border-b border-[#E7EADD] pb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="history" class="w-5 h-5" style="color:var(--emi-verde)"></i>
                        <h3 class="font-heading font-bold text-base" style="color:var(--emi-tinta)">Últimas Intervenciones Registradas</h3>
                    </div>
                    <a href="<?= BASE_URL ?>admin/reportes/index.php" class="text-xs font-semibold hover:underline" style="color:var(--emi-verde)">
                        Ver todas
                    </a>
                </div>

                <?php if (empty($ultimas_actividades)): ?>
                    <p class="text-xs italic py-4" style="color:var(--emi-tinta-suave)">Aún no se han registrado actividades de mantenimiento.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($ultimas_actividades as $act): ?>
                            <div class="p-3 rounded-xl flex items-center justify-between gap-3 border" style="background:var(--emi-verde-suave); border-color:#CBD9B8">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold" style="color:var(--emi-verde-oscuro)"><?= htmlspecialchars($act['tipo_actividad']) ?></span>
                                        <span class="text-[11px]" style="color:var(--emi-tinta-suave)">• <?= htmlspecialchars($act['codigo_placa']) ?> (<?= htmlspecialchars($act['nombre_comun']) ?>)</span>
                                    </div>
                                    <p class="text-xs truncate max-w-sm" style="color:var(--emi-tinta-suave)"><?= htmlspecialchars($act['descripcion']) ?></p>
                                    <span class="block text-[10px]" style="color:var(--emi-tinta-suave)">
                                        <?= formatear_fecha($act['fecha_actividad'], true) ?>
                                        <?= !empty($act['nombres']) ? '— ' . htmlspecialchars($act['nombres'] . ' ' . $act['apellidos']) : '— Reporte Ciudadano' ?>
                                    </span>
                                </div>
                                <a href="<?= BASE_URL ?>publico/ficha.php?codigo=<?= urlencode($act['codigo_placa']) ?>" target="_blank" class="p-2" style="color:var(--emi-tinta-suave)">
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
        'Sano': '#5B7C3A',
        'En observación': '#E9A22E',
        'Enfermo': '#DC2626',
        'Plagado': '#9333EA',
        'En riesgo': '#C2410C',
        'Muerto en pie': '#78716C',
        'Talado': '#52525B'
    };

    const colores = etiquetasSalud.map(e => colorMap[e] || '#7A9B54');

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
                backgroundColor: '#5B7C3A',
                hoverBackgroundColor: '#E9A22E',
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
