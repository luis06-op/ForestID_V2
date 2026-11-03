# ForestID — Despliegue previsto en Hostinger

> **Estado:** despliegue objetivo para la feria FISAD en el dominio **Forestid.cfmasoft.com** (HTTPS). El entorno local XAMPP sigue siendo la referencia para desarrollo.

## Situación actual

El despliegue de producción para la feria FISAD se realizará en el dominio **https://Forestid.cfmasoft.com/**. Las credenciales reales de BD/SMTP y el certificado SSL se configuran en el servidor (no se versionan). La referencia de desarrollo sigue siendo la instalación local XAMPP.

Mientras el acuerdo no se cierre, la referencia válida sigue siendo la instalación local con XAMPP descrita en el [README principal](README.md).

## Pendiente de definir

- Dominio definitivo: `https://Forestid.cfmasoft.com/` (configurar DNS A/CNAME hacia el hosting y emitir SSL).
- Plan de Hostinger (compartido o VPS/Cloud) y ubicación del centro de datos.
- Credenciales de la base de datos y del servidor SMTP.
- Responsable de la migración y ventana de corte.

## Pasos previstos cuando se cierre el acuerdo

1. Crear el subdominio y emitir el certificado SSL.
2. Crear una base de datos vacía e importar el dump:
   - `database/forestsrd_db.sql` (recomendado): incluye un **preflight** que aborta el import si la base destino ya tiene tablas con datos (error `45000`). Requiere permisos para `CREATE PROCEDURE` (normal en Hostinger).
   - `database/forestsrd_db_hostinger.sql` (alternativa): sin preflight ni procedimientos almacenados, para hosts compartidos que restrinjan `CREATE PROCEDURE`. Usar **solo en una base vacía** (hace `DROP TABLE`).
3. Verificar la versión de MariaDB del plan (se espera **10.11/11.x**) y probar el import primero en un entorno de staging: el dump se ha probado en MariaDB 10.4 y el `DEFAULT (UUID())` debe validarse en la versión destino.
4. Crear `config/config.local.php` desde la plantilla (sin valores reales): `cp config/config.local.example.php config/config.local.php` y definir `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` y `APP_URL=https://Forestid.cfmasoft.com/`. El archivo está ignorado por Git. (Existe también `config/config.local.ejemplo.php`; ambos son plantillas equivalentes.)
5. Crear `config/mailer.php` desde la plantilla: `cp config/mailer.example.php config/mailer.php` con el SMTP de Hostinger (`smtp.hostinger.com`, puerto `465` con `ssl`). El archivo está ignorado por Git. (Existe también `config/mailer.ejemplo.php`.)
6. Fijar la zona horaria `America/Bogota` (por ejemplo en `.user.ini`: `date.timezone=America/Bogota`) y revisar `upload_max_filesize`, `post_max_size` y `memory_limit`.
7. Comprobar permisos de escritura en `assets/uploads/` y `assets/qr/`.
8. Ejecutar el verificador: `php scripts/verificar_despliegue.php` y corregir cualquier `FALLA` (ver la sección siguiente).
9. Probar login, alta de individuo con foto, generación de QR, ficha pública y envío de correo.
10. Regenerar todas las placas QR desde **Admin → Gestor QR**, porque los códigos codifican `APP_URL`.
11. Activar HTTPS forzado, copias de seguridad y retención de logs.

## Verificador de despliegue (`scripts/verificar_despliegue.php`)

Script **solo para línea de comandos** (bloqueado por HTTP: `scripts/.htaccess` lo deniega y el `.htaccess` raíz devuelve 404 para `scripts/`). No requiere Composer ni toca la sesión web.

```bash
# En el servidor (SSH) o en la instalación local:
php scripts/verificar_despliegue.php
```

Qué comprueba:

- PHP `>= 8.1` (composer.json) y extensiones `pdo_mysql`, `mbstring`, `fileinfo`, `curl`, `openssl`, `json`, `session`.
- Zona horaria `date.timezone = America/Bogota`.
- `upload_max_filesize >= 2M`, `post_max_size >= upload_max_filesize`, `memory_limit >= 128M` (o `-1`).
- Directorios `assets/uploads/` y `assets/qr/` existentes y con permiso de escritura.
- Conexión real a la base de datos (prioridad `config/config.local.php` > variables de entorno > defaults de XAMPP) e imprime la versión del servidor.
- `APP_URL` definida con esquema `https`.

