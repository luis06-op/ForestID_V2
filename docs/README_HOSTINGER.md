# ForestID — Despliegue previsto en Hostinger

> **Estado:** despliegue objetivo para la feria FISAD en el dominio **Forestid.cfmasoft.com** (HTTPS). El entorno local XAMPP sigue siendo la referencia para desarrollo.

## Situación actual

El despliegue de producción para la feria FISAD se realizará en el dominio **https://Forestid.cfmasoft.com/**. Las credenciales reales de BD/SMTP y el certificado SSL se configuran en el servidor (no se versionan). La referencia de desarrollo sigue siendo la instalación local XAMPP.

La aplicación ya está preparada para el despliegue. Lo que falta se ejecuta en el servidor: crear las credenciales locales (`config/config.local.php` y `config/mailer.php`), importar la base, subir el paquete y ejecutar el verificador. La referencia de desarrollo sigue siendo la instalación local con XAMPP descrita en el [README principal](README.md).

## Pendiente de definir

- Dominio definitivo: `https://Forestid.cfmasoft.com/` (configurar DNS A/CNAME hacia el hosting y emitir SSL).
- Plan de Hostinger (compartido o VPS/Cloud) y ubicación del centro de datos.
- Credenciales de la base de datos y del servidor SMTP.
- Responsable de la migración y ventana de corte.

## Limpieza previa del servidor (obligatoria)

Extraer el ZIP encima de una instalación anterior **no borra** lo que ya estaba: pueden quedar `database/`, `.sql`, `renumerar_codigos.php` o un `config/db.php` viejo. Secuencia recomendada por SSH:

```bash
cd ~/public_html          # o la carpeta del subdominio

# 1) Respaldo de lo que sí hay que conservar
tar -czf ~/backup_uploads_$(date +%F).tar.gz assets/uploads assets/qr 2>/dev/null || true
cp config/config.local.php ~/config.local.php.bak 2>/dev/null || true
cp config/mailer.php       ~/mailer.php.bak       2>/dev/null || true

# 2) Vaciar por completo la carpeta pública
find . -mindepth 1 -maxdepth 1 -exec rm -rf {} +

# 3) Subir y extraer el ZIP limpio
unzip ~/ForestID_Feria_Forestid.cfmasoft.zip -d .

# 4) Restaurar credenciales y revisar APP_URL
cp ~/config.local.php.bak config/config.local.php 2>/dev/null || true
cp ~/mailer.php.bak       config/mailer.php       2>/dev/null || true
grep -n "APP_URL" config/config.local.php           # debe ser https://forestid.cfmasoft.com/
```

Si usa el Administrador de Archivos de Hostinger, haga lo mismo: entre en `public_html`, seleccione todo, elimine, suba el ZIP y extraiga. Después confirme que **no quedan restos**:

```bash
find . \( -name '*.sql' -o -name 'renumerar_codigos.php' -o -name 'database' \) -print
# no debe imprimir nada
grep -n "X-Forwarded-Proto" config/db.php   # confirma que está el db.php nuevo
```

## Verificación de integridad del paquete

El ZIP se entrega con un SHA-256 conocido. Compruébelo **en el servidor** después de subirlo, antes de extraer, para descartar una subida corrupta o truncada:

```bash
# En su equipo, junto al ZIP descargado:
sha256sum ForestID_Feria_Forestid.cfmasoft.zip

# En el servidor, sobre el archivo subido:
sha256sum ~/ForestID_Feria_Forestid.cfmasoft.zip
```

Los dos valores deben coincidir exactamente. El hash de esta entrega está en el informe `AUDITORIA_FORESTID_2026-11-04.md`. Si no coincide, **no extraiga**: vuelva a subir el ZIP.

El dump SQL también tiene su propio SHA-256 en el informe.

## Pasos previstos cuando se cierre el acuerdo

