# Migraciones de base de datos

## 2026-10-23 — Alcance exclusivo Emincut

Archivo: `2026-10-23_solo_emincut.sql`

Elimina el concepto de instituciones / entidades administradoras:

- Quita `id_institucion` de `usuarios`, `zonas` e `individuos_forestales`.
- Elimina las llaves foráneas y el índice asociado.
- Elimina la tabla `instituciones`.

### Antes de ejecutar

1. Respaldar la base de datos:

   ```bash
   mysqldump -uroot forestsrd_db > respaldo_forestsrd_db.sql
   ```

2. Verificar que todos los datos pertenecen a Emincut:

   ```sql
   SELECT DISTINCT id_institucion FROM usuarios;
   SELECT DISTINCT id_institucion FROM zonas;
   SELECT DISTINCT id_institucion FROM individuos_forestales;
   ```

3. Ejecutar la migración una sola vez:

   ```bash
   mysql -uroot forestsrd_db < database/migraciones/2026-10-23_solo_emincut.sql
   ```

Para instalaciones nuevas use directamente `database/forestsrd_db.sql`, que ya
no crea la tabla `instituciones` ni las columnas `id_institucion`.
