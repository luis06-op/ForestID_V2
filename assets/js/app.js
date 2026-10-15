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