1. Crear el subdominio y emitir el certificado SSL.
2. Crear una base de datos vacía e importar el dump (ambos ya incluyen la tabla `placas_qr`):
   - `database/forestsrd_db_hostinger.sql` (recomendado para Hostinger): sin preflight ni procedimientos almacenados; compatible con hosting compartido. Usar **solo en una base vacía** (hace `DROP TABLE`).
   - `database/forestsrd_db.sql` (alternativa): incluye un **preflight** que aborta el import si la base destino ya tiene tablas con datos (error `45000`). Requiere permisos para `CREATE PROCEDURE`.
3. Verificar la versión de MariaDB del plan (se espera **10.11/11.x**) y probar el import primero en un entorno de staging: el dump se ha probado en MariaDB 10.4 y el `DEFAULT (UUID())` debe validarse en la versión destino.
4. Crear `config/config.local.php` desde la plantilla (sin valores reales): `cp config/config.local.example.php config/config.local.php` y definir `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` y **siempre** `APP_URL='https://forestid.cfmasoft.com/'`. `APP_URL` es la fuente de verdad de los enlaces, QR y correos; si se omite, la aplicación usa como red de seguridad el dominio de la petición, pero solo si está en la lista blanca (`forestid.cfmasoft.com` y `www.forestid.cfmasoft.com`). Para otro dominio legítimo (staging), añada `FORESTID_HOSTS`. El archivo está ignorado por Git. (Existe también `config/config.local.ejemplo.php`; ambos son plantillas equivalentes.)
5. Crear `config/mailer.php` desde la plantilla: `cp config/mailer.example.php config/mailer.php` con el SMTP de Hostinger (`smtp.hostinger.com`, puerto `465` con `ssl`), remitente visible `no-reply@forestid.cfmasoft.com` y los datos reales del buzón. El archivo está ignorado por Git. (Existe también `config/mailer.ejemplo.php`.)
6. Fijar la zona horaria `America/Bogota` (por ejemplo en `.user.ini`: `date.timezone=America/Bogota`) y revisar `upload_max_filesize`, `post_max_size` y `memory_limit`.
7. Comprobar permisos: directorios `755` y archivos `644`; si el servidor necesita escritura por grupo, `775` con `setgid` (`2775`). **Nunca `777`.** Deben ser escribibles solo `assets/uploads/` y `assets/qr/`.
8. Ejecutar el verificador: `php scripts/verificar_despliegue.php` y corregir cualquier `FALLA` (ver la sección siguiente).
9. Probar login, alta de individuo con foto, generación de QR, ficha pública y envío de correo.
10. Regenerar todas las placas QR desde **Admin → Gestor QR**, porque los códigos codifican `APP_URL`.
11. Activar HTTPS forzado, copias de seguridad y retención de logs.
12. **Eliminar del servidor** los `.sql` y `database/renumerar_codigos.php` después de importar la base (el paquete de entrega ya no los incluye).

## Verificador de despliegue (`scripts/verificar_despliegue.php`)

Script **solo para línea de comandos** (bloqueado por HTTP: `scripts/.htaccess` lo deniega y el `.htaccess` raíz devuelve 404 para `scripts/`). No requiere Composer ni toca la sesión web.

```bash
# En el servidor (SSH) o en la instalación local:
php scripts/verificar_despliegue.php
```

Qué comprueba:

- PHP `>= 8.1` (composer.json) y extensiones `pdo_mysql`, `mbstring`, `fileinfo`, `curl`, `openssl`, `json`, `session`, `gd`.
- Zona horaria `date.timezone = America/Bogota` (en CLI o en `.user.ini`).
- `display_errors` desactivado y `log_errors` activado.
- `upload_max_filesize >= 2M`, `post_max_size >= upload_max_filesize`, `memory_limit >= 128M` (o `-1`).
- `.htaccess` presente en `config/`, `includes/`, `vendor/` y `database/`.
- Directorios `assets/uploads/` y `assets/qr/` existentes y con permiso de escritura.
- Conexión real a la base de datos (prioridad `config/config.local.php` > variables de entorno > defaults de XAMPP) e imprime la versión del servidor.
- `APP_URL` con `https` si está definida; si falta, se informa que se detecta del dominio. La comprobación adicional `APP_URL no apunta a localhost` **falla** si quedó un valor de desarrollo.

