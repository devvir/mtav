# PARTE 2: ANÁLISIS TÉCNICO DETALLADO (Continuación)

## Sección 9: Desarrollo Asistido por IA - Metodología Detallada

### 9.1. GitHub Copilot como Herramienta de Pair Programming

En el desarrollo de MTAV, GitHub Copilot no fue una simple herramienta de autocompletado, sino un verdadero **compañero de programación**.

#### Metodología de Trabajo

**Sesión de desarrollo típica:**

1. **Planificación (humano):**
   - Definir feature: "Implementar sistema de preferencias de familias"
   - Dividir en tareas: Models, migrations, controllers, tests, UI

2. **Escritura de tests (humano + IA):**
   - Humano escribe descripción del test
   - Copilot sugiere implementación
   - Humano revisa y ajusta
   - **Red-Green-Refactor (TDD)**

3. **Implementación (humano + IA):**
   - Humano escribe comentarios explicando qué necesita
   - Copilot genera código base
   - Humano refina y optimiza

4. **Revisión (humano):**
   - Verificar que tests pasen
   - Code review manual
   - Refactoring si es necesario

### 9.2. Técnicas de Prompt Engineering

**Técnica 1: Comentarios Descriptivos**

```php
// Create a method that calculates family satisfaction score based on
// the preference rank they received. First preference = 100 points,
// second = 80, third = 60, fourth = 40, fifth+ = 20.

// Copilot genera:
public function satisfactionScore(): int
{
    return match($this->assigned_preference_rank) {
        1 => 100,
        2 => 80,
        3 => 60,
        4 => 40,
        default => 20,
    };
}
```

**Técnica 2: Ejemplos en Comentarios**

```php
// Validate family preferences
// Example: family can rate 1-5 stars, must select at least 3 units

// Copilot genera reglas de validación apropiadas
```

**Técnica 3: Contexto en el Archivo**

Copilot aprende del código existente:

```php
// Ya existente:
protected function visitRoute(string $route, int $asMember = null): TestResponse { ... }

// Empiezo a escribir:
protected function sendPostRequest(

// Copilot completa con mismo patrón:
protected function sendPostRequest(
    string $route,
    int $asMember = null,
    array $data = [],
    bool $redirects = true
): TestResponse { ... }
```

### 9.3. Áreas de Mayor Impacto

#### Tests (60% generados por IA)

**Por qué alta tasa:**
- Tests siguen patrones muy consistentes
- Mucha repetición estructural
- Copilot aprende rápido de tests anteriores

**Ejemplo:**

Escribo:
```php
it('requires firstname when creating member', function () {
```

Copilot completa:
```php
it('requires firstname when creating member', function () {
    $response = $this->sendPostRequest('members.store', asAdmin: 1, data: [
        'lastname' => 'Doe',
        'email' => 'test@example.com',
        // firstname omitted
    ], redirects: false);

    $response->assertInvalid('firstname');
});
```

#### Migraciones (70% generadas por IA)

**Por qué alta tasa:**
- Sintaxis muy predecible
- Patrones estándar (foreign keys, indexes, timestamps)

**Ejemplo:**

Escribo:
```php
// Migration: create family_preferences table with family_id, unit_id, rating (1-5)
```

Copilot genera:
```php
Schema::create('family_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('family_id')->constrained()->onDelete('cascade');
    $table->foreignId('unit_id')->constrained()->onDelete('cascade');
    $table->integer('rating')->default(3);
    $table->timestamps();

    $table->unique(['family_id', 'unit_id']);
});
```

#### Componentes Vue Simples (40% generados por IA)

**Tasa moderada porque:**
- Lógica de UI requiere decisiones de diseño
- Accesibilidad requiere consideración manual
- Componentes tienen variabilidad alta

**Ejemplo de uso:**

Escribo:
```vue
<script setup lang="ts">
// Component: Display family card with name, member count, and link to details
```

Copilot sugiere estructura base, luego ajusto estilos y accesibilidad manualmente.

### 9.4. Limitaciones Encontradas

#### Limitación 1: No Entiende Reglas de Negocio Específicas

