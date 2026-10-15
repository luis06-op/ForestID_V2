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

1. **Capa Institucional:**
   - `roles`: Catálogo de perfiles (`AdminEmincut`, `OperarioEmincut`, `UsuarioPublico`).
   - `instituciones`: Clientes y entidades territoriales (con sede principal Emincut en El Bagre).
   - `usuarios`: Cuentas de funcionarios con contraseñas Bcrypt y control de acceso.
   - `tokens_recuperacion`: Tokens criptográficos temporales de un solo uso (validez 1 hora).
2. **Capa Territorial:**
   - `zonas`: Parques, campus y sectores georreferenciados con coordenadas de referencia.
3. **Capa Núcleo (Core Dendrológico):**
   - `individuos_forestales`: Registro maestro con código de placa física único, mediciones dasométricas (DAP, altura, copa), fecha de siembra, coordenadas GPS y foto principal.
   - `estados_fitosanitarios`: Historial inmutable de evaluaciones de salud con diagnóstico y recomendaciones.
4. **Capa de Actividad y Auditoría:**
   - `actividades_mantenimiento`: Registro cronológico de labores silviculturales (poda, fumigación, fertilización, riego, etc.) y reportes ciudadanos con evidencia fotográfica.
   - `logs_sistema`: Bitácora inmutable de eventos con estructura JSON para detalles técnicos e IP de origen.

---

## 3. Estándares y Políticas de Seguridad

- **Prevención de Inyección SQL:** 100% de las consultas a la base de datos se ejecutan a través de **Prepared Statements** de PDO con paso estricto de parámetros. Queda prohibida la concatenación de variables en sentencias SQL.
- **Autenticación Segura:** Las contraseñas se almacenan mediante `password_hash($password, PASSWORD_BCRYPT)` con un factor de costo 10. La validación se efectúa exclusivamente con `password_verify()`.
- **Protección contra CSRF:** Formularios críticos incluyen un campo oculto `csrf_token` generado con `bin2hex(random_bytes(32))` y validado con `hash_equals()`.
- **Sanitización de Entradas:** Todas las entradas se procesan a través de la función `sanear()` con `htmlspecialchars(trim(), ENT_QUOTES, 'UTF-8')`.
- **Subida Segura de Archivos:** Las imágenes cargadas se validan a través de `finfo_file(FILEINFO_MIME_TYPE)` aceptando únicamente `image/jpeg`, `image/png` y `image/webp`. Los nombres de archivo se reemplazan por un nuevo UUID para evitar ataques de sobreescritura o ejecución remota de código.

---

## 4. Generador Vectorial Autónomo de Códigos QR

El generador implementado en `includes/qrcode_helper.php` produce códigos QR vectoriales en formato **SVG** nativo:
- No requiere librerías externas pesadas ni APIs de terceros.
- Es 100% vectorial, lo que permite escalarlo a cualquier tamaño de impresión física (para placas metálicas, acrílico o laminado) sin pérdida de resolución ni pixelado.
- Almacena automáticamente los archivos generados en `assets/qr/qr_[CODIGO_PLACA].svg`.

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