Salida: tabla `PRUEBA | RESULTADO | DETALLE` con `OK`/`FALLA`, resumen final y **código de salida 0 si todo pasa, 1 si hay alguna FALLA**, por lo que sirve en despliegues automatizados. No imprime contraseñas ni datos sensibles.

Si una prueba sale `FALLA`, corrija el entorno y vuelva a ejecutar hasta que el resumen sea `0 FALLA(s)` antes de pasar al paso 9.

## Checklist final (antes de abrir a público)

- [ ] Subdominio creado con SSL activo; HTTP redirige a HTTPS y el candado es válido **antes** de confiar en HSTS.
- [ ] `sha256sum` del ZIP en el servidor coincide con el del informe **antes** de extraer.
- [ ] `public_html` vaciado antes de extraer el ZIP; sin `database/`, `.sql` ni `renumerar_codigos.php` residuales.
- [ ] `APP_URL` real definido en `config/config.local.php` (no `localhost`).
- [ ] MariaDB del plan confirmada (10.11/11.x) e **import de `database/forestsrd_db_hostinger.sql` probado en staging** (o de `forestsrd_db.sql`, que incluye preflight; nunca sobre datos sin respaldo).
- [ ] `config/config.local.php` y `config/mailer.php` creados desde las plantillas `.example.php` con los valores reales; ausentes del índice de Git (`git status --ignored`).
- [ ] `php scripts/verificar_despliegue.php` termina en `0 FALLA(s)` (incluye `America/Bogota` y BD conectada; si `APP_URL` está definida, debe ser HTTPS).
- [ ] `.htaccess` raíz activo y respetado por LiteSpeed: `database/`, `docs/`, `config/`, `vendor/`, `includes/`, `scripts/`, `.git`, `.opencode` → 404; `*.md`, `*.sql`, `*.env`, `.zip`, `.bak` → 403/404; HSTS presente solo bajo HTTPS (cabecera `Strict-Transport-Security`).
- [ ] Sin `.sql` ni `database/renumerar_codigos.php` en el servidor tras importar la base.
- [ ] `assets/uploads/.htaccess` y `assets/qr/.htaccess` activos: ningún `.php` se ejecuta dentro de esas carpetas.
- [ ] Subidas validadas por MIME real, tamaño, dimensiones y recodificación con GD; nombres generados con UUID.
- [ ] Cookies de sesión con `HttpOnly`, `SameSite=Lax` y `Secure` bajo HTTPS; el ID se regenera al iniciar sesión.
- [ ] Login, alta de individuo con foto, QR, ficha pública y correo de activación probados en el entorno final.
- [ ] Consola del navegador (F12) sin errores `Refused to load` en landing, ficha pública, dashboard, reportes y escáner.
- [ ] Escáner probado en un celular real con HTTPS y permiso de cámara concedido.
- [ ] `reservar_codigos_placa()` genera serie numérica sin colisionar con los códigos `EBG-…` existentes; búsqueda y ficha resuelven ambos formatos.
- [ ] Placas QR regeneradas con el dominio definitivo.
- [ ] Contraseñas semilla de `admin@emincut.com` y `operario@emincut.com` cambiadas (Bcrypt coste 12); `FORESTID_DEBUG` **no** definido en producción.
- [ ] Copias de seguridad automáticas y retención de logs configuradas.

## Notas técnicas

