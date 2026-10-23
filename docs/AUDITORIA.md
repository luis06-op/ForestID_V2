# Auditoría técnica — ForestID_V2

> **Fecha:** 22 de octubre de 2026
> **Repositorio:** [`luis06-op/ForestID_V2`](https://github.com/luis06-op/ForestID_V2)
> **Rama revisada:** `diseno-amarillo-sol` (commit `b43eecc`)
> **Rama de correcciones:** `fix/auditoria-p0`
> **Alcance:** 51 archivos PHP propios (~8.400 líneas), esquema SQL, `.htaccess`, documentación y assets QR.
> **Método:** revisión estática + pruebas de humo reales con PHP 8.2.12, MariaDB 10.4, Apache/XAMPP y `zbarimg`.
> **Estado:** los hallazgos **P0 están corregidos** en la rama `fix/auditoria-p0`. Los P1/P2 quedan planificados.

---

## 1. Resumen ejecutivo

| Área | Antes | Después de P0 | Comentario |
|---|---:|---:|---|
| Seguridad | 4/10 | **7/10** | Se cerraron el bypass de login y la toma de cuenta por reseteo. Quedan endurecimiento de sesión, rate limiting y tokens hasheados. |
| Funcionalidad | 6/10 | **8/10** | Los QR ya son estándar y el buscador dejó de devolver 500. |
| Calidad de código | 7/10 | **7,5/10** | Se añadió manejo de errores en QR; faltan transacciones y eliminar el doble escape. |
| Documentación | 9/10 | **9/10** | README y manuales actualizados con la nueva configuración. |
| **Global** | **6,3/10** | **7,9/10** | Apto para demo/pruebas internas. Para producción faltan los P1. |

### Hallazgos por severidad

| Severidad | Detectados | Corregidos | Pendientes |
|---|---:|---:|---:|
| Crítico (P0) | 6 | **6** | 0 |
| Alto (P1) | 7 | 0 | 7 |
| Medio (P2) | 8 | 3 (parciales) | 5 |
| Bajo (P3) | 5 | 1 | 4 |

---

## 2. Hallazgos críticos (P0) — ✅ corregidos

### C1. Toma de cuenta vía enlace de restablecimiento visible
**Archivo:** `auth/recuperar_password.php`
**Antes:** al ingresar un correo existente, la página generaba el token y mostraba el enlace `restablecer_password.php?token=...` directamente en pantalla. Cualquiera que conociera `admin@emincut.com` podía cambiar la contraseña del administrador.
**Corrección aplicada:**
- El enlace ya **no se muestra** por defecto.
- Se envía por correo con `enviar_correo_recuperacion()` (SMTP/PHPMailer o `mail()`).
- El mensaje es siempre genérico: no revela si el correo existe.
- Solo se muestra en pantalla si se activa explícitamente `FORESTID_MODO_DEMO=1` (modo demo local).
**Verificación:** POST a `recuperar_password.php` con `admin@emincut.com` devuelve el mensaje genérico y **0** enlaces de reseteo en el HTML.

### C2. Credenciales de administrador hardcodeadas en el login
**Archivo:** `auth/login.php`
**Antes:** un `elseif` aceptaba `Admin123*`, `Operario123*` y `Ciudadano123*` en texto plano si `password_verify()` fallaba, y la UI mostraba botones que autocompletaban esas claves.
**Corrección aplicada:**
- Eliminado el fallback por completo. Solo se acepta `password_verify()` contra el hash almacenado.
- Eliminados los botones "Cuentas de Prueba Rápida" y la función JS `cargarCredenciales()`.
**Verificación:** login correcto con `admin@emincut.com / Admin123*` → `302` al dashboard; login con clave incorrecta → mensaje "Credenciales incorrectas…".

### C3. Hashes semilla inválidos en el script SQL
**Archivo:** `database/forestsrd_db.sql`
**Antes:** los hashes de los 3 usuarios semilla no eran bcrypt válidos (longitudes 56/58/60 y `password_verify()` fallaba). Las cuentas solo funcionaban por el fallback C2.
**Corrección aplicada:** hashes Bcrypt reales con coste 12, generados con `password_hash()` y verificados con `password_verify()`. Se añadió advertencia para cambiarlos en producción.
**Verificación:** los 3 hashes nuevos devuelven `OK` con sus contraseñas.

### C4. El generador de QR no producía códigos QR
**Archivo:** `includes/qrcode_helper.php` y `assets/qr/*.svg`
**Antes:** el "generador propio" dibujaba patrones de posición y rellenaba con un hash SHA-256. No implementaba Reed-Solomon, formato/versión ni enmascaramiento. Los SVG tenían 10–31 `rect` y **no eran decodificables**.
**Corrección aplicada:**
- Integrada `chillerlan/php-qrcode` 5.0.5 (MIT) vía Composer, incluida en `vendor/`.
- `QRCodeGenerator` genera SVG estándar ISO/IEC 18004 con nivel de corrección M, quiet zone y colores institucionales validados.
- Se regeneraron los 4 QR semilla (`EBG-PARQ-001`, `EBG-SENA-002`, `EBG-NECH-003`, `EBG-PARQ-004`).
- Se añadió manejo de excepciones en las altas y en la regeneración para no dejar registros a medias.
**Verificación:** `zbarimg` decodifica los 4 SVG y devuelve la URL correcta de cada ficha.

### C5. Credenciales de BD versionadas y rutas sensibles expuestas
**Archivos:** `config/db.php`, `.gitignore`, `.htaccess`
**Antes:** `root` / contraseña vacía estaban en Git. El dump SQL, `.git`, `docs/`, `config/` y `vendor/` eran accesibles por HTTP en XAMPP.
**Corrección aplicada:**
- `config/db.php` carga `config/config.local.php` (ignorado por Git) con prioridad sobre variables de entorno y valores por defecto.
- Plantilla `config/config.local.ejemplo.php`.
- `.gitignore` ampliado (`config/config.local.php`, `.env`, logs, backups).
- `.htaccess` raíz + `.htaccess` por carpeta (`database/`, `docs/`, `config/`, `includes/`, `vendor/`) + `assets/uploads/.htaccess` para impedir ejecución de PHP en subidas.
- Cabeceras `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` y `Permissions-Policy`.
- El detalle técnico del error de conexión solo se muestra con `FORESTID_DEBUG=1`.
**Verificación en Apache:** `.git/config` → 404; `database/*.sql`, `config/*`, `includes/*`, `vendor/*`, `docs/*`, `README.md` → 403/404; `publico/index.php` → 200; `assets/uploads/shell.php` → 403.

### C6. Buscador y listados devolvían HTTP 500 (hallazgo durante las pruebas)
**Archivos:** `publico/buscar.php`, `admin/individuos/index.php`, `admin/usuarios/index.php`, `operario/individuos/index.php`
**Antes:** la misma consulta reutilizaba el placeholder `:q` 3–4 veces. Con `PDO::ATTR_EMULATE_PREPARES => false`, MySQL nativo rechaza placeholders repetidos: `SQLSTATE[HY093]: Invalid parameter number`.
**Corrección aplicada:** placeholders únicos `:q1`…`:q4` con el mismo valor.
**Verificación:** los 4 listados con `?q=` devuelven `200` y resultados correctos.

---

## 3. Hallazgos altos (P1) — ⏳ pendientes

| ID | Hallazgo | Ubicación | Impacto | Corrección recomendada |
|---|---|---|---|---|
| A1 | Sin endurecimiento de sesión: no hay `session_regenerate_id()` tras login, ni cookies `HttpOnly`/`Secure`/`SameSite`, ni expiración por inactividad | `auth/login.php:49`, `config/db.php:8` | Fijación/robo de sesión | Regenerar ID al autenticar; configurar cookies antes de `session_start()`; timeout de 30–60 min. |
| A2 | Sin rate limiting ni bloqueo en login/recuperación | `auth/login.php`, `auth/recuperar_password.php` | Fuerza bruta y spam de tokens | Contador por IP/correo en BD o APCu + retardo progresivo + captcha opcional. |
| A3 | Tokens de recuperación en texto plano y contraseña mínima de 6 caracteres | `auth/recuperar_password.php:42`, `auth/restablecer_password.php:28,49`, `admin/usuarios/crear.php:40` | Quien lea la BD puede secuestrar cuentas | Guardar `hash('sha256', $token)` y comparar el hash; mínimo 10–12 caracteres + complejidad. |
| A4 | Doble escape: `sanear()` aplica `htmlspecialchars()` al guardar y se vuelve a escapar al mostrar | `includes/funciones.php:12`, usado en 70 sitios | Se almacena `&amp;`; nombres con `&`, `'`, `"` se ven mal y las búsquedas fallan | Validar/normalizar al guardar y escapar solo en la salida (`htmlspecialchars` en vistas). |
| A5 | Altas de individuos sin transacción: individuo + estado + referencia + log son 4 escrituras independientes | `admin/individuos/crear.php:85-136`, `operario/registrar_individuo.php:74-121` | Registros huérfanos si falla una escritura intermedia | `beginTransaction()` / `commit()` / `rollBack()`. |
| A6 | Acciones destructivas por GET con CSRF en la URL | `admin/individuos/eliminar.php:13-20`, `auth/logout.php` | Token en historial, logs y `Referer`; cierre de sesión CSRF | Usar POST + formulario con token; logout por POST. |
| A7 | Contraseñas enviadas en texto plano por correo/WhatsApp | `includes/notificaciones.php:45,70`, `admin/usuarios/crear.php:84-104` | Exposición en bandejas y logs de proveedores | Enviar enlace de activación de un solo uso; nunca la contraseña. |

---

## 4. Hallazgos medios (P2)

| ID | Hallazgo | Ubicación | Estado |
|---|---|---|---|
| M1 | El error de conexión imprimía `$e->getMessage()` | `config/db.php` | ✅ Corregido: solo con `FORESTID_DEBUG=1`. |
| M2 | `BASE_URL` se construye con `HTTP_HOST` sin validar | `config/db.php:45` | ⏳ Validar contra `APP_URL` o lista blanca. |
| M3 | Enums y FK sin validar en servidor (`estado_salud`, `tipo_individuo`, `id_zona`, `id_institucion`) | `crear.php`, `operario/registrar_individuo.php`, `detalle.php` | ⏳ Validar contra listas permitidas y verificar FK activas. |
| M4 | Sin CSP ni HTTPS forzado | global | ⏳ Parcial: cabeceras añadidas en `.htaccess`; falta CSP/HSTS y quitar CDNs sin SRI. |
| M5 | Coordenadas GPS exactas públicas en la ficha | `publico/ficha.php:274-275` | ⏳ Ofuscar (~100 m) o mostrar solo a autenticados; riesgo de extracción de especies. |
| M6 | CSV sin protección contra inyección de fórmulas | `admin/reportes/index.php:39-60` | ⏳ Prefijar `'` a valores que empiecen por `= + - @`. |
| M7 | Sin pruebas automatizadas; `test*.html/js` de depuración versionados | raíz | ⏳ Mover a `tests/` o eliminar; añadir smoke tests. |
| M8 | `vendor/phpmailer` copiado a mano, sin Composer ni versión fijada; `.opencode/` versionado | raíz | ⏳ Gestionar PHPMailer con Composer; excluir herramientas de desarrollo del release. |

---

## 5. Hallazgos bajos (P3)

| ID | Hallazgo | Recomendación |
|---|---|---|
| B1 | `eliminar.php` y `logout.php` por GET (ver A6) | POST + CSRF. |
| B2 | Logs de intentos fallidos sin límite | Pueden llenar `logs_sistema`; añadir retención/rotación. |
| B3 | `formatear_fecha()` depende de `strtotime()` y zona horaria del servidor | Fijar `date_default_timezone_set('America/Bogota')`. |
| B4 | Mezcla de estilos inline y Tailwind CDN | Migrar a build de Tailwind para producción y CSP estricta. |
| B5 | Copia desplegada en `/opt/lampp/htdocs/ForestID_V2` distinta del repo | Sincronizar con un script/CI; no copiar `config/mailer.php` ni `config/config.local.php`. |

---

## 6. Lo que está bien (mantener)

- ✅ **100 % de consultas con prepared statements**; no hay interpolación de variables en SQL.
- ✅ **Middleware de roles** aplicado en los 27 archivos de `admin/`, `operario/` y `publico/`.
- ✅ **CSRF** en todos los formularios de escritura, con `hash_equals()`.
- ✅ **Subida de archivos** valida MIME real con `finfo` y renombra con UUID.
- ✅ **Auditoría** en `logs_sistema` con IP y JSON en acciones clave.
- ✅ Esquema **InnoDB, 9 tablas, 13 FK y 19 índices**, UUID y `utf8mb4_spanish_ci`; `codigo_placa` es `UNIQUE`.
- ✅ **Cero errores de sintaxis** en los 51 PHP (`php -l`) y documentación muy completa.

---

## 7. Evidencia de verificación

```text
php -l (51 archivos)                         → OK
password_verify(Admin123*, hash SQL)         → OK
password_verify(Operario123*, hash SQL)      → OK
password_verify(Ciudadano123*, hash SQL)     → OK
zbarimg qr_EBG-PARQ-001.svg                  → URL de la ficha
zbarimg qr_EBG-SENA-002.svg                  → URL de la ficha
zbarimg qr_EBG-NECH-003.svg                  → URL de la ficha
zbarimg qr_EBG-PARQ-004.svg                  → URL de la ficha

POST /auth/login.php (admin correcto)        → 302 /admin/dashboard.php
POST /auth/login.php (clave incorrecta)      → 200 "Credenciales incorrectas…"
POST /auth/recuperar_password.php            → 200, 0 enlaces de reseteo
GET  /publico/buscar.php?q=Guayac            → 200 con resultados
GET  /admin/individuos/index.php?q=Guayac    → 200
GET  /admin/usuarios/index.php?q=Luis        → 200
GET  /operario/individuos/index.php?q=Guayac → 200
GET  /publico/ficha.php?codigo=EBG-PARQ-001  → 200
GET  /.git/config                            → 404 (Apache + .htaccess)
GET  /database/forestsrd_db.sql              → 403
GET  /assets/uploads/shell.php               → 403
```

---

## 8. Plan de remediación

### Hecho en esta rama
- C1–C6, M1 y parte de M4/M8.
- Actualización de README, manual técnico y documentación de instalación.

### Siguiente iteración (P1)
1. Endurecer sesión (A1) y rate limiting (A2).
2. Hashear tokens de recuperación y elevar la política de contraseñas (A3).
3. Separar sanitización de entrada y escape de salida (A4).
4. Transacciones en altas (A5).
5. POST para eliminar/logout (A6).
6. Enlaces de activación en lugar de contraseñas (A7).

### Antes de producción
- Resolver M2–M8, añadir pruebas automatizadas y CI, forzar HTTPS y CSP, rotar credenciales y revisar la historia de Git (ver `docs/PLAN_PUBLICACION_GITHUB.md`).

---

## 9. Nota sobre el despliegue

El proyecto revisado es el repositorio en `/home/luis/Proyectos/github/ForestID_V2`. La copia servida por Apache en `/opt/lampp/htdocs/ForestID_V2` es **independiente** y contiene el `config/mailer.php` real. No se sobrescribió para no perder esas credenciales. Para probar los cambios en el navegador, sincronice el repo **excluyendo** `config/mailer.php` y `config/config.local.php`, o use el servidor embebido:

```bash
php -S 127.0.0.1:8099 -t /home/luis/Proyectos/github/ForestID_V2
```
