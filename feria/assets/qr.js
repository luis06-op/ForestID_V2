/* ==========================================================================
   ForestID — Modo Feria FISAD
   Genera los QR en el navegador a partir de la URL real del sitio, de modo
   que el código impreso siempre coincide con el dominio donde se publicó
   (local, vercel.app o el dominio definitivo). No requiere base de datos.
   ========================================================================== */

(function () {
    'use strict';

    /**
     * Resuelve el nombre de una página hermana respecto de la página actual,
     * conservando el subdirectorio si el sitio se publica en uno.
     */
    function urlHermana(pagina) {
        var ruta = window.location.pathname;
        var base = ruta.substring(0, ruta.lastIndexOf('/') + 1);
        return window.location.origin + base + pagina;
    }

    /**
     * Pinta el QR en un contenedor y devuelve la URL codificada.
     * Si la librería no está disponible (sin conexión), muestra la URL en
     * texto para que se pueda escribir o generar a mano.
     */
    function pintarQR(contenedor, texto) {
        if (!contenedor) { return texto; }

        if (typeof window.QRCode !== 'function') {
            contenedor.innerHTML = '<span class="qr-respaldo">' +
                texto.replace(/[<>&]/g, '') + '</span>';
            return texto;
        }

        contenedor.innerHTML = '';
        new window.QRCode(contenedor, {
            text: texto,
            width: 240,
            height: 240,
            colorDark: '#1B5E20',
            colorLight: '#FFFFFF',
            correctLevel: window.QRCode.CorrectLevel.M
        });
        return texto;
    }

    /** Descarga el canvas del QR como PNG con el nombre indicado. */
    function descargar(contenedor, nombre) {
        var canvas = contenedor ? contenedor.querySelector('canvas') : null;
        if (!canvas) { return; }
        var enlace = document.createElement('a');
        enlace.href = canvas.toDataURL('image/png');
        enlace.download = nombre;
        document.body.appendChild(enlace);
        enlace.click();
        document.body.removeChild(enlace);
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Elementos con data-qr="pagina.html" generan el QR de esa página.
        document.querySelectorAll('[data-qr]').forEach(function (caja) {
            var destino = caja.getAttribute('data-qr');
            var url = urlHermana(destino);
            pintarQR(caja, url);

            // Texto opcional con la URL (para verificación o impresión).
            var etiqueta = document.querySelector('[data-qr-url="' + destino + '"]');
            if (etiqueta) { etiqueta.textContent = url; }

            // Botón opcional para descargar el QR en PNG.
            document.querySelectorAll('[data-qr-descarga="' + destino + '"]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    descargar(caja, 'QR-' + destino.replace(/\.html$/, '') + '.png');
                });
            });
        });

        // Botón de impresión de la ficha/placa.
        document.querySelectorAll('[data-imprimir]').forEach(function (btn) {
            btn.addEventListener('click', function () { window.print(); });
        });
    });
})();
