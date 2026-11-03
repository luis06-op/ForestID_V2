(function (global, document) {
    'use strict';

    var defaults = {
        latInput: 'latInput',
        lonInput: 'lonInput',
        buttonId: 'button-gps',
        statusId: 'status-gps'
    };

    function init(options) {
        options = options || {};
        var ids = Object.assign({}, defaults, options);
        var latitude = document.getElementById(ids.latInput);
        var longitude = document.getElementById(ids.lonInput);

        if (!latitude || !longitude) {
            return null;
        }

        var form = latitude.form || longitude.form || latitude.closest('form');
        if (!form) {
            return null;
        }

        var button = document.getElementById(ids.buttonId);
        if (!button) {
            button = document.createElement('button');
            button.type = 'button';
            button.id = ids.buttonId;
            button.textContent = 'Capturar ubicacion GPS';
            button.className = 'emi-button emi-button--secondary';
            latitude.insertAdjacentElement('afterend', button);
        }

        var status = document.getElementById(ids.statusId);
        if (!status) {
            status = document.createElement('p');
            status.id = ids.statusId;
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            button.insertAdjacentElement('afterend', status);
        }

        function show(message) {
            status.textContent = message;
        }

        button.addEventListener('click', function () {
            if (!global.navigator.geolocation) {
                show('GPS no disponible en este dispositivo o navegador.');
                return;
            }

            button.disabled = true;
            show('Buscando senal GPS...');
            global.navigator.geolocation.getCurrentPosition(function (position) {
                latitude.value = position.coords.latitude.toFixed(7);
                longitude.value = position.coords.longitude.toFixed(7);
                latitude.dispatchEvent(new Event('input', { bubbles: true }));
                latitude.dispatchEvent(new Event('change', { bubbles: true }));
                longitude.dispatchEvent(new Event('input', { bubbles: true }));
                longitude.dispatchEvent(new Event('change', { bubbles: true }));
                show('Ubicacion capturada. Precision: ' + Math.round(position.coords.accuracy) + ' m.');
                button.disabled = false;
            }, function (error) {
                if (error.code === error.PERMISSION_DENIED) {
                    show('Permiso de ubicacion denegado. Habilitalo en el navegador para continuar.');
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    show('Sin senal GPS disponible. Intenta nuevamente en un lugar despejado.');
                } else {
                    show('No se pudo obtener la ubicacion (tiempo de espera agotado).');
                }
                button.disabled = false;
            }, { enableHighAccuracy: true, timeout: 20000 });
        });

        return { button: button, status: status };
    }

    global.GpsCapture = { init: init };
}(window, document));

// Integracion esperada en operario/registrar_individuo.php: <script src="../assets/js/gps_capture.js"></script> y GpsCapture.init({latInput: 'latInput', lonInput: 'lonInput', buttonId: 'button-gps', statusId: 'status-gps'}).
