# Manual Técnico y Arquitectura de Software — ForestID

Documento técnico para desarrolladores y administradores de infraestructura sobre la arquitectura, convenciones, seguridad y extensión del sistema **ForestID** (Emincut S.A.S. — El Bagre, Antioquia).

---

## 1. Arquitectura del Sistema

ForestID está estructurado como una aplicación web modular en PHP puro sobre el motor de base de datos MySQL 8.0, diseñada para máxima portabilidad en servidores locales XAMPP y entornos LAMP/LEMP de producción sin dependencias de compilación complejas.

```
┌─────────────────────────────────────────────────────────────┐
│                    Capa de Presentación                     │
│  HTML5 + Tailwind CSS (CDN) + JavaScript ES6 + Lucide Icons │
│       Leaflet.js (OpenStreetMap) + html5-qrcode (Cámara)    │
└──────────────────────────────┬──────────────────────────────┘
                               │ HTTP / HTTPS
┌──────────────────────────────▼──────────────────────────────┐
│                    Capa de Controladores                    │
│    Rutas Modulares: /publico, /operario, /admin, /auth      │
│            Middleware: includes/auth_check.php              │
└──────────────────────────────┬──────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────┐
│                   Capa de Lógica y Helpers                  │
│   includes/funciones.php  •  includes/qrcode_helper.php     │
│        Seguridad, Sanitización, Generador SVG QR            │
└──────────────────────────────┬──────────────────────────────┘
                               │ PDO (100% Prepared Statements)
┌──────────────────────────────▼──────────────────────────────┐
│               Capa de Datos (MySQL 8.0 / InnoDB)            │
│       8 Tablas en 4 Capas Lógicas con Llaves Primarias UUID │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Capas de la Base de Datos (MySQL 8.0)

La base de datos `forestsrd_db` utiliza el motor `InnoDB`, juego de caracteres `utf8mb4` y collation `utf8mb4_spanish_ci`. Todas las llaves primarias son cadenas UUID de 36 caracteres generadas nativamente mediante `DEFAULT (UUID())`.

1. **Capa de Acceso y Seguridad:**
   - `roles`: Catálogo de perfiles (`AdminEmincut`, `OperarioEmincut`, `UsuarioPublico`).
   - `usuarios`: Cuentas del personal de Emincut con contraseñas Bcrypt y control de acceso.
   - `tokens_recuperacion`: Tokens criptográficos temporales de un solo uso, almacenados como hash SHA-256 (recuperación: 1 hora; activación de cuentas nuevas: 48 horas).
2. **Capa Territorial:**
   - `zonas`: Parques, campus y sectores internos de Emincut, georreferenciados con coordenadas de referencia.
3. **Capa Núcleo (Core Dendrológico):**
   - `individuos_forestales`: Registro maestro con código de placa física único, mediciones dasométricas (DAP, altura, copa), fecha de siembra, coordenadas GPS y foto principal.
   - `estados_fitosanitarios`: Historial inmutable de evaluaciones de salud con diagnóstico y recomendaciones.
4. **Capa de Actividad y Auditoría:**
   - `actividades_mantenimiento`: Registro cronológico de labores silviculturales (poda, fumigación, fertilización, riego, etc.) y reportes ciudadanos con evidencia fotográfica.
   - `logs_sistema`: Bitácora inmutable de eventos con estructura JSON para detalles técnicos e IP de origen.

---

## 3. Estándares y Políticas de Seguridad

- **Prevención de Inyección SQL:** 100% de las consultas a la base de datos se ejecutan a través de **Prepared Statements** de PDO con paso estricto de parámetros. Queda prohibida la concatenación de variables en sentencias SQL.
- **Autenticación Segura:** Las contraseñas se almacenan mediante `password_hash($password, PASSWORD_BCRYPT)` con factor de costo 12. La validación se efectúa exclusivamente con `password_verify()`; no existen credenciales en texto plano ni rehasheos automáticos. Las cuentas nuevas no reciben contraseña por correo: se envían enlaces de activación de un solo uso para que el usuario la cree.
- **Política de contraseñas:** mínimo 10 caracteres, con al menos una mayúscula, una minúscula y un número (`validar_password_fuerte()`).
- **Sesiones endurecidas:** cookies `HttpOnly` y `SameSite=Lax` (y `Secure` bajo HTTPS), modo estricto de sesión, regeneración del ID al autenticarse y expiración tras 30 minutos de inactividad (`SESSION_IDLE_TIMEOUT`).
- **Límite de intentos:** el login se bloquea temporalmente tras 5 fallos por IP o correo en 15 minutos (`LOGIN_MAX_INTENTOS` / `LOGIN_VENTANA_MIN`), con retardo progresivo; las solicitudes de recuperación se limitan a 3 por IP en la misma ventana.
- **Protección contra CSRF:** Formularios críticos incluyen un campo oculto `csrf_token` generado con `bin2hex(random_bytes(32))` y validado con `hash_equals()`. Las acciones destructivas (baja lógica, cierre de sesión, activación de usuarios, regeneración de QR) solo se aceptan por `POST`.
- **Sanitización de Entradas:** `sanear()` normaliza UTF-8 y recorta espacios, pero **no escapa HTML**. Todo dato de usuario se escapa en la salida con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`; los textos se guardan sin entidades HTML.
- **Subida Segura de Archivos:** Las imágenes cargadas se validan a través de `finfo_file(FILEINFO_MIME_TYPE)` aceptando únicamente `image/jpeg`, `image/png` y `image/webp`. Los nombres de archivo se reemplazan por un nuevo UUID para evitar ataques de sobreescritura o ejecución remota de código.

