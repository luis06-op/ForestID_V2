<?php
/**
 * ForestID / ForestSRD - Funciones Auxiliares y Seguridad
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Sanitiza recursivamente cadenas de texto y arrays para prevenir XSS
 */
function sanear($dato) {
    if (is_array($dato)) {
        return array_map('sanear', $dato);
    }
    if (is_null($dato)) {
        return null;
    }
    return htmlspecialchars(trim((string)$dato), ENT_QUOTES, 'UTF-8');
}

/**
 * Genera un UUID v4 seguro en PHP
 */
function generar_uuid(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // versión 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variante RFC 4122
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Genera un código de placa único y legible (ej. EBG-PARQ-042)
 */
function generar_codigo_placa(PDO $pdo, string $prefijo_zona = 'EBG'): string {
    $prefijo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($prefijo_zona, 0, 4)));
    if (empty($prefijo)) {
        $prefijo = 'EBG';
    }

    $intentos = 0;
    do {
        $numero = str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $codigo = "{$prefijo}-{$numero}";

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `individuos_forestales` WHERE `codigo_placa` = :codigo");
        $stmt->execute([':codigo' => $codigo]);
        $existe = (int)$stmt->fetchColumn() > 0;
        $intentos++;
    } while ($existe && $intentos < 50);

    return $codigo;
}

/**
 * Registra una acción en la bitácora de auditoría (logs_sistema)
 */
function registrar_log(PDO $pdo, ?string $id_usuario, string $accion, string $modulo, array $detalle = []): bool {
    try {
        $ip_origen = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $detalle_json = json_encode($detalle, JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("
            INSERT INTO `logs_sistema` (`id_log`, `id_usuario`, `accion`, `modulo_afectado`, `detalle`, `ip_origen`, `fecha_registro`)
            VALUES (UUID(), :id_usuario, :accion, :modulo, :detalle, :ip, NOW())
        ");
        return $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':accion'     => $accion,
            ':modulo'     => $modulo,
            ':detalle'    => $detalle_json,
            ':ip'         => $ip_origen
        ]);
    } catch (PDOException $e) {
        error_log("Error al registrar log de auditoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Crea y almacena un token CSRF en la sesión
 */
function crear_token_csrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF enviado por formulario
 */
function validar_token_csrf(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Retorna el HTML del badge visual con color e ícono según el estado fitosanitario
 */
function obtener_badge_estado(string $estado): string {
    $estado = trim($estado);
    switch ($estado) {
        case 'Sano':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span> Sano
                    </span>';
        case 'En observación':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-300 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-amber-600 animate-pulse"></span> En observación
                    </span>';
        case 'Enfermo':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-300 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-rose-600"></span> Enfermo
                    </span>';
        case 'Plagado':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-300 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-purple-600"></span> Plagado
                    </span>';
        case 'En riesgo':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-400 shadow-sm animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-red-600"></span> En riesgo crítico
                    </span>';
        case 'Muerto en pie':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-stone-200 text-stone-700 border border-stone-300 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-stone-500"></span> Muerto en pie
                    </span>';
        case 'Talado':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-zinc-200 text-zinc-600 border border-zinc-300 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-zinc-400"></span> Talado
                    </span>';
        default:
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-300">
                        ' . htmlspecialchars($estado) . '
                    </span>';
    }
}

/**
 * Formatea fechas a formato legible en español
 */
function formatear_fecha(?string $fecha, bool $con_hora = false): string {
    if (empty($fecha)) {
        return 'No registrada';
    }
    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return htmlspecialchars($fecha);
    }

    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    $dia = date('j', $timestamp);
    $mes = $meses[(int)date('n', $timestamp)];
    $anio = date('Y', $timestamp);

    if ($con_hora) {
        $hora = date('h:i A', $timestamp);
        return "{$dia} de {$mes}, {$anio} ({$hora})";
    }
    return "{$dia} de {$mes}, {$anio}";
}

/**
 * Formatea cantidades numéricas con decimales y sufijo opcional
 */
function formatear_numero($numero, int $decimales = 2, string $sufijo = ''): string {
    if (!is_numeric($numero)) {
        return '0.00' . ($sufijo ? ' ' . $sufijo : '');
    }
    return number_format((float)$numero, $decimales, '.', ',') . ($sufijo ? ' ' . $sufijo : '');
}

/**
 * Manejo seguro de subida de imágenes con validación de tipo MIME real y tamaño
 */
function subir_imagen_segura(array $archivo, string $subdirectorio = 'individuos', int $max_mb = 5): array {
    if (!isset($archivo['error']) || is_array($archivo['error'])) {
        return ['ok' => false, 'error' => 'Parámetros de subida inválidos.'];
    }

    if ($archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'ruta' => null];
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Error al subir el archivo (Código: ' . $archivo['error'] . ').'];
    }

    if ($archivo['size'] > $max_mb * 1024 * 1024) {
        return ['ok' => false, 'error' => "El tamaño de la imagen supera el límite permitido de {$max_mb} MB."];
    }

    // Validar tipo MIME real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    $mimes_permitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!array_key_exists($mime, $mimes_permitidos)) {
        return ['ok' => false, 'error' => 'Formato no permitido. Solo se aceptan imágenes JPG, PNG o WEBP.'];
    }

    $extension = $mimes_permitidos[$mime];
    $nombre_unico = generar_uuid() . '.' . $extension;

    $directorio_destino = ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subdirectorio;
    if (!is_dir($directorio_destino)) {
        mkdir($directorio_destino, 0755, true);
    }

    $ruta_absoluta = $directorio_destino . DIRECTORY_SEPARATOR . $nombre_unico;
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_absoluta)) {
        return ['ok' => false, 'error' => 'No se pudo guardar la imagen en el servidor.'];
    }

    $ruta_relativa = "assets/uploads/{$subdirectorio}/{$nombre_unico}";
    return ['ok' => true, 'ruta' => $ruta_relativa];
}

