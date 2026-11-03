<?php
/**
 * ForestID / ForestSRD - Plantilla de credenciales de envío
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 *
 * INSTRUCCIONES:
 *  1. Copie este archivo como «config/mailer.php»:
 *     copy config\mailer.ejemplo.php config\mailer.php
 *
 *  2. No modifique este archivo: la copia real NO se sube a Git
 *     (está en .gitignore) para que las contraseñas no vayan al repositorio.
 *
 *  3. Rellene los valores del SMTP con los de su proveedor. Ejemplos comunes:
 *
 *     Gmail (con contraseña de aplicación, 16 caracteres):
 *       SMTP_HOST gmail.com  →  smtp.gmail.com
 *       SMTP_PORT            →  587
 *       SMTP_CIFRADO         →  tls
 *
 *     Outlook / Microsoft 365:
 *       SMTP_HOST            →  smtp.office365.com
 *       SMTP_PORT            →  587
 *       SMTP_CIFRADO         →  tls
 *
 *     Hosting propio (cpanel@emincut.com):
 *       SMTP_HOST            →  mail.emincut.com
 *       SMTP_PORT            →  465
 *       SMTP_CIFRADO         →  ssl
 *
 *  4. Si SMTP_HOST queda vacío, la aplicación usa el respaldo mail() de PHP.
 */

// ---- Correo (SMTP) ----
define('CORREO_REMITENTE', 'no-reply@forestid.cfmasoft.com'); // remitente visible
define('SMTP_HOST',        'smtp.hostinger.com'); // hosting: smtp.hostinger.com (Hostinger)
define('SMTP_PORT',        465);                  // 465 = SSL
define('SMTP_CIFRADO',     'ssl');              // 'tls' (587) | 'ssl' (465) | '' (sin cifrado)
define('SMTP_USUARIO',     '');          // cuenta completa remitente | vacío = mail() de respaldo
define('SMTP_CONTRASENA',  '');          // contraseña o contraseña de aplicación

// Si deja SMTP_USUARIO vacío, se usa CORREO_REMITENTE definido en notificaciones.php.

// ---- WhatsApp Business Cloud API (opcional) ----
// Obténgalas en https://developers.facebook.com > WhatsApp > Configuración de la API.
define('WHATSAPP_API_TOKEN',    '');   // token permanente de acceso, pw-... o EAAG...
define('WHATSAPP_API_PHONE_ID', '');   // ID del número de teléfono de WhatsApp Business
