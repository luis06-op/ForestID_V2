# Prompt de contexto — ForestID V2 (Emincut)

Copia y pega este texto al iniciar una sesión nueva con el agente:

---

Vas a trabajar en el proyecto **ForestID V2**, ubicado en `/home/luis/Proyectos/github/ForestID_V2` (Linux) o `C:\xampp\htdocs\ForestID_V2` (Windows), servido con XAMPP en `http://localhost/ForestID_V2/`.

## Qué es el proyecto

Aplicación web **exclusiva de Emincut S.A.S.** (El Bagre, Antioquia, Colombia) para gestionar el inventario forestal —árboles y plantas ornamentales— y el cumplimiento de la Ley 2173 de 2021 (restauración posminería). Cada árbol tiene una **placa física con código QR** que el público puede escanear y consultar **sin iniciar sesión**.

**Decisión vigente:** la aplicación ya **no es multi-entidad ni multi-institución**. No existen instituciones, entidades administradoras ni clientes externos. Todos los usuarios, zonas e individuos pertenecen a Emincut. Las **zonas** se conservan como áreas internas de Emincut (parques, campus, rondas hídricas, etc.).

Fue desarrollado por aprendices del SENA del Centro de Formación Minero Ambiental de El Bagre (Luis Ángel Muñoz, Gabriel Correa y Valentina Hernández Zapata); hay una conmemoración *In memoriam* para Michael Rodríguez L. (UI/QA) en el footer. Esos créditos históricos no implican multi-entidad.

## Stack y entorno

- **PHP** (XAMPP: `C:\xampp\php\php.exe`, Apache + MySQL) con PDO/MySQL, sin frameworks.
- **Tailwind CSS por CDN**, Lucide Icons, Chart.js, Leaflet, html5-qrcode.
- Base de datos: `forestsrd_db` (usuario `root`, sin contraseña en local).
- Git en `C:\Program Files\Git\cmd\git.exe` (PowerShell es la shell: usar `foreach ($x in $lista)`, no `as`).
- Repositorio: `https://github.com/luis06-op/ForestID_V2.git`

## Rama de trabajo

Trabaja **siempre en la rama `diseno-amarillo-sol`** (nunca en `main`). Al terminar cada cambio: `git add`, `git commit -m "..."` (mensaje en español, descriptivo) y `git push`.

## Diseño (identidad visual Emincut)

Variante "sol": **el amarillo es protagonista, con el verde como institucional y el blanco de base**. Sin degradados genéricos morados ni estéticas "AI slop".

- Paleta (variables CSS en `assets/css/emincut.css`):
  - Verde principal `#5B7C3A` · verde casco `#35502B` · verde noche `#22371C`
  - Ámbar/amarillo `#E9A22E` y `#F2B632` · blanco/hueso `#FBFAF6`
- Tipografías: **Chakra Petch** (títulos/labels, clase `.emi-display`) y **Asap** (cuerpo). Evitar Inter/Roboto/Arial y Space Grotesk.
- Logo oficial de Emincut: `assets/img/logo_emincut_oficial.svg` (casco verde + sol amarillo + colina).
- Componentes reutilizables: `.emi-btn`, `.emi-btn-sol`, `.emi-btn-verde`, `.emi-card`, `.emi-kpi`, `.emi-input`, `.emi-label`, `.emi-sidebar`, `.emi-navbar`, `.linea-sol`.
- Animaciones globales: clase `.al-scroll` (+ `.d1`, `.d2`, `.d3`) con IntersectionObserver, contadores animados con `data-contador` y `data-sufijo` (el JS vive en `includes/footer.php`). Siempre respetar `prefers-reduced-motion`.
- Los paneles usan navbar verde noche **unida** al sidebar (mismo color) y en móvil el sidebar es un **drawer deslizante** con overlay.

## Estructura clave

- `index.php` — enrutador; redirige según rol.
- `auth/login.php` — login (branding Emincut, sin lógica alterada).
- `publico/index.php` — portada pública con hero animado (sol con rayos, hojas flotantes, contadores).
- `admin/` — panel de administración (dashboard, individuos, zonas, qr, usuarios, reportes, logs).
- `operario/` — panel de campo (dashboard, escanear QR, altas, mantenimientos).
- `includes/` — `header.php`, `navbar.php`, `footer.php`, `sidebar_admin.php`, `sidebar_operario.php`, `auth_check.php`, `funciones.php`, `notificaciones.php`, `qrcode_helper.php`.
- `config/empresa.php` — datos corporativos de Emincut (nombre, NIT, dirección, teléfono, correo, logo).
- `assets/css/emincut.css` — identidad visual de marca (se carga global desde `header.php`).

