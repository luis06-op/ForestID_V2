# Plan de publicación en GitHub — ForestID_V2

> **Objetivo:** publicar las correcciones P0 de la auditoría en GitHub de forma revisable, reversible y sin filtrar credenciales.
> **Rama de trabajo:** `fix/auditoria-p0` (creada desde `diseno-amarillo-sol`).
> **Estrategia:** PR pequeño y temático hacia `diseno-amarillo-sol`; después, un PR de release hacia `main`.
> **Regla de oro:** ningún commit debe contener `config/mailer.php`, `config/config.local.php`, `.env`, logs, backups ni contraseñas reales.

---

## 0. Estado de partida

| Elemento | Valor |
|---|---|
| Repositorio | `https://github.com/luis06-op/ForestID_V2` |
| Rama base local | `diseno-amarillo-sol` @ `b43eecc` |
| Rama remota de integración | `origin/diseno-amarillo-sol` @ `a8da6a3` |
| Rama de release | `origin/main` @ `e95491d` |
| Rama de correcciones | `fix/auditoria-p0` |
| Documento de auditoría | `docs/AUDITORIA.md` |

> ✅ **Rebase ya ejecutado:** la rama `fix/auditoria-p0` está basada en `a8da6a3` (`origin/diseno-amarillo-sol`). El PR parte de ahí. Si el remoto cambia antes de abrirlo, repita la Fase 3.

### Fases

| Fase | Duración | Responsable | Criterio de salida |
|---|---:|---|---|
| 0. Respaldo | 15 min | Líder técnico | Bundle + tag + dump de BD |
| 1. Verificación local | 20 min | Dev/QA | Lint, login, QR, buscador y `.htaccess` OK |
| 2. Commits | 10 min | Dev | 5–6 commits lógicos, sin secretos |
| 3. Sincronización | 10 min | Dev | Rebase limpio sobre `origin/diseno-amarillo-sol` |
| 4. Push + PR | 10 min | Dev | PR en borrador con evidencia |
| 5. Revisión | 1–2 días | QA + revisor | Checklist completo y aprobación |
| 6. Merge + tag | 15 min | Líder técnico | Merge y etiqueta `v1.1.0` |
| 7. Despliegue | 30–60 min | DevOps | App actualizada y verificada |
| 8. Post-deploy / rollback | 20 min | DevOps | Smoke tests OK o rollback ejecutado |

---

## Fase 0 — Respaldo antes de tocar Git

**Objetivo:** poder volver al estado actual sin depender de la memoria.

```bash
cd /home/luis/Proyectos/github/ForestID_V2

# 1. Etiqueta local del estado previo
git tag -a backup-pre-auditoria b43eecc -m "Respaldo antes de correcciones P0"

# 2. Bundle completo (ramas + tags + historia)
git bundle create ../ForestID_V2_backup_$(date +%F).bundle --all

# 3. Respaldo de la base de datos
mysqldump -uroot forestsrd_db > ../forestsrd_db_backup_$(date +%F).sql

# 4. Respaldo de la copia desplegada (si se usa XAMPP)
cp -a /opt/lampp/htdocs/ForestID_V2 /opt/lampp/htdocs/ForestID_V2_backup_$(date +%F)
```

**Criterio de salida:** existen el bundle, el tag, el dump y la copia de `htdocs`.

> ⚠️ No suba los `.bundle`, `.sql` de respaldo ni la carpeta de backup a GitHub.

---

## Fase 1 — Verificación local antes de commitear

**Objetivo:** demostrar que los cambios funcionan y que no hay secretos.

> 💡 **Composer es opcional en el servidor:** `vendor/` ya está versionado. Los comandos `composer ...` solo son necesarios si va a actualizar dependencias; si no tiene Composer instalado, omítalos.

```bash
cd /home/luis/Proyectos/github/ForestID_V2

# 1. Sintaxis de todos los PHP propios
for f in $(find . -name '*.php' -not -path './vendor/*' -not -path './.opencode/*'); do
  php -l "$f" || exit 1
done

# 2. Dependencias
composer validate --no-check-publish
composer audit

# 3. Hashes semilla reales
php -r '$h=password_hash("Admin123*", PASSWORD_BCRYPT, ["cost"=>12]); var_dump(password_verify("Admin123*", $h));'

# 4. QR decodificables
for f in assets/qr/*.svg; do
  magick -background white "$f" /tmp/qr.png
  echo -n "$f -> "; zbarimg --quiet --raw /tmp/qr.png
done

# 5. Buscar secretos en el árbol de trabajo
git grep -nE "SMTP_CONTRASENA|WHATSAPP_API_TOKEN|DB_PASS.*[^'']|Admin123\*" -- . \
  ':!README.md' ':!docs/AUDITORIA.md' ':!config/mailer.ejemplo.php' || echo "Sin secretos en tracked files"

# 6. Confirmar que los archivos locales están ignorados
git check-ignore -v config/config.local.php config/mailer.php
```

