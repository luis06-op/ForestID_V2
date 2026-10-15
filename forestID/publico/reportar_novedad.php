<?php
/**
 * ForestID / ForestSRD - Recepción de Reporte Ciudadano de Novedades
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf   = $_POST['csrf_token'] ?? '';
    $id_individuo = $_POST['id_individuo'] ?? '';
    $codigo_placa = $_POST['codigo_placa'] ?? '';
    $tipo_novedad = sanear($_POST['tipo_novedad'] ?? 'Reporte ciudadano');
    $descripcion  = sanear($_POST['descripcion'] ?? '');

    if (!validar_token_csrf($token_csrf)) {
        set_flash('error', 'Token de seguridad inválido. Intente nuevamente.');
        header('Location: ' . BASE_URL . 'publico/ficha.php?codigo=' . urlencode($codigo_placa));
        exit;
    }

    if (empty($id_individuo) || empty($descripcion)) {
        set_flash('error', 'Debe ingresar una descripción detallada de la novedad.');
        header('Location: ' . BASE_URL . 'publico/ficha.php?codigo=' . urlencode($codigo_placa));
        exit;
    }

    $pdo = getDBConnection();

    // Manejo de foto opcional de evidencia
    $foto_url = null;
    if (!empty($_FILES['foto_evidencia']) && $_FILES['foto_evidencia']['error'] !== UPLOAD_ERR_NO_FILE) {
        $subida = subir_imagen_segura($_FILES['foto_evidencia'], 'evidencias');
        if ($subida['ok']) {
            $foto_url = $subida['ruta'];
        }
    }

    $descripcion_completa = "[Reporte Ciudadano - {$tipo_novedad}]: " . $descripcion;
    $id_usuario = !empty($_SESSION['usuario']['id_usuario']) ? $_SESSION['usuario']['id_usuario'] : null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO `actividades_mantenimiento` (
                `id_actividad`, `id_individuo`, `tipo_actividad`, `descripcion`, 
                `evidencia_foto_url`, `realizado_por`, `fecha_actividad`, `fecha_registro`
            ) VALUES (
                UUID(), :id_individuo, 'Reporte ciudadano', :descripcion,
                :foto_url, :id_usuario, NOW(), NOW()
            )
        ");
        $stmt->execute([
            ':id_individuo' => $id_individuo,
            ':descripcion'  => $descripcion_completa,
            ':foto_url'     => $foto_url,
            ':id_usuario'   => $id_usuario
        ]);

        registrar_log($pdo, $id_usuario, 'REPORTE_CIUDADANO', 'PUBLICO', [
            'codigo_placa' => $codigo_placa,
            'tipo'         => $tipo_novedad
        ]);

        set_flash('success', '¡Gracias por su colaboración! Su reporte ha sido registrado exitosamente para revisión de la cuadrilla técnica de Emincut.');
    } catch (PDOException $e) {
        error_log("Error guardando reporte ciudadano: " . $e->getMessage());
        set_flash('error', 'Ocurrió un error al registrar el reporte en el sistema.');
    }

    header('Location: ' . BASE_URL . 'publico/ficha.php?codigo=' . urlencode($codigo_placa));
    exit;
}

// Redirección si se accede por GET sin parámetros
header('Location: ' . BASE_URL . 'publico/buscar.php');
exit;
