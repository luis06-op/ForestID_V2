<?php
/**
 * ForestID - Plantilla de configuración para Hostinger (SIN VALORES REALES)
 *
 * INSTRUCCIONES:
 *   1. Copie este archivo como «config/config.local.php»:
 *        cp config/config.local.example.php config/config.local.php
 *   2. Rellene los placeholders con los datos de su panel de Hostinger
 *      (Base de datos MySQL y el subdominio con SSL).
 *   3. config/config.local.php está en .gitignore: NO se sube a Git.
 *
 * En producción use un usuario MySQL con permisos mínimos (SELECT, INSERT,
 * UPDATE, DELETE) sobre la base de ForestID; nunca root.
 *
 * NOTA: esta plantilla usa el sufijo «.example.php» (plantilla genérica).
 * El repositorio también conserva «config/config.local.ejemplo.php» con las
 * mismas claves; ambos archivos son plantillas y ninguno debe editarse con
 * credenciales reales.
 */

// ---- Base de datos (Hostinger > Sitios web > Base de datos) ----
define('DB_HOST', 'DB_HOST');          // p. ej. localhost o el host que indique Hostinger
define('DB_NAME', 'DB_NAME');          // nombre de la base de datos creada en el panel
define('DB_USER', 'DB_USER');          // usuario MySQL con permisos mínimos
define('DB_PASS', 'DB_PASS');          // contraseña del usuario MySQL

// ---- Aplicación ----
// URL canónica pública, con HTTPS y barra final. Se codifica en los códigos QR
// y en los enlaces de correo, por lo que debe definirse ANTES de generar placas.
define('APP_URL', 'APP_URL');          // p. ej. https://forestid.emincut.com/ (reemplace el placeholder)

// Opcional: lista blanca adicional de hosts separados por coma (p. ej. IP del servidor).
// define('FORESTID_HOSTS', '127.0.0.1');
