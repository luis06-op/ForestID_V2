<?php
/**
 * ForestID / ForestSRD - Lector QR con Cámara en Vivo (html5-qrcode)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth_check.php';

requerir_rol(['OperarioEmincut', 'AdminEmincut']);

$titulo_pagina = 'Escanear Placa QR — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="flex-1 flex flex-col md:flex-row">
    <?php 
    if (es_admin()) {
        require_once __DIR__ . '/../includes/sidebar_admin.php';
    } else {
        require_once __DIR__ . '/../includes/sidebar_operario.php';
    }
    ?>

    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto">
        
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-teal-700 uppercase tracking-widest">Escáner Óptico de Campo</span>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900">Escanear Placa QR</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Apunta la cámara al código QR fijado en el árbol para abrir su ficha de inspección técnica.
                </p>
            </div>
            <a href="<?= BASE_URL ?>operario/dashboard.php" class="btn-forest-secondary text-xs py-2 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start max-w-4xl">
            
            <!-- Visor de la Cámara -->
            <div class="lg:col-span-7 forest-card p-6 space-y-4">
                <div id="reader-container" class="relative overflow-hidden rounded-2xl bg-black border-2 border-emerald-500/40 shadow-inner">
                    <div id="qr-reader" class="w-full" style="min-height: 280px;"></div>
                </div>

                <!-- Estado del Escáner -->
                <div id="scan-status" class="p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                        <span id="scan-msg">Cámara activa buscando código QR...</span>
                    </span>
                    <button id="btn-toggle-cam" onclick="reiniciarEscaner()" class="text-xs font-bold text-emerald-700 hover:underline">
                        Reiniciar
                    </button>
                </div>
            </div>

            <!-- Entrada Manual de Respaldo -->
            <div class="lg:col-span-5 forest-card p-6 space-y-4">
                <div class="space-y-1">
                    <h3 class="font-heading font-bold text-base text-gray-900 flex items-center gap-2">
                        <i data-lucide="keyboard" class="w-4 h-4 text-teal-600"></i>
                        Ingreso Manual de Placa
                    </h3>
                    <p class="text-xs text-gray-500">
                        Si la placa física está sucia o la cámara no enfoca, digita el código impreso:
                    </p>
                </div>

                <form action="<?= BASE_URL ?>operario/individuos/detalle.php" method="GET" class="space-y-3">
                    <div>
                        <label for="codigo" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Código de Placa</label>
                        <input type="text" id="codigo" name="codigo" required placeholder="ej. EBG-PARQ-001"
                               class="w-full py-2.5 px-3 border border-gray-300 rounded-xl text-xs font-mono font-bold uppercase focus:ring-2 focus:ring-emerald-600 outline-none">
                    </div>
                    <button type="submit" class="w-full btn-forest-primary text-xs py-2.5 justify-center shadow-sm">
                        <i data-lucide="search" class="w-4 h-4"></i> Abrir Ficha del Árbol
                    </button>
                </form>

                <div class="pt-3 border-t border-gray-100 space-y-2 text-[11px] text-gray-500">
                    <span class="block font-semibold text-gray-700">Placas de prueba rápida:</span>
                    <div class="flex flex-wrap gap-1.5">
                        <a href="<?= BASE_URL ?>operario/individuos/detalle.php?codigo=EBG-PARQ-001" class="px-2 py-1 bg-gray-100 hover:bg-emerald-100 text-gray-700 rounded font-mono font-semibold">EBG-PARQ-001</a>
                        <a href="<?= BASE_URL ?>operario/individuos/detalle.php?codigo=EBG-SENA-002" class="px-2 py-1 bg-gray-100 hover:bg-emerald-100 text-gray-700 rounded font-mono font-semibold">EBG-SENA-002</a>
                        <a href="<?= BASE_URL ?>operario/individuos/detalle.php?codigo=EBG-NECH-003" class="px-2 py-1 bg-gray-100 hover:bg-emerald-100 text-gray-700 rounded font-mono font-semibold">EBG-NECH-003</a>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<!-- Lógica del Escáner con html5-qrcode -->
<script>
let html5QrCode = null;

function onScanSuccess(decodedText, decodedResult) {
    document.getElementById('scan-msg').innerText = "¡Código detectado! Redirigiendo...";
    
    // Si viene como URL completa (ej. http://.../ficha.php?codigo=EBG-PARQ-001)
    let codigo = decodedText;
    if (decodedText.includes('codigo=')) {
        const urlParams = new URLSearchParams(decodedText.split('?')[1]);
        codigo = urlParams.get('codigo') || decodedText;
    }

    // Pequeña vibración en móvil si está soportado
    if (navigator.vibrate) {
        navigator.vibrate([100, 50, 100]);
    }

    // Detener escáner y redirigir
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            window.location.href = "<?= BASE_URL ?>operario/individuos/detalle.php?codigo=" + encodeURIComponent(codigo);
        }).catch(() => {
            window.location.href = "<?= BASE_URL ?>operario/individuos/detalle.php?codigo=" + encodeURIComponent(codigo);
        });
    } else {
        window.location.href = "<?= BASE_URL ?>operario/individuos/detalle.php?codigo=" + encodeURIComponent(codigo);
    }
}

function iniciarEscaner() {
    html5QrCode = new Html5Qrcode("qr-reader");
    const config = { fps: 10, qrbox: { width: 220, height: 220 } };

    html5QrCode.start(
        { facingMode: "environment" }, // Cámara trasera en móviles
        config,
        onScanSuccess
    ).catch(err => {
        document.getElementById('scan-msg').innerText = "No se pudo acceder a la cámara. Usa el ingreso manual.";
        console.warn("Error iniciando cámara:", err);
    });
}

function reiniciarEscaner() {
    if (html5QrCode) {
        html5QrCode.stop().then(iniciarEscaner).catch(iniciarEscaner);
    } else {
        iniciarEscaner();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarEscaner();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