- El esquema se ha probado en **MariaDB 10.4**. Hostinger suele ofrecer MariaDB 10.11/11.x; valide antes de migrar: `DEFAULT (UUID())` (8 defaults `CHAR(36)`), columnas `JSON` (alias de `LONGTEXT` con `CHECK (JSON_VALID(...))` en MariaDB) y los `ENUM`. La collation `utf8mb4_spanish_ci` existe en MariaDB 10.11/11.x.
- El dump `database/forestsrd_db.sql` incluye un **preflight con `SIGNAL SQLSTATE '45000'`**: si alguna de las 9 tablas de la aplicación existe y tiene filas, el import se aborta antes de los `DROP TABLE`. En una base vacía el preflight pasa y el import continúa. El dump `forestsrd_db_hostinger.sql` omite el preflight (no requiere `CREATE PROCEDURE`) y debe importarse **solo en una base vacía**.
- `vendor/` está versionado en el repositorio: **no se necesita Composer en el servidor**.
- Hostinger usa **LiteSpeed**. El HSTS del `.htaccess` ya no usa la expresión `<If "%{HTTPS} == 'on'">`: ahora se emite con `Header ... env=HTTPS_ON` a partir de `SetEnvIf HTTPS "on"` (además de `X-Forwarded-Proto=https` para proxies), lo que es portable entre Apache y LiteSpeed.
- **HSTS en el primer despliegue:** se entrega con `max-age=86400` (1 día), sin `includeSubDomains` y **sin `preload`**. Primero confirme el candado y el certificado en el navegador. **No suba el valor el mismo día**: espere varios días de uso estable con HTTPS validado en escritorio y móvil; solo entonces cambie a `31536000` (1 año). Si el certificado falla con HSTS activo, el navegador no permite continuar; con un día, el bloqueo es acotado.
- **Lista blanca de Host:** `config/db.php` solo acepta `forestid.cfmasoft.com`, `www.forestid.cfmasoft.com`, hosts locales, el host de `APP_URL` y los de `FORESTID_HOSTS`. Cualquier otro `Host` se sustituye por `APP_URL` o se rechaza con **400**. El `.htaccess` tampoco refleja un `Host` ajeno en la redirección a HTTPS. Esto protege los enlaces de recuperación y activación, que se envían por correo.
- **CSP:** probada en navegador real (Chromium) sobre la landing, la ficha pública, el dashboard, reportes, alta de individuo y escáner. Tailwind, Leaflet (teselas OpenStreetMap), Chart.js, Lucide y las fuentes Poppins/Inter cargan sin errores `Refused to load`. La CSP incluye `media-src 'self' blob:` y `worker-src 'self' blob:` para la cámara del escáner. Si en el futuro se añade otro CDN, hay que declararlo en `script-src`, `style-src` o `img-src`.
- **Cámara:** `Permissions-Policy` se envía con `camera=(self)` (no `camera=()`), por lo que `operario/escanear.php` puede usar `getUserMedia`. El escáner solo funciona en **HTTPS** o en `localhost`; en producción use el celular con el dominio real y acepte el permiso de cámara.
- **Códigos EBG y serie numérica:** los individuos semilla usan códigos heredados `EBG-PARQ-001`, etc., mientras que las placas nuevas se reservan con serie numérica pura (`001`, `002`, …). `reservar_codigos_placa()` filtra con `REGEXP '^[0-9]+$'`, así que **ignora los EBG y no colisiona** con ellos; la ficha pública y el buscador resuelven ambos formatos por coincidencia exacta o `LIKE`. El alta manual de un individuo nuevo sigue exigiendo serie numérica (es la placa física nueva); para buscar o escanear un EBG no hay restricción numérica.
- `.htaccess` bloquea `config/`, `vendor/`, `database/`, `docs/`, `includes/`, `scripts/`, `.git`, `.opencode` y los archivos `.md`; hay que verificar que LiteSpeed respete esas reglas en el subdominio.
- `scripts/` está pensado para utilidades CLI; `verificar_despliegue.php` rechaza ejecuciones fuera de CLI con HTTP 403.
- Las credenciales reales (`config/config.local.php`, `config/mailer.php`) no se versionan ni deben copiarse desde la copia local sin revisarlas.

## Referencias

- [README principal](README.md)
- [Manual técnico](docs/MANUAL_TECNICO.md)
- [Auditoría técnica](docs/AUDITORIA.md)
