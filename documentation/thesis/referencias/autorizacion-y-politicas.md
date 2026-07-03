# Referencia: autorización y políticas (para Parte IV §21 y Apéndice D)

> Referencia interna trimmed (de la vieja `ai/policies-reference.md`, verificada contra el código, 2025‑11). Fuente de verdad = `app/Policies/` y `app/Http/Requests/`. Alimenta la matriz de roles/permisos (Apéndice D) y §21.

## Principios

- **Bypass de superadmin a nivel framework:** `Gate::before(fn (User $user) => $user->isSuperadmin() ?: null)` en `AppServiceProvider`. Los superadmins pueden todo; las políticas solo manejan guest/member/admin. (Algunas políticas igual chequean `isSuperadmin()` para lógica puntual, p. ej. `AdminPolicy::update` = superadmin o self.)
- **Políticas** en `app/Policies/{Model}Policy.php` (descubrimiento automático). Métodos estándar: `viewAny`, `view`, `create`, `update`, `delete`, `restore`.
- **Autorización "tercerizada" en FormRequests (por N+1):** algunas políticas devuelven `true` o hacen un chequeo parcial, y la restricción completa (p. ej. "proyectos solapados") se aplica en el `FormRequest` correspondiente vía el trait **`OverlappingProjectsConstraint`** (`app/Http/Requests/Concerns/`), que aborta 403 si el usuario autenticado y el usuario objetivo no comparten proyecto (permite superadmin y self automáticamente). Motivo: User↔Project es muchos‑a‑muchos y chequearlo por colección causaría N+1.
- **Casts por rol:** `$user->asAdmin()` / `$user->asMember()` para acceder a datos específicos del rol; scoping por proyecto con `$user->asAdmin()?->manages($resource->project_id)`.
- **Las políticas chequean *habilidad*, no *estado*** (no chequear si algo está soft‑deleted, etc. — eso es estado de runtime, va en controlador/validación).

## Matriz de autorización por modelo (verificar siempre contra el código)

**Admin:** viewAny→cualquiera · view→Admins pasan; Members ven admins de su proyecto (constraint completa en `ShowAdminRequest`) · create→solo Admins · update/delete→superadmin o self · restore→solo superadmin.

**Member:** viewAny→cualquiera · view→proyectos compartidos (`ShowMemberRequest`) · create→cualquiera · update/delete→self o admin que gestiona el proyecto (`Update/DeleteMemberRequest`) · restore→admin que gestiona el proyecto (`RestoreMemberRequest`).

**Family:** viewAny→cualquiera · view→Members mismo proyecto / Admins que gestionan · create→solo Admins · update→familia propia o admin que gestiona · delete/restore→admin que gestiona.

**Project:** viewAny→superadmin o admin que gestiona 2+ proyectos · view/update/delete/restore→admin que gestiona el proyecto · create→`false` (solo superadmin vía Gate). Members no tienen CRUD de proyectos.

**Unit:** viewAny/view→cualquiera · create→solo Admins · update/delete/restore→admin que gestiona el proyecto de la unidad.

**UnitType:** viewAny/view→cualquiera · create/update/delete/restore→solo Admins (sin restricción por proyecto).

**Log:** viewAny/view→cualquiera. **Inmutable:** sin create/update/delete/restore (no hay método de política ni acción de controlador); los logs los crea el sistema por eventos Eloquent.

**`ProjectScopedRequest`** (base abstracta): valida integridad de contexto (si hay proyecto actual y `project_id`, deben coincidir) y que el usuario tenga acceso al proyecto pedido; inyecta el proyecto actual si no se pasa `project_id`.