**Problema:**

Copilot no sabe que "familias son atómicas" es una regla crítica de MTAV.

**Ejemplo:**

Escribo:
```php
public function assignUnit(Member $member, Unit $unit) {
```

Copilot sugiere:
```php
public function assignUnit(Member $member, Unit $unit) {
    $member->unit_id = $unit->id;
    $member->save();
}
```

**Error:** Asigna unidad a un miembro individual, viola atomicidad de familia.

**Corrección manual:**

```php
public function assignUnit(Family $family, Unit $unit) {
    $family->unit_id = $unit->id;
    $family->save();
    // Todos los miembros de la familia reciben esta unidad
}
```

**Lección:** Siempre revisar lógica de negocio crítica.

#### Limitación 2: Sugerencias Anticuadas

**Problema:**

Copilot entrenado con millones de repos, algunos usan patrones viejos.

**Ejemplo:**

Copilot sugiere:
```php
$families = DB::table('families')->where('project_id', 1)->get();
```

**Mejor (Eloquent):**
```php
$families = Family::where('project_id', 1)->get();
```

**Lección:** Conocer mejores prácticas actuales y rechazar sugerencias anticuadas.

#### Limitación 3: No Considera Accesibilidad

**Problema:**

Copilot genera UI funcional pero no necesariamente accesible.

**Ejemplo:**

Copilot sugiere:
```vue
<button class="text-sm">Guardar</button>
```

**Corrección manual (accesibilidad):**
```vue
<button class="text-base md:text-lg font-medium px-6 py-3" aria-label="Guardar cambios">
    Guardar
</button>
```

**Lección:** Siempre verificar contra `ACCESSIBILITY_AND_TARGET_AUDIENCE.md`.

### 9.5. Impacto en Aprendizaje

**Beneficios educativos:**

1. **Exposición a buenos patrones:**
   - Copilot sugiere código idiomático
   - Aprendo convenciones de Laravel/Vue

2. **Feedback inmediato:**
   - Si mi código tiene errores, Copilot no autocompleta bien
   - Indicador de que algo está mal

3. **Descubrimiento de APIs:**
   - Copilot me muestra métodos de Laravel que no conocía
   - Ejemplo: `$request->boolean('active')` en lugar de manual cast

**Riesgos mitigados:**

1. **Dependencia:**
   - Siempre entiendo código antes de aceptarlo
   - Si no entiendo, investigo en documentación

2. **Complacencia:**
   - No acepto primera sugerencia ciegamente
   - Considero alternativas

### 9.6. Comparación: Con vs Sin IA

**Estimación de tiempo para feature "Sistema de Preferencias":**

**Sin Copilot:**
- Migrations: 30 min
- Models: 45 min
- Controllers: 2 horas
- Policies: 1 hora
- Vue components: 3 horas
- Tests: 4 horas
- **Total: ~11 horas**

**Con Copilot:**
- Migrations: 10 min (70% generado)
- Models: 20 min (50% generado)
- Controllers: 1 hora (40% generado)
- Policies: 30 min (50% generado)
- Vue components: 2 horas (30% generado)
- Tests: 2 horas (60% generado)
- **Total: ~6 horas**

**Ahorro: ~45%**

**Importante:** No es solo velocidad, sino también calidad. Copilot ayuda a evitar errores comunes.

### 9.7. Documentación de Código Generado

**Práctica adoptada:** Indicar en commits cuando código fue generado con IA.

**Ejemplo de commit message:**

```
feat: Add family preferences system

- Models and migrations (70% AI-generated, reviewed)
- Controllers with validation (50% AI-assisted)
- Vue components (30% AI-scaffolded)
- Tests (60% AI-generated, manually verified)
```

**Beneficio:** Transparencia para futuros mantenedores.

### 9.8. El Futuro: Co-evolución Humano-IA

**Reflexión:**

El desarrollo de MTAV fue un experimento en **programación colaborativa humano-IA**. No fue humano O IA, sino humano Y IA.

**Roles complementarios:**
- **IA:** Velocidad, patrones, código boilerplate
- **Humano:** Juicio, arquitectura, lógica de negocio, creatividad

