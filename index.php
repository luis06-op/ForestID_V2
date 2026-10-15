<?php
/**
 * ForestID / ForestSRD - Enrutador Principal
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

if (esta_autenticado()) {
    redirigir_segun_rol();
} else {
    header('Location: ' . BASE_URL . 'publico/index.php');
    exit;
}
