# Guía de Arquitectura — Coordinador de Orquestación (handoff)

> **Propósito:** documento de referencia para el **coordinador** de orquestación multi-agente
> (plataforma Orca). Es **universal**: aplica a cualquier proyecto/workspace, no solo a ForestID.
> Incluye el ciclo canónico, los contratos y un **registro de acciones** de la sesión actual para
> entregar a otro agente como coordinador.

---

## 1. Vocería Orca (terminología)

- **Run:** namespace durable + inbox del coordinador. No agenda ni coloca workers.
- **Task:** unidad de trabajo (self-contained: Target, Change, Constraints, Ownership, Observable acceptance).
- **Dispatch:** un intento autoritativo de una Task.
- **Worker:** terminal agéntica supervisada sobre un Dispatch.

## 2. Ciclo de coordinación (canónico)

```text
ORCA status --json
ORCA orchestration run-create --objective "<objetivo>" --json
ORCA orchestration worker-start --spec "<task spec>" --worktree current --agent <agent> --json
ORCA orchestration check --wait --types "worker_done,escalation,question" --timeout-ms 900000 --json
```

Procesar cada mensaje de la entrega:
- Preguntas → `ORCA orchestration reply --id <msg> --body "<respuesta>" --json`.
- `worker_done` → validar contra el Dispatch activo esperado → decidir next owner:
  - reusar terminal → nuevo dispatch;
  - retener → `worker-retain`;
  - liberar post-settled → `worker-release`.
- Ack de la entrega: `ORCA orchestration check --terminal <coordinator_handle> --ack <delivery_id> --json`.

Repetir `check --wait` hasta `count = 0`. Si hay 3 waits vacíos consecutivos, enumerar con
`ORCA orchestration worker-list --include-remote --json` y actuar sobre `projection.attention`,
`projection.attention.requiresAction` y `projection.nextAction.argv`.

## 3. Contrato del task spec (self-contained, obligatorio)

Todo task debe nombrar:
1. **Target:** archivos/componente/entorno en scope.
2. **Change:** resultado concreto.
3. **Constraints:** invariante, reglas de compatibilidad, límites no tocar.
4. **Ownership:** qué puede editar el worker y frontera de coordinación.
5. **Observable acceptance:** test/output/evidence que prueba el completado.

## 4. Obligaciones del worker

1. Hacer solo la task actual; preguntar al coordinador vía `ask` si bloquea.
2. Heartbeats al cadencia del preamble; `worker_done` exactamente una vez con resumen de 3 frases,
   ambos lifecycle IDs y `--outcome succeeded|failed`.
3. Antes de `worker_done`: `check --terminal <handle> --json`; ack batch hasta `count = 0`.
4. Tras `worker_done` aceptado: reusar / retener / liberar.

## 5. Cierre del coordinador

- Tras settle aceptado: reusar, retener o liberar; no pollear.
- Reportar al solicitante: outcome por task, evidencia, blockers no resueltos.

---

## 6. Registro de acciones — sesión de coordinación (2026-10-29 → 2026-10-30)

### 6.1 Verificación de agentes con terminales abiertas (tema inicial)

- Runtime Orca: `ready`, `reachable`, `connected` (v1.4.244).
- Terminales vivos (`orca terminal list --json`): 4 en el worktree ForestID_V2 —
  agentIdentity `hermes`, `opencode`, `codex`, `kilo` (todos `connected`, `writable`, no orphaned).
- Run smoke `run_d673afd84912`: 3 tasks → `completed/succeeded`; workers → `released/exited`.

### 6.2 Upload del task-distribution a GitHub

- Creado `docs/AGENTES_TAREAS.md` (reparto: Kilo→catálogo, Codex→endpoint, OpenCode→JS, Hermes→formulario).
- Commit `e49f112` y push OK a `origin/diseno-amarillo-sol`.

### 6.3 Orquestación del autocompletado de especies (Run `run_c772bcccf3ed`)

- Workers lanzados (misma ronda): Kilo (`task_340b819b5870`), Codex (`task_6061d2722e69`),
  OpenCode (`task_91c5b46a0517`), Hermes (`task_2165d62bfa51`).
- Codex / OpenCode / Hermes → `worker_done` **succeeded**; releases → `retained` (`user_takeover`).
- Kilo → **dejado fuera** por instrucción del coordinador.
- Task `task_5644cdebe7a9` → **cancelada** (spec corrupto por backticks del shell).
- Task `task_72002a8e7247` → **preparada v2** (spec limpio) y lanzada a **Grok** (`dispatch ctx_3a195ef97012`).
- Grok → **descartado** (`worker-stop`); relanzada a **OpenClaw** (`dispatch ctx_9ef95637a0f6`).
- OpenClaw → `worker_done` **succeeded** (task `task_72002a8e7247`: *"Catálogo curado creado y lint OK"*).

### 6.4 Commits de los workers (sin push inicial)

- `80ef772` — feat: autocompletado de especies (endpoint API, JS, formulario).

---

## 7. Repo del coordinador (ejemplo ForestID_V2)

- GitHub: `luis06-op/ForestID_V2` · Local: `/home/luis/Proyectos/github/ForestID_V2`
- Rama: `diseno-amarillo-sol` · Remote: `git@github.com:luis06-op/ForestID_V2.git`
- Stack: PHP 8 + MySQL 8 (XAMPP), Tailwind CDN + vanilla JS, `.emi-*` tokens, baja lógica, PDO+CSRF+htmlspecialchars.

---

## 8. Handoff a otro agente coordinador

1. Leer esta guía completa.
2. Ejecutar el ciclo de §2 con el objetivo/Task del nuevo coordinador.
3. Usar specs auto-contenidos (§3) y cumplir obligations (§4).
4. Cerrar con §5.
5. Aplicar §7 si el repo objetivo es ForestID_V2.
