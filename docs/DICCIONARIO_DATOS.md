# Diccionario de Datos — Base de Datos `forestsrd_db`

Estructura completa de las 8 tablas en 4 capas lógicas diseñadas para MySQL 8.0 en el sistema **ForestID** (Emincut S.A.S. — El Bagre, Antioquia).

---

## 1. Capa Institucional

### 1.1 Tabla `roles`
Catálogo de perfiles y niveles de autorización en el sistema.

| Campo | Tipo | Nulo | Por Defecto | Descripción / Regla |
| :--- | :--- | :---: | :--- | :--- |
| `id_rol` | CHAR(36) | NO | `(UUID())` | Llave primaria UUID. |
| `nombre_rol` | VARCHAR(50) | NO | — | Único (`AdminEmincut`, `OperarioEmincut`, `UsuarioPublico`). |
| `descripcion` | VARCHAR(255) | SÍ | NULL | Descripción de los privilegios del rol. |
| `fecha_creacion`| DATETIME | NO | `CURRENT_TIMESTAMP` | Fecha y hora de creación. |

---

### 1.2 Tabla `instituciones`
Entidades públicas, privadas o educativas que administran inventarios arbóreos.

| Campo | Tipo | Nulo | Por Defecto | Descripción / Regla |
| :--- | :--- | :---: | :--- | :--- |
| `id_institucion` | CHAR(36) | NO | `(UUID())` | Llave primaria UUID. |
| `nombre_institucion` | VARCHAR(150) | NO | — | Razón social de la entidad (ej. Emincut S.A.S.). |
| `nit` | VARCHAR(20) | NO | — | Identificador tributario único. |
| `direccion` | VARCHAR(200) | SÍ | NULL | Dirección de la sede física. |
| `municipio` | VARCHAR(100) | NO | `'El Bagre'` | Municipio sede. |
| `departamento` | VARCHAR(100) | NO | `'Antioquia'` | Departamento sede. |
| `telefono_contacto` | VARCHAR(20) | SÍ | NULL | Teléfono móvil o fijo. |
| `correo_contacto` | VARCHAR(150) | SÍ | NULL | Correo de atención institucional. |
| `logo_url` | VARCHAR(255) | SÍ | NULL | Ruta relativa al archivo de logotipo. |
| `estado` | TINYINT(1) | NO | `1` | `1`: Activa, `0`: Inactiva. |
| `fecha_registro` | DATETIME | NO | `CURRENT_TIMESTAMP` | Fecha de alta en el sistema. |

---

### 1.3 Tabla `usuarios`
Cuentas de acceso de funcionarios, técnicos de campo y administradores.

| Campo | Tipo | Nulo | Por Defecto | Descripción / Regla |
| :--- | :--- | :---: | :--- | :--- |
| `id_usuario` | CHAR(36) | NO | `(UUID())` | Llave primaria UUID. |
| `id_rol` | CHAR(36) | NO | — | FK → `roles.id_rol`. |
| `id_institucion` | CHAR(36) | SÍ | NULL | FK → `instituciones.id_institucion`. |
| `nombres` | VARCHAR(100) | NO | — | Nombres del usuario. |
| `apellidos` | VARCHAR(100) | NO | — | Apellidos del usuario. |
| `correo` | VARCHAR(150) | NO | — | Correo electrónico único para inicio de sesión. |
| `password_hash` | VARCHAR(255) | NO | — | Hash Bcrypt generado con `password_hash()`. |
| `telefono` | VARCHAR(20) | SÍ | NULL | Teléfono móvil de contacto. |
| `estado` | TINYINT(1) | NO | `1` | `1`: Activo (puede ingresar), `0`: Inactivo. |
| `ultimo_acceso` | DATETIME | SÍ | NULL | Registro de fecha del último inicio de sesión. |
| `fecha_creacion` | DATETIME | NO | `CURRENT_TIMESTAMP` | Fecha de creación de la cuenta. |

---

### 1.4 Tabla `tokens_recuperacion`
Tokens criptográficos de corta duración para restablecimiento de contraseña.

