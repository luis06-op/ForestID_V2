# Reparto de Trabajo — Autocompletado de Especies (ForestID_V2)

**Objetivo del reparto:** Implementar el enfoque 2 (catálogo local + aprendizaje de BD)
para el autocompletado offline de **Nombre Científico** y **Familia Botánica** en
`operario/registrar_individuo.php`.

**Instrucciones de implementación completas:** `/home/luis/Descargas/instrucciones_autocompletado_especies_forestid_v2.md`

---

## Repositorio

- **Repo:** ForestID_V2
- **GitHub:** `luis06-op/ForestID_V2` (remote `origin`)
- **Ruta local:** `/home/luis/Proyectos/github/ForestID_V2`
- **Rama activa:** `diseno-amarillo-sol`

---

## Distribución de tareas

### 1. Agente Kilo — Catálogo curado

- Crear `includes/catalogo_especies.php` con el array curado (`comun`, `cientifico`, `familia`).
- Ampliar el catálogo con las especies reales censadas en El Bagre / Bajo Cauca
  (verificar nombres en GBIF / Catalogue of Life antes de publicar).

### 2. Agente Codex — Endpoint de API

- Crear `api/especies.php` (GET, JSON, filtrable por `?q=`).
- Combinar catálogo curado + registros de `individuos_forestales` (`estado_registro = 1`).
- Normalización (minúsculas, sin tildes) y deduplicación por nombre común.

### 3. Agente OpenCode — Lógica de autocompletado (JS)

- Crear `assets/js/especies_autocomplete.js`.
- Normalización JS, carga del catálogo, autocompletado y `<datalist>`.
- No sobrescribir ediciones manuales del operario (flags).

### 4. Agente Hermes — Integración en el formulario

- Modificar `operario/registrar_individuo.php`:
  - Añadir campo visible **Familia Botánica** (`id="familia_botanica"`).
  - Añadir datalist de nombres comunes.
  - Incluir JS de autocompletado y `window.BASE_URL`.

---

## Orden recomendado

1. Kilo → catálogo (`includes/catalogo_especies.php`)
2. Codex → endpoint (`api/especies.php`)
3. OpenCode → JS (`assets/js/especies_autocomplete.js`)
4. Hermes → formulario (`operario/registrar_individuo.php`)

---

## Criterios de aceptación

Ver checklist de pruebas manuales en la sección 8 del plan de implementación.
