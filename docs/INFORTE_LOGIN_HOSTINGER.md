# Informe de diagnóstico — Login ForestID (Hostinger)

**Fecha:** 2026-11-10
**Proyecto:** ForestID / ForestSRD — Aplicación PHP 8 + MySQL para Emincut S.A.S.
**Dominio:** https://forestid.cfmasoft.com
**Rama:** `nuevo-diseño-v2`
**Alcance:** Diagnóstico y arranque del flujo de login tras restablecimiento de contraseña MySQL en Hostinger.

---

## 1. Resumen del caso

Tras el cambio de contraseña del usuario MySQL `u521674450_forestid` en Hostinger
(error original: `SQLSTATE[HY000] [1045] Access denied for user 'u521674450_forestid'@'localhost'`),
se dispone un checklist de diagnóstico y puesta en marcha del login en producción:

1. Verificar cadena de configuración (`config/config.php` → `config/config.local.php` → `config/db.php`).
2. Verificar entorno PHP/MySQL/PDO.
3. Verificar base y usuario en Hostinger.
4. Verificar dump y esquema.
5. Probar conexión PDO y flujo GET/POST del login.
6. Revisar logs posteriores al cambio.
7. Clasificar el error 500 restante.
8. Reportar en 5 bloques: lo que funciona / lo corregido / lo pendiente / causa / siguiente paso.

## 2. Estado verificado contra el repositorio

| Ítem | Estado |
|---|---|
| Flujo POST login (PDO → `usuarios` + `roles` → `password_verify` → fallback → rehash → sesión → `logs_sistema`) | ✅ Implementado en `auth/login.php` |
| Manejo de errores PDO (`try/catch`, `error_log`, mensaje controlado) | ✅ Implementado |
| Fallback temporal de credenciales de prueba (`Admin123*`, `Operario123*`, `Ciudadano123*`) | ✅ Conservado (commits `f1bc589`, `aca5781`) |
| Botones "Cuentas de Prueba Rápidas" | ✅ Retirados |
| Hashes Bcrypt semilla (coste 12) | ✅ Presentes en `database/forestid.sql` |
| Dump: 9 tablas, FKs, `utf8mb4_spanish_ci` | ✅ Presente |
| Live `GET /auth/login.php` | ✅ HTTP 200, renderiza sin botones de prueba |
| `php -l`, verificador CLI de despliegue | ⚠️ Pendiente re-ejecución |

## 3. Hallazgos y divergencias detectadas

1. `config/config.php` no se referencia en ningún `require` del código: está sin trackear en git y es un archivo huérfano. La cadena real de carga pasa por `config/db.php`.
2. `config/db.php` no valida `DB_PASS` vacío con error controlado y registro en log (el prompt lo exige; el código solo valida `DB_NAME`/`DB_USER`).
3. Comentarios de `config/config.local.php` conservan la referencia al nombre antiguo `u521674450_forestID`.
4. La copia versionada de `config/config.local.php` contiene valores de desarrollo local (`127.0.0.1`, `forestsrd_db`, ...), no los de Hostinger.

## 4. Próximos pasos sugeridos

- Ejecutar `php scripts/verificar_despliegue.php` en el servidor.
- Ejecutar las pruebas GET/POST en `https://forestid.cfmasoft.com/auth/login.php`.
- Revisar logs del servidor tras el último cambio.
- Clasificar el error 500 real y aplicar la siguiente corrección.

---

## Instrucciones para subir el aplicativo (evitar incluir lo que no debe)

Al empaquetar el ZIP o subir archivos al servidor/Repositorio para Hostinger:

- **NO incluir** en el ZIP ni commitear:
  - `config/config.local.php` (credenciales reales; está en `.gitignore`).
  - `config/mailer.php` (credenciales reales; está en `.gitignore`).
  - Volcados de BD: `database/*.sql`, `*.sql`, `*.sql.gz`.
  - `forestid.zip`, `*.zip`, logs (`*.log`), backups (`*.bak`), `.env`, `.env.*`.
  - Archivos de diagnóstico temporales: `diagnostico_temporal.php`.
  - `scripts/__pycache__/`.
- **NO sobrescribir** `config/config.local.php` con una versión del ZIP: la contraseña real vive solo en el servidor.
- Tras importar la base en `u521674450_forestid`, eliminar los `.sql` del servidor público.
