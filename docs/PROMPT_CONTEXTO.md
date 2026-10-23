# Prompt de contexto — ForestID V2 (Emincut)

Copia y pega este texto al iniciar una sesión nueva con el agente:

---

Vas a trabajar en el proyecto **ForestID V2**, ubicado en `C:\xampp\htdocs\ForestID_V2` y servido con XAMPP en `http://localhost/ForestID_V2/`.

## Qué es el proyecto
Aplicación web de **Emincut S.A.S.** (El Bagre, Antioquia, Colombia) para gestionar el inventario forestal —árboles y plantas ornamentales— y el cumplimiento de la Ley 2173 de 2021 (restauración posminería). Cada árbol tiene una **placa física con código QR** que el público puede escanear y consultar **sin iniciar sesión**. Fue desarrollado por aprendices del SENA del Centro de Formación Minero Ambiental de El Bagre (Luis Ángel Muñoz, Gabriel Correa y Valentina Hernández Zapata); hay una conmemoración *In memoriam* para Michael Rodríguez L. (UI/QA) en el footer.

## Stack y entorno
- **PHP** (XAMPP: `C:\xampp\php\php.exe`, Apache + MySQL) con PDO/MySQL, sin frameworks.
- **Tailwind CSS por CDN**, Lucide Icons, Chart.js, Leaflet, html5-qrcode.
- Base de datos: `forestsrd_db` (usuario `root`, sin contraseña).
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
- `admin/` — panel de administración (dashboard, individuos, zonas, qr, usuarios, reportes, logs, instituciones).
- `operario/` — panel de campo (dashboard, escanear QR, altas, mantenimientos).
- `includes/` — `header.php`, `navbar.php`, `footer.php`, `sidebar_admin.php`, `sidebar_operario.php`, `auth_check.php`, `funciones.php`, `notificaciones.php`.
- `assets/css/emincut.css` — identidad visual de marca (se carga global desde `header.php`).

## Reglas de código
- **Seguridad:** toda página privada debe empezar con `requerir_rol([...])`. Solo `AdminEmincut` entra a `admin/*`; `operario/*` permite `OperarioEmincut` y `AdminEmincut`. Usar `sanear()`, `validar_token_csrf()` y `htmlspecialchars()` como ya se hace.
- Mantener el estilo visual y los includes existentes; no duplicar CSS ya presente en `emincut.css`.
- Verificar siempre la sintaxis con `& "C:\xampp\php\php.exe" -l <archivo>` antes de confirmar.
- No incluir la palabra "ForestID" como si fuera de Emincut en textos institucionales nuevos: el proyecto es **exclusivo de Emincut** (no mencionar redes de innovación ni otros aliados en el footer).

## Estado actual / pendientes conocidos
- El envío de credenciales por **correo** y **WhatsApp** ya está implementado en `includes/notificaciones.php` y se dispara al crear un usuario, pero requiere configurar un **SMTP real** (XAMPP no envía correo) y las credenciales de **WhatsApp Business Cloud API** (`WHATSAPP_API_TOKEN`, `WHATSAPP_API_PHONE_ID`) para el envío automático; mientras tanto se ofrece enlace `wa.me` y `mailto:` de respaldo.
- El módulo de **Instituciones** existe pero está oculto del menú lateral (sección renombrada a "Gestión").
- Las referencias a SENA/iRed Innovacción siguen en `README.md`, `config/db.php`, `database/forestsrd_db.sql` y `admin/qr/imprimir.php` (no se han eliminado).

## Cómo empezar
1. Sitúate en `C:\xampp\htdocs\ForestID_V2` y confirma la rama con `git branch --show-current` (debe ser `diseno-amarillo-sol`).
2. Revisa los archivos relevantes antes de editar; respeta el diseño y las reglas anteriores.
3. Ejecuta, verifica sintaxis, prueba en `http://localhost/ForestID_V2/` y sube los cambios a GitHub.

---

**Tarea de esta sesión:** _(describe aquí lo que quieres hacer)_
