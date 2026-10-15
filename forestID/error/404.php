<?php
/**
 * ForestID / ForestSRD - Error 404 (Recurso No Encontrado)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

http_response_code(404);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
$titulo_pagina = '404 — Página No Encontrada | ForestID';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="min-h-[70vh] flex items-center justify-center p-6">
    <div class="forest-card p-10 max-w-md w-full text-center space-y-6">
        <div class="w-16 h-16 rounded-3xl bg-amber-100 text-amber-700 flex items-center justify-center mx-auto shadow-inner">
            <i data-lucide="compass" class="w-8 h-8"></i>
        </div>
        <div class="space-y-2">
            <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Código 404</span>
            <h1 class="font-heading font-extrabold text-2xl text-gray-900">Página No Encontrada</h1>
            <p class="text-xs text-gray-500 leading-relaxed">
                La ruta solicitada no existe, ha sido movida o la placa consultada no está disponible en la base de datos de Emincut.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row justify-center gap-2 pt-2">
            <a href="<?= BASE_URL ?>publico/index.php" class="btn-forest-primary text-xs py-2 px-4">
                <i data-lucide="home" class="w-4 h-4"></i> Portal Principal
            </a>
            <a href="<?= BASE_URL ?>publico/buscar.php" class="btn-forest-secondary text-xs py-2 px-4">
                <i data-lucide="search" class="w-4 h-4"></i> Explorar Catálogo
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