**Smoke test con servidor embebido:**

```bash
php -S 127.0.0.1:8099 -t . &
# Login correcto -> 302 a /admin/dashboard.php
# Login incorrecto -> "Credenciales incorrectas"
# Recuperación -> sin enlace de reseteo en pantalla
# /publico/buscar.php?q=Guayac -> 200
# /publico/ficha.php?codigo=EBG-PARQ-001 -> 200
```

**Criterio de salida:** todo en verde y `git status` solo muestra cambios esperados.

---

## Fase 2 — Organización de commits (ya aplicada)

Los cambios están separados por tema para facilitar la revisión:

| Commit | Contenido |
|---|---|
| `fix(publico): evitar bucle de redirección en la portada` | `publico/index.php` |
| `fix(seguridad): eliminar bypass de login y enlace de reseteo visible` | `auth/login.php`, `auth/recuperar_password.php`, `includes/notificaciones.php`, hashes del `.sql` |
| `fix(qr): usar generador QR estándar y regenerar placas` | `includes/qrcode_helper.php`, `composer.json/lock`, `vendor/chillerlan`, assets QR, altas y regeneración |
| `fix(busqueda): usar placeholders únicos en filtros LIKE` | 4 módulos con `:q1…:q4` |
| `fix(config): aislar credenciales y bloquear rutas sensibles` | `config/db.php`, `config.local.ejemplo.php`, `.gitignore`, `.htaccess` |
| `docs: añadir auditoría P0 y plan de publicación` | `README.md`, `docs/*` |

**Verificación:**

```bash
git log --oneline --decorate -10
git show --stat HEAD
git status --short
```

**Criterio de salida:** commits atómicos, mensajes en imperativo y sin archivos ignorados.

---

## Fase 3 — Sincronizar con el remoto (rebase) — ✅ ya ejecutado

**Objetivo:** evitar un PR con conflictos o con commits duplicados.

```bash
git fetch origin

# Rebase sobre la rama remota de integración
git rebase origin/diseno-amarillo-sol

# Si hay conflictos:
#   git status
#   editar archivos
#   git add <archivo>
#   git rebase --continue
# Si algo sale mal:
#   git rebase --abort
```

Después del rebase, **repetir la Fase 1** (lint + smoke test).

**Criterio de salida:** `git log --oneline origin/diseno-amarillo-sol..HEAD` muestra solo los commits de la auditoría, sin conflictos.

---

## Fase 4 — Push y Pull Request

```bash
git push -u origin fix/auditoria-p0
```

### Opción A — GitHub CLI

```bash
gh pr create \
  --base diseno-amarillo-sol \
  --head fix/auditoria-p0 \
  --title "fix(P0): auditoría de seguridad, QR estándar y buscador" \
  --body-file .github/PULL_REQUEST_TEMPLATE.md \
  --draft
```

### Opción B — Web

1. Abrir `https://github.com/luis06-op/ForestID_V2/compare/diseno-amarillo-sol...fix/auditoria-p0`
2. Título: **fix(P0): auditoría de seguridad, QR estándar y buscador**
3. Marcar como **Draft** hasta terminar la revisión.
4. Asignar revisores: backend + QA.

### Plantilla de descripción del PR

