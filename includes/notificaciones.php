<?php
/**
 * ForestID / ForestSRD - Notificaciones de Credenciales (Correo y WhatsApp)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 *
 * Envía al usuario nuevo su contraseña de acceso por correo electrónico
 * y por WhatsApp (número móvil registrado).
 *
 * Configuración (véase config/mailer.ejemplo.php):
 *  - CORREO_REMITENTE: dirección desde la que se envían los correos.
 *  - SMTP real: si existe config/mailer.php con SMTP_HOST definido, el
 *    correo se envía vía SMTP con PHPMailer. Sin SMTP, se usa mail() de
 *    respaldo (XAMPP no incluye servidor de correo) y se ofrece un
 *    enlace wa.me / mailto: para envío manual.
 *  - WHATSAPP_API_TOKEN / WHATSAPP_API_PHONE_ID: si se definen
 *    (variables de entorno o config/mailer.php), el envío por WhatsApp
 *    es automático vía WhatsApp Business Cloud API.
 */

// Credenciales locales de envío (archivo privado, no versionado en Git)
if (is_file(__DIR__ . '/../config/mailer.php')) {
    require_once __DIR__ . '/../config/mailer.php';
}

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
 * Cuerpo HTML del correo de credenciales (identidad visual Emincut)
 */
function cuerpo_html_credenciales(string $nombres, string $apellidos, string $correo, string $password, string $rol): string {
    $url_login = BASE_URL . 'auth/login.php';
    $html = '<!DOCTYPE html>
<html lang="es"><body style="margin:0;padding:24px;background:#FBFAF6;font-family:Arial,Helvetica,sans-serif;color:#22371C;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #E7EADD;border-radius:14px;">
    <tr><td style="padding:24px 28px;border-bottom:4px solid #E9A22E;">
      <p style="margin:0;font-size:12px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;color:#E9A22E;">Emincut S.A.S. — El Bagre, Antioquia</p>
      <h1 style="margin:6px 0 0;font-size:20px;">Credenciales de acceso ForestID</h1>
    </td></tr>
    <tr><td style="padding:24px 28px;font-size:14px;line-height:1.6;">
      <p style="margin:0 0 14px;">Hola <strong>' . htmlspecialchars($nombres . ' ' . $apellidos) . '</strong>,</p>
      <p style="margin:0 0 16px;">Tu cuenta ha sido creada. Estos son tus datos para ingresar:</p>
      <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#F2B63222;border:1px solid #F2DCAC;border-radius:10px;">
        <tr><td style="padding:14px 18px;font-size:14px;">
          <p style="margin:0 0 6px;"><strong>Correo:</strong> ' . htmlspecialchars($correo) . '</p>
          <p style="margin:0 0 6px;"><strong>Contraseña:</strong> <span style="font-family:Consolas,monospace;background:#fff;border:1px solid #F2DCAC;border-radius:4px;padding:2px 8px;">' . htmlspecialchars($password) . '</span></p>
          <p style="margin:0;"><strong>Rol:</strong> ' . htmlspecialchars($rol) . '</p>
        </td></tr>
      </table>
      <p style="margin:20px 0;"><a href="' . $url_login . '" style="display:inline-block;background:#E9A22E;color:#22371C;font-weight:bold;font-size:14px;padding:10px 22px;border-radius:10px;text-decoration:none;">Ingresar a la plataforma</a></p>
      <p style="margin:0;color:#5a6b4f;font-size:12px;">Por seguridad, cambia tu contraseña después del primer ingreso.</p>
    </td></tr>
    <tr><td style="padding:16px 28px;border-top:1px solid #E7EADD;background:#FBFAF6;border-radius:0 0 14px 14px;font-size:11px;color:#5a6b4f;">
      ForestID | Emincut S.A.S. | El Bagre, Antioquia
    </td></tr>
  </table>
</body></html>';
    return $html;
}

/**
 * Envía las credenciales por correo electrónico.
 * Con SMTP configurado (config/mailer.php) usa PHPMailer; si no, mail() de respaldo.
 * @return array{ok: bool, detalle: string}
 */
function enviar_correo_credenciales(string $correo, string $nombres, string $apellidos, string $password, string $rol): array {
    $asunto = 'ForestID — Tus credenciales de acceso';
    $mensaje = texto_credenciales($nombres, $apellidos, $correo, $password, $rol);

    $remitente = (defined('SMTP_USUARIO') && SMTP_USUARIO !== '') ? SMTP_USUARIO : CORREO_REMITENTE;
    $puede_smtp = defined('SMTP_HOST') && SMTP_HOST !== '' && defined('SMTP_USUARIO') && SMTP_USUARIO !== '';

    if ($puede_smtp) {
        try {
            $base = __DIR__ . '/../vendor/phpmailer/src';
            require_once $base . '/Exception.php';
            require_once $base . '/PHPMailer.php';
            require_once $base . '/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->isHTML(true);
            $mail->Host       = SMTP_HOST;
            $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USUARIO;
            $mail->Password   = SMTP_CONTRASENA;
            $mail->Timeout    = 15;
            $cifrado = defined('SMTP_CIFRADO') ? strtolower((string)SMTP_CIFRADO) : 'tls';
            if ($cifrado === 'ssl') {
                $mail->SMTPSecure = 'ssl';
            } elseif ($cifrado === 'tls') {
                $mail->SMTPSecure = 'tls';
            }
            $mail->setFrom($remitente, 'ForestID Emincut');
            $mail->addReplyTo($remitente, 'ForestID Emincut');
            $mail->addAddress($correo, trim($nombres . ' ' . $apellidos));
            $mail->Subject = $asunto;
            $mail->altBody = $mensaje;
            $mail->Body    = cuerpo_html_credenciales($nombres, $apellidos, $correo, $password, $rol);
            $mail->send();

            return ['ok' => true, 'detalle' => "Correo enviado por SMTP (" . SMTP_HOST . ") a {$correo}."];
        } catch (Throwable $e) {
            error_log('[ForestID] Fallo SMTP a ' . $correo . ': ' . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()));
            return ['ok' => false, 'detalle' => 'No fue posible enviar el correo vía SMTP: revise config/mailer.php (host, puerto, usuario y contraseña).'];
        }
    }

    $headers  = "From: ForestID Emincut <" . CORREO_REMITENTE . ">\r\n";
    $headers .= "Reply-To: " . CORREO_REMITENTE . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $resultado = @mail($correo, '=?UTF-8?B?' . base64_encode($asunto) . '?=', wordwrap($mensaje, 70), $headers);

    if ($resultado) {
        return ['ok' => true, 'detalle' => "Correo enviado a {$correo}."];
    }

    error_log("[ForestID] Fallo de envío de correo de credenciales a: {$correo}");
    return ['ok' => false, 'detalle' => 'No fue posible enviar el correo automáticamente: configure SMTP en config/mailer.php o use mailto: de respaldo.'];
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
