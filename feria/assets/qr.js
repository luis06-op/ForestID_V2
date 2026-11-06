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
            contenedor.innerHTML = '<span style="font-size:10px;color:#0F172A;word-break:break-all;padding:4px;display:block;text-align:center;">' +
                texto.replace(/[<>&]/g, '') + '</span>';
            return texto;
        }

        contenedor.innerHTML = '';
        new window.QRCode(contenedor, {
            text: texto,
            width: 180,
            height: 180,
            colorDark: '#1B5E20',
            colorLight: '#FFFFFF',
            correctLevel: window.QRCode.CorrectLevel.M
        });
        return texto;
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
        });

        // Botón de impresión de la ficha/placa.
        document.querySelectorAll('[data-imprimir]').forEach(function (btn) {
            btn.addEventListener('click', function () { window.print(); });
        });
    });
})();