**Analogía final:**

> Copilot es como tener un asistente junior excepcionalmente rápido que sabe mucho pero necesita supervisión. Puedo delegar tareas mecánicas y enfocarme en lo que realmente requiere pensamiento humano: arquitectura, decisiones de negocio, experiencia de usuario.

---

## Sección 10: Seguridad - Control de Acceso y Protección

### 10.1. Modelo de Seguridad: Scope-Based Access

MTAV implementa un modelo de seguridad **basado en contexto (scope)**:

**Niveles de aislamiento:**

1. **Project Scope:** Usuarios solo acceden a datos de su proyecto
2. **Family Scope:** Miembros solo acceden a datos de su familia
3. **Role Scope:** Admins vs Members tienen permisos diferentes

### 10.2. Implementación: Global Scopes

**ProjectScope (nivel de query):**

```php
namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProjectScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        // Superadmins ven todo
        if ($user?->isSuperadmin()) {
            return;
        }

        // Otros solo ven su proyecto
        if ($user && $user->project_id) {
            $builder->where($model->getTable() . '.project_id', $user->project_id);
        }
    }
}
```

**Aplicación a modelos:**

```php
class Family extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new ProjectScope());
    }
}
```

**Efecto:**

```php
// Como admin del proyecto 1
Family::all();
// SELECT * FROM families WHERE project_id = 1

// Como superadmin
Family::all();
// SELECT * FROM families  (sin filtro)
```

**Beneficio:** Imposible acceder accidentalmente a datos de otros proyectos.

### 10.3. Implementación: Policies

**FamilyPolicy (nivel de acción):**

```php
namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    // ¿Puede ver la lista de familias?
    public function viewAny(User $user): bool
    {
        // Todos los usuarios autenticados pueden ver familias de su proyecto
        return true;  // Global scope filtra por proyecto
    }

    // ¿Puede ver esta familia específica?
    public function view(User $user, Family $family): bool
    {
        // Solo si pertenece al mismo proyecto
        return $user->project_id === $family->project_id;
    }

    // ¿Puede crear familias?
    public function create(User $user): bool
    {
        // Solo admins y superadmins
        return $user->isAdmin() || $user->isSuperadmin();
    }

    // ¿Puede actualizar esta familia?
    public function update(User $user, Family $family): bool
    {
        // Solo admins del mismo proyecto o superadmins
        return ($user->isAdmin() && $user->project_id === $family->project_id)
            || $user->isSuperadmin();
    }

    // ¿Puede eliminar esta familia?
    public function delete(User $user, Family $family): bool
    {
        // Solo admins del mismo proyecto o superadmins
        return ($user->isAdmin() && $user->project_id === $family->project_id)
            || $user->isSuperadmin();
    }

    // ¿Puede gestionar preferencias de esta familia?
    public function managePreferences(User $user, Family $family): bool
    {
        // Miembros de la familia o admins del proyecto
        return $user->isMemberOf($family)
            || ($user->isAdmin() && $user->project_id === $family->project_id)
            || $user->isSuperadmin();
    }
}
```

**Enforcement en controladores:**

```php
public function update(Request $request, Family $family)
{
    // Lanza 403 Forbidden si policy falla
    $this->authorize('update', $family);

    $family->update($request->validated());
    return to_route('families.show', $family);
}
```

**Enforcement automático en rutas:**

```php
Route::resource('families', FamilyController::class)
    ->middleware('can:viewAny,App\Models\Family');
```

### 10.4. Middleware de Autenticación

**auth middleware:**

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::resource('families', FamilyController::class);
    // ... todas las rutas protegidas
});
```

**guest middleware (solo para no autenticados):**

```php
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show']);
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show']);
});
```

### 10.5. Protección CSRF

Laravel incluye protección CSRF (Cross-Site Request Forgery) automática.

**Token CSRF en formularios:**

```vue
<template>
    <form @submit.prevent="submit">
        <!-- Inertia.js incluye token automáticamente -->
        <input v-model="form.name" />
        <button type="submit">Guardar</button>
    </form>
