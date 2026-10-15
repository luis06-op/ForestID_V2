<?php
/**
 * ForestID / ForestSRD - Registrar Nueva Actividad de Mantenimiento (OperarioEmincut)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/auth_check.php';

requerir_rol(['OperarioEmincut', 'AdminEmincut']);

$pdo = getDBConnection();
$error = '';
$individuos = $pdo->query("SELECT id_individuo, codigo_placa, nombre_comun FROM `individuos_forestales` WHERE estado_registro = 1 ORDER BY codigo_placa ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_csrf     = $_POST['csrf_token'] ?? '';
    $id_individuo   = $_POST['id_individuo'] ?? '';
    $tipo_actividad = $_POST['tipo_actividad'] ?? 'Poda';
    $descripcion    = sanear($_POST['descripcion'] ?? '');

    if (!validar_token_csrf($token_csrf)) {
        $error = 'Token de seguridad inválido.';
    } elseif (empty($id_individuo) || empty($descripcion)) {
        $error = 'Debe seleccionar el espécimen e ingresar la descripción del trabajo.';
    } else {
        $foto_url = null;
        if (!empty($_FILES['foto_evidencia']) && $_FILES['foto_evidencia']['error'] !== UPLOAD_ERR_NO_FILE) {
            $sub = subir_imagen_segura($_FILES['foto_evidencia'], 'evidencias');
            if ($sub['ok']) {
                $foto_url = $sub['ruta'];
            }
        }

        $id_usuario = $_SESSION['usuario']['id_usuario'];
        $ins = $pdo->prepare("
            INSERT INTO `actividades_mantenimiento` (
                `id_actividad`, `id_individuo`, `tipo_actividad`, `descripcion`, 
                `evidencia_foto_url`, `realizado_por`, `fecha_actividad`, `fecha_registro`
            ) VALUES (
                UUID(), :id_ind, :tipo, :desc, :foto, :user, NOW(), NOW()
            )
        ");
        $ins->execute([
            ':id_ind' => $id_individuo,
            ':tipo'   => $tipo_actividad,
            ':desc'   => $descripcion,
            ':foto'   => $foto_url,
            ':user'   => $id_usuario
        ]);

        registrar_log($pdo, $id_usuario, 'CREAR_ACTIVIDAD_MANTENIMIENTO', 'MANTENIMIENTO', [
            'id_individuo' => $id_individuo,
            'tipo'         => $tipo_actividad
        ]);

        set_flash('success', 'Intervención de mantenimiento registrada con éxito.');
        header('Location: ' . BASE_URL . 'operario/actividades/index.php');
        exit;
    }
}

$csrf_token = crear_token_csrf();
$titulo_pagina = 'Registrar Intervención — ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php 
    if (es_admin()) {
        require_once __DIR__ . '/../../includes/sidebar_admin.php';
    } else {
        require_once __DIR__ . '/../../includes/sidebar_operario.php';
    }
    ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-teal-700 uppercase tracking-widest">Labores Silviculturales</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Registrar Mantenimiento</h1>
            </div>
            <a href="<?= BASE_URL ?>operario/actividades/index.php" class="btn-forest-secondary text-xs py-2 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl text-xs text-rose-800 flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="forest-card p-6 sm:p-8 max-w-2xl">
            <form method="POST" action="crear.php" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div>
                    <label for="id_individuo" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Individuo Forestal *</label>
                    <select name="id_individuo" id="id_individuo" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                        <option value="">Seleccione el espécimen...</option>
                        <?php foreach ($individuos as $ind): ?>
                            <option value="<?= $ind['id_individuo'] ?>"><?= htmlspecialchars($ind['codigo_placa']) ?> — <?= htmlspecialchars($ind['nombre_comun']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="tipo_actividad" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tipo de Intervención *</label>
                    <select name="tipo_actividad" id="tipo_actividad" required class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none">
                        <option value="Poda">Poda (Formativa / Sanitaria / Realce)</option>
                        <option value="Fumigación">Fumigación / Control Biológico</option>
                        <option value="Fertilización">Fertilización / Enmiendas de suelo</option>
                        <option value="Riego">Riego e Hidratación</option>
                        <option value="Tutoreo">Tutoreo / Enderezamiento</option>
                        <option value="Trasplante">Trasplante / Reubicación</option>
                        <option value="Tala">Tala por Riesgo / Compensación</option>
                        <option value="Otro">Otro mantenimiento</option>
                    </select>
                </div>

                <div>
                    <label for="descripcion" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descripción del Trabajo Realizado *</label>
                    <textarea name="descripcion" id="descripcion" required rows="3" placeholder="Detalle de herramientas usadas, químicos, retiro de biomasa..." class="w-full p-3 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-600 outline-none"></textarea>
                </div>

                <div>
                    <label for="foto_evidencia" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Evidencia Fotográfica (Opcional)</label>
                    <input type="file" name="foto_evidencia" id="foto_evidencia" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                    <a href="<?= BASE_URL ?>operario/actividades/index.php" class="btn-forest-secondary text-xs py-2.5 px-4">Cancelar</a>
                    <button type="submit" class="btn-forest-primary text-xs py-2.5 px-5">
                        <i data-lucide="check" class="w-4 h-4"></i> Registrar Actividad
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
