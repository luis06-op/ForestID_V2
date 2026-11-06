# ForestID — Modo Feria FISAD (sitio estático)

Sitio **sin PHP y sin base de datos**, pensado para publicarse en Vercel durante la feria.
Contiene tres fichas accesibles por QR:

| Página | QR apunta a | Contenido |
|---|---|---|
| `ficha-mango.html` | `…/ficha-mango.html` | Mango (`Mangifera indica`) en el Polideportivo de El Bagre, coordenadas 7.5940938, -74.8028396 |
| `ficha-guayacan.html` | `…/ficha-guayacan.html` | Guayacán Rosado `EBG-PARQ-001` (datos reales del volcado oficial) |
| `qr-vacio.html` | `…/qr-vacio.html` | «Este QR está vacío» + acceso al login para operarios de Emincut |

La portada (`index.html`) muestra las tres con su QR.

## Los QR se generan solos

`assets/qr.js` dibuja cada código en el navegador a partir de la **URL real** donde esté
publicado el sitio (`location.origin`). Por eso no hay URLs de dominio incrustadas: funciona
igual en `localhost`, en `*.vercel.app` o en el dominio definitivo. Si se cambia de dominio,
los QR impresos desde la página siguen siendo correctos.

## Publicar en Vercel (desde GitHub)

1. En Vercel: **Add New → Project → Import Git Repository** y elige este repositorio.
2. En **Root Directory** selecciona **`feria`** (importante: así no se publica el código PHP,
   ni `docs/`, ni los `.sql` del repositorio).
3. Framework Preset: **Other**. Build Command: vacío. Output Directory: vacío (o `.`).
4. **Deploy**.
5. Abre la URL resultante y comprueba que aparecen los tres QR.

> No subas el repositorio completo como sitio estático: expondría `database/*.sql`, `docs/`
> y el código fuente. El directorio raíz del proyecto en Vercel debe ser `feria/`.

## Probar en local

```bash
cd feria
php -S 127.0.0.1:8110
# http://127.0.0.1:8110/
```

## Datos y trazabilidad

- **Mango**: datos botánicos públicos. Coordenadas del Polideportivo de El Bagre tomadas de
  OpenStreetMap (7.5940938, -74.8028396). DAP, altura y diámetro de copa quedan como
  «por registrar» hasta la medición en campo: no se inventaron valores.
- **Guayacán**: datos del volcado oficial `database/forestsrd_db_hostinger.sql`
  (semilla `EBG-PARQ-001`).
- **Acceso funcionarios**: enlace al sistema real en `https://forestid.cfmasoft.com/auth/login.php`.

## Cuando termine la feria

Este directorio es independiente de la aplicación PHP. Se puede archivar el proyecto de
Vercel sin afectar a Hostinger, donde vive el sistema con su base de datos.
