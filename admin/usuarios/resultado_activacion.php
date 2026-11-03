<?php
/**
 * ForestID / ForestSRD - Resultado del reenvío del enlace de activación
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 *
 * Muestra el mismo panel de resultado que admin/usuarios/crear.php después
 * de reenviar el enlace de activación (correo / WhatsApp) a un usuario.
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$panel = $_SESSION['panel_envio_activacion'] ?? null;
unset($_SESSION['panel_envio_activacion']);

if (!is_array($panel) || empty($panel['usuario']) || empty($panel['envio'])) {
    set_flash('error', 'No hay un envío de activación reciente para mostrar.');
    header('Location: ' . BASE_URL . 'admin/usuarios/index.php');
    exit;
}

$usuario_nuevo    = $panel['usuario'];
$envio            = $panel['envio'];
$horas_activacion = (int)($panel['horas_activacion'] ?? max(1, (int)round(TOKEN_VIGENCIA_ACTIVACION / 3600)));
$panel_accion     = 'reenviado';

$csrf_token    = crear_token_csrf();
$titulo_pagina = 'Enlace reenviado — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto" style="background:var(--emi-hueso)">

        <div class="flex items-center justify-between">
            <div>
                <span class="emi-display text-xs font-bold uppercase tracking-widest" style="color:var(--emi-ambar)">Administración de Personal</span>
                <h1 class="font-heading font-extrabold text-2xl" style="color:var(--emi-tinta)">Envío del enlace de activación</h1>
                <div class="linea-sol mt-2"></div>
            </div>
            <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="emi-btn text-xs py-2 px-3" style="background:#fff; color:var(--emi-verde-oscuro); border:1px solid #CBD9B8">
                <span class="inline-flex items-center gap-2"><i data-lucide="arrow-left" class="w-4 h-4"></i> Volver a Usuarios</span>
            </a>
        </div>

        <?php require __DIR__ . '/../../includes/panel_envio_activacion.php'; ?>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