</template>
```

**Verificación en backend:**

Laravel verifica automáticamente que todos los POST/PUT/DELETE incluyan token válido.

**Requests sin token → 419 Page Expired**

### 10.6. Protección XSS

**Vue.js escapa HTML automáticamente:**

```vue
<template>
    <!-- Seguro: Vue escapa {{ }} -->
    <p>{{ userInput }}</p>

    <!-- Si userInput = "<script>alert('XSS')</script>" -->
    <!-- Renderiza: &lt;script&gt;alert('XSS')&lt;/script&gt; -->
</template>
```

**Renderizado HTML explícito (usar solo con datos confiables):**

```vue
<template>
    <!-- Peligroso si $dangerousContent viene de usuarios -->
    <div v-html="dangerousContent"></div>
</template>
```

**Regla en MTAV:** NUNCA usar `v-html` con input de usuarios.

### 10.7. Protección SQL Injection

**Eloquent previene SQL injection:**

```php
// ✅ SEGURO (Eloquent usa prepared statements)
$families = Family::where('name', $request->input('search'))->get();

// ❌ PELIGROSO (SQL crudo sin binding)
$families = DB::select("SELECT * FROM families WHERE name = '{$request->input('search')}'");

// ✅ SEGURO (raw query con bindings)
$families = DB::select("SELECT * FROM families WHERE name = ?", [$request->input('search')]);
```

**Regla en MTAV:** Siempre usar Eloquent o bindings.

### 10.8. Protección de Archivos Sensibles

**Nginx configurado para denegar acceso a archivos ocultos:**

```nginx
location ~ /\.(?!well-known).* {
    deny all;
}
```

**Bloquea acceso a:**
- `.env` (contiene secrets)
- `.git` (código fuente)
- `.env.example`
- Cualquier archivo que empiece con `.`

### 10.9. Validación de Inputs

**Regla:** NUNCA confiar en input del usuario.

**Validación exhaustiva:**

```php
$request->validate([
    'email' => [
        'required',
        'email:rfc,dns',  // Verifica formato Y que dominio existe
        'max:255',
        'unique:users,email',
    ],
    'password' => [
        'required',
        'min:8',
        'confirmed',
        'regex:/[a-z]/',      // Al menos una minúscula
        'regex:/[A-Z]/',      // Al menos una mayúscula
        'regex:/[0-9]/',      // Al menos un número
    ],
    'firstname' => [
        'required',
        'string',
        'max:100',
        'regex:/^[\pL\s\-]+$/u',  // Solo letras, espacios, guiones
    ],
    'unit_type_id' => [
        'required',
        'exists:unit_types,id',  // Verifica que exista
    ],
]);
```

### 10.10. Rate Limiting

**Protección contra brute-force en login:**

```php
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:login');
```

**Configuración:**

```php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->email);
});
```

**Efecto:** Máximo 5 intentos de login por email por minuto.

### 10.11. HTTPS en Producción

**Forzar HTTPS:**

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }
}
```

