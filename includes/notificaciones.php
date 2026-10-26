<?php
/**
 * ForestID / ForestSRD - Notificaciones de Credenciales (Correo y WhatsApp)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 *
 * Envía al usuario nuevo un enlace de activación de un solo uso por correo
 * electrónico y por WhatsApp (número móvil registrado). Nunca se envían
 * contraseñas en texto plano (A7).
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
 * Texto del enlace de activación (nunca incluye contraseñas).
 */
function texto_activacion(string $nombres, string $apellidos, string $enlace, int $horas): string {
    return "Hola {$nombres} {$apellidos},\n\n"
         . "Se creó tu cuenta en ForestID (Emincut S.A.S.).\n\n"
         . "Para activarla y crear tu contraseña, abre este enlace (válido durante {$horas} horas y de un solo uso):\n"
         . "{$enlace}\n\n"
         . "Si no esperabas este mensaje, comunícate con el administrador de ForestID.\n"
         . "— ForestID | Emincut S.A.S. | El Bagre, Antioquia";
}

/**
 * Cuerpo HTML del correo de activación (identidad visual Emincut).
 */
function cuerpo_html_activacion(string $nombres, string $apellidos, string $enlace, int $horas): string {
    $nombre_html = htmlspecialchars(trim($nombres . ' ' . $apellidos), ENT_QUOTES, 'UTF-8');
    $enlace_html = htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8');

    return '<!DOCTYPE html>
<html lang="es"><body style="margin:0;padding:24px;background:#FBFAF6;font-family:Arial,Helvetica,sans-serif;color:#22371C;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #E7EADD;border-radius:14px;">
    <tr><td style="padding:24px 28px;border-bottom:4px solid #E9A22E;">
      <p style="margin:0;font-size:12px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;color:#E9A22E;">Emincut S.A.S. — El Bagre, Antioquia</p>
      <h1 style="margin:6px 0 0;font-size:20px;">Activa tu cuenta ForestID</h1>
    </td></tr>
    <tr><td style="padding:24px 28px;font-size:14px;line-height:1.6;">
      <p style="margin:0 0 14px;">Hola <strong>' . $nombre_html . '</strong>,</p>
      <p style="margin:0 0 18px;">Tu cuenta ha sido creada. Para activarla, crea tu propia contraseña con el siguiente botón:</p>
      <p style="margin:0 0 20px;"><a href="' . $enlace_html . '" style="display:inline-block;background:#E9A22E;color:#22371C;font-weight:bold;font-size:14px;padding:10px 22px;border-radius:10px;text-decoration:none;">Crear mi contraseña</a></p>
      <p style="margin:0;color:#5a6b4f;font-size:12px;">El enlace es válido durante ' . (int)$horas . ' horas y solo puede usarse una vez. Si no esperabas este mensaje, ignóralo o comunícate con el administrador.</p>
    </td></tr>
    <tr><td style="padding:16px 28px;border-top:1px solid #E7EADD;background:#FBFAF6;border-radius:0 0 14px 14px;font-size:11px;color:#5a6b4f;">
      ForestID | Emincut S.A.S. | El Bagre, Antioquia
    </td></tr>
  </table>
</body></html>';
}

/**
 * Envía un correo HTML usando SMTP (PHPMailer) o el respaldo mail() de PHP.
 * @return array{ok: bool, detalle: string}
 */
function enviar_correo_html(string $correo, string $nombre_destino, string $asunto, string $texto, string $html): array {
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
            $mail->addAddress($correo, $nombre_destino);
            $mail->Subject = $asunto;
            $mail->altBody = $texto;
            $mail->Body    = $html;
            $mail->send();

            return ['ok' => true, 'detalle' => "Correo enviado por SMTP (" . SMTP_HOST . ") a {$correo}."];
        } catch (Throwable $e) {
            error_log('[ForestID] Fallo SMTP a ' . $correo . ': ' . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()));
            return ['ok' => false, 'detalle' => 'No fue posible enviar el correo vía SMTP: revise config/mailer.php (host, puerto, usuario y contraseña).'];
        }
    }

    $headers  = "From: ForestID Emincut <" . CORREO_REMITENTE . ">\r\n";
    $headers .= "Reply-To: " . CORREO_REMITENTE . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $resultado = @mail($correo, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $html, $headers);

    if ($resultado) {
        return ['ok' => true, 'detalle' => "Correo enviado a {$correo}."];
    }

    error_log("[ForestID] Fallo de envío de correo a: {$correo}");
    return ['ok' => false, 'detalle' => 'No fue posible enviar el correo automáticamente: configure SMTP en config/mailer.php.'];
}

