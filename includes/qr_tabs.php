<?php
/**
 * ForestID / ForestSRD - Pestañas del módulo de identificación física
 * Requiere definir $tab_qr = 'asignadas' | 'vacias' antes de incluirlo.
 */
$tab_qr = $tab_qr ?? 'asignadas';
?>
<div class="inline-flex flex-wrap items-center gap-1.5 p-1.5 bg-white border border-gray-200 rounded-2xl shadow-sm">
    <a href="<?= BASE_URL ?>admin/qr/index.php"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-colors <?= $tab_qr === 'asignadas' ? 'bg-emerald-950 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
        <i data-lucide="qr-code" class="w-4 h-4"></i>
        Placas asignadas
    </a>
    <a href="<?= BASE_URL ?>admin/qr/masivo.php"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-colors <?= $tab_qr === 'vacias' ? 'bg-emerald-950 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
        <i data-lucide="tags" class="w-4 h-4"></i>
        Generar placas vacías
    </a>
</div>