**Nginx con certificado SSL (Let's Encrypt):**

```nginx
server {
    listen 443 ssl http2;
    ssl_certificate /etc/letsencrypt/live/mtav.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/mtav.example.com/privkey.pem;

    # Configuración SSL segura
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
}

server {
    listen 80;
    return 301 https://$host$request_uri;  # Redirect HTTP → HTTPS
}
```

### 10.12. Gestión de Secrets

**Variables de entorno en `.env`:**

```env
APP_KEY=base64:RANDOM_32_BYTE_STRING
DB_PASSWORD=strong_password_here
MAIL_PASSWORD=smtp_password_here
```

**`.env` NUNCA se commitea a Git:**

```gitignore
.env
.env.backup
.env.production
```

**Distribución segura:**
- Desarrollo: Cada dev tiene su propio `.env` local
- Producción: `.env` gestionado por administrador del servidor
- CI/CD: Secrets inyectados por sistema de CI (GitHub Secrets, etc.)

### 10.13. Auditoría (Futuro)

**Planificado: Modelo `Log`**

```php
class Log extends Model
{
    protected $fillable = [
        'user_id',
        'action',      // 'created', 'updated', 'deleted'
        'model_type',  // 'Family', 'Unit', 'Preference'
        'model_id',
        'changes',     // JSON de cambios
        'ip_address',
        'user_agent',
    ];
}
```

**Uso:**

```php
// Después de cada acción crítica
Log::create([
    'user_id' => auth()->id(),
    'action' => 'executed_lottery',
    'model_type' => 'Project',
    'model_id' => $project->id,
    'changes' => json_encode($lotteryResults),
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

**Beneficio:** Trazabilidad completa de acciones sensibles.

---

## Sección 11: Auditabilidad y Trazabilidad

### 11.1. Principio: Transparencia Total

En cooperativas de vivienda, la confianza es fundamental. MTAV debe proporcionar **trazabilidad completa** de todas las acciones críticas.

### 11.2. Timestamps Automáticos

**Laravel proporciona timestamps en todos los modelos:**

```php
class Family extends Model
{
    // Automáticamente gestiona:
    // - created_at: Cuándo se creó el registro
    // - updated_at: Cuándo se modificó por última vez
}
```

**Query por fecha:**

```php
// Familias creadas en los últimos 7 días
$recentFamilies = Family::where('created_at', '>', now()->subDays(7))->get();
```

### 11.3. Soft Deletes

**Eliminación suave (no destruye datos):**

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    use SoftDeletes;
}
```

**Efecto:**

```php
$family->delete();  // No elimina de la DB, solo marca deleted_at = now()

Family::all();  // No incluye eliminados
Family::withTrashed()->get();  // Incluye eliminados
Family::onlyTrashed()->get();  // Solo eliminados

$family->restore();  // "Revive" un registro eliminado
$family->forceDelete();  // Eliminación permanente (raro)
```

**Beneficio:** Nada se pierde realmente, se puede auditar qué se eliminó y cuándo.

### 11.4. Immutable Lottery Results

**Regla crítica:** Una vez ejecutado el sorteo, los resultados NO se pueden modificar.

**Implementación:**

```php
class Project extends Model
{
    public function executeLottery(): LotteryResult
    {
        // Verificar que no se haya ejecutado antes
        if ($this->lottery_executed_at) {
            throw new LotteryAlreadyExecutedException();
        }

        // Ejecutar sorteo
        $results = app(LotteryService::class)->execute($this);

        // Guardar resultados
        $this->update([
            'lottery_executed_at' => now(),
            'lottery_results' => json_encode($results),
        ]);

        return $results;
    }

    public function canExecuteLottery(): bool
    {
        return is_null($this->lottery_executed_at);
    }
}
```

**Política:**

```php
public function executeLottery(User $user, Project $project): bool
{
    return $user->isAdmin()
        && $user->project_id === $project->project_id
        && $project->canExecuteLottery();
}
```

### 11.5. Log Model (Futuro)

**Estructura propuesta:**

```php
Schema::create('logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained();
    $table->string('action');  // 'created', 'updated', 'deleted', 'executed_lottery'
    $table->string('model_type');  // 'Family', 'Unit', 'Preference'
    $table->unsignedBigInteger('model_id')->nullable();
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->ipAddress('ip_address');
    $table->string('user_agent')->nullable();
    $table->timestamp('created_at');
});
```

**Logging automático con Observers:**

```php
namespace App\Observers;

class FamilyObserver
{
    public function created(Family $family): void
    {
        Log::create([
            'user_id' => auth()->id(),
            'action' => 'created',
            'model_type' => 'Family',
            'model_id' => $family->id,
            'new_values' => $family->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function updated(Family $family): void
    {
        Log::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'model_type' => 'Family',
            'model_id' => $family->id,
            'old_values' => $family->getOriginal(),
            'new_values' => $family->getChanges(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### 11.6. Consulta de Auditoría

**Interface para admins:**

```vue
<template>
    <div>
        <h2>Historial de Cambios</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Entidad</th>
                    <th>Cambios</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="log in logs" :key="log.id">
                    <td>{{ log.created_at }}</td>
                    <td>{{ log.user.fullname }}</td>
                    <td>{{ log.action }}</td>
                    <td>{{ log.model_type }} #{{ log.model_id }}</td>
                    <td>
                        <details>
                            <summary>Ver detalles</summary>
                            <pre>{{ log.new_values }}</pre>
                        </details>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
```

---

## Sección 12: Flexibilidad y Extensibilidad

### 12.1. Arquitectura de Plugins: Lottery Service

**Problema:** El algoritmo de optimización del sorteo puede evolucionar.

**Solución:** Strategy Pattern con dependency injection.

#### Interface

```php
namespace App\Contracts;

interface LotteryService
{
    /**
     * Load family preferences into the service
     */
    public function load(array $preferences): void;

    /**
     * Execute lottery and return assignments
     *
     * @return array<int, int>  family_id => unit_id
     */
    public function execute(): array;
}
```

#### Implementaciones

**DummyLotteryService (desarrollo/demo):**

```php
namespace App\Services\Lottery;

use App\Contracts\LotteryService;

class DummyLotteryService implements LotteryService
{
    private array $preferences;

    public function load(array $preferences): void
    {
        $this->preferences = $preferences;
    }

    public function execute(): array
    {
        // Asignación aleatoria (no optimiza)
        $families = array_keys($this->preferences);
        $units = array_unique(array_merge(...array_values($this->preferences)));

        shuffle($units);

        return array_combine($families, $units);
    }
}
```

**ProductionLotteryService (futuro):**

```php
namespace App\Services\Lottery;

use App\Contracts\LotteryService;
use Illuminate\Support\Facades\Http;

class ProductionLotteryService implements LotteryService
{
    private array $preferences;

    public function load(array $preferences): void
    {
        $this->preferences = $preferences;
    }

    public function execute(): array
    {
        // Llamar a API externa de optimización
        $response = Http::post('https://optimization-api.example.com/lottery', [
            'preferences' => $this->preferences,
        ]);

        return $response->json('assignments');
    }
}
```

#### Binding en Service Provider

```php
namespace App\Providers;

use App\Contracts\LotteryService;
use App\Services\Lottery\DummyLotteryService;
use App\Services\Lottery\ProductionLotteryService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind según entorno
        $this->app->bind(LotteryService::class, function () {
            if (app()->environment('production')) {
                return new ProductionLotteryService();
            }

            return new DummyLotteryService();
        });
    }
}
```

#### Uso

```php
class ProjectController extends Controller
{
    public function executeLottery(Project $project, LotteryService $lottery)
    {
        $this->authorize('executeLottery', $project);

        // Cargar preferencias
        $preferences = $project->families()->with('preferences')->get()
            ->mapWithKeys(function ($family) {
                return [$family->id => $family->preferences->pluck('unit_id')->toArray()];
            })->toArray();

        // Ejecutar (inyección de dependencia, no sabemos cuál implementación)
        $lottery->load($preferences);
        $assignments = $lottery->execute();

        // Guardar resultados
        foreach ($assignments as $familyId => $unitId) {
            Family::find($familyId)->update(['unit_id' => $unitId]);
        }

        $project->update(['lottery_executed_at' => now()]);

        return to_route('projects.show', $project);
    }
}
```

**Beneficio:** Cambiar algoritmo = cambiar una línea en `AppServiceProvider`, el resto del código no cambia.

### 12.2. Tiny Components (Vue)

**Principio:** Componentes pequeños, reutilizables, single-purpose.

**Ejemplo: Button Component**

```vue
<script setup lang="ts">
interface Props {
    variant?: 'primary' | 'secondary' | 'danger';
    size?: 'sm' | 'md' | 'lg';
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    disabled: false,
});