## Modelo de datos (8 tablas)

No existe la tabla `instituciones` ni columnas `id_institucion`.

- **Acceso y seguridad:** `roles`, `usuarios`, `tokens_recuperacion`.
- **Territorial:** `zonas` (áreas internas de Emincut).
- **Núcleo dendrológico:** `individuos_forestales`, `estados_fitosanitarios`.
- **Actividad y auditoría:** `actividades_mantenimiento`, `logs_sistema`.

Los datos de la empresa viven en `config/empresa.php` (`EMINCUT_NOMBRE`, `EMINCUT_NIT`, `EMINCUT_TELEFONO`, etc.), no en la base de datos.

## Flujo QR (organizado)

- `config/db.php` define `APP_URL` (URL canónica). Si no está definida en `config/config.local.php`, se detecta automáticamente desde la petición.
- `includes/qrcode_helper.php` expone `QRCodeGenerator::urlFicha($codigoPlaca)` y `QRCodeGenerator::guardarQR(...)`. Todos los QR deben construirse con `urlFicha()`.
- `admin/qr/index.php` muestra el estado de los QR, permite **regenerar uno o todos** (o los de una zona) y enlaza a la impresión.
- `admin/qr/imprimir.php` genera las placas físicas solo con identidad Emincut.
- Los SVG se guardan en `assets/qr/qr_[CODIGO].svg`.
- **En producción:** defina `APP_URL` con el dominio real en `config/config.local.php` y regenere los QR desde el gestor. Los QR semilla actuales apuntan a `http://localhost/ForestID_V2/`.

## Reglas de código

- **Seguridad:** toda página privada debe empezar con `requerir_rol([...])`. Solo `AdminEmincut` entra a `admin/*`; `operario/*` permite `OperarioEmincut` y `AdminEmincut`. Usar `sanear()` para normalizar la entrada (no escapa HTML), `htmlspecialchars()` en toda salida y `validar_token_csrf()` en formularios POST.
- Mantener el estilo visual y los includes existentes; no duplicar CSS ya presente en `emincut.css`.
- Verificar siempre la sintaxis con `& "C:\xampp\php\php.exe" -l <archivo>` (Windows) o `php -l <archivo>` (Linux) antes de confirmar.
- No incluir referencias a instituciones, entidades administradoras ni clientes externos: el proyecto es **exclusivo de Emincut**.
- No volver a introducir `id_institucion` ni la tabla `instituciones`.

## Estado actual / pendientes conocidos

- Refactor “solo Emincut” completado: tabla `instituciones` y columnas `id_institucion` eliminadas del esquema; módulo `admin/instituciones/` retirado; formularios, reportes, ficha pública y QR sin institución.
- Migración para bases existentes: `database/migraciones/2026-10-23_solo_emincut.sql`.
- El envío de credenciales por **correo** y **WhatsApp** requiere configurar un **SMTP real** y las credenciales de **WhatsApp Business Cloud API** (`WHATSAPP_API_TOKEN`, `WHATSAPP_API_PHONE_ID`); mientras tanto se ofrece enlace `wa.me` y `mailto:` de respaldo.
- Quedan menciones históricas del equipo y del SENA como créditos en `README.md`, `docs/` y el footer. No afectan el alcance funcional exclusivo de Emincut.

## Cómo empezar

1. Sitúate en `/home/luis/Proyectos/github/ForestID_V2` (o `C:\xampp\htdocs\ForestID_V2`) y confirma la rama con `git branch --show-current` (debe ser `diseno-amarillo-sol`).
2. Revisa los archivos relevantes antes de editar; respeta el diseño y las reglas anteriores.
3. Ejecuta, verifica sintaxis, prueba en `http://localhost/ForestID_V2/` y sube los cambios a GitHub.

---

**Tarea de esta sesión:** _(describe aquí lo que quieres hacer)_
