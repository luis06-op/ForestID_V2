# PROMPT PARA OPENCODE — Rediseño visual de ForestID con la identidad de Emincut

> Copia todo este documento en OpenCode (o guárdalo en la raíz del repo y dile: *"Lee `PROMPT_OPENCODE_Rediseno_Emincut.md` y ejecútalo paso a paso"*).

---

## 1. Qué vas a hacer (resumen)

Vas a **rediseñar la parte visual** del aplicativo web **ForestID / ForestSRD** (Sistema de Registro Dendrológico Forestal) para que siga fielmente el **Manual de Marca de Emincut**, la empresa minera cliente del proyecto.

El trabajo se hace **en una rama nueva**, creada a partir de la rama existente `diseno-amarillo-sol`, para no tocar el diseño actual.

- **Repositorio:** https://github.com/luis06-op/ForestID_V2
- **Rama base (origen):** `diseno-amarillo-sol` → https://github.com/luis06-op/ForestID_V2/tree/diseno-amarillo-sol
- **Rama nueva (destino):** `diseno-emincut-marca`

---

## 2. Paso 0 — Git (hazlo ANTES de cambiar cualquier archivo)

Ejecuta exactamente esto:

```bash
git fetch origin
git checkout diseno-amarillo-sol
git pull origin diseno-amarillo-sol
git checkout -b diseno-emincut-marca
```

Reglas de Git:

1. **Nunca** trabajes directamente sobre `main` ni sobre `diseno-amarillo-sol`.
2. Haz commits pequeños y descriptivos en español, uno por pantalla o componente (ejemplo: `feat(diseno): navbar con logo horizontal Emincut`).
3. Al terminar, sube la rama con `git push -u origin diseno-emincut-marca`. **No hagas merge** ni elimines ninguna rama.
4. Si hay cambios sin guardar o conflictos, detente y avísame antes de continuar.

---

## 3. Contexto del proyecto

- **Qué es:** aplicación web para gestionar el inventario forestal de árboles y plantas ornamentales. Cada individuo tiene una placa con **código QR**; la ciudadanía lo escanea y ve la ficha técnica **sin iniciar sesión**.
- **Cliente:** **Emincut**, empresa minera legal y certificada que debe contar, plantar y verificar las hectáreas reforestadas posminería (Ley 2173 de Colombia).
- **Equipo:** aprendices del SENA de El Bagre, Antioquia (Centro de Formación Minero Ambiental).
- **Roles:** `AdminEmincut`, `OperarioEmincut`, `UsuarioPublico`.
- **Idioma de toda la interfaz:** español.
- **El software es a la medida de Emincut**, así que su identidad visual debe verse como un producto de Emincut, no como una plantilla genérica.

### Stack (NO lo cambies)

- PHP puro con PDO, MySQL 8.0
- Tailwind CSS por **CDN**
- JavaScript vanilla
- Leaflet (mapas), Chart.js (gráficas), íconos **Lucide**
- Entorno local: XAMPP

> Está **prohibido** migrar a React, Vue, Laravel, Bootstrap, npm/Vite o cualquier build step. Todo debe seguir corriendo en XAMPP sin compilar nada.

---

## 4. Alcance: SOLO diseño visual

**SÍ puedes modificar:**

- Estilos (Tailwind, CSS propio), tipografías, colores, espaciados, sombras, bordes, animaciones suaves.
- Estructura HTML de las vistas y de los `includes` (header, navbar, footer, sidebar) para mejorar la presentación.
- Imágenes, íconos, logos y favicon.
- Responsive (prioridad móvil).

**NO puedes tocar:**

- Lógica PHP, consultas SQL, `config/db.php`, autenticación, sesiones, roles ni redirecciones.
- Nombres de campos de formularios (`name="..."`), IDs que use JavaScript, rutas, ni el esquema de la base de datos.
- Las variables de sesión: `usuario_id`, `usuario_nombre`, `usuario_email`, `usuario_rol`.
- El funcionamiento de la búsqueda por placa, el mapa, los gráficos ni la generación de QR.

Si para lograr un diseño necesitas cambiar algo funcional, **detente y pregúntame**.

---

## 5. Manual de marca Emincut (fuente de verdad)

Las seis imágenes del manual son `MANUAL_DE_MARCA-01.png` a `MANUAL_DE_MARCA-06.png`. Cópialas a `assets/img/brand/` (créala si no existe) y úsalas como referencia visual.

| Archivo | Contenido |
|---|---|
| `MANUAL_DE_MARCA-01.png` | Paleta de colores |
| `MANUAL_DE_MARCA-02.png` | Tipografía principal (Poppins) |
| `MANUAL_DE_MARCA-03.png` | Logo **vertical** con eslogan |
| `MANUAL_DE_MARCA-04.png` | Logo **vertical** sin eslogan |
| `MANUAL_DE_MARCA-05.png` | Logo **horizontal** sin eslogan |
| `MANUAL_DE_MARCA-06.png` | Logo **horizontal** con eslogan |