const classes = computed(() => {
    const base = 'rounded font-semibold transition focus:outline-none focus:ring-2';
    const sizes = {
        sm: 'px-3 py-1 text-sm',
        md: 'px-4 py-2 text-base',
        lg: 'px-6 py-3 text-lg',
    };
    const variants = {
        primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        secondary: 'bg-gray-200 text-gray-800 hover:bg-gray-300 focus:ring-gray-400',
        danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    };

    return `${base} ${sizes[props.size]} ${variants[props.variant]}`;
});
</script>

<template>
    <button :class="classes" :disabled="disabled">
        <slot />
    </button>
</template>
```

**Uso:**

```vue
<AppButton variant="primary" size="lg" @click="submit">
    Guardar Cambios
</AppButton>

<AppButton variant="danger" size="sm" @click="confirmDelete">
    Eliminar
</AppButton>
```

**Beneficio:** Consistencia visual, fácil cambiar estilos globalmente.

### 12.3. Inyección de Dependencias (Laravel IoC)

**Laravel Service Container** gestiona dependencias automáticamente.

**Sin IoC (manual):**

```php
class FamilyController extends Controller
{
    private FamilyRepository $repository;

    public function __construct()
    {
        $this->repository = new FamilyRepository(new Database());
    }
}
```

**Con IoC (automático):**

```php
class FamilyController extends Controller
{
    public function __construct(
        private FamilyRepository $repository  // Laravel lo inyecta automáticamente
    ) {}

