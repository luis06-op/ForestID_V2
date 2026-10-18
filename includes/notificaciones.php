<?php
/**
 * ForestID / ForestSRD - Notificaciones de Credenciales (Correo y WhatsApp)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 *
 * Envía al usuario nuevo su contraseña de acceso por correo electrónico
 * y por WhatsApp (número móvil registrado).
 *
 * Configuración:
 *  - CORREO_REMITENTE: dirección desde la que se envían los correos.
 *  - SMTP/WhatsApp: XAMPP no incluye servidor de correo por defecto; si
 *    mail() no está disponible, se registra el intento y se ofrece un
 *    enlace wa.me / mailto: de respaldo para envío manual.
 *  - WHATSAPP_API_TOKEN / WHATSAPP_API_PHONE_ID: opcional. Si se definen,
 *    el envío por WhatsApp es automático vía WhatsApp Business Cloud API.
 */

// Remitente institucional
if (!defined('CORREO_REMITENTE')) {
    define('CORREO_REMITENTE', 'no-reply@emincut.com');
}

// API de WhatsApp Business (opcional — dejar vacío para enlace wa.me manual)
if (!defined('WHATSAPP_API_TOKEN')) {
    define('WHATSAPP_API_TOKEN', getenv('WHATSAPP_API_TOKEN') ?: '');
}
if (!defined('WHATSAPP_API_PHONE_ID')) {
    define('WHATSAPP_API_PHONE_ID', getenv('WHATSAPP_API_PHONE_ID') ?: '');
}

/**
 * Construye el texto de credenciales compartido para correo y WhatsApp
 */
function texto_credenciales(string $nombres, string $apellidos, string $correo, string $password, string $rol): string {
    return "Hola {$nombres} {$apellidos},\n\n"
         . "Tu cuenta en ForestID (Emincut S.A.S.) ha sido creada.\n\n"
         . "Correo: {$correo}\n"
         . "Contraseña: {$password}\n"
         . "Rol: {$rol}\n\n"
         . "Ingresa en: " . BASE_URL . "auth/login.php\n\n"
         . "Por seguridad, cambia tu contraseña después del primer ingreso.\n"
         . "— ForestID | Emincut S.A.S. | El Bagre, Antioquia";
}

/**
 * Envía las credenciales por correo electrónico.
 * @return array{ok: bool, detalle: string}
 */
function enviar_correo_credenciales(string $correo, string $nombres, string $apellidos, string $password, string $rol): array {
    $asunto = 'ForestID — Tus credenciales de acceso';
    $mensaje = texto_credenciales($nombres, $apellidos, $correo, $password, $rol);

    $headers  = "From: ForestID Emincut <" . CORREO_REMITENTE . ">\r\n";
    $headers .= "Reply-To: " . CORREO_REMITENTE . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $resultado = @mail($correo, '=?UTF-8?B?' . base64_encode($asunto) . '?=', wordwrap($mensaje, 70), $headers);

    if ($resultado) {
        return ['ok' => true, 'detalle' => "Correo enviado a {$correo}."];
    }

    error_log("[ForestID] Fallo de envío de correo de credenciales a: {$correo}");
    return ['ok' => false, 'detalle' => 'No fue posible enviar el correo automáticamente (verifique el servidor de correo de XAMPP).'];
}

/**
 * Normaliza un número colombiano a formato internacional WhatsApp (57...)
 */
function normalizar_telefono_whatsapp(string $telefono): string {
    $digitos = preg_replace('/\D+/', '', $telefono);
    if ($digitos === '') return '';
    // Quitar prefijo 00
    $digitos = preg_replace('/^00/', '', $digitos);
    // Colombia: 10 dígitos (3...) -> 57 + número
    if (strlen($digitos) === 10 && $digitos[0] === '3') {
        $digitos = '57' . $digitos;
    } elseif (strlen($digitos) === 11 && substr($digitos, 0, 2) === '57') {
        // ya tiene prefijo país
    } elseif (strlen($digitos) === 12 && substr($digitos, 0, 3) === '573') {
        // ya completo
    }
    return $digitos;
}

/**
 * Enlace wa.me con el mensaje pre-cargado (respaldo manual / automático si no hay API)
 */
function enlace_whatsapp_credenciales(string $telefono, string $nombres, string $apellidos, string $correo, string $password, string $rol): ?string {
    $numero = normalizar_telefono_whatsapp($telefono);
    if ($numero === '') return null;
    $texto = str_replace("\n", "%0A", texto_credenciales($nombres, $apellidos, $correo, $password, $rol));
    return "https://wa.me/{$numero}?text={$texto}";
}

/**
 * Envío automático por WhatsApp Business Cloud API (solo si hay token configurado).
 * @return array{ok: bool, detalle: string}
 */
function enviar_whatsapp_credenciales(string $telefono, string $nombres, string $apellidos, string $correo, string $password, string $rol): array {
    $numero = normalizar_telefono_whatsapp($telefono);
    if ($numero === '') {
        return ['ok' => false, 'detalle' => 'El usuario no tiene número de WhatsApp registrado.'];
    }

    if (WHATSAPP_API_TOKEN === '' || WHATSAPP_API_PHONE_ID === '') {
        return ['ok' => false, 'detalle' => 'API de WhatsApp sin configurar: use el botón de envío manual.'];
    }

    $url = 'https://graph.facebook.com/v20.0/' . WHATSAPP_API_PHONE_ID . '/messages';
    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $numero,
        'type'              => 'text',
        'text'              => ['body' => texto_credenciales($nombres, $apellidos, $correo, $password, $rol)]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WHATSAPP_API_TOKEN,
            'Content-Type: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15
    ]);
    $respuesta = curl_exec($ch);
    $codigo    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($codigo >= 200 && $codigo < 300) {
        return ['ok' => true, 'detalle' => "WhatsApp enviado a +{$numero}."];
    }

    error_log("[ForestID] Fallo WhatsApp API ({$codigo}): " . substr((string)$respuesta, 0, 300));
    return ['ok' => false, 'detalle' => 'No fue posible enviar el WhatsApp automático: use el botón de envío manual.'];
}