```markdown
## Qué cambia
- Cierra el bypass de login y el enlace de reseteo visible (P0).
- Reemplaza el generador QR no conforme por chillerlan/php-qrcode.
- Aísla credenciales en config/config.local.php y bloquea rutas sensibles.
- Corrige el 500 del buscador por placeholders repetidos.
- Añade docs/AUDITORIA.md y este plan.

## Por qué
Ver docs/AUDITORIA.md, hallazgos C1–C6.

## Cómo probar
1. `php -l` en todos los PHP.
2. Login correcto/incorrecto.
3. `zbarimg` sobre los 4 QR.
4. `?q=Guayac` en los 4 listados.
5. `curl -I` a `.git/config`, `database/*.sql`, `assets/uploads/shell.php`.

## Riesgos
- Cambia la configuración de BD: requiere crear config/config.local.php.
- Los QR semilla apuntan a localhost; regenerar en producción.

## Checklist
- [ ] Sin secretos en el diff
- [ ] Lint OK
- [ ] Smoke tests OK
- [ ] Documentación actualizada
```

**Criterio de salida:** PR abierto en Draft con evidencia y revisores asignados.

---

## Fase 5 — Revisión y QA

### Checklist del revisor

**Seguridad**
- [ ] No hay contraseñas, tokens ni claves en el diff.
- [ ] `config/config.local.php` y `config/mailer.php` están ignorados.
- [ ] El login solo usa `password_verify()`.
- [ ] El enlace de reseteo no se imprime por defecto.
- [ ] `.htaccess` bloquea `.git`, `database/`, `docs/`, `config/`, `includes/`, `vendor/`.
- [ ] `assets/uploads/*.php` devuelve 403.

**Funcionalidad**
- [ ] Login admin/operario funciona con los hashes nuevos.
- [ ] Buscador y listados con `?q=` devuelven 200.
- [ ] Los 4 QR se decodifican con `zbarimg`.
- [ ] Alta de individuo genera QR y ficha.
- [ ] Ficha pública y reporte ciudadano funcionan.

**Datos**
- [ ] El `.sql` no se ejecuta sobre una BD con datos (hace `DROP TABLE`).
- [ ] Los hashes del `.sql` verifican con sus contraseñas.
- [ ] No se perdió `config/mailer.php` del despliegue.

**Documentación**
- [ ] README y manual técnico reflejan `config/config.local.php`.
- [ ] `docs/AUDITORIA.md` está enlazado o referenciado.
- [ ] El plan de despliegue está actualizado.

**Criterio de salida:** aprobación de al menos un revisor y CI en verde.

---

## Fase 6 — Merge y etiqueta

**Estrategia recomendada:** *Squash and merge* si se quiere un solo commit en la rama de integración; *Merge commit* si se quiere conservar el detalle de los 6 commits. Para auditoría, se recomienda **Merge commit** (trazabilidad).

```bash
# Tras aprobar el PR y actualizar la rama
git checkout diseno-amarillo-sol
git pull origin diseno-amarillo-sol
git merge --no-ff fix/auditoria-p0
git push origin diseno-amarillo-sol

# Etiqueta de release
git tag -a v1.1.0 -m "Auditoría P0: seguridad, QR estándar y buscador"
git push origin v1.1.0
```

**Release notes (plantilla):**

```markdown
## v1.1.0 — Auditoría P0

### Seguridad
- Eliminado bypass de login con credenciales en texto plano.
- El enlace de recuperación ya no se muestra en pantalla; se envía por correo.
- Credenciales de BD fuera de Git (`config/config.local.php`).
- Rutas sensibles bloqueadas con `.htaccess`.

### Funcionalidad
- QR estándar ISO/IEC 18004 (chillerlan/php-qrcode) y placas regeneradas.
- Buscador y listados corregidos (placeholders únicos).
- Mensajes de error de BD sin detalles internos.

### Documentación
- `docs/AUDITORIA.md` y `docs/PLAN_PUBLICACION_GITHUB.md`.
```

**Criterio de salida:** merge en `diseno-amarillo-sol` y tag `v1.1.0` publicado.

---

## Fase 7 — Despliegue

### 7.1 Sincronizar la copia de XAMPP (sin sobrescribir secretos)

```bash
rsync -av --delete \
  --exclude='.git/' \
  --exclude='.opencode/' \
  --exclude='config/mailer.php' \
  --exclude='config/config.local.php' \
  /home/luis/Proyectos/github/ForestID_V2/ \
  /opt/lampp/htdocs/ForestID_V2/
```

> `--delete` elimina archivos que ya no existen en el repo, pero **respeta los excludes**. Verifique con `rsync -avn` (dry-run) antes.

### 7.2 Configuración local

```bash
cp /opt/lampp/htdocs/ForestID_V2/config/config.local.ejemplo.php \
   /opt/lampp/htdocs/ForestID_V2/config/config.local.php
# Editar DB_HOST, DB_NAME, DB_USER, DB_PASS
```

### 7.3 Base de datos

- **Instalación nueva:** importar `database/forestsrd_db.sql` en phpMyAdmin.
- **Producción con datos:** **NO** importar el dump (hace `DROP TABLE`). Aplicar solo los cambios necesarios con un script de migración.
- Crear un usuario MySQL con permisos mínimos (`SELECT, INSERT, UPDATE, DELETE`), no `root`.

### 7.4 Seguridad post-despliegue

- Cambiar las contraseñas de las 3 cuentas semilla o desactivarlas.
- Confirmar que `FORESTID_MODO_DEMO` **no** está definido.
- Activar HTTPS y redirigir `http → https`.
- Verificar que Apache tenga `AllowOverride All` y `mod_rewrite`.
- Regenerar los QR desde `admin/qr/index.php` para el dominio real.

**Criterio de salida:** app accesible por HTTPS, login y QR funcionando, rutas sensibles bloqueadas.

---

## Fase 8 — Verificación post-despliegue y rollback

### Smoke tests

```bash
BASE="https://tu-dominio/ForestID_V2"

curl -I "$BASE/publico/index.php"                 # 200
curl -I "$BASE/auth/login.php"                    # 200
curl -I "$BASE/.git/config"                       # 404
curl -I "$BASE/database/forestsrd_db.sql"         # 403/404
curl -I "$BASE/config/config.local.php"           # 403/404
curl -I "$BASE/assets/uploads/shell.php"          # 403/404

curl -s "$BASE/publico/buscar.php?q=Guayac" | grep -i "Guayac"
zbarimg --raw /ruta/al/qr_EBG-PARQ-001.svg        # URL del dominio real
```

### Rollback

**Código:**

```bash
git revert --no-commit <merge_commit>
git commit -m "revert: desplegar versión anterior por incidente"
git push origin diseno-amarillo-sol
```

**Base de datos:** restaurar el dump de la Fase 0.

**XAMPP:** restaurar `/opt/lampp/htdocs/ForestID_V2_backup_<fecha>`.

**Criterio de salida:** smoke tests OK o rollback completado y comunicado.

---

## Qué NO subir nunca

- `config/mailer.php` (SMTP real)
- `config/config.local.php` (BD real)
- `.env`, `.env.*`
- `*.log`, `*.bak`, `*.sql.gz`
- Dumps de respaldo (`forestsrd_db_backup_*.sql`)
- `vendor/` no: **sí se sube** porque el proyecto funciona sin Composer en el servidor.
- Archivos de `assets/uploads/` con datos de usuarios reales.

---

## Historia de Git y secretos

Los commits antiguos contienen las contraseñas demo (`Admin123*`, etc.) y `DB_USER=root` / `DB_PASS=''`. Si el repositorio es **público**, trate esas credenciales como comprometidas:

1. **Rote** cualquier credencial real (SMTP, WhatsApp, BD de producción).
2. Cambie las contraseñas demo en la BD y en el `.sql`.
3. Verifique que `config/mailer.php` nunca estuvo versionado:

```bash
git log --all --oneline -- config/mailer.php
git log --all --oneline -S "SMTP_CONTRASENA"
```

4. Si necesita borrar la historia (solo si hay secretos reales), use `git filter-repo` **sobre un clon de respaldo** y coordine con el equipo. Para credenciales demo, rotarlas es suficiente.

---

## Definición de terminado (DoD)

- [ ] PR aprobado y mergeado en `diseno-amarillo-sol`.
- [ ] Tag `v1.1.0` publicado.
- [ ] `diseno-amarillo-sol` mergeada en `main` mediante un PR de release.
- [ ] Despliegue verificado con los smoke tests.
- [ ] Contraseñas demo rotadas o cuentas desactivadas.
- [ ] `.htaccess` activo y rutas sensibles bloqueadas.
- [ ] QR regenerados con el dominio real.
- [ ] Backup y plan de rollback documentados.

---

## Anexo A — CI opcional (GitHub Actions)

Crear `.github/workflows/ci.yml`:

```yaml
name: CI

on:
  pull_request:
  push:
    branches: [diseno-amarillo-sol, main]

jobs:
  php:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, gd, pdo_mysql
      - name: Validar Composer
        run: composer validate --no-check-publish
      - name: Instalar dependencias
        run: composer install --no-interaction --prefer-dist
      - name: Lint PHP
        run: |
          find . -name '*.php' -not -path './vendor/*' -print0 \
            | xargs -0 -n1 php -l
```

---

## Anexo B — PR de release `diseno-amarillo-sol` → `main`

Cuando la rama de diseño esté estable:

```bash
git checkout diseno-amarillo-sol
git pull origin diseno-amarillo-sol
git push origin diseno-amarillo-sol

gh pr create --base main --head diseno-amarillo-sol \
  --title "release: ForestID v1.1.0 (auditoría P0 + diseño)" \
  --body "Incluye las correcciones P0 de docs/AUDITORIA.md y el rediseño visual."
```

Revisar especialmente que `main` no reciba `config/mailer.php` ni `config/config.local.php`.

---

## Anexo C — Comandos rápidos de verificación

```bash
# Todo el lint
find . -name '*.php' -not -path './vendor/*' -not -path './.opencode/*' -exec php -l {} \;

# QR
for f in assets/qr/*.svg; do magick -background white "$f" /tmp/qr.png; echo -n "$f -> "; zbarimg --quiet --raw /tmp/qr.png; done

# Secretos en tracked files
git grep -nE "Admin123\*|Operario123\*|Ciudadano123\*|SMTP_CONTRASENA|DB_PASS" -- . ':!README.md' ':!docs/*'

# Estado del repo
git status --short
git log --oneline --decorate -10
```
