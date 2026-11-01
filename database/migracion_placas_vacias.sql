-- Placas QR generadas antes del registro del individuo forestal.
-- Ejecutar después de crear individuos_forestales.
CREATE TABLE `placas_qr` (
    `id_placa` CHAR(36) NOT NULL DEFAULT (UUID()),
    `id_lote` CHAR(36) NOT NULL,
    `codigo_placa` VARCHAR(30) NOT NULL,
    `estado` ENUM('pendiente', 'asignada') NOT NULL DEFAULT 'pendiente',
    `generado_por` CHAR(36) NULL,
    `fecha_generacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `asignado_a` CHAR(36) NULL,
    `fecha_asignacion` DATETIME NULL,
    `tamano` ENUM('pequena', 'mediana', 'grande') NOT NULL DEFAULT 'mediana',
    PRIMARY KEY (`id_placa`),
    UNIQUE KEY `uq_placas_qr_codigo` (`codigo_placa`),
    KEY `idx_placas_qr_lote` (`id_lote`),
    KEY `idx_placas_qr_estado` (`estado`),
    CONSTRAINT `fk_placas_qr_individuo` FOREIGN KEY (`asignado_a`)
        REFERENCES `individuos_forestales` (`id_individuo`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
