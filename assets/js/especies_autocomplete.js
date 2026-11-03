/**
 * ForestID / ForestSRD - Interactividad Frontend Vanilla JS
 * Autocompletado de Especies (Nombre Común + Nombre Científico + Familia Botánica)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 *
 * - Normaliza el texto (minúsculas y sin tildes) con la misma regla que el
 *   endpoint PHP `api/especies.php` (normalizarNombre).
 * - Carga el catálogo de especies desde `api/especies.php`.
 * - Autocompleta el Nombre Común y rellena Nombre Científico y Familia
 *   Botánica desde el <datalist> de nombres comunes.
 * - No sobrescribe ediciones manuales del operario (flag `data-edited`).
 */

const CAMPOS_ESPECIE = {
    comun: 'nombre_comun',
    cientifico: 'nombre_cientifico',
    familia: 'familia_botanica'
};

const ID_DATALIST = 'nombres_comunes';
const MAPA_ESPECIES = new Map();

/**
 * Normaliza texto para comparar y autocompletar: minúsculas y sin diacríticos.
 * Debe coincidir con la normalización del lado PHP (`normalizarNombre` en api/especies.php).
 */
function normalizarEspecie(valor) {
    if (valor === null || valor === undefined) {
        return '';
    }
    return String(valor)
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

/**
 * Obtiene la URL base configurable para construir las rutas del endpoint.
 * Se lee de `window.BASE_URL` (inyectado por el formulario) y se deriva del
 * path actual como fallback.
 */
function obtenerBaseUrl() {
    let base = '';
    if (typeof window !== 'undefined' && window.BASE_URL) {
        base = String(window.BASE_URL);
    }
    if (base) {
        return base.charAt(base.length - 1) === '/' ? base : base + '/';
    }
    const ruta = (typeof window !== 'undefined' && window.location && window.location.pathname) ? window.location.pathname : '';
    const indice = ruta.lastIndexOf('/');
    if (indice > 0) {
        return ruta.substring(0, indice + 1);
    }
    if (indice === 0) {
        return '/';
    }
    return '/';
}

function urlCatalogo() {
    return obtenerBaseUrl() + 'api/especies.php';
}

/**
 * Carga el catálogo (catálogo curado + registros de campo) desde el endpoint.
 * Lo cachea en un Map con clave normalizada y puebla el <datalist>.
 */
async function cargarCatalogo() {
    try {
        const respuesta = await fetch(urlCatalogo(), { headers: { 'Accept': 'application/json' } });
        if (!respuesta.ok) {
            throw new Error('No se pudo cargar el catálogo (HTTP ' + respuesta.status + ')');
        }
        const datos = await respuesta.json();
        const especies = Array.isArray(datos) ? datos : [];

        MAPA_ESPECIES.clear();
        especies.forEach((especie) => {
            const clave = normalizarEspecie(especie.comun);
            if (clave === '' || MAPA_ESPECIES.has(clave)) {
                return;
            }
            MAPA_ESPECIES.set(clave, {
                comun: especie.comun || clave,
                cientifico: especie.cientifico || especie.nombre_cientifico || '',
                familia: especie.familia || especie.familia_botanica || ''
            });
        });

        poblarDatalist();
        return true;
    } catch (error) {
        console.warn('[ForestID] No se pudo cargar el catálogo de especies:', error);
        return false;
    }
}

/**
 * Devuelve el <datalist> de nombres comunes, creándolo si la página no lo incluye.
 */
function obtenerDatalist() {
    let datalist = document.getElementById(ID_DATALIST);
    if (!datalist) {
        datalist = document.createElement('datalist');
        datalist.id = ID_DATALIST;
        const inputComun = document.getElementById(CAMPOS_ESPECIE.comun);
        const contenedor = inputComun ? inputComun.parentNode : document.body;
        if (contenedor) {
            contenedor.appendChild(datalist);
        } else {
            document.body.appendChild(datalist);
        }
    }
    return datalist;
}

/**
 * Puebla el <datalist> con todas las especies del catálogo cargado.
 */
function poblarDatalist() {
    const datalist = obtenerDatalist();
    if (!datalist) {
        return;
    }
    datalist.innerHTML = '';
    MAPA_ESPECIES.forEach((especie) => {
        const opcion = document.createElement('option');
        opcion.value = especie.comun;
        datalist.appendChild(opcion);
    });
}

/**
 * Actualiza las opciones del <datalist> según la búsqueda normalizada.
 */
function actualizarOpcionesDatalist(clave) {
    const datalist = document.getElementById(ID_DATALIST);
    if (!datalist) {
        return;
    }
    datalist.innerHTML = '';
    MAPA_ESPECIES.forEach((especie) => {
        const claveEspecie = normalizarEspecie(especie.comun);
        if (clave && claveEspecie.indexOf(clave) === -1) {
            return;
        }
        const opcion = document.createElement('option');
        opcion.value = especie.comun;
        datalist.appendChild(opcion);
    });
}

/**
 * Indica si el usuario editó manualmente un campo (evita sobrescrituras).
 */
function tieneEdicionManual(campo) {
    return !!(campo && campo.dataset && campo.dataset.editado);
}

function marcarEdicionManual(campo) {
    if (campo && campo.dataset) {
        campo.dataset.editado = 'true';
    }
}

function limpiarEdicionManual(campo) {
    if (campo && campo.dataset) {
        delete campo.dataset.editado;
    }
}

/**
 * Limpia los campos de sugerencia cuando el nombre común está vacío.
 */
function limpiarAutocompletado() {
    const inputCientifico = document.getElementById(CAMPOS_ESPECIE.cientifico);
    const inputFamilia = document.getElementById(CAMPOS_ESPECIE.familia);
    if (inputCientifico && !tieneEdicionManual(inputCientifico)) {
        inputCientifico.value = '';
    }
    if (inputFamilia && !tieneEdicionManual(inputFamilia)) {
        inputFamilia.value = '';
    }
}

/**
 * Intenta autocompletar a partir del valor normalizado del Nombre Común.
 */
function intentarAutocompletar() {
    const inputComun = document.getElementById(CAMPOS_ESPECIE.comun);
    if (!inputComun) {
        return;
    }
    const clave = normalizarEspecie(inputComun.value);
    if (clave === '') {
        limpiarAutocompletado();
        return;
    }
    const especie = MAPA_ESPECIES.get(clave);
    if (!especie) {
        return;
    }
    const inputCientifico = document.getElementById(CAMPOS_ESPECIE.cientifico);
    const inputFamilia = document.getElementById(CAMPOS_ESPECIE.familia);
    if (inputCientifico && !tieneEdicionManual(inputCientifico)) {
        inputCientifico.value = especie.cientifico || '';
    }
    if (inputFamilia && !tieneEdicionManual(inputFamilia)) {
        inputFamilia.value = especie.familia || '';
    }
}

/**
 * Maneja el cambio del campo Nombre Común: filtra el datalist y autocompleta.
 */
function manejarCambioComun() {
    const inputComun = document.getElementById(CAMPOS_ESPECIE.comun);
    if (!inputComun) {
        return;
    }
    const clave = normalizarEspecie(inputComun.value);
    actualizarOpcionesDatalist(clave);

    // Un cambio de nombre común invalida las sugerencias previas.
    limpiarEdicionManual(document.getElementById(CAMPOS_ESPECIE.cientifico));
    limpiarEdicionManual(document.getElementById(CAMPOS_ESPECIE.familia));
    intentarAutocompletar();
}

document.addEventListener('DOMContentLoaded', () => {
    const inputComun = document.getElementById(CAMPOS_ESPECIE.comun);
    if (!inputComun) {
        return;
    }

    inputComun.addEventListener('input', () => {
        manejarCambioComun();
    });

    // Los campos de sugerencia se marcan como editados manualmente por el operario.
    [CAMPOS_ESPECIE.cientifico, CAMPOS_ESPECIE.familia].forEach((id) => {
        const campo = document.getElementById(id);
        if (campo) {
            campo.addEventListener('input', () => marcarEdicionManual(campo));
        }
    });

    cargarCatalogo().then((cargado) => {
        if (cargado && inputComun.value) {
            manejarCambioComun();
        }
    });
});
