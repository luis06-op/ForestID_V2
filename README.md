<div align="center">

<img src="assets/readme/banner-forestid.svg" alt="🌲 ForestID — Banner animado" width="100%"/>

# 🌲 ForestID <sub>(ForestSRD)</sub>

**Sistema de Identificación y Gestión del Inventario Forestal**

<img src="assets/readme/typing.svg" alt="Monitoreo fitosanitario en tiempo real — animación" width="420"/>

<br/>

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-CDN-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Leaflet](https://img.shields.io/badge/Leaflet-OpenStreetMap-22771E?style=for-the-badge&logo=leaflet&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-Tableros-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)
![QR SVG](https://img.shields.io/badge/QR-Generador%20SVG%20propio-1B5E20?style=for-the-badge)

<img src="assets/readme/divider.svg" alt="" width="65%"/>

</div>

**ForestID** (ForestSRD) es una plataforma web integral para la catalogación, el monitoreo fitosanitario y la trazabilidad histórica del arbolado urbano y rural de la empresa **Emincut S.A.S.**, con sede en el municipio de **El Bagre, Antioquia** (Bajo Cauca antioqueño).

Cada individuo forestal recibe una **placa QR única**: el personal de campo la escanea con el celular para registrar salud y mantenimientos con coordenadas GPS, y cualquier ciudadano puede consultar su ficha técnica o reportar novedades **sin iniciar sesión**.

El proyecto nace en el marco de la iniciativa **SENA Regional Antioquia – iRed Innovacción**, semillero de innovación que fomenta soluciones tecnológicas con alto impacto ambiental, social y productivo.

---

<a id="indice"></a>
## 📌 Índice

|  |  |  |
| :--: | :--: | :--: |
| 🌳 [Acerca del proyecto](#acerca) | ✨ [Características](#caracteristicas) | 🧩 [Módulos y roles](#modulos) |
| 🗄️ [Modelo de datos](#datos) | 🛠️ [Stack tecnológico](#stack) | 🔐 [Seguridad](#seguridad) |
| 🔑 [Credenciales de prueba](#credenciales) | 🚀 [Instalación en XAMPP](#instalacion) | 📁 [Estructura del proyecto](#estructura) |
| 📚 [Documentación](#docs) | 👥 [Equipo de proyecto](#equipo) | 🏆 [Reconocimientos](#reconocimientos) |

---

<a id="acerca"></a>
## 🌳 Acerca del proyecto

ForestID nació como respuesta a una necesidad real de **Emincut S.A.S.**: dejar atrás las listas en papel y las planillas dispersas para tener un inventario vivo, georreferenciado y auditable del arbolado de El Bagre. Hoy funciona como una aplicación web modular en **PHP puro + MySQL**, pensada para correr en servidores locales XAMPP y en entornos LAMP/LEMP de producción, sin dependencias de compilación ni servicios de pago.

|  |  |
| :-- | :-- |
| 🏢 **Cliente** | Emincut S.A.S. — «En camino con la minería responsable» |
| 📍 **Sede / cobertura** | El Bagre, Antioquia (Bajo Cauca antioqueño) |
| 🎓 **Origen** | SENA Regional Antioquia – iRed Innovacción |
| 🎯 **Objeto** | Catalogación, monitoreo fitosanitario y trazabilidad histórica del arbolado |
| 👥 **Usuarios** | Administradores, operarios de campo y ciudadanía en general |
| 💻 **Despliegue** | XAMPP (desarrollo local) · LAMP / LEMP (producción) |

---

<a id="caracteristicas"></a>
## ✨ Características destacadas

|  |  |
| :-- | :-- |
| **🏷️ Placas QR propias**<br/>Generador vectorial SVG autónomo integrado: cada árbol obtiene su placa única, lista para imprimir y pegar en campo. | **📱 Escáner en campo**<br/>Lectura en vivo de placas con la cámara del celular o la webcam mediante `html5-qrcode`. |
| **🗺️ GPS + mapas**<br/>Alta rápida georreferenciada y visualización de zonas e individuos con Leaflet + OpenStreetMap, sin claves de API ni costos. | **🩺 Salud del arbolado**<br/>Historial inmutable de estados fitosanitarios con diagnóstico, recomendaciones y evolución en el tiempo. |
| **📊 Tableros y reportes**<br/>Indicadores dasométricos y fitosanitarios con Chart.js, más exportación de reportes a CSV / Excel. | **🌐 Portal ciudadano**<br/>Ficha técnica pública vía QR, explorador y buscador de árboles, y reporte de novedades sin iniciar sesión. |
| **🔍 Trazabilidad total**<br/>Bitácora de actividades (poda, fumigación, riego…) y auditoría de logs con IP de origen y detalles en JSON. | **🔒 Seguridad por diseño**<br/>Bcrypt, roles por sesión, middleware de autenticación y consultas 100 % con Prepared Statements. |

<div align="center">
<table>
<tr>
<td width="50%" align="center">
<img src="assets/readme/qr-scan.svg" alt="Animación: escaneo QR en tiempo real" width="100%"/>
</td>
<td width="50%" align="center">
<img src="assets/readme/chart-animate.svg" alt="Animación: tablero de indicadores con gráficos" width="100%"/>
</td>
</tr>
</table>
</div>

---

<a id="modulos"></a>
## 🧩 Módulos y roles

| Módulo | Ruta | Rol | Qué puedes hacer |
| :-- | :--: | :-- | :-- |
| 🧑‍💼 **Administrador** | `/admin/` | `AdminEmincut` | Dashboard de indicadores, gestión de individuos, zonas e instituciones, generación e impresión masiva de placas QR, reportes con exportación, administración de usuarios y bitácora de logs. |
| 🌿 **Operario de campo** | `/operario/` | `OperarioEmincut` | Escaneo QR con la cámara, alta rápida de individuos con GPS, registro de mantenimientos y actualización del estado fitosanitario. |
| 🌐 **Portal ciudadano** | `/publico/` | Sin login | Ficha técnica pública por QR, explorador y buscador de árboles y reporte de novedades con evidencia fotográfica. |
| 🔑 **Autenticación** | `/auth/` | — | Inicio y cierre de sesión, y recuperación de contraseña mediante token criptográfico de un solo uso. |

---

<a id="datos"></a>
## 🗄️ Modelo de datos

La base de datos `forestsrd_db` corre sobre **MySQL 8.0** con motor **InnoDB**, juego de caracteres `utf8mb4` (collation `utf8mb4_spanish_ci`) y **llaves primarias UUID** generadas nativamente con `DEFAULT (UUID())`.

| Capa lógica | Tablas | Contenido |
| :-- | :-- | :-- |
| 🏛️ **Institucional** | `roles` · `instituciones` · `usuarios` · `tokens_recuperacion` | Catálogo de perfiles, clientes, cuentas con Bcrypt y tokens temporales de 1 hora. |
| 🗺️ **Territorial** | `zonas` | Parques, campus y sectores georreferenciados. |
| 🌳 **Núcleo dendrológico** | `individuos_forestales` · `estados_fitosanitarios` | Registro maestro (placa, DAP, altura, copa, siembra, GPS, foto) e historial de salud inmutable. |
| 🕵️ **Actividad y auditoría** | `actividades_mantenimiento` · `logs_sistema` | Labores silviculturales y reportes ciudadanos, más bitácora de eventos con IP y detalles JSON. |

---

<a id="stack"></a>
## 🛠️ Stack tecnológico

| Capa | Tecnología | Detalle |
| :-- | :-- | :-- |
| ⚙️ **Backend** | **PHP 8.x** puro | Arquitectura modular sin frameworks; **100 % de consultas con Prepared Statements** de PDO. |
| 🗄️ **Base de datos** | **MySQL 8.0** / MariaDB 10.3+ | InnoDB, `utf8mb4_spanish_ci` y llaves primarias UUID nativas. |
| 🔑 **Autenticación** | `password_hash()` / `password_verify()` | Bcrypt + control de acceso por sesión y roles. |
| 🎨 **Frontend** | HTML5 semántico · **Tailwind CSS** (CDN) · JavaScript ES6+ | Interfaz responsive con **Lucide Icons**. |
| 🗺️ **Georreferenciación** | **Leaflet.js** + OpenStreetMap | Cartografía 100 % libre, sin APIs de pago. |
| 🏷️ **Códigos QR** | Generador SVG propio + `html5-qrcode` | Placas vectoriales autónomas y escaneo con cámara en vivo. |
| 📊 **Analítica** | **Chart.js** | Tableros dasométricos y fitosanitarios. |

---

<a id="seguridad"></a>
## 🔐 Seguridad

- ✅ **Inyección SQL imposible:** el 100 % de las consultas usan *Prepared Statements* de PDO con paso estricto de parámetros (concatenar variables en SQL está prohibido por convención).
- ✅ **Contraseñas con Bcrypt** mediante `password_hash()` / `password_verify()`.
- ✅ **Control de acceso por roles** con el middleware `includes/auth_check.php`.
- ✅ **Tokens de recuperación** criptográficos, de un solo uso y con validez de 1 hora.
- ✅ **Auditoría inmutable** en `logs_sistema` con estructura JSON e IP de origen.

---

<a id="credenciales"></a>
## 🔑 Credenciales de prueba

<details>
<summary>🔒 <b>Haz clic para desplegar las credenciales del entorno local</b></summary>

<br/>

> ⚠️ **Uso exclusivo en entorno local de desarrollo.** Cámbialas o elimínalas antes de publicar el sistema en un servidor.

| Rol | Correo electrónico | Contraseña | Alcance / permisos |
| :-- | :-- | :-- | :-- |
| **AdminEmincut** | `admin@emincut.com` | `Admin123*` | Control total: usuarios, zonas, instituciones, QR masivo y logs. |
| **OperarioEmincut** | `operario@emincut.com` | `Operario123*` | Escaneo QR en campo, alta rápida con GPS, mantenimientos y salud. |
| **UsuarioPublico** | `ciudadano@emincut.com` | `Ciudadano123*` | Portal ciudadano, ficha pública sin login y reporte de novedades. |

</details>

---

<a id="instalacion"></a>
## 🚀 Instalación paso a paso en XAMPP

**Requisitos:** [XAMPP](https://www.apachefriends.org/) con Apache, MySQL y PHP 8.x instalados.

1. **Copia el proyecto en `htdocs`**

   Coloca la carpeta `forestsrd` dentro del directorio `htdocs` de tu instalación de XAMPP:

   - 🪟 **Windows:** `C:\xampp\htdocs\forestsrd`
   - 🐧 **Linux:** `/opt/lampp/htdocs/forestsrd`

2. **Arranca los servicios**

   Abre el panel de XAMPP e inicia los módulos **Apache** y **MySQL**.

3. **Importa la base de datos**

   - Entra a `http://localhost/phpmyadmin`.
   - Crea una nueva base de datos llamada `forestsrd_db` con collation `utf8mb4_spanish_ci`.
   - Selecciona la base de datos, ve a la pestaña **Importar** y elige el archivo `database/forestsrd_db.sql`.
   - Haz clic en **Continuar** para crear las tablas y los datos semilla.

4. **Verifica la conexión (`config/db.php`)**

   Asegúrate de que los parámetros coincidan con tu entorno:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'forestsrd_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

5. **Entra al sistema** 🎉

   Abre en tu navegador 👉 **`http://localhost/forestsrd`** e inicia sesión con las credenciales de prueba de la sección anterior.

---

<a id="estructura"></a>
## 📁 Estructura del proyecto

<details>
<summary>📂 <b>Haz clic para desplegar el árbol de carpetas</b></summary>

<br/>

```text
forestsrd/
├── index.php      # Enrutador principal (redirige según rol o al portal público)
├── config/        # Conexión PDO centralizada y constantes globales
├── auth/          # Login, logout y recuperación de contraseña con token
├── admin/         # Panel administrativo (individuos, zonas, QR, reportes, logs, usuarios)
├── operario/      # Panel de campo (escáner QR, alta rápida con GPS, mantenimientos)
├── publico/       # Portal ciudadano (ficha vía QR, catálogo y reporte de novedades)
├── includes/      # Componentes reutilizables, middlewares, helpers y generador QR
├── assets/        # CSS, JS, imágenes, QR SVG generados y subidas
├── error/         # Páginas de error 403, 404 y 500
├── database/      # Script DDL + DML completo para MySQL 8.0
└── docs/          # Manuales, diccionario de datos y diagramas
```

</details>

---

<a id="docs"></a>
## 📚 Documentación

Todo el conocimiento del proyecto vive en [`docs/`](docs):

[![Manual de Usuario](https://img.shields.io/badge/%F0%9F%93%98-Manual%20de%20Usuario-2E7D32?style=for-the-badge)](docs/MANUAL_USUARIO.md)
[![Manual Técnico](https://img.shields.io/badge/%F0%9F%9B%A0-Manual%20T%C3%A9cnico-1B5E20?style=for-the-badge)](docs/MANUAL_TECNICO.md)
[![Diccionario de Datos](https://img.shields.io/badge/%F0%9F%97%84-Diccionario%20de%20Datos-0C4831?style=for-the-badge)](docs/DICCIONARIO_DATOS.md)
[![Diagrama Entidad-Relación](https://img.shields.io/badge/%F0%9F%93%90-Diagrama%20ER-E9A22E?style=for-the-badge)](docs/diagramas/diagrama_er.md)
[![Diagrama de Flujos](https://img.shields.io/badge/%F0%9F%94%80-Diagrama%20de%20Flujos-F2B632?style=for-the-badge)](docs/diagramas/diagrama_flujos.md)

---

<a id="equipo"></a>
## 👥 Equipo de proyecto

| Nombre | Rol en ForestID |
| :-- | :-- |
| **Luis Angel Muñoz Márquez** | 🏗️ Líder de Arquitectura |
| **Gabriel Jaime Correa Correa** | ⚙️ Desarrollador Backend |
| **Michael Rodríguez Londoño** | 🎨 Desarrollador UI / QA |
| **Valentina** | ✅ Supervisor / Verificador de Calidad |

---

<a id="reconocimientos"></a>
## 🏆 Reconocimientos

<div align="center">

🌱 Proyecto de innovación desarrollado en el marco de **SENA Regional Antioquia – iRed Innovacción**<br/>
para la empresa **Emincut S.A.S.** — El Bagre, Antioquia 🇨🇴

<img src="assets/readme/divider.svg" alt="" width="65%"/>

[![Volver al índice](https://img.shields.io/badge/%E2%86%91-Volver%20al%20%C3%ADndice-2E7D32?style=flat-square)](#indice)

<sub>Hecho con 💚 y PHP puro en el Bajo Cauca antioqueño.</sub>

</div>
