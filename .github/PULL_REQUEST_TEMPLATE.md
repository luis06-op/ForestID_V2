## Qué cambia

- Cierra el bypass de login y el enlace de reseteo visible (P0).
- Reemplaza el generador QR no conforme por `chillerlan/php-qrcode` y regenera las placas.
- Aísla credenciales en `config/config.local.php` y bloquea rutas sensibles con `.htaccess`.
- Corrige el HTTP 500 del buscador y listados por placeholders repetidos.
- Añade `docs/AUDITORIA.md` y `docs/PLAN_PUBLICACION_GITHUB.md`.

## Por qué

Ver `docs/AUDITORIA.md`, hallazgos **C1–C6**. El detalle de cada corrección y su evidencia están en las secciones 2 y 7.

## Cómo probar

1. `php -l` en todos los PHP propios.
2. Login correcto e incorrecto con las cuentas semilla.
3. `zbarimg` sobre los 4 archivos de `assets/qr/`.
4. `?q=Guayac` en `publico/buscar.php`, `admin/individuos/`, `admin/usuarios/` y `operario/individuos/`.
5. `curl -I` a `.git/config`, `database/forestsrd_db.sql`, `config/config.local.php` y `assets/uploads/shell.php`.

## Riesgos y migración

- **Base de datos:** ahora se usa `config/config.local.php` (ignorado por Git). Copiar `config/config.local.ejemplo.php` y ajustar credenciales.
- **QR:** los SVG semilla apuntan a `http://localhost/ForestID_V2/...`; regenerarlos desde `admin/qr/index.php` con el dominio real.
- **SQL:** `database/forestsrd_db.sql` contiene `DROP TABLE`; no importarlo sobre una base con datos de producción.

## Checklist

- [ ] Sin secretos en el diff (`config/mailer.php`, `config/config.local.php`, `.env`).
- [ ] `php -l` OK.
- [ ] Login, buscador, ficha y QR probados.
- [ ] `.htaccess` verificado en Apache.
- [ ] Documentación actualizada.
- [ ] Sin cambios en `config/mailer.php` del despliegue.
