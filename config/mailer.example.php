<?php
/**
 * ForestID / ForestSRD - Plantilla de credenciales de correo para Hostinger
 * (SIN VALORES REALES)
 *
 * INSTRUCCIONES:
 *   1. Copie este archivo como «config/mailer.php»:
 *        cp config/mailer.example.php config/mailer.php
 *   2. Reemplace los placeholders con los datos de su cuenta de correo de
 *      Hostinger (Buzones de correo > configurar cliente de correo).
 *   3. config/mailer.php está en .gitignore: NO se sube a Git.
 *
 * Parámetros de Hostinger (SMTP): host smtp.hostinger.com, puerto 465 con SSL.
 * Si SMTP_HOST queda vacío, la aplicación usa el respaldo mail() de PHP.
 *
 * NOTA: esta plantilla usa el sufijo «.example.php» (plantilla genérica).
 * El repositorio también conserva «config/mailer.ejemplo.php»; ambos son
 * plantillas y ninguno debe editarse con credenciales reales.
 */

// ---- Correo saliente (SMTP de Hostinger) ----
define('CORREO_REMITENTE', 'no-reply@forestid.cfmasoft.com'); // remitente visible
define('SMTP_HOST',       'smtp.hostinger.com'); // servidor SMTP de Hostinger
define('SMTP_PORT',       465);                  // 465 = SSL
define('SMTP_CIFRADO',    'ssl');                // 'ssl' (465) | 'tls' (587)
define('SMTP_USUARIO',    'SMTP_USUARIO');       // buzón completo, p. ej. usuario@sudominio.com
define('SMTP_CONTRASENA', 'SMTP_CONTRASENA');    // contraseña del buzón

// ---- WhatsApp Business Cloud API (opcional) ----
// Si se deja vacío, el sistema ofrece enlace wa.me manual.
define('WHATSAPP_API_TOKEN',    'WHATSAPP_API_TOKEN');
define('WHATSAPP_API_PHONE_ID', 'WHATSAPP_API_PHONE_ID');