| Campo | Tipo | Nulo | Por Defecto | Descripción / Regla |
| :--- | :--- | :---: | :--- | :--- |
| `id_token` | CHAR(36) | NO | `(UUID())` | Llave primaria UUID. |
| `id_usuario` | CHAR(36) | NO | — | FK → `usuarios.id_usuario` (CASCADE). |
| `token` | VARCHAR(255) | NO | — | Cadena aleatoria única de 64 caracteres hex. |
| `fecha_expiracion` | DATETIME | NO | — | Límite temporal de validez (1 hora). |
| `usado` | TINYINT(1) | NO | `0` | `0`: No usado, `1`: Consumido. |
| `fecha_creacion` | DATETIME | NO | `CURRENT_TIMESTAMP` | Fecha y hora de solicitud. |

---

## 2. Capa Territorial

### 2.1 Tabla `zonas`
Sectores, parques, senderos y campus donde se ubica el arbolado en El Bagre.

| Campo | Tipo | Nulo | Por Defecto | Descripción / Regla |
| :--- | :--- | :---: | :--- | :--- |
| `id_zona` | CHAR(36) | NO | `(UUID())` | Llave primaria UUID. |
| `id_institucion` | CHAR(36) | NO | — | FK → `instituciones.id_institucion`. |
| `nombre_zona` | VARCHAR(100) | NO | — | Nombre representativo (ej. Parque Principal). |
| `descripcion` | VARCHAR(255) | SÍ | NULL | Detalles y límites del sector. |
| `municipio` | VARCHAR(100) | NO | `'El Bagre'` | Municipio. |
| `departamento` | VARCHAR(100) | NO | `'Antioquia'` | Departamento. |
| `latitud_referencia` | DECIMAL(10,7)| SÍ | NULL | Coordenada latitud de referencia. |
| `longitud_referencia`| DECIMAL(10,7)| SÍ | NULL | Coordenada longitud de referencia. |
| `estado` | TINYINT(1) | NO | `1` | `1`: Activa, `0`: Inactiva. |
| `fecha_creacion` | DATETIME | NO | `CURRENT_TIMESTAMP` | Fecha de creación. |

---

## 3. Capa Núcleo (Core Dendrológico)

### 3.1 Tabla `individuos_forestales`
Registro maestro de cada árbol, palma o planta ornamental censada.

| Campo | Tipo | Nulo | Por Defecto | Descripción / Regla |
| :--- | :--- | :---: | :--- | :--- |
| `id_individuo` | CHAR(36) | NO | `(UUID())` | Llave primaria UUID. |
| `codigo_placa` | VARCHAR(30) | NO | — | Código físico único grabado en la placa QR. |
| `id_zona` | CHAR(36) | NO | — | FK → `zonas.id_zona`. |
| `id_institucion` | CHAR(36) | NO | — | FK → `instituciones.id_institucion`. |
| `nombre_comun` | VARCHAR(150) | NO | — | Nombre vernáculo (ej. Guayacán Rosado). |
| `nombre_cientifico` | VARCHAR(150) | NO | — | Nombre botánico en latín. |
| `familia_botanica` | VARCHAR(100) | NO | — | Familia taxonómica (ej. Bignoniaceae). |
| `tipo_individuo` | ENUM(...) | NO | `'Árbol'` | `Árbol`, `Palma`, `Arbusto`, `Planta ornamental`. |
| `fecha_siembra` | DATE | SÍ | NULL | Fecha de plantación si es conocida. |
| `latitud` | DECIMAL(10,7)| NO | — | Latitud GPS precisa del individuo. |
| `longitud` | DECIMAL(10,7)| NO | — | Longitud GPS precisa del individuo. |
| `dap_cm` | DECIMAL(6,2) | NO | — | Diámetro a la Altura del Pecho (a 1.30 m). |
| `altura_total_m` | DECIMAL(5,2) | NO | — | Altura total en metros. |
| `diametro_copa_m`| DECIMAL(5,2) | NO | — | Diámetro promedio de la copa en metros. |
| `foto_principal_url` | VARCHAR(255) | SÍ | NULL | Ruta relativa de la foto principal. |
| `descripcion_general` | TEXT | SÍ | NULL | Observaciones dendrológicas y de entorno. |
| `id_estado_fitosanitario_actual` | CHAR(36) | SÍ | NULL | FK → `estados_fitosanitarios.id_estado`. |
| `qr_url` | VARCHAR(255) | SÍ | NULL | Ruta al archivo SVG del código QR. |
| `registrado_por` | CHAR(36) | NO | — | FK → `usuarios.id_usuario`. |
| `estado_registro` | TINYINT(1) | NO | `1` | `1`: Activo, `0`: Dado de baja lógica. |
| `fecha_registro` | DATETIME | NO | `CURRENT_TIMESTAMP` | Fecha de creación del censo. |
| `fecha_actualizacion`| DATETIME | NO | `CURRENT_TIMESTAMP ON UPDATE` | Última modificación. |

