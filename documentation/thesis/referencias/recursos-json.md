# Referencia: recursos JSON / serialización (para Parte IV §22 y Apéndice C)

> Referencia interna trimmed (de la vieja `ai/resources-reference.md`, 2025‑11). Fuente de verdad = `app/Http/Resources/` y `packages/laravel-resource-tools/`. Alimenta §22 y el glosario de recursos (Apéndice C).

## Principios

- **Transformación automática:** los modelos se convierten a su `JsonResource` al enviarse al frontend. Nunca se hace la conversión a mano (`JsonResource::make`, `->toResource()`, etc.): se devuelve el modelo/colección y Laravel/Inertia lo transforma.
- **Recursos** en `app/Http/Resources/{Model}Resource.php`; clase base propia `JsonResource` en ese mismo directorio.
- **Traits obligatorios** (del paquete propio `laravel-resource-tools`): `ResourceSubsets` (inclusión condicional de campos según contexto) y `WithResourceAbilities` (metadatos de autorización).

## Patrones estándar

- **Fechas:** `created_at`, `created_ago` (`diffForHumans()`), `deleted_at`.
- **Relaciones con fallback (evita N+1):** `'project' => $this->whenLoaded('project', default: ['id' => $this->project_id])`.
- **Conteos:** `$this->whenCountedOrLoaded('projects')` — prefiere `withCount()`, cae a contar relaciones ya cargadas, nunca dispara consultas extra.
- **Nullables:** defaults sensatos (`?? ''` para texto opcional, `?? null` para verdaderamente opcional).
- **Datos sensibles:** inclusión condicional (p. ej., solo si `$request->user()?->isAdmin()`).

## Abilities (la clave `abilities`/`can`)

`WithResourceAbilities` incluye automáticamente los permisos del usuario para ese objeto, de modo que el frontend sabe qué puede hacer sin consultas extra:

```json
{ "id": 1, "name": "John", "abilities": { "view": true, "update": false, "delete": false } }
```

## Herencia

`UserResource` es clase base; `AdminResource` y `MemberResource` la extienden (`parent::toArray()` + campos propios).

**Recursos actuales:** `UserResource` (base), `AdminResource`, `MemberResource`, `ProjectResource`, `FamilyResource`, `UnitResource`, `UnitTypeResource`, `EventResource`, `LogResource`. (Verificar el conjunto y sus campos contra `app/Http/Resources/`.)
