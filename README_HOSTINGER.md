# ForestID — Despliegue previsto en Hostinger

> **Estado:** acuerdo en curso con Emincut. Todavía **no** existe un entorno de producción.

## Situación actual

El paso a producción de ForestID está previsto en **Hostinger**, junto al sitio de Emincut y como subdominio del mismo. A la fecha de esta nota **solo se está llegando a un acuerdo con Emincut**: no hay dominio, plan de hosting, credenciales ni fecha confirmados, y **no hay un entorno de producción activo**.

Mientras el acuerdo no se cierre, la referencia válida sigue siendo la instalación local con XAMPP descrita en el [README principal](README.md).

## Pendiente de definir

- Dominio o subdominio definitivo (se ha planteado `forestid.emincut.com`, sin confirmar).
- Plan de Hostinger (compartido o VPS/Cloud) y ubicación del centro de datos.
- Credenciales de la base de datos y del servidor SMTP.
- Responsable de la migración y ventana de corte.

## Pasos previstos cuando se cierre el acuerdo

1. Crear el subdominio y emitir el certificado SSL.
2. Crear una base de datos vacía e importar `database/forestsrd_db.sql`. El script empieza con `DROP TABLE`: **nunca** debe ejecutarse sobre datos existentes.
3. Verificar la versión de MySQL/MariaDB del plan y probar el import primero en un entorno de staging.
4. Crear `config/config.local.php` con `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` y `APP_URL=https://<dominio>/`. El archivo está ignorado por Git.
5. Crear `config/mailer.php` con el SMTP de Hostinger (`smtp.hostinger.com`, puerto 465 con SSL). El archivo está ignorado por Git.
6. Fijar la zona horaria `America/Bogota` y revisar `upload_max_filesize`, `post_max_size` y `memory_limit`.
7. Comprobar permisos de escritura en `assets/uploads/` y `assets/qr/`.
8. Probar login, alta de individuo con foto, generación de QR, ficha pública y envío de correo.
9. Regenerar todas las placas QR desde **Admin → Gestor QR**, porque los códigos codifican `APP_URL`.
10. Activar HTTPS forzado, copias de seguridad y retención de logs.

## Notas técnicas

- El esquema se ha probado en **MariaDB 10.4**. Hostinger suele ofrecer MariaDB 10.11/11.x, así que conviene validar `DEFAULT (UUID())`, las columnas `JSON` y los `ENUM` antes de migrar.
- Hostinger usa LiteSpeed. El bloque `<If "%{HTTPS} == 'on'">` del `.htaccess` (HSTS) puede no ser portable: conviene sustituirlo por una directiva equivalente basada en `env=HTTPS`.
- `vendor/` está versionado en el repositorio: **no se necesita Composer en el servidor**.
- `.htaccess` bloquea `config/`, `vendor/`, `database/`, `docs/`, `.git`, `.opencode` y los archivos `.md`; hay que verificar que LiteSpeed respete esas reglas en el subdominio.
- Las credenciales reales (`config/config.local.php`, `config/mailer.php`) no se versionan ni deben copiarse desde la copia local sin revisarlas.

## Referencias

- [README principal](README.md)
- [Manual técnico](docs/MANUAL_TECNICO.md)
- [Auditoría técnica](docs/AUDITORIA.md)
