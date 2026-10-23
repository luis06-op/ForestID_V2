-- =============================================================================
-- SISTEMA FORESTID / FORESTSRD - GESTIÓN E IDENTIFICACIÓN DEL INVENTARIO FORESTAL
-- Empresa: Emincut S.A.S. | Sede: El Bagre, Antioquia, Colombia
-- Alcance: exclusivo de Emincut S.A.S. (sin instituciones ni entidades externas)
-- Equipo de Desarrollo:
--   - Luis Angel Muñoz Márquez (Líder de Arquitectura)
--   - Gabriel Jaime Correa Correa (Desarrollador Backend)
--   - Michael Rodríguez Londoño (Desarrollador UI / QA)
--   - Valentina (Supervisor / Verificador de Calidad)
-- Base de Datos: MySQL 8.0 / MariaDB 10.3+ (Soporte UUID() por defecto)
-- Collation: utf8mb4_spanish_ci | Motor: InnoDB
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `forestsrd_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_spanish_ci;

USE `forestsrd_db`;

-- Desactivar temporalmente revisión de llaves foráneas para recreación limpia
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `logs_sistema`;
DROP TABLE IF EXISTS `actividades_mantenimiento`;
DROP TABLE IF EXISTS `estados_fitosanitarios`;
DROP TABLE IF EXISTS `individuos_forestales`;
DROP TABLE IF EXISTS `zonas`;
DROP TABLE IF EXISTS `tokens_recuperacion`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `roles`;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- 1. CAPA DE ACCESO Y SEGURIDAD
-- =============================================================================

-- 1.1 Tabla: roles
CREATE TABLE `roles` (
    `id_rol` CHAR(36) NOT NULL DEFAULT (UUID()),
    `nombre_rol` VARCHAR(50) NOT NULL UNIQUE,
    `descripcion` VARCHAR(255) NULL,
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 1.2 Tabla: usuarios
CREATE TABLE `usuarios` (
    `id_usuario` CHAR(36) NOT NULL DEFAULT (UUID()),
    `id_rol` CHAR(36) NOT NULL,
    `nombres` VARCHAR(100) NOT NULL,
    `apellidos` VARCHAR(100) NOT NULL,
    `correo` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `telefono` VARCHAR(20) NULL,
    `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Activo, 0: Inactivo',
    `ultimo_acceso` DATETIME NULL,
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_usuario`),
    CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX `idx_usuarios_correo` (`correo`),
    INDEX `idx_usuarios_rol` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 1.3 Tabla: tokens_recuperacion
CREATE TABLE `tokens_recuperacion` (
    `id_token` CHAR(36) NOT NULL DEFAULT (UUID()),
    `id_usuario` CHAR(36) NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `fecha_expiracion` DATETIME NOT NULL,
    `usado` TINYINT(1) NOT NULL DEFAULT 0,
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_token`),
    CONSTRAINT `fk_tokens_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX `idx_tokens_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================================================
-- 2. CAPA TERRITORIAL
-- =============================================================================

