# 🎨 Assets del README

Recursos visuales que acompañan al README de **ForestID**. Los banners no son imágenes exportadas de un editor: son **escenas 3D reales renderizadas en la GPU** con Three.js + WebGL.

## 📦 Inventario

| Archivo | Tipo | Dimensiones | Descripción |
| :-- | :-- | :-- | :-- |
| `forestid-hero-3d.gif` | GIF animado | 1200×360 | Héroe: árbol de puntos con placas QR en órbita, pines GPS y ondas de radar. |
| `placa-qr-3d.gif` | GIF animado | 1000×260 | Placa QR forestal en 3D con línea de escaneo. |
| `mapa-gps-3d.gif` | GIF animado | 1000×260 | Rejilla cartográfica con pines, ruta de monitoreo y punto recorriendo el trazado. |
| `modelo-datos-3d.gif` | GIF animado | 1000×260 | Base de datos volumétrica (3 discos) con filas de datos orbitando. |
| `divider.svg` | SVG | — | Separador decorativo en verde. |
| `typing.svg` | SVG animado | 420px | Subtítulo con efecto de máquina de escribir. |

## ⚙️ Pipeline de generación

1. **Escena 3D (WebGL)** — cada banner es una escena de Three.js renderizada con la **tarjeta gráfica** (AMD Radeon RX 9060 XT · `ANGLE / Direct3D 11 · vs_5_0 / ps_5_0`).
2. **Captura de fotogramas** — el lienzo se exporta a PNG fotograma a fotograma de forma determinista (todas las animaciones cierran en bucle perfecto).
3. **Codificación GIF** — los PNG se unen con **FFmpeg** usando una paleta global de 192 colores:

   ```bash
   ffmpeg -y -framerate 15 -i frames/escena_%03d.png \
     -filter_complex "[0:v]fps=12.5,split[a][b];[a]palettegen=max_colors=192:stats_mode=full[p];[b][p]paletteuse=dither=bayer:bayer_scale=4" \
     -loop 0 out/escena.gif
   ```

## 🎮 Sobre la GPU

El renderizado 3D es responsabilidad de la **tarjeta gráfica**: la geometría, el *blending* aditivo del glow, las luciérnagas por partículas y el terreno ondulado se dibujan en la GPU. En las mediciones del pipeline el render 3D cuesta apenas **~0,2–0,3 ms por fotograma**, mientras que el cuello de botella real está en la codificación GIF (PNG + FFmpeg, en CPU).

## 🎨 Paleta

| Color | Hex | Uso |
| :-- | :-- | :-- |
| Verde neón | `#00FF41` | Color principal, rejillas, wireframes y texto de acento. |
| Verde claro | `#9DFFC0` | Brillos, partículas y filas de datos. |
| Azul | `#38BDF8` | Acento secundario: rutas, sellos y datos destacados. |
| Fondo | `#040706` | Fondo base de todas las escenas. |

> 💡 Si actualizas un banner, **cámbiale el nombre al archivo**: GitHub cachea las imágenes por URL en su CDN.