/**
 * Envía el enlace de activación de una cuenta nueva (A7).
 * @return array{ok: bool, detalle: string}
 */
function enviar_correo_activacion(string $correo, string $nombres, string $apellidos, string $enlace, int $horas): array {
    return enviar_correo_html(
        $correo,
        trim($nombres . ' ' . $apellidos),
        'ForestID — Activa tu cuenta',
        texto_activacion($nombres, $apellidos, $enlace, $horas),
        cuerpo_html_activacion($nombres, $apellidos, $enlace, $horas)
    );
}

/**
 * Envía el enlace de restablecimiento de contraseña (válido 1 hora).
 * @return array{ok: bool, detalle: string}
 */
function enviar_correo_recuperacion(string $correo, string $nombres, string $enlace): array {
    $asunto = 'ForestID — Restablece tu contraseña';
    $texto = "Hola {$nombres},\n\n"
           . "Recibimos una solicitud para restablecer la contraseña de tu cuenta en ForestID.\n\n"
           . "Abre este enlace (válido durante 1 hora y de un solo uso):\n{$enlace}\n\n"
           . "Si no solicitaste este cambio, ignora este mensaje: tu contraseña actual seguirá funcionando.\n\n"
           . "— ForestID | Emincut S.A.S. | El Bagre, Antioquia";

    $enlace_html = htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8');
    $nombre_html = htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8');
    $html = '<!DOCTYPE html><html lang="es"><body style="margin:0;padding:24px;background:#FBFAF6;font-family:Arial,Helvetica,sans-serif;color:#22371C;">'
          . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #E7EADD;border-radius:14px;">'
          . '<tr><td style="padding:24px 28px;border-bottom:4px solid #E9A22E;">'
          . '<p style="margin:0;font-size:12px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;color:#E9A22E;">Emincut S.A.S. — El Bagre, Antioquia</p>'
          . '<h1 style="margin:6px 0 0;font-size:20px;">Restablecer contraseña</h1></td></tr>'
          . '<tr><td style="padding:24px 28px;font-size:14px;line-height:1.6;">'
          . '<p style="margin:0 0 14px;">Hola <strong>' . $nombre_html . '</strong>,</p>'
          . '<p style="margin:0 0 18px;">Recibimos una solicitud para restablecer la contraseña de tu cuenta en ForestID.</p>'
          . '<p style="margin:0 0 20px;"><a href="' . $enlace_html . '" style="display:inline-block;background:#E9A22E;color:#22371C;font-weight:bold;font-size:14px;padding:10px 22px;border-radius:10px;text-decoration:none;">Crear nueva contraseña</a></p>'
          . '<p style="margin:0;color:#5a6b4f;font-size:12px;">El enlace es válido durante 1 hora y solo puede usarse una vez. Si no solicitaste este cambio, ignora este mensaje.</p>'
          . '</td></tr><tr><td style="padding:16px 28px;border-top:1px solid #E7EADD;background:#FBFAF6;border-radius:0 0 14px 14px;font-size:11px;color:#5a6b4f;">ForestID | Emincut S.A.S. | El Bagre, Antioquia</td></tr>'
          . '</table></body></html>';

    return enviar_correo_html($correo, $nombres, $asunto, $texto, $html);
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
 * Enlace wa.me con el mensaje de activación pre-cargado.
 */
function enlace_whatsapp_activacion(string $telefono, string $nombres, string $apellidos, string $enlace, int $horas): ?string {
    $numero = normalizar_telefono_whatsapp($telefono);
    if ($numero === '') return null;
    $texto = rawurlencode(texto_activacion($nombres, $apellidos, $enlace, $horas));
    return "https://wa.me/{$numero}?text={$texto}";
}

/**
 * Envío automático del enlace de activación por WhatsApp Business Cloud API.
 * @return array{ok: bool, detalle: string}
 */
function enviar_whatsapp_activacion(string $telefono, string $nombres, string $apellidos, string $enlace, int $horas): array {
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
        'text'              => ['body' => texto_activacion($nombres, $apellidos, $enlace, $horas)]
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
