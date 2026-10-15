<?php
/**
 * ForestID / ForestSRD - Error 403 (Acceso Denegado)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

http_response_code(403);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
$titulo_pagina = '403 — Acceso Restringido | ForestID';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="min-h-[70vh] flex items-center justify-center p-6">
    <div class="forest-card p-10 max-w-md w-full text-center space-y-6">
        <div class="w-16 h-16 rounded-3xl bg-rose-100 text-rose-700 flex items-center justify-center mx-auto shadow-inner">
            <i data-lucide="shield-alert" class="w-8 h-8"></i>
        </div>
        <div class="space-y-2">
            <span class="text-xs font-bold text-rose-600 uppercase tracking-widest">Código 403</span>
            <h1 class="font-heading font-extrabold text-2xl text-gray-900">Acceso No Autorizado</h1>
            <p class="text-xs text-gray-500 leading-relaxed">
                Su rol actual de usuario no cuenta con los privilegios requeridos para visualizar o gestionar este módulo del sistema ForestID.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row justify-center gap-2 pt-2">
            <a href="<?= BASE_URL ?>index.php" class="btn-forest-primary text-xs py-2 px-4">
                <i data-lucide="home" class="w-4 h-4"></i> Ir a Mi Panel
            </a>
            <a href="<?= BASE_URL ?>auth/login.php" class="btn-forest-secondary text-xs py-2 px-4">
                <i data-lucide="user" class="w-4 h-4"></i> Cambiar Usuario
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
