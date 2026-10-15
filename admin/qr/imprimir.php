<?php
/**
 * ForestID / ForestSRD - Vista de Impresión de Placas Físicas QR
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$pdo = getDBConnection();

$codigo = $_GET['codigo'] ?? '';
$id_zona = $_GET['zona'] ?? '';

$where = ["i.estado_registro = 1"];
$params = [];

if (!empty($codigo)) {
    $where[] = "i.codigo_placa = :cod";
    $params[':cod'] = $codigo;
} elseif (!empty($id_zona)) {
    $where[] = "i.id_zona = :zona";
    $params[':zona'] = $id_zona;
}

$where_sql = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT i.*, z.nombre_zona, inst.nombre_institucion
    FROM `individuos_forestales` i
    INNER JOIN `zonas` z ON i.id_zona = z.id_zona
    INNER JOIN `instituciones` inst ON i.id_institucion = inst.id_institucion
    WHERE {$where_sql}
    ORDER BY i.codigo_placa ASC
");
$stmt->execute($params);
$placas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresión de Placas QR — ForestID Emincut</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F3F4F6;
        }
        @media print {
            body {
                background: #FFFFFF !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .hoja-impresion {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .placa-fisica {
                page-break-inside: avoid;
                margin-bottom: 25px !important;
                border: 2px solid #000000 !important;
            }
        }
    </style>
</head>
<body class="p-4 sm:p-8">

    <!-- Barra Superior de Control (No imprimible) -->
    <div class="no-print max-w-4xl mx-auto mb-6 p-4 bg-white border border-gray-200 rounded-2xl shadow-sm flex items-center justify-between">
        <div class="space-y-0.5">
            <h2 class="font-bold text-gray-900 text-sm">Plantilla de Impresión de Placas Físicas QR</h2>
            <p class="text-xs text-gray-500">Se encontraron <?= count($placas) ?> placa(s) listas para troquelado, grabado o impresión.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Imprimir Placas Ahora
            </button>
            <button onclick="window.close()" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-medium">
                Cerrar
            </button>
        </div>
    </div>

    <!-- Contenedor de Placas -->
    <div class="hoja-impresion max-w-4xl mx-auto space-y-6">
        
        <?php if (empty($placas)): ?>
            <div class="p-8 text-center bg-white rounded-2xl border border-gray-200 text-xs text-gray-500">
                No hay placas para imprimir con los filtros seleccionados.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($placas as $p): 
                    $qr_img = !empty($p['qr_url']) ? BASE_URL . $p['qr_url'] : BASE_URL . 'assets/qr/qr_' . $p['codigo_placa'] . '.svg';
                ?>
                    <!-- Placa Física Estándar para Tronco de Árbol -->
                    <div class="placa-fisica bg-white border-2 border-emerald-900 rounded-2xl p-5 relative shadow-md flex flex-col justify-between" style="min-height: 280px;">
                        
                        <!-- Agujeros guía de sujeción (para resortes o clavos de aluminio sin estrangular el árbol) -->
                        <div class="absolute top-3 left-3 w-3 h-3 rounded-full border border-gray-400 bg-gray-100" title="Punto de fijación"></div>
                        <div class="absolute top-3 right-3 w-3 h-3 rounded-full border border-gray-400 bg-gray-100" title="Punto de fijación"></div>

                        <!-- Cabecera Institucional -->
                        <div class="text-center border-b border-gray-200 pb-2">
                            <span class="block font-black text-xs text-emerald-950 uppercase tracking-widest" style="font-family: 'Poppins', sans-serif;">
                                EMINCUT S.A.S. • EL BAGRE, ANTIOQUIA
                            </span>
                            <span class="text-[9px] text-gray-500 font-semibold uppercase tracking-wider">
                                SENA Regional Antioquia — iRed Innovacción
                            </span>
                        </div>

                        <!-- Cuerpo con QR y Placa -->
                        <div class="py-3 flex items-center justify-between gap-4">
                            
                            <!-- Código QR Vectorial Grande -->
                            <div class="w-32 h-32 p-1 bg-white border border-gray-300 rounded-xl shrink-0 flex items-center justify-center">
                                <img src="<?= htmlspecialchars($qr_img) ?>" alt="QR <?= $p['codigo_placa'] ?>" class="w-full h-full object-contain">
                            </div>

                            <!-- Información Botánica -->
                            <div class="flex-1 space-y-1">
                                <div class="bg-emerald-950 text-white px-2 py-1 rounded text-center font-mono font-black text-sm tracking-wider">
                                    <?= htmlspecialchars($p['codigo_placa']) ?>
                                </div>
                                <h3 class="font-bold text-sm text-gray-900 leading-tight pt-1">
                                    <?= htmlspecialchars($p['nombre_comun']) ?>
                                </h3>
                                <p class="text-[11px] text-gray-600 italic leading-snug">
                                    <?= htmlspecialchars($p['nombre_cientifico']) ?>
                                </p>
                                <span class="block text-[10px] text-gray-500 font-medium pt-0.5">
                                    Fam: <?= htmlspecialchars($p['familia_botanica']) ?>
                                </span>
                                <span class="block text-[10px] text-emerald-800 font-semibold">
                                    📍 <?= htmlspecialchars($p['nombre_zona']) ?>
                                </span>
                            </div>

                        </div>

                        <!-- Pie de Placa con Instrucción -->
                        <div class="border-t border-gray-200 pt-2 text-center">
                            <p class="text-[9px] text-gray-600 font-medium">
                                📷 Escanea con la cámara de tu celular para consultar ficha técnica, historia y salud del árbol.
                            </p>
                        </div>

                        <!-- Agujeros inferiores -->
                        <div class="absolute bottom-3 left-3 w-3 h-3 rounded-full border border-gray-400 bg-gray-100"></div>
                        <div class="absolute bottom-3 right-3 w-3 h-3 rounded-full border border-gray-400 bg-gray-100"></div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