### 5.1 Paleta de colores oficial

| Nombre | HEX | RGB | CMYK | Uso sugerido |
|---|---|---|---|---|
| Verde principal | `#467F36` | 70, 127, 54 | 76 / 28 / 97 / 13 | Botones primarios, enlaces, acentos de éxito |
| Verde oscuro | `#2C5C3D` | 44, 92, 61 | 82 / 39 / 80 / 36 | Navbar, footer, sidebar, títulos, fondos de panel |
| Naranja | `#F49B14` | 244, 155, 20 | 0 / 46 / 99 / 0 | Llamados a la acción, resaltados, hover, badges |
| Amarillo | `#F5CC43` | 245, 204, 67 | 5 / 19 / 81 / 0 | Detalles, iconos destacados, fondos suaves, alertas |

Define estos colores **una sola vez** como variables CSS y como configuración de Tailwind CDN, por ejemplo:

```html
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          emincut: {
            verde:   '#467F36',
            oscuro:  '#2C5C3D',
            naranja: '#F49B14',
            amarillo:'#F5CC43',
          }
        },
        fontFamily: { sans: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'] }
      }
    }
  }
</script>
```

```css
:root {
  --emincut-verde: #467F36;
  --emincut-oscuro: #2C5C3D;
  --emincut-naranja: #F49B14;
  --emincut-amarillo: #F5CC43;
}
```

Reglas de color:

1. Usa **solo** estos cuatro colores como colores de marca. Para fondos y textos puedes usar neutros (blanco, grises muy claros, casi-negro) y **tintes/sombras derivados** de estos mismos tonos (por ejemplo, un verde muy claro para fondos de tarjetas).
2. **Contraste (accesibilidad):** el amarillo `#F5CC43` y el naranja `#F49B14` **no deben llevar texto blanco** en tamaños pequeños. Sobre ellos usa texto verde oscuro `#2C5C3D`. El texto blanco va sobre verde oscuro o verde principal.
3. Elimina cualquier otro verde, amarillo o azul que haya quedado de la rama `diseno-amarillo-sol` y que no pertenezca a la paleta.

### 5.2 Tipografía

- **Principal: Poppins** (Regular 400, Medium 500, Italic, Bold 700, ExtraBold 800, Black 900).
- Cárgala desde Google Fonts:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,700;0,800;0,900;1,400;1,700&display=swap" rel="stylesheet">
```

- Jerarquía sugerida: títulos H1/H2 en **ExtraBold/Black** (verde oscuro), subtítulos en **Bold/Medium**, cuerpo en **Regular**, énfasis en *Italic*.
- Debe soportar la **Ñ** y las tildes correctamente (todo el contenido está en español).

### 5.3 Logo (variantes y cuándo usar cada una)

| Variante | Dónde usarla |
|---|---|
| Vertical con eslogan (`-03`) | Portada/hero de la landing, pantalla de login, pantallas de carga |
| Vertical sin eslogan (`-04`) | Paneles estrechos, tarjetas, pantallas de error (404/403) |
| Horizontal sin eslogan (`-05`) | Navbar, sidebar de los dashboards, encabezados compactos |
| Horizontal con eslogan (`-06`) | Footer, encabezados amplios, documentos/reportes |

Reglas del logo:

1. **No lo deformes, no lo recolorees, no le agregues sombras ni efectos.** Mantén su proporción.
2. Respeta un margen de seguridad alrededor del logo (mínimo la altura de la letra "E" de Emincut).
3. Los archivos originales tienen fondo blanco. **Úsalos sobre fondo blanco o muy claro.** Si necesitas ponerlo sobre verde oscuro, colócalo dentro de un contenedor blanco redondeado; no lo pongas directo sobre un fondo oscuro.
4. Eslogan oficial: **"En camino con la minería responsable"**. Úsalo en la landing, el login y el footer.
5. Genera el **favicon** a partir del símbolo del sol sobre el prado (la parte gráfica del logo).

> Importante: este proyecto es **ForestID** (inventario forestal) elaborado **para** Emincut. En la interfaz debe leerse claramente que es el sistema de Emincut; el nombre ForestID/ForestSRD puede aparecer como nombre del sistema junto al logo de Emincut, sin competir con él.

---

## 6. Pantallas a rediseñar (por prioridad)

Antes de editar, **explora el repo** y lista los archivos reales de cada pantalla. Estructura esperada: `auth/`, `admin/`, `operario/`, `publico/`, `error/`, `assets/`, `includes/`.

1. **Includes compartidos** (header, navbar, footer, sidebars): aplicar logo horizontal, colores y Poppins. Esto da coherencia a todo lo demás.
2. **Landing pública `publico/index.php`:** hero llamativo con logo vertical + eslogan, buscador por placa bien destacado (naranja como llamado a la acción), estadísticas, mapa Leaflet, galería de individuos y sección "Emincut / SENA". Más imágenes y mejor jerarquía visual. (El `index.php` raíz solo redirige según sesión; no lo conviertas en landing.)
3. **Ficha pública del árbol (la que se abre al escanear el QR):** **mobile-first**, tarjetas claras, datos dasométricos legibles (DAP, estado fitosanitario, zona), foto grande, botón de volver/buscar otra placa.
4. **Login `auth/login.php`:** layout de dos paneles; panel izquierdo verde oscuro con logo vertical + eslogan (en contenedor claro), panel derecho con el formulario.
5. **Dashboard `AdminEmincut`:** sidebar verde oscuro, tarjetas de métricas, gráficas Chart.js con la paleta de marca, tablas legibles.
6. **Dashboard `OperarioEmincut`:** formularios de registro de individuos y zonas, cómodos de usar en campo (botones grandes, buena legibilidad al sol).
7. **Páginas de error (`error/`):** 403/404/500 con logo vertical sin eslogan y mensaje amable en español.

Coherencia obligatoria: la landing, el login y todos los dashboards deben **verse como un solo producto**, no como plantillas distintas.

---

## 7. Lineamientos de diseño

- **Estilo:** limpio, moderno, natural y confiable (minería responsable + reforestación). Mucho espacio en blanco, bordes redondeados (`rounded-xl` / `rounded-2xl`), sombras suaves.
- **Componentes reutilizables:** conserva o unifica clases como `btn-forest-primary` y `forest-card`, actualizándolas a la nueva paleta, para no romper las vistas que ya las usan.
  - Botón primario: fondo verde `#467F36`, hover verde oscuro `#2C5C3D`.
  - Botón de acción destacada: fondo naranja `#F49B14`, texto verde oscuro, hover un tono más intenso.
  - Tarjetas: fondo blanco, borde sutil, acento superior o lateral en amarillo/verde.
