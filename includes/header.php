<?php
/**
 * ForestID / ForestSRD - Encabezado Común HTML5 y CDN
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/db.php';
}
require_once __DIR__ . '/funciones.php';

$titulo_pagina = $titulo_pagina ?? APP_FULL_NAME;
$meta_descripcion = $meta_descripcion ?? 'Sistema integral de gestión, identificación y trazabilidad del inventario dendrológico y arborización urbana de Emincut en El Bagre, Antioquia.';
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#FAFAF7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($meta_descripcion) ?>">
    <meta name="author" content="Emincut S.A.S. - SENA Regional Antioquia">
    <title><?= htmlspecialchars($titulo_pagina) ?></title>

    <!-- Google Fonts: Poppins (Títulos) & Inter (Lectura) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: {
                            50: '#F0FDF4',
                            100: '#DCFCE7',
                            200: '#BBF7D0',
                            300: '#86EFAC',
                            400: '#4ADE80',
                            500: '#22C55E',
                            600: '#16A34A',
                            700: '#15803D',
                            800: '#166534',
                            900: '#14532D',
                            950: '#052E16',
                            dark: '#1B5E20',
                            primary: '#2E7D32',
                            light: '#E8F5E9'
                        },
                        earth: {
                            500: '#6D4C41',
                            700: '#4E342E',
                            900: '#3E2723'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Poppins', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Leaflet CSS & JS (Mapas OpenStreetMap sin costos) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Chart.js para estadísticas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- html5-qrcode para escáner QR con cámara móvil/webcam -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <!-- Estilos personalizados ForestID -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom.css">
</head>
<body class="min-h-full flex flex-col antialiased text-gray-800 bg-[#FAFAF7]">
    <?= render_flash() ?>