Salida: tabla `PRUEBA | RESULTADO | DETALLE` con `OK`/`FALLA`, resumen final y **código de salida 0 si todo pasa, 1 si hay alguna FALLA**, por lo que sirve en despliegues automatizados. No imprime contraseñas ni datos sensibles.

Si una prueba sale `FALLA`, corrija el entorno y vuelva a ejecutar hasta que el resumen sea `0 FALLA(s)` antes de pasar al paso 9.

## Checklist final (antes de abrir a público)

- [ ] Subdominio creado con SSL activo; HTTP redirige a HTTPS.
- [ ] MariaDB del plan confirmada (10.11/11.x) e **import de `database/forestsrd_db.sql` probado en staging** (preflight incluido; nunca sobre datos sin respaldo).
- [ ] `config/config.local.php` y `config/mailer.php` creados desde las plantillas `.example.php` con los valores reales; ausentes del índice de Git (`git status --ignored`).
- [ ] `php scripts/verificar_despliegue.php` termina en `0 FALLA(s)` (incluye `America/Bogota`, HTTPS en `APP_URL` y BD conectada).
- [ ] `.htaccess` raíz activo y respetado por LiteSpeed: `database/`, `docs/`, `config/`, `vendor/`, `includes/`, `scripts/`, `.git`, `.opencode` → 404; `*.md`, `*.sql`, `*.env` → 403/404; HSTS presente solo bajo HTTPS (cabecera `Strict-Transport-Security`).
- [ ] Login, alta de individuo con foto, QR, ficha pública y correo de activación probados en el entorno final.
- [ ] Placas QR regeneradas tras definir `APP_URL` definitivo.
- [ ] Contraseñas semilla del SQL cambiadas (o cuentas eliminadas) antes de publicar; `FORESTID_DEBUG` **no** definido en producción.
- [ ] Copias de seguridad automáticas y retención de logs configuradas.

## Notas técnicas

- El esquema se ha probado en **MariaDB 10.4**. Hostinger suele ofrecer MariaDB 10.11/11.x; valide antes de migrar: `DEFAULT (UUID())` (8 defaults `CHAR(36)`), columnas `JSON` (alias de `LONGTEXT` con `CHECK (JSON_VALID(...))` en MariaDB) y los `ENUM`. La collation `utf8mb4_spanish_ci` existe en MariaDB 10.11/11.x.
- El dump `database/forestsrd_db.sql` incluye un **preflight con `SIGNAL SQLSTATE '45000'`**: si alguna de las 8 tablas de la aplicación existe y tiene filas, el import se aborta antes de los `DROP TABLE`. En una base vacía el preflight pasa y el import continúa.
- `vendor/` está versionado en el repositorio: **no se necesita Composer en el servidor**.
- Hostinger usa **LiteSpeed**. El HSTS del `.htaccess` ya no usa la expresión `<If "%{HTTPS} == 'on'">`: ahora se emite con `Header ... env=HTTPS_ON` a partir de `SetEnvIf HTTPS "on"` (además de `X-Forwarded-Proto=https` para proxies), lo que es portable entre Apache y LiteSpeed.
- `.htaccess` bloquea `config/`, `vendor/`, `database/`, `docs/`, `includes/`, `scripts/`, `.git`, `.opencode` y los archivos `.md`; hay que verificar que LiteSpeed respete esas reglas en el subdominio.
- `scripts/` está pensado para utilidades CLI; `verificar_despliegue.php` rechaza ejecuciones fuera de CLI con HTTP 403.
- Las credenciales reales (`config/config.local.php`, `config/mailer.php`) no se versionan ni deben copiarse desde la copia local sin revisarlas.

## Referencias

- [README principal](README.md)
- [Manual técnico](docs/MANUAL_TECNICO.md)
- [Auditoría técnica](docs/AUDITORIA.md)