- **Mobile-first y responsive** en todas las vistas (probar en 360 px, 768 px y 1280 px).
- **Animaciones:** solo sutiles (transiciones de hover, aparición suave). Sin librerías pesadas.
- **Accesibilidad:** contraste suficiente, `alt` en las imágenes, tamaños táctiles de al menos 44 px, foco visible en inputs y botones.
- **Imágenes:** usa imágenes locales en `assets/img/`. Si necesitas fotos de apoyo (bosque, plantación, árboles), pon marcadores claros o imágenes libres de derechos y déjame la lista de cuáles faltan. No inventes datos de árboles.
- **Rendimiento:** pensar en conexiones lentas (zona rural de El Bagre): imágenes optimizadas, sin dependencias innecesarias.
- **Íconos:** Lucide (ya está en el proyecto).

---

## 8. Forma de trabajo

1. Haz el **Paso 0 de Git** (sección 2).
2. Explora el repo y **muéstrame un plan corto** con: lista de archivos que vas a tocar y orden de trabajo. Espera mi visto bueno antes de editar.
3. Trabaja **archivo por archivo**, en el orden de la sección 6. Al terminar cada uno, haz commit y dime qué cambió y cómo probarlo en `http://localhost/...` con XAMPP.
4. No generes todo el proyecto de golpe.
5. Todo texto visible al usuario en **español**.
6. Si algo del manual de marca es ambiguo, **pregunta** en lugar de inventar.

---

## 9. Criterios de aceptación

- [ ] La rama `diseno-emincut-marca` existe, sale de `diseno-amarillo-sol` y está subida a GitHub.
- [ ] `diseno-amarillo-sol` y `main` quedaron intactas.
- [ ] Solo se usan los 4 colores de marca (más neutros y tintes derivados) y **Poppins** en toda la interfaz.
- [ ] El logo se usa en la variante correcta en cada pantalla, sin deformarlo ni recolorearlo.
- [ ] Hay favicon basado en el símbolo del sol.
- [ ] Landing, ficha pública (QR), login, dashboards y páginas de error son coherentes entre sí.
- [ ] La ficha pública se ve y funciona bien en móvil.
- [ ] Login, búsqueda por placa, mapa, gráficas y formularios siguen funcionando igual que antes (cero cambios de lógica).
- [ ] No se añadió ningún framework ni paso de compilación.
- [ ] Textos en español, con tildes y Ñ correctas.

---

## 10. Entrega final

Al terminar, dame:

1. La lista de commits de la rama.
2. Un resumen por pantalla de lo que cambió.
3. La lista de imágenes o recursos que falten por conseguir.
4. Cualquier decisión de diseño que hayas tomado por tu cuenta y por qué.
