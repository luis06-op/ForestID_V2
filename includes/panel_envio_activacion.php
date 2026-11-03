<?php
/**
 * ForestID / ForestSRD - Panel de resultado del envío del enlace de activación
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 *
 * Parcial reutilizable por admin/usuarios/crear.php y
 * admin/usuarios/resultado_activacion.php.
 *
 * Variables esperadas:
 *  - $usuario_nuevo     array con nombres, apellidos, correo, rol, telefono, enlace_activacion
 *  - $envio             array con correo, whatsapp, enlace_wa, enlace_activacion, enlace_mail
 *  - $horas_activacion  int con la vigencia del enlace en horas
 *  - $panel_accion      string 'creado' | 'reenviado' (opcional, por defecto 'creado')
 */
if (!isset($usuario_nuevo, $envio) || $usuario_nuevo === null || $envio === null) {
    return;
}
$panel_accion = $panel_accion ?? 'creado';
$panel_es_reenvio = ($panel_accion === 'reenviado');
?>
<!-- ===== Panel de resultado: envío del enlace de activación ===== -->
<div class="emi-card p-6 sm:p-8 max-w-2xl al-scroll">
    <div class="flex items-center gap-3 mb-1">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:var(--emi-verde-suave); color:var(--emi-verde)">
            <i data-lucide="<?= $panel_es_reenvio ? 'mail-check' : 'user-check' ?>" class="w-6 h-6"></i>
        </div>
        <div>
            <h2 class="font-heading font-bold text-xl" style="color:var(--emi-tinta)">
                <?php if ($panel_es_reenvio): ?>
                    Enlace de activación reenviado a <?= htmlspecialchars($usuario_nuevo['nombres'] . ' ' . $usuario_nuevo['apellidos']) ?>
                <?php else: ?>
                    <?= htmlspecialchars($usuario_nuevo['nombres'] . ' ' . $usuario_nuevo['apellidos']) ?> creado
                <?php endif; ?>
            </h2>
            <p class="text-xs" style="color:var(--emi-tinta-suave)">Rol: <?= htmlspecialchars($usuario_nuevo['rol']) ?></p>
        </div>
    </div>

    <div class="linea-sol mt-3 mb-5"></div>

    <!-- Enlace de activación -->
    <div class="rounded-xl p-4 border mb-5" style="background:var(--emi-amarillo-suave); border-color:#F2DCAC">
        <span class="emi-label block mb-2">Enlace de activación (un solo uso)</span>
        <p class="text-sm" style="color:var(--emi-tinta)">
            <strong>Correo:</strong> <?= htmlspecialchars($usuario_nuevo['correo']) ?>
        </p>
        <a href="<?= htmlspecialchars($usuario_nuevo['enlace_activacion']) ?>" class="block mt-2 text-xs break-all font-medium" style="color:var(--emi-verde)">
            <?= htmlspecialchars($usuario_nuevo['enlace_activacion']) ?>
        </a>
        <p class="text-[11px] mt-2" style="color:var(--emi-tinta-suave)">
            El usuario debe abrir este enlace para crear su propia contraseña. Caduca en <?= (int)$horas_activacion ?> horas.
        </p>
    </div>

    <!-- Estado de los envíos -->
    <div class="space-y-3 mb-6">
        <!-- Correo -->
        <div class="flex items-start gap-3 p-3.5 rounded-xl border" style="background:<?= $envio['correo']['ok'] ? 'var(--emi-verde-suave)' : 'var(--emi-hueso)' ?>; border-color:#CBD9B8">
            <i data-lucide="<?= $envio['correo']['ok'] ? 'check-circle' : 'alert-triangle' ?>" class="w-5 h-5 shrink-0 mt-0.5" style="color:<?= $envio['correo']['ok'] ? 'var(--emi-verde)' : 'var(--emi-ambar)' ?>"></i>
            <div class="text-xs" style="color:var(--emi-tinta)">
                <span class="font-bold block">Correo electrónico</span>
                <span style="color:var(--emi-tinta-suave)"><?= htmlspecialchars($envio['correo']['detalle']) ?></span>
                <?php if (!$envio['correo']['ok']): ?>
                    <a href="<?= htmlspecialchars($envio['enlace_mail']) ?>" class="block mt-1.5 font-semibold" style="color:var(--emi-verde)">Abrir en el cliente de correo →</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- WhatsApp -->
        <div class="flex items-start gap-3 p-3.5 rounded-xl border" style="background:<?= $envio['whatsapp']['ok'] ? 'var(--emi-verde-suave)' : 'var(--emi-hueso)' ?>; border-color:#CBD9B8">
            <i data-lucide="<?= $envio['whatsapp']['ok'] ? 'check-circle' : 'message-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5" style="color:<?= $envio['whatsapp']['ok'] ? 'var(--emi-verde)' : 'var(--emi-ambar)' ?>"></i>
            <div class="text-xs" style="color:var(--emi-tinta)">
                <span class="font-bold block">WhatsApp</span>
                <span style="color:var(--emi-tinta-suave)"><?= htmlspecialchars($envio['whatsapp']['detalle']) ?></span>
                <?php if (!$envio['whatsapp']['ok'] && !empty($envio['enlace_wa'])): ?>
                    <a href="<?= htmlspecialchars($envio['enlace_wa']) ?>" target="_blank" rel="noopener" class="emi-btn emi-btn-sol text-[11px] py-2 px-3 mt-2 inline-flex">
                        <span class="inline-flex items-center gap-1.5 relative z-10"><i data-lucide="send" class="w-3.5 h-3.5"></i> Enviar enlace por WhatsApp</span>
                    </a>
                <?php endif; ?>
                <?php if (empty($envio['enlace_wa'])): ?>
                    <span class="block mt-1 italic" style="color:var(--emi-tinta-suave)">Sin teléfono registrado: agregue un número al usuario para poder enviar por WhatsApp.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="pt-4 border-t border-[#E7EADD] flex justify-end gap-2">
        <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="emi-btn emi-btn-verde text-xs py-2.5 px-5">
            <span class="inline-flex items-center gap-2"><i data-lucide="users" class="w-4 h-4"></i> Ir a Usuarios</span>
        </a>
        <?php if ($panel_es_reenvio): ?>
            <a href="<?= BASE_URL ?>admin/usuarios/index.php" class="emi-btn text-xs py-2.5 px-5" style="background:#fff; color:var(--emi-verde-oscuro); border:1px solid #CBD9B8">
                Cerrar
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>admin/usuarios/crear.php" class="emi-btn text-xs py-2.5 px-5" style="background:#fff; color:var(--emi-verde-oscuro); border:1px solid #CBD9B8">
                Crear otro usuario
            </a>
        <?php endif; ?>
    </div>
</div>
