-- =============================================================================
-- ForestID / ForestSRD — Migración: alcance exclusivo Emincut S.A.S.
--
-- Elimina el concepto de instituciones / entidades administradoras:
--   * Quita las llaves foráneas y columnas `id_institucion` de usuarios,
--     zonas e individuos_forestales.
--   * Elimina la tabla `instituciones`.
--
-- IMPORTANTE:
--   1. Haga un respaldo de la base de datos antes de ejecutar este script.
--   2. Verifique que todos los registros pertenecen a Emincut:
--        SELECT DISTINCT id_institucion FROM usuarios;
--        SELECT DISTINCT id_institucion FROM zonas;
--        SELECT DISTINCT id_institucion FROM individuos_forestales;
--   3. Ejecute este script UNA sola vez sobre una base existente.
--      Para instalaciones nuevas use database/forestsrd_db.sql actualizado.
-- =============================================================================

USE forestsrd_db;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Eliminar llaves foráneas hacia instituciones (si existen).
SET @sql = (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `usuarios` DROP FOREIGN KEY `fk_usuarios_instituciones`',
        'SELECT 1')
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND CONSTRAINT_NAME = 'fk_usuarios_instituciones'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `zonas` DROP FOREIGN KEY `fk_zonas_instituciones`',
        'SELECT 1')
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'zonas'
      AND CONSTRAINT_NAME = 'fk_zonas_instituciones'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `individuos_forestales` DROP FOREIGN KEY `fk_indiv_instituciones`',
        'SELECT 1')
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'individuos_forestales'
      AND CONSTRAINT_NAME = 'fk_indiv_instituciones'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Eliminar el índice de zonas (si existe).
SET @sql = (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `zonas` DROP INDEX `idx_zonas_institucion`',
        'SELECT 1')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'zonas'
      AND INDEX_NAME = 'idx_zonas_institucion'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Eliminar las columnas id_institucion (si existen).
SET @sql = (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `usuarios` DROP COLUMN `id_institucion`',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'id_institucion'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `zonas` DROP COLUMN `id_institucion`',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'zonas'
      AND COLUMN_NAME = 'id_institucion'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `individuos_forestales` DROP COLUMN `id_institucion`',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'individuos_forestales'
      AND COLUMN_NAME = 'id_institucion'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Eliminar la tabla instituciones.
DROP TABLE IF EXISTS `instituciones`;

SET FOREIGN_KEY_CHECKS = 1;