    public function index()
    {
        return $this->repository->all();
    }
}
```

**Binding de interfaces:**

```php
// AppServiceProvider
$this->app->bind(LotteryService::class, DummyLotteryService::class);

// Uso en controller
public function execute(LotteryService $lottery)
{
    // $lottery es una instancia de DummyLotteryService
}
```

**Beneficio:** Fácil cambiar implementaciones sin tocar código que las usa.

---

## Sección 13: Legibilidad - Código como Prosa

### 13.1. Principio: El Código Debe Leerse Como Inglés

**Objetivo:** Cualquier desarrollador debe entender qué hace el código sin comentarios.

**Malo:**

```php
public function x($u, $p) {
    return DB::table('f')->where('p', $p)->where('u', $u)->first();
}
```

**Bueno:**

```php
public function findFamilyByUserAndProject(User $user, Project $project): ?Family
{
    return Family::where('project_id', $project->id)
        ->whereHas('members', fn($q) => $q->where('user_id', $user->id))
        ->first();
}
```

### 13.2. Named Parameters (PHP 8)

**Antes:**

```php
sendEmail('user@example.com', 'Subject', 'Body', true, false, null, 3);
```

**Ahora:**

```php
sendEmail(
    to: 'user@example.com',
    subject: 'Subject',
    body: 'Body',
    html: true,
    queue: false,
    attachments: null,
    retries: 3,
);
```

### 13.3. Nombres Descriptivos

**Variables:**

```php
// ❌ Malo
$d = now()->subDays(7);
$r = Family::where('created_at', '>', $d)->get();

// ✅ Bueno
$oneWeekAgo = now()->subDays(7);
$recentFamilies = Family::where('created_at', '>', $oneWeekAgo)->get();
```

**Métodos:**

```php
// ❌ Malo
public function check($u) { return $u->role === 'admin'; }

