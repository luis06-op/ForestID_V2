-- =============================================================================
-- ForestID / ForestSRD — Migración: corrección del doble escape (A4)
--
-- Contexto: hasta esta versión, sanear() aplicaba htmlspecialchars() al guardar,
-- por lo que los textos con &, ', " y < > quedaron almacenados como entidades
-- HTML (&amp;, &#039;, &quot;, &lt;, &gt;) y se volvían a escapar al mostrarse.
--
-- Este script revierte un nivel de escape en los textos de usuario. El orden de
-- los REPLACE es intencional: &amp; se decodifica AL FINAL para no convertir
-- &amp;lt; en < de forma accidental.
--
-- IMPORTANTE:
--   1. Haga un respaldo antes de ejecutar:
--        mysqldump -uroot forestsrd_db > respaldo_forestsrd_db.sql
--   2. Ejecute el script UNA sola vez. Volver a ejecutarlo no cambia el
--      resultado (los registros ya limpios no contienen entidades).
--   3. Los registros históricos de logs_sistema.detalle no se modifican:
--      decodificar entidades dentro del JSON podría invalidarlo. Es posible
--      que esos logs antiguos muestren entidades en la vista de auditoría.
--   4. Las instalaciones nuevas no necesitan este script: importe
--      database/forestsrd_db.sql, que ya no guarda datos escapados.
-- =============================================================================

USE forestsrd_db;

-- roles
UPDATE `roles`
SET `nombre_rol` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`nombre_rol`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&'),
    `descripcion` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`descripcion`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&')
WHERE `nombre_rol` LIKE '%&%;' OR `descripcion` LIKE '%&%;';

-- usuarios
UPDATE `usuarios`
SET `nombres` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`nombres`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&'),
    `apellidos` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`apellidos`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&')
WHERE `nombres` LIKE '%&%;' OR `apellidos` LIKE '%&%;';

-- zonas
UPDATE `zonas`
SET `nombre_zona` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`nombre_zona`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&'),
    `descripcion` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`descripcion`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&'),
    `municipio` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`municipio`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&'),
    `departamento` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`departamento`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&')
WHERE `nombre_zona` LIKE '%&%;' OR `descripcion` LIKE '%&%;'
   OR `municipio` LIKE '%&%;' OR `departamento` LIKE '%&%;';

-- individuos_forestales
UPDATE `individuos_forestales`
SET `nombre_comun` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`nombre_comun`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&'),
    `nombre_cientifico` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`nombre_cientifico`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&'),
    `familia_botanica` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`familia_botanica`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&'),
    `descripcion_general` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`descripcion_general`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&')
WHERE `nombre_comun` LIKE '%&%;' OR `nombre_cientifico` LIKE '%&%;'
   OR `familia_botanica` LIKE '%&%;' OR `descripcion_general` LIKE '%&%;';

-- estados_fitosanitarios
UPDATE `estados_fitosanitarios`
SET `diagnostico` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`diagnostico`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&'),
    `recomendacion` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`recomendacion`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&')
WHERE `diagnostico` LIKE '%&%;' OR `recomendacion` LIKE '%&%;';

-- actividades_mantenimiento
UPDATE `actividades_mantenimiento`
SET `descripcion` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`descripcion`, '&#039;', CHAR(39)), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&')
WHERE `descripcion` LIKE '%&%;';