-- 2.1 Tabla: zonas
CREATE TABLE `zonas` (
    `id_zona` CHAR(36) NOT NULL DEFAULT (UUID()),
    `nombre_zona` VARCHAR(100) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `municipio` VARCHAR(100) NOT NULL DEFAULT 'El Bagre',
    `departamento` VARCHAR(100) NOT NULL DEFAULT 'Antioquia',
    `latitud_referencia` DECIMAL(10,7) NULL,
    `longitud_referencia` DECIMAL(10,7) NULL,
    `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Activa, 0: Inactiva',
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_zona`),
    INDEX `idx_zonas_municipio` (`municipio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================================================
-- 3. CAPA NÚCLEO (CORE DENDROLÓGICO)
-- =============================================================================

-- 3.1 Tabla: individuos_forestales
CREATE TABLE `individuos_forestales` (
    `id_individuo` CHAR(36) NOT NULL DEFAULT (UUID()),
    `codigo_placa` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Identificador alfanumérico físico de la placa QR',
    `id_zona` CHAR(36) NOT NULL,
    `nombre_comun` VARCHAR(150) NOT NULL,
    `nombre_cientifico` VARCHAR(150) NOT NULL,
    `familia_botanica` VARCHAR(100) NOT NULL,
    `tipo_individuo` ENUM('Árbol', 'Palma', 'Arbusto', 'Planta ornamental') NOT NULL DEFAULT 'Árbol',
    `fecha_siembra` DATE NULL,
    `latitud` DECIMAL(10,7) NOT NULL,
    `longitud` DECIMAL(10,7) NOT NULL,
    `dap_cm` DECIMAL(6,2) NOT NULL COMMENT 'Diámetro a la Altura del Pecho en centímetros (a 1.30 m)',
    `altura_total_m` DECIMAL(5,2) NOT NULL COMMENT 'Altura total estimada en metros',
    `diametro_copa_m` DECIMAL(5,2) NOT NULL COMMENT 'Diámetro promedio de copa en metros',
    `foto_principal_url` VARCHAR(255) NULL,
    `descripcion_general` TEXT NULL,
    `id_estado_fitosanitario_actual` CHAR(36) NULL COMMENT 'Referencia al último estado registrado',
    `qr_url` VARCHAR(255) NULL COMMENT 'Ruta relativa de la imagen QR generada',
    `registrado_por` CHAR(36) NOT NULL,
    `estado_registro` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Activo, 0: Dado de baja lógica',
    `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_individuo`),
    CONSTRAINT `fk_indiv_zonas` FOREIGN KEY (`id_zona`) REFERENCES `zonas` (`id_zona`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_indiv_usuarios` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX `idx_indiv_codigo_placa` (`codigo_placa`),
    INDEX `idx_indiv_zona` (`id_zona`),
    INDEX `idx_indiv_especie` (`nombre_comun`, `nombre_cientifico`),
    INDEX `idx_indiv_coords` (`latitud`, `longitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 3.2 Tabla: estados_fitosanitarios
CREATE TABLE `estados_fitosanitarios` (
    `id_estado` CHAR(36) NOT NULL DEFAULT (UUID()),
    `id_individuo` CHAR(36) NOT NULL,
    `estado` ENUM('Sano', 'En observación', 'Enfermo', 'Plagado', 'En riesgo', 'Muerto en pie', 'Talado') NOT NULL DEFAULT 'Sano',
    `diagnostico` TEXT NULL,
    `recomendacion` TEXT NULL,
    `evaluado_por` CHAR(36) NOT NULL,
    `fecha_evaluacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_estado`),
    CONSTRAINT `fk_estados_individuos` FOREIGN KEY (`id_individuo`) REFERENCES `individuos_forestales` (`id_individuo`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_estados_usuarios` FOREIGN KEY (`evaluado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX `idx_estados_individuo` (`id_individuo`),
    INDEX `idx_estados_estado` (`estado`),
    INDEX `idx_estados_fecha` (`fecha_evaluacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Enlazar llave foránea circular opcional para el estado actual
ALTER TABLE `individuos_forestales` 
ADD CONSTRAINT `fk_indiv_estado_actual` 
FOREIGN KEY (`id_estado_fitosanitario_actual`) 
REFERENCES `estados_fitosanitarios` (`id_estado`) 
ON UPDATE CASCADE 
ON DELETE SET NULL;

-- =============================================================================
-- 4. CAPA DE ACTIVIDAD Y AUDITORÍA
-- =============================================================================

-- 4.1 Tabla: actividades_mantenimiento
CREATE TABLE `actividades_mantenimiento` (
    `id_actividad` CHAR(36) NOT NULL DEFAULT (UUID()),
    `id_individuo` CHAR(36) NOT NULL,
    `tipo_actividad` ENUM('Poda', 'Fumigación', 'Fertilización', 'Riego', 'Trasplante', 'Tala', 'Tutoreo', 'Reporte ciudadano', 'Otro') NOT NULL,
    `descripcion` TEXT NOT NULL,
    `evidencia_foto_url` VARCHAR(255) NULL,
    `realizado_por` CHAR(36) NULL COMMENT 'Nullable para permitir reportes ciudadanos anónimos',
    `fecha_actividad` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_actividad`),
    CONSTRAINT `fk_actividades_individuos` FOREIGN KEY (`id_individuo`) REFERENCES `individuos_forestales` (`id_individuo`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_actividades_usuarios` FOREIGN KEY (`realizado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX `idx_actividades_individuo` (`id_individuo`),
    INDEX `idx_actividades_tipo` (`tipo_actividad`),
    INDEX `idx_actividades_fecha` (`fecha_actividad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 4.2 Tabla: logs_sistema
CREATE TABLE `logs_sistema` (
    `id_log` CHAR(36) NOT NULL DEFAULT (UUID()),
    `id_usuario` CHAR(36) NULL,
    `accion` VARCHAR(150) NOT NULL,
    `modulo_afectado` VARCHAR(100) NOT NULL,
    `detalle` JSON NULL,
    `ip_origen` VARCHAR(45) NULL,
    `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_log`),
    CONSTRAINT `fk_logs_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX `idx_logs_usuario` (`id_usuario`),
    INDEX `idx_logs_modulo` (`modulo_afectado`),
    INDEX `idx_logs_fecha` (`fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;


-- =============================================================================
-- 5. DATOS SEMILLA (SEEDS) INICIALES
-- =============================================================================

-- 5.1 Roles del Sistema
INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`, `fecha_creacion`) VALUES
('11111111-1111-1111-1111-111111111101', 'AdminEmincut', 'Administrador general del sistema de Emincut con acceso total', NOW()),
('11111111-1111-1111-1111-111111111102', 'OperarioEmincut', 'Personal técnico de campo encargado de inspecciones, mantenimiento y registro dendrológico', NOW()),
('11111111-1111-1111-1111-111111111103', 'UsuarioPublico', 'Ciudadano o visitante con acceso a consulta pública y reporte de novedades', NOW());

-- 5.2 Usuarios Iniciales
-- NOTA DE SEGURIDAD PARA ENTORNO LOCAL / PRUEBAS:
-- Contraseñas en texto plano para los 3 roles de prueba:
-- 1. Administrador (admin@emincut.com): Admin123*
-- 2. Operario (operario@emincut.com): Operario123*
-- 3. Ciudadano (ciudadano@emincut.com): Ciudadano123*
-- Los hashes a continuación son Bcrypt reales (cost 12), generados con
-- password_hash() y verificados con password_verify(). CÁMBIELOS antes de
-- cualquier despliegue real: estas contraseñas son públicas (están en el README).
INSERT INTO `usuarios` (
    `id_usuario`, `id_rol`, `nombres`, `apellidos`, 
    `correo`, `password_hash`, `telefono`, `estado`, `ultimo_acceso`, `fecha_creacion`
) VALUES 
(
    '33333333-3333-3333-3333-333333333301',
    '11111111-1111-1111-1111-111111111101', -- AdminEmincut
    'Luis Angel',
    'Muñoz Márquez',
    'admin@emincut.com',
    '$2y$12$H.2pA5GAoGuzgWCjCWRSUuvzg4wg7pN55cKZdzcqpIgrcfgR0KBX.',
    '+57 310 445 8899',
    1,
    NOW(),
    NOW()
),
(
    '33333333-3333-3333-3333-333333333302',
    '11111111-1111-1111-1111-111111111102', -- OperarioEmincut
    'Gabriel Jaime',
    'Correa Correa',
    'operario@emincut.com',
    '$2y$12$iYjdqdr/GyjgXVqfMGAGR.BK0S7RbLiINfuFfLRegehlFoA.GYsYK',
    '+57 311 556 7788',
    1,
    NOW(),
    NOW()
),
(
    '33333333-3333-3333-3333-333333333303',
    '11111111-1111-1111-1111-111111111103', -- UsuarioPublico
    'Michael',
    'Rodríguez Londoño',
    'ciudadano@emincut.com',
    '$2y$12$cNCGEdfYqhGVAKu6QDC/uOOY1jXk.LdAdodVQ8rpdV2wb6wYpqX4i',
    '+57 314 667 9900',
    1,
    NOW(),
    NOW()
);

-- 5.3 Zonas Territoriales en El Bagre, Antioquia
INSERT INTO `zonas` (
    `id_zona`, `nombre_zona`, `descripcion`, `municipio`, 
    `departamento`, `latitud_referencia`, `longitud_referencia`, `estado`, `fecha_creacion`
) VALUES 
(
    '44444444-4444-4444-4444-444444444401',
    'Parque Principal El Bagre',
    'Plaza central municipal, corredor peatonal y zona de esparcimiento comunal con arborización madura.',
    'El Bagre',
    'Antioquia',
    7.6043200,
    -74.8091500,
    1,
    NOW()
),
(
    '44444444-4444-4444-4444-444444444402',
    'SENA Complejo Minero Bajo Cauca',
    'Campus académico y semillero de innovación ambiental SENA Regional Antioquia.',
    'El Bagre',
    'Antioquia',
    7.6012400,
    -74.8145200,
    1,
    NOW()
),
(
    '44444444-4444-4444-4444-444444444403',
    'Ronda Hídrica Río Nechí / Malecón',
    'Franja de protección ribereña y sendero ecológico sobre la cuenca del Río Nechí.',
    'El Bagre',
    'Antioquia',
    7.6089100,
    -74.8023400,
    1,
    NOW()
);

-- 5.4 Individuos Forestales Iniciales (Flora representativa de la región)
INSERT INTO `individuos_forestales` (
    `id_individuo`, `codigo_placa`, `id_zona`, 
    `nombre_comun`, `nombre_cientifico`, `familia_botanica`, `tipo_individuo`, 
    `fecha_siembra`, `latitud`, `longitud`, `dap_cm`, `altura_total_m`, `diametro_copa_m`, 
    `foto_principal_url`, `descripcion_general`, `qr_url`, `registrado_por`, `estado_registro`, `fecha_registro`
) VALUES 
(
    '55555555-5555-5555-5555-555555555501',
    'EBG-PARQ-001',
    '44444444-4444-4444-4444-444444444401',
    'Guayacán Rosado',
    'Handroanthus chrysanthus',
    'Bignoniaceae',
    'Árbol',
    '2018-04-22',
    7.6044500,
    -74.8092100,
    38.50,
    14.20,
    9.50,
    'assets/uploads/individuos/guayacan_rosado.jpg',
    'Espécimen maduro ubicado en el costado noroccidental del parque principal. Floración abundante y fuste recto con buen desarrollo de dosel.',
    'assets/qr/qr_EBG-PARQ-001.svg',
    '33333333-3333-3333-3333-333333333301',
    1,
    NOW()
),
(
    '55555555-5555-5555-5555-555555555502',
    'EBG-SENA-002',
    '44444444-4444-4444-4444-444444444402',
    'Ceiba Bruja / Bonga',
    'Ceiba pentandra',
    'Malvaceae',
    'Árbol',
    '2015-10-12',
    7.6013500,
    -74.8146700,
    72.00,
    21.50,
    16.80,
    'assets/uploads/individuos/ceiba_pentandra.jpg',
    'Árbol emblemático de gran porte dentro del campus SENA. Excelente sombra y refugio para avifauna regional.',
    'assets/qr/qr_EBG-SENA-002.svg',
    '33333333-3333-3333-3333-333333333301',
    1,
    NOW()
),
(
    '55555555-5555-5555-5555-555555555503',
    'EBG-NECH-003',
    '44444444-4444-4444-4444-444444444403',
    'Caracolí',
    'Anacardium excelsum',
    'Anacardiaceae',
    'Árbol',
    '2012-06-05',
    7.6088200,
    -74.8024500,
    85.40,
    24.00,
    18.20,
    'assets/uploads/individuos/caracoli.jpg',
    'Individuo ribereño protector de la cuenca del Río Nechí. Raíces profundas estabilizadoras de ribera con alta captación de carbono.',
    'assets/qr/qr_EBG-NECH-003.svg',
    '33333333-3333-3333-3333-333333333302',
    1,
    NOW()
),
(
    '55555555-5555-5555-5555-555555555504',
    'EBG-PARQ-004',
    '44444444-4444-4444-4444-444444444401',
    'Palma Real / Palma de Cera',
    'Roystonea regia',
    'Arecaceae',
    'Palma',
    '2020-02-18',
    7.6042100,
    -74.8090500,
    32.00,
    11.80,
    4.20,
    'assets/uploads/individuos/palma_real.jpg',
    'Palma ornamental central. Estípite limpio, copa vigorosa y buen anclaje en el suelo del parque.',
    'assets/qr/qr_EBG-PARQ-004.svg',
    '33333333-3333-3333-3333-333333333302',
    1,
    NOW()
);

-- 5.5 Estados Fitosanitarios Iniciales
INSERT INTO `estados_fitosanitarios` (
    `id_estado`, `id_individuo`, `estado`, `diagnostico`, `recomendacion`, `evaluado_por`, `fecha_evaluacion`
) VALUES 
(
    '66666666-6666-6666-6666-666666666601',
    '55555555-5555-5555-5555-555555555501',
    'Sano',
    'Follaje verde vigoroso, sin signos de ataque de barrenadores ni necrosis en tronco. Estructura balanceada.',
    'Continuar con monitoreo semestral regular y fertilización orgánica al inicio del periodo de lluvias.',
    '33333333-3333-3333-3333-333333333302',
    NOW()
),
(
    '66666666-6666-6666-6666-666666666602',
    '55555555-5555-5555-5555-555555555502',
    'En observación',
    'Presencia leve de epífitas y ramas secas en el tercio superior de la copa debido a estrés hídrico estacional.',
    'Realizar poda de mantenimiento y aclareo de ramas secas; aplicar riego complementario.',
    '33333333-3333-3333-3333-333333333302',
    NOW()
),
(
    '66666666-6666-6666-6666-666666666603',
    '55555555-5555-5555-5555-555555555503',
    'Sano',
    'Excelente estado de conservación biomecánica y sanidad foliar. Sistema radicular afianzado.',
    'Mantener la zona de goteo libre de escombros o compactación mecánica.',
    '33333333-3333-3333-3333-333333333301',
    NOW()
),
(
    '66666666-6666-6666-6666-666666666604',
    '55555555-5555-5555-5555-555555555504',
    'Sano',
    'Estípite firme, sin pudrición del cogollo ni presencia de picudo del cocotero (Rhynchophorus palmarum).',
    'Control preventivo de plagas y limpieza periódica de hojas senescentes.',
    '33333333-3333-3333-3333-333333333302',
    NOW()
);

-- Actualizar referencia de estado fitosanitario actual en los individuos
UPDATE `individuos_forestales` SET `id_estado_fitosanitario_actual` = '66666666-6666-6666-6666-666666666601' WHERE `id_individuo` = '55555555-5555-5555-5555-555555555501';
UPDATE `individuos_forestales` SET `id_estado_fitosanitario_actual` = '66666666-6666-6666-6666-666666666602' WHERE `id_individuo` = '55555555-5555-5555-5555-555555555502';
UPDATE `individuos_forestales` SET `id_estado_fitosanitario_actual` = '66666666-6666-6666-6666-666666666603' WHERE `id_individuo` = '55555555-5555-5555-5555-555555555503';
UPDATE `individuos_forestales` SET `id_estado_fitosanitario_actual` = '66666666-6666-6666-6666-666666666604' WHERE `id_individuo` = '55555555-5555-5555-5555-555555555504';

-- 5.6 Actividades de Mantenimiento Históricas
INSERT INTO `actividades_mantenimiento` (
    `id_actividad`, `id_individuo`, `tipo_actividad`, `descripcion`, 
    `evidencia_foto_url`, `realizado_por`, `fecha_actividad`, `fecha_registro`
) VALUES 
(
    '77777777-7777-7777-7777-777777777701',
    '55555555-5555-5555-5555-555555555501',
    'Fertilización',
    'Aplicación de abono orgánico compuesto (humus y compost) e incorporación de fósforo y potasio en corona.',
    NULL,
    '33333333-3333-3333-3333-333333333302',
    DATE_SUB(NOW(), INTERVAL 45 DAY),
    DATE_SUB(NOW(), INTERVAL 45 DAY)
),
(
    '77777777-7777-7777-7777-777777777702',
    '55555555-5555-5555-5555-555555555502',
    'Poda',
    'Poda sanitaria de elevación y eliminación de ramas bajeras con riesgo de desprendimiento sobre sendero peatonal.',
    NULL,
    '33333333-3333-3333-3333-333333333302',
    DATE_SUB(NOW(), INTERVAL 12 DAY),
    DATE_SUB(NOW(), INTERVAL 12 DAY)
),
(
    '77777777-7777-7777-7777-777777777703',
    '55555555-5555-5555-5555-555555555504',
    'Riego',
    'Jornada de hidratación intensiva y aplicación de acolchado vegetal (mulch) alrededor del plato de la palma.',
    NULL,
    '33333333-3333-3333-3333-333333333302',
    DATE_SUB(NOW(), INTERVAL 3 DAY),
    DATE_SUB(NOW(), INTERVAL 3 DAY)
);

-- 5.8 Logs Iniciales de Auditoría
INSERT INTO `logs_sistema` (
    `id_log`, `id_usuario`, `accion`, `modulo_afectado`, `detalle`, `ip_origen`, `fecha_registro`
) VALUES 
(
    '88888888-8888-8888-8888-888888888801',
    '33333333-3333-3333-3333-333333333301',
    'INICIALIZACION_SISTEMA',
    'SISTEMA_CORE',
    JSON_OBJECT('evento', 'Despliegue inicial de base de datos', 'version', '1.0.0', 'municipio', 'El Bagre - Antioquia'),
    '127.0.0.1',
    NOW()
),
(
    '88888888-8888-8888-8888-888888888802',
    '33333333-3333-3333-3333-333333333301',
    'ALTA_INDIVIDUO',
    'INDIVIDUOS_FORESTALES',
    JSON_OBJECT('codigo_placa', 'EBG-PARQ-001', 'especie', 'Guayacán Rosado', 'zona', 'Parque Principal El Bagre'),
    '127.0.0.1',
    NOW()
);

-- =============================================================================
-- FIN DEL SCRIPT DE BASE DE DATOS
-- =============================================================================
