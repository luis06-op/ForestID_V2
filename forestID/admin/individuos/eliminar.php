<?php
/**
 * ForestID / ForestSRD - Baja Lógica de Individuo Forestal (AdminEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['AdminEmincut']);

$id = $_GET['id'] ?? '';
$csrf = $_GET['csrf'] ?? '';

if (!validar_token_csrf($csrf)) {
    set_flash('error', 'Token de seguridad inválido.');
    header('Location: ' . BASE_URL . 'admin/individuos/index.php');
    exit;
}

$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT codigo_placa, nombre_comun FROM `individuos_forestales` WHERE `id_individuo` = :id");
$stmt->execute([':id' => $id]);
$arbol = $stmt->fetch();

if ($arbol) {
    // Baja lógica (estado_registro = 0)
    $upd = $pdo->prepare("UPDATE `individuos_forestales` SET `estado_registro` = 0 WHERE `id_individuo` = :id");
    $upd->execute([':id' => $id]);

    registrar_log($pdo, $_SESSION['usuario']['id_usuario'], 'BAJA_LOGICA_INDIVIDUO', 'INDIVIDUOS_FORESTALES', [
        'id_individuo' => $id,
        'codigo_placa' => $arbol['codigo_placa'],
        'nombre_comun' => $arbol['nombre_comun']
    ]);

    set_flash('success', "El individuo {$arbol['nombre_comun']} ({$arbol['codigo_placa']}) ha sido dado de baja lógicamente.");
} else {
    set_flash('error', 'El individuo forestal no fue encontrado.');
}

header('Location: ' . BASE_URL . 'admin/individuos/index.php');
exit;
