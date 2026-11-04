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
// URL canónica pública, con HTTPS y barra final. Es la fuente de verdad de los
// QR y de los enlaces de correo: defínala siempre en producción.
define('APP_URL', 'https://forestid.cfmasoft.com/');

// Lista blanca de Host: la aplicación acepta únicamente
// forestid.cfmasoft.com, www.forestid.cfmasoft.com, hosts locales, el host de
// APP_URL y los declarados aquí. Cualquier otro Host se sustituye por APP_URL
// o se rechaza con 400. Use FORESTID_HOSTS solo para staging u otro dominio
// legítimo, separado por comas:
// define('FORESTID_HOSTS', 'staging.cfmasoft.com,192.168.1.50');