---

### 3.2 Tabla `estados_fitosanitarios`
Historial de diagnósticos y evaluaciones de salud biológica y mecánica.

| Campo | Tipo | Nulo | Por Defecto | Descripción / Regla |
| :--- | :--- | :---: | :--- | :--- |
| `id_estado` | CHAR(36) | NO | `(UUID())` | Llave primaria UUID. |
| `id_individuo` | CHAR(36) | NO | — | FK → `individuos_forestales.id_individuo`. |
| `estado` | ENUM(...) | NO | `'Sano'` | `Sano`, `En observación`, `Enfermo`, `Plagado`, `En riesgo`, `Muerto en pie`, `Talado`. |
| `diagnostico` | TEXT | SÍ | NULL | Detalle de síntomas, patógenos o daños mecánicos. |
| `recomendacion` | TEXT | SÍ | NULL | Instrucciones de manejo o tratamiento. |
| `evaluado_por` | CHAR(36) | NO | — | FK → `usuarios.id_usuario`. |
| `fecha_evaluacion` | DATETIME | NO | `CURRENT_TIMESTAMP` | Fecha de la inspección. |

---

## 4. Capa de Actividad y Auditoría

### 4.1 Tabla `actividades_mantenimiento`
Registro de intervenciones silviculturales y reportes ciudadanos.

| Campo | Tipo | Nulo | Por Defecto | Descripción / Regla |
| :--- | :--- | :---: | :--- | :--- |
| `id_actividad` | CHAR(36) | NO | `(UUID())` | Llave primaria UUID. |
| `id_individuo` | CHAR(36) | NO | — | FK → `individuos_forestales.id_individuo`. |
| `tipo_actividad` | ENUM(...) | NO | — | `Poda`, `Fumigación`, `Fertilización`, `Riego`, `Trasplante`, `Tala`, `Tutoreo`, `Reporte ciudadano`, `Otro`. |
| `descripcion` | TEXT | NO | — | Detalle de las acciones efectuadas. |
| `evidencia_foto_url` | VARCHAR(255) | SÍ | NULL | Ruta a la fotografía de evidencia. |
| `realizado_por` | CHAR(36) | SÍ | NULL | FK → `usuarios.id_usuario` (Nulo si es reporte ciudadano anónimo). |
| `fecha_actividad` | DATETIME | NO | `CURRENT_TIMESTAMP` | Fecha en que se ejecutó la labor. |
| `fecha_registro` | DATETIME | NO | `CURRENT_TIMESTAMP` | Fecha en que se ingresó al sistema. |

---

### 4.2 Tabla `logs_sistema`
Bitácora inmutable de auditoría de seguridad y trazabilidad.

| Campo | Tipo | Nulo | Por Defecto | Descripción / Regla |
| :--- | :--- | :---: | :--- | :--- |
| `id_log` | CHAR(36) | NO | `(UUID())` | Llave primaria UUID. |
| `id_usuario` | CHAR(36) | SÍ | NULL | FK → `usuarios.id_usuario` (SET NULL). |
| `accion` | VARCHAR(150) | NO | — | Nombre del evento (`INICIO_SESION`, `CREAR_INDIVIDUO`, etc.). |
| `modulo_afectado` | VARCHAR(100) | NO | — | Módulo (`AUTENTICACION`, `INDIVIDUOS`, `ZONAS`, etc.). |
| `detalle` | JSON | SÍ | NULL | Parámetros del evento en formato JSON. |
| `ip_origen` | VARCHAR(45) | SÍ | NULL | Dirección IP del cliente. |
| `fecha_registro` | DATETIME | NO | `CURRENT_TIMESTAMP` | Marca temporal inmutable. |
