# ForestID — Sistema de Identificación y Gestión del Inventario Forestal

**ForestID** (ForestSRD) es una plataforma web integral diseñada para la catalogación, monitoreo fitosanitario y trazabilidad histórica del arbolado urbano y rural para la empresa **Emincut S.A.S.** con sede en el municipio de **El Bagre, Antioquia** (Bajo Cauca antioqueño).

El proyecto nace en el marco de la iniciativa **SENA Regional Antioquia – iRed Innovacción**, semillero de innovación que fomenta soluciones tecnológicas con alto impacto ambiental, social y productivo.

---

## 👥 Equipo de Proyecto

- **Luis Angel Muñoz Márquez** — Líder de Arquitectura
- **Gabriel Jaime Correa Correa** — Desarrollador Backend
- **Michael Rodríguez Londoño** — Desarrollador UI / QA
- **Valentina** — Supervisor / Verificador de Calidad

---

## 🛠️ Stack Tecnológico

- **Backend:** PHP 8.x puro (modular, PDO con 100% de consultas preparadas para máxima seguridad contra SQL Injection).
- **Base de Datos:** MySQL 8.0 / MariaDB 10.3+ (Llaves primarias `UUID`, motor `InnoDB`, collation `utf8mb4_spanish_ci`).
- **Autenticación:** `password_hash()` / `password_verify()` con algoritmo Bcrypt y control de acceso por sesión y roles.
- **Frontend:** HTML5 semántico, Tailwind CSS (vía CDN), Vanilla JavaScript ES6+, Lucide Icons.
- **Georreferenciación:** Leaflet.js con cartografía de OpenStreetMap (100% libre, sin requerir APIs de pago).
- **Lectura y Generación QR:** 
  - Generador autónomo integrado de códigos QR vectoriales en formato SVG.
  - Escáner óptico en tiempo real con cámara móvil/webcam mediante `html5-qrcode`.
- **Estadísticas:** Chart.js para tableros de análisis dasométrico y fitosanitario.

---

## 🔑 Credenciales de Prueba (Entorno Local)

| Rol | Correo Electrónico | Contraseña | Alcance / Permisos |
| :--- | :--- | :--- | :--- |
| **AdminEmincut** | `admin@emincut.com` | `Admin123*` | Control total, usuarios, zonas, instituciones, QR masivo, logs. |
| **OperarioEmincut** | `operario@emincut.com` | `Operario123*` | Escaneo QR en campo, alta rápida con GPS, mantenimientos y salud. |
| **UsuarioPublico** | `ciudadano@emincut.com` | `Ciudadano123*` | Portal ciudadano, ficha pública sin login, reporte de novedades. |

---

## 🚀 Guía de Instalación Paso a Paso en XAMPP

1. **Copiar el Proyecto en `htdocs`:**
   Copie la carpeta `forestsrd` dentro del directorio `htdocs` de su instalación de XAMPP:
   - Windows: `C:\xampp\htdocs\forestsrd`
   - Linux: `/opt/lampp/htdocs/forestsrd`

2. **Iniciar Servicios en el Panel de XAMPP:**
   Inicie los módulos de **Apache** y **MySQL**.

3. **Importar la Base de Datos en phpMyAdmin:**
   - Abra en su navegador web: `http://localhost/phpmyadmin`
   - Cree una nueva base de datos llamada `forestsrd_db` (Collation: `utf8mb4_spanish_ci`).
   - Seleccione la base de datos, vaya a la pestaña **Importar** y seleccione el archivo:
     `forestsrd/database/forestsrd_db.sql`
   - Haga clic en **Continuar** para ejecutar la creación de tablas y datos semilla.

4. **Configurar credenciales locales (`config/config.local.php`):**
   Copie la plantilla y ajuste los parámetros de su entorno:
   ```bash
   cp config/config.local.ejemplo.php config/config.local.php
   ```
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'forestsrd_db');
   define('DB_USER', 'forestsrd_app');
   define('DB_PASS', 'su_clave_segura');
   ```
   Este archivo está en `.gitignore` y **no debe subirse al repositorio**. En producción use un usuario MySQL con permisos mínimos, nunca `root`.

5. **Acceder a la Aplicación:**
   Abra en su navegador:
   👉 **`http://localhost/ForestID_V2`**
   > Asegúrese de que Apache tenga `AllowOverride All` y `mod_rewrite` activo para que los `.htaccess` protejan `.git`, `database/`, `docs/`, `config/`, `includes/` y `vendor/`.

---

## 📁 Estructura del Proyecto

```
forestsrd/
├── config/              # Conexión PDO centralizada y constantes globales
├── auth/                # Login, Logout y recuperación de contraseña con token
├── admin/               # Panel administrativo (Individuos, Zonas, QR, Reportes, Logs, Usuarios)
├── operario/            # Panel operativo móvil (Escáner cámara, Alta rápida GPS, Mantenimientos)
├── publico/             # Portal público, ficha técnica vía QR, catálogo y reporte ciudadano
├── error/               # Páginas de error 403, 404, 500
├── assets/              # Estilos CSS, Scripts JS, Imágenes, QR SVG generados y Uploads
├── includes/            # Componentes reutilizables, sidebars, middlewares y helpers
├── database/            # Script DDL + DML completo para MySQL 8.0
└── docs/                # Manual de usuario, manual técnico, diccionario de datos y diagramas
```