// ✅ Bueno
public function isAdmin(User $user): bool { return $user->role === 'admin'; }
```

### 13.4. Early Returns

**Antes:**

```php
public function update(Request $request, Family $family)
{
    if ($this->authorize('update', $family)) {
        if ($request->has('name')) {
            if (strlen($request->name) > 3) {
                $family->update(['name' => $request->name']);
                return to_route('families.show', $family);
            }
        }
    }
}
```

**Ahora:**

```php
public function update(Request $request, Family $family)
{
    $this->authorize('update', $family);

    if (! $request->has('name')) {
        return back()->withErrors(['name' => 'Name is required']);
    }

    if (strlen($request->name) <= 3) {
        return back()->withErrors(['name' => 'Name must be longer than 3 characters']);
    }

    $family->update(['name' => $request->name]);

    return to_route('families.show', $family);
}
```

### 13.5. Extract Methods

**Antes:**

```php
public function calculateSatisfaction(Project $project)
{
    $total = 0;
    $count = 0;

    foreach ($project->families as $family) {
        if ($family->unit_id) {
            $prefs = $family->preferences()->orderBy('ranking')->get();
            $assignedRank = 0;

            foreach ($prefs as $index => $pref) {
                if ($pref->unit_id === $family->unit_id) {
                    $assignedRank = $index + 1;
                    break;
                }
            }

            $score = match ($assignedRank) {
                1 => 100,
                2 => 80,
                3 => 60,
                4 => 40,
                default => 20,
            };

            $total += $score;
            $count++;
        }
    }

    return $count > 0 ? $total / $count : 0;
}
```

**Ahora:**

```php
public function calculateSatisfaction(Project $project): float
{
    $scores = $project->families
        ->filter->hasAssignedUnit()
        ->map->satisfactionScore();

    return $scores->average() ?? 0;
}

// En modelo Family
public function hasAssignedUnit(): bool
{
    return ! is_null($this->unit_id);
}

public function satisfactionScore(): int
{
    $assignedRank = $this->preferences()
        ->orderBy('ranking')
        ->pluck('unit_id')
        ->search($this->unit_id) + 1;

    return match ($assignedRank) {
        1 => 100,
        2 => 80,
        3 => 60,
        4 => 40,
        default => 20,
    };
}
```

---

## Sección 14: Localización - Soporte Multiidioma

### 14.1. Arquitectura de Traducciones

**Laravel proporciona sistema de localización integrado.**

**Estructura:**

```
lang/
  en.json           # Traducciones inglés (vacío por defecto)
  es_UY.json        # Traducciones español uruguayo
  en/
    validation.php  # Mensajes de validación inglés
  es_UY/
    validation.php  # Mensajes de validación español
```

### 14.2. Traducción de Strings Inline

**En código PHP:**

```php
return back()->with('success', __('Family created successfully'));
```

**En archivos JSON:**

```json
{
  "Family created successfully": "Familia creada exitosamente"
}
```

**En Vue:**

```vue
<template>
    <h1>{{ $t('Welcome to MTAV') }}</h1>
</template>
```

### 14.3. Mensajes de Validación

**lang/es_UY/validation.php:**

```php
return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'attributes' => [
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'firstname' => 'nombre',
        'lastname' => 'apellido',
    ],
];
```

**Resultado:**

```
Input: { email: '' }
Error EN: "The email field is required."
Error ES: "El campo correo electrónico es obligatorio."
```

### 14.4. Cambio de Idioma

**Middleware de localización:**

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->user()?->locale ?? config('app.locale');

        app()->setLocale($locale);

        return $next($request);
    }
}
```

**Selector de idioma (UI):**

```vue
<script setup lang="ts">
import { router } from '@inertiajs/vue3';

function changeLocale(locale: string) {
    router.post(route('locale.update'), { locale });
}
</script>

<template>
    <div>
        <button @click="changeLocale('en')">English</button>
        <button @click="changeLocale('es_UY')">Español</button>
    </div>
</template>
```

**Controlador:**

```php
public function update(Request $request)
{
    $request->validate(['locale' => 'required|in:en,es_UY']);

    $request->user()->update(['locale' => $request->locale]);

    return back();
}
```

### 14.5. Fechas y Formatos

**Carbon con localización:**

```php
$date = now()->locale('es_UY')->isoFormat('dddd D [de] MMMM [de] YYYY');
// "lunes 5 de noviembre de 2025"
```

**Números:**

```php
// Español: separador de miles = punto, decimal = coma
number_format(1234.56, 2, ',', '.');  // "1.234,56"

// Inglés: separador de miles = coma, decimal = punto
number_format(1234.56, 2, '.', ',');  // "1,234.56"
```

### 14.6. Contenido Dinámico

**Blade/Vue con placeholders:**

```php
__('You have :count new messages', ['count' => 5]);
// EN: "You have 5 new messages"
// ES: "Tienes 5 mensajes nuevos"
```

**Pluralización:**

```json
{
  "You have :count new message|You have :count new messages": "Tienes :count mensaje nuevo|Tienes :count mensajes nuevos"
}
```

---

**FIN DE PARTE 2**

