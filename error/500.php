<?php
/**
 * ForestID / ForestSRD - Error 500 (Error Interno del Servidor)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

http_response_code(500);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
$titulo_pagina = '500 — Error Interno | ForestID';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="min-h-[70vh] flex items-center justify-center p-6">
    <div class="forest-card p-10 max-w-md w-full text-center space-y-6">
        <div class="w-16 h-16 rounded-3xl bg-rose-100 text-rose-700 flex items-center justify-center mx-auto shadow-inner">
            <i data-lucide="server-crash" class="w-8 h-8"></i>
        </div>
        <div class="space-y-2">
            <span class="text-xs font-bold text-rose-600 uppercase tracking-widest">Código 500</span>
            <h1 class="font-heading font-extrabold text-2xl text-gray-900">Error Interno del Servidor</h1>
            <p class="text-xs text-gray-500 leading-relaxed">
                Ocurrió un problema inesperado al procesar la solicitud. Nuestro equipo técnico ha sido notificado.
            </p>
        </div>
        <div class="flex justify-center pt-2">
            <a href="<?= BASE_URL ?>index.php" class="btn-forest-primary text-xs py-2 px-4">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Volver a Intentar
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