---

## 4. Generador de Códigos QR Estándar

El generador implementado en `includes/qrcode_helper.php` usa la librería MIT **`chillerlan/php-qrcode`** (incluida en `vendor/`) y produce códigos QR vectoriales en formato **SVG** conformes a ISO/IEC 18004:

- Incluye corrección de errores (nivel M), información de formato/versión y enmascaramiento, por lo que **cualquier lector estándar puede decodificarlos** (cámaras, `html5-qrcode`, `zbar`, etc.).
- Es 100 % vectorial, lo que permite escalarlo a cualquier tamaño de impresión física (placas metálicas, acrílico o laminado) sin pérdida de resolución.
- Guarda automáticamente los archivos en `assets/qr/qr_[CODIGO_PLACA].svg`.
- El contenido codificado es la URL pública de la ficha (`APP_URL + publico/ficha.php?codigo=...`). `APP_URL` se define en `config/config.local.php` y, si se omite, se detecta automáticamente desde la petición.
- El gestor `admin/qr/index.php` permite regenerar un QR individual o todo el inventario (o una zona) con la URL canónica vigente. **Al desplegar en otro dominio, configure `APP_URL` y regenere las placas** para que apunten a la URL correcta.
- La impresión de placas está en `admin/qr/imprimir.php` y usa únicamente la identidad de Emincut.
- Verificación recomendada antes de imprimir: `zbarimg --raw assets/qr/qr_EBG-PARQ-001.svg`.

---

## 5. Guía para Desarrollo Asistido por IA (Kilo Code / Cursor / Claude Code)

Dado que el equipo de Emincut trabaja con asistentes de inteligencia artificial en VS Code:

1. **Modularidad:** Todo nuevo módulo debe seguir el patrón de carpetas existente:
   - Formulario de creación: `crear.php`
   - Formulario de edición: `editar.php`
   - Listado / Catálogo: `index.php`
   - Detalle / Inspección: `detalle.php`
2. **Protección de Rutas:** Cualquier archivo dentro de `admin/` u `operario/` debe iniciar obligatoriamente con:
   ```php
   require_once __DIR__ . '/../../config/db.php';
   require_once __DIR__ . '/../../includes/funciones.php';
   require_once __DIR__ . '/../../includes/auth_check.php';

   requerir_rol(['AdminEmincut']); // O ['OperarioEmincut', 'AdminEmincut']
   ```
3. **Registro de Auditoría:** Cualquier operación de creación, actualización o eliminación lógica debe invocar a:
   ```php
   registrar_log($pdo, $id_usuario, 'NOMBRE_ACCION', 'MODULO', ['clave' => 'valor']);
   ```
4. **Bajas Lógicas:** Nunca ejecutar `DELETE FROM individuos_forestales`. Utilizar siempre `UPDATE individuos_forestales SET estado_registro = 0 WHERE id_individuo = :id`.
