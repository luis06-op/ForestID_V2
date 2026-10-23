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

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto" style="background:var(--emi-hueso)">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 al-scroll">
            <div>
                <span class="emi-display text-xs font-bold uppercase tracking-widest" style="color:var(--emi-ambar)">Trabajo de Campo • Emincut</span>
                <h1 class="font-heading font-extrabold text-2xl sm:text-3xl" style="color:var(--emi-tinta)">
                    Panel Técnico Operario
                </h1>
                <div class="linea-sol mt-2"></div>
                <p class="text-xs mt-2" style="color:var(--emi-tinta-suave)">
                    Bienvenido(a), <?= htmlspecialchars($_SESSION['usuario']['nombres']) ?>. Gestione inspecciones y actividades en campo.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>operario/escanear.php" class="emi-btn emi-btn-sol text-xs py-2.5 px-4">
                    <span class="inline-flex items-center gap-2"><i data-lucide="camera" class="w-4 h-4"></i> Escanear Placa QR</span>
                </a>
            </div>
        </div>

        <!-- Accesos Rápidos de Campo (Mobile-First) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="<?= BASE_URL ?>operario/escanear.php" class="emi-card p-6 flex items-center gap-4 group al-scroll">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform" style="background:var(--emi-amarillo-suave); color:var(--emi-ambar)">
                    <i data-lucide="camera" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="block text-base font-bold font-heading" style="color:var(--emi-tinta)">Escanear Placa QR</span>
                    <span class="text-xs" style="color:var(--emi-tinta-suave)">Abrir escáner con cámara</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>operario/registrar_individuo.php" class="emi-card p-6 flex items-center gap-4 group al-scroll d1">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform" style="background:var(--emi-verde-suave); color:var(--emi-verde)">
                    <i data-lucide="plus-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="block text-base font-bold font-heading" style="color:var(--emi-tinta)">Alta Rápida en Campo</span>
                    <span class="text-xs" style="color:var(--emi-tinta-suave)">Registrar árbol nuevo con GPS</span>
                </div>
            </a>

            <a href="<?= BASE_URL ?>operario/actividades/crear.php" class="emi-card p-6 flex items-center gap-4 group al-scroll d2">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform" style="background:var(--emi-amarillo-suave); color:var(--emi-ambar)">
                    <i data-lucide="wrench" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="block text-base font-bold font-heading" style="color:var(--emi-tinta)">Nueva Intervención</span>
                    <span class="text-xs" style="color:var(--emi-tinta-suave)">Podas, abonos y tratamientos</span>
                </div>
            </a>
        </div>

        <!-- Tareas Prioritarias de Inspección -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <div class="lg:col-span-7 emi-card p-6 space-y-4 al-scroll">
                <div class="flex items-center justify-between border-b border-[#E7EADD] pb-3">
                    <h3 class="font-heading font-bold text-base flex items-center gap-2" style="color:var(--emi-tinta)">
                        <i data-lucide="alert-triangle" class="w-5 h-5" style="color:var(--emi-ambar)"></i>
                        Especímenes Prioritarios para Inspección
                    </h3>
                    <span class="text-xs" style="color:var(--emi-tinta-suave)">El Bagre</span>
                </div>

                <div class="space-y-3">
                    <?php if (empty($pendientes)): ?>
                        <p class="text-xs italic" style="color:var(--emi-tinta-suave)">No hay alertas de inspección pendientes.</p>
                    <?php else: ?>
                        <?php foreach ($pendientes as $p): ?>
                            <div class="p-3.5 rounded-xl flex items-center justify-between gap-3 border" style="background:var(--emi-verde-suave); border-color:#CBD9B8">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs" style="color:var(--emi-tinta)"><?= htmlspecialchars($p['codigo_placa']) ?></span>
                                        <?= obtener_badge_estado($p['estado'] ?? 'Sano') ?>
                                    </div>
                                    <h4 class="font-semibold text-xs mt-0.5" style="color:var(--emi-tinta)"><?= htmlspecialchars($p['nombre_comun']) ?></h4>
                                    <span class="block text-[11px]" style="color:var(--emi-tinta-suave)">📍 <?= htmlspecialchars($p['nombre_zona']) ?></span>
                                </div>
                                <a href="<?= BASE_URL ?>operario/individuos/detalle.php?id=<?= $p['id_individuo'] ?>" class="emi-btn text-xs py-1.5 px-3" style="background:#fff; color:var(--emi-verde-oscuro); border:1px solid #CBD9B8">
                                    Inspeccionar
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mantenimientos Registrados por el Operario -->
            <div class="lg:col-span-5 emi-card p-6 space-y-4 al-scroll d1">
                <div class="flex items-center justify-between border-b border-[#E7EADD] pb-3">
                    <h3 class="font-heading font-bold text-base flex items-center gap-2" style="color:var(--emi-tinta)">
                        <i data-lucide="history" class="w-5 h-5" style="color:var(--emi-verde)"></i>
                        Mis Intervenciones Recientes
                    </h3>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full cifra" style="background:var(--emi-amarillo-suave); color:#8A5E10" data-contador="<?= (int)$total_mis_actividades ?>" data-sufijo=" total">0 total</span>
                </div>

                <div class="space-y-3">
                    <?php if (empty($ultimos_mantenimientos)): ?>
                        <p class="text-xs italic" style="color:var(--emi-tinta-suave)">No has registrado intervenciones aún.</p>
                    <?php else: ?>
                        <?php foreach ($ultimos_mantenimientos as $m): ?>
                            <div class="p-3 rounded-xl space-y-1 border" style="background:var(--emi-hueso); border-color:#E7EADD">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs" style="color:var(--emi-verde-oscuro)"><?= htmlspecialchars($m['tipo_actividad']) ?></span>
                                    <span class="text-[10px]" style="color:var(--emi-tinta-suave)"><?= formatear_fecha($m['fecha_actividad']) ?></span>
                                </div>
                                <p class="text-xs" style="color:var(--emi-tinta-suave)"><?= htmlspecialchars($m['descripcion']) ?></p>
                                <span class="block text-[10px] font-mono" style="color:var(--emi-tinta-suave)"><?= htmlspecialchars($m['codigo_placa']) ?> (<?= htmlspecialchars($m['nombre_comun']) ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