/**
 * Mensajes Flash (toasts)
 */
function set_flash(string $tipo, string $mensaje): void {
    $_SESSION['flash_msg'] = [
        'tipo'    => $tipo, // success, error, warning, info
        'mensaje' => $mensaje
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash_msg'])) {
        $flash = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $flash;
    }
    return null;
}

function render_flash(): string {
    $flash = get_flash();
    if (!$flash) {
        return '';
    }

    $tipo = $flash['tipo'];
    $mensaje = htmlspecialchars($flash['mensaje']);

    $estilos = [
        'success' => ['bg' => 'bg-emerald-50 border-emerald-500 text-emerald-900', 'icon' => 'check-circle', 'badge' => 'Éxito'],
        'error'   => ['bg' => 'bg-rose-50 border-rose-500 text-rose-900', 'icon' => 'alert-circle', 'badge' => 'Error'],
        'warning' => ['bg' => 'bg-amber-50 border-amber-500 text-amber-900', 'icon' => 'alert-triangle', 'badge' => 'Atención'],
        'info'    => ['bg' => 'bg-sky-50 border-sky-500 text-sky-900', 'icon' => 'info', 'badge' => 'Información']
    ];

    $cfg = $estilos[$tipo] ?? $estilos['info'];

    return "
    <div id='flash-toast' class='fixed bottom-5 right-5 z-50 max-w-md w-full bg-white border-l-4 {$cfg['bg']} p-4 rounded-xl shadow-xl transition-all duration-300 transform translate-y-0 opacity-100 flex items-start justify-between'>
        <div class='flex items-start gap-3'>
            <div class='mt-0.5 font-bold text-sm'>
                <span class='block font-semibold uppercase tracking-wider text-xs'>{$cfg['badge']}</span>
                <p class='font-normal text-sm mt-0.5 leading-snug'>{$mensaje}</p>
            </div>
        </div>
        <button onclick=\"document.getElementById('flash-toast').remove()\" class='text-gray-400 hover:text-gray-600 p-1'>
            <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'/></svg>
        </button>
    </div>
    <script>
        setTimeout(() => {
            const toast = document.getElementById('flash-toast');
            if (toast) {
                toast.classList.add('opacity-0', 'translate-y-4');
                setTimeout(() => toast.remove(), 400);
            }
        }, 5000);
    </script>
    ";
}
