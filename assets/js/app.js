/**
 * ForestID / ForestSRD - Interactividad Frontend Vanilla JS
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar íconos Lucide si están presentes
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Manejo de previsualización de imágenes al subir archivos
    const inputImagenes = document.querySelectorAll('input[type="file"][data-preview]');
    inputImagenes.forEach(input => {
        input.addEventListener('change', (e) => {
            const previewId = input.getAttribute('data-preview');
            const previewImg = document.getElementById(previewId);
            if (!previewImg) return;

            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    previewImg.src = event.target.result;
                    previewImg.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Manejo de confirmaciones de eliminación / baja lógica
    const botonesConfirmar = document.querySelectorAll('[data-confirm]');
    botonesConfirmar.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const mensaje = btn.getAttribute('data-confirm') || '¿Está seguro de realizar esta acción?';
            if (!confirm(mensaje)) {
                e.preventDefault();
            }
        });
    });

    // Prevención de doble envío y feedback visual "Guardando..." en formularios
    const forms = document.querySelectorAll('form:not([data-no-protect])');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                return;
            }
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const width = submitBtn.offsetWidth;
                if (width > 0) {
                    submitBtn.style.minWidth = `${width}px`;
                }
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                submitBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Guardando...</span>
                `;
            }
        });
    });
});

// Función para abrir modales
function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
}

// Función para cerrar modales
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }
}
