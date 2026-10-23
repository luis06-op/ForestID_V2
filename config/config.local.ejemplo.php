<?php
/**
 * ForestID - Plantilla de configuración local
 *
 * INSTRUCCIONES:
 *   1. Copie este archivo como config/config.local.php:
 *        cp config/config.local.ejemplo.php config/config.local.php
 *   2. Rellene los datos de su servidor MySQL.
 *   3. config/config.local.php está en .gitignore: no se sube a GitHub.
 *
 * En producción use un usuario MySQL con permisos mínimos (SELECT, INSERT,
 * UPDATE, DELETE) sobre forestsrd_db; nunca root.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'forestsrd_db');
define('DB_USER', 'forestsrd_app');
define('DB_PASS', 'CAMBIA_ESTA_CLAVE');

// URL canónica pública usada en los códigos QR y enlaces compartidos.
// En producción defínala con el dominio real (con barra final opcional):
// define('APP_URL', 'https://forestid.emincut.com/');
// En local, si se omite, se detecta automáticamente desde la petición.
