# PARTE 2: ANÁLISIS TÉCNICO DETALLADO

Esta sección profundiza en los aspectos técnicos del proyecto, destinada a lectores con formación en ingeniería de software o informática que deseen comprender las decisiones arquitectónicas, patrones implementados y justificaciones técnicas.

---

## Sección 6: Docker - Infraestructura de Contenedores

### 6.1. Motivación: ¿Por Qué Docker?

El desarrollo de aplicaciones web modernas enfrenta un desafío fundamental: **la complejidad del entorno de ejecución**.

**Problema sin Docker:**

Una aplicación Laravel típica requiere:
- PHP (versión específica, ej: 8.3)
- Extensions de PHP (pdo_pgsql, redis, gd, etc.)
- Composer (gestor de dependencias PHP)
- Node.js (para compilar assets)
- npm/yarn (gestor de dependencias JavaScript)
- PostgreSQL (base de datos)
- Nginx o Apache (servidor web)
- Redis (opcional, para caché y colas)

**Desafíos:**
1. **Instalación manual compleja:** Cada desarrollador debe instalar y configurar todo
2. **Versiones inconsistentes:** Un dev tiene PHP 8.2, otro 8.3 → comportamiento diferente
3. **Conflictos entre proyectos:** Proyecto A necesita PostgreSQL 14, Proyecto B necesita PostgreSQL 16
4. **"Funciona en mi máquina":** Código funciona localmente pero falla en producción
5. **Onboarding lento:** Nuevo desarrollador necesita días para configurar el entorno

**Solución con Docker:**

Docker empaqueta la aplicación y todas sus dependencias en **contenedores** autocontenidos:
- Contenedor = Entorno aislado con todo lo necesario
- Imagen = Plantilla de contenedor (receta)
- Dockerfile = Instrucciones para construir la imagen

**Beneficios logrados en MTAV:**
- ✅ Cualquier desarrollador ejecuta `./mtav up` y tiene el entorno completo funcionando en minutos
- ✅ Mismo entorno en desarrollo, testing y producción
- ✅ No hay conflictos con otros proyectos
- ✅ Fácil actualizar versiones (cambiar un número en `Dockerfile`)

### 6.2. Arquitectura Docker de MTAV

MTAV usa **Docker Compose** para orquestar múltiples contenedores que trabajan juntos.

#### Contenedores Definidos

**1. Contenedor `php` (Servicio Principal)**

```dockerfile
FROM php:8.3-fpm-alpine

# Instalar extensiones de PHP
RUN docker-php-ext-install pdo_pgsql opcache

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
```

**Responsabilidades:**
- Ejecutar código PHP (Laravel)
- Procesar requests vía PHP-FPM
- Ejecutar comandos Artisan
- Correr tests

**2. Contenedor `node` (Compilación de Assets)**

```dockerfile
FROM node:20-alpine

WORKDIR /var/www/html
```

**Responsabilidades:**
- Instalar dependencias npm
- Compilar Vue.js con Vite
- Generar CSS con TailwindCSS
- Hot-reload en desarrollo (Vite dev server)

**3. Contenedor `postgres` (Base de Datos)**

```dockerfile
FROM postgres:16-alpine
```

**Responsabilidades:**
- Almacenar datos (familias, miembros, unidades, preferencias)
- Ejecutar queries SQL
- Garantizar integridad referencial

**4. Contenedor `nginx` (Servidor Web)**

```dockerfile
FROM nginx:alpine

COPY .docker/nginx/default.conf /etc/nginx/conf.d/default.conf
```

**Responsabilidades:**
- Servir archivos estáticos (CSS, JS, imágenes)
- Proxy inverso hacia PHP-FPM
- Manejar HTTPS (en producción)

**5. Contenedor `redis` (Caché y Colas) [Opcional]**

```dockerfile
FROM redis:7-alpine
```

**Responsabilidades:**
- Caché de sesiones
- Cola de trabajos (emails, notificaciones)

#### Composición con Docker Compose

Archivo `.docker/compose.yml`:

```yaml
services:
  php:
    build:
      context: .
      dockerfile: .docker/Dockerfile.php
    volumes:
      - ./:/var/www/html
    environment:
      DB_HOST: postgres
      DB_DATABASE: mtav
      DB_USERNAME: mtav
      DB_PASSWORD: secret
    depends_on:
      - postgres

  node:
    build:
      context: .
      dockerfile: .docker/Dockerfile.node
    volumes:
      - ./:/var/www/html
    ports:
      - "5173:5173"  # Vite dev server
    command: npm run dev

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: mtav
      POSTGRES_USER: mtav
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
      - ./.docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php

volumes:
  postgres_data:
```

**Conceptos clave:**
- `volumes`: Mapean carpetas del host dentro del contenedor (sincronización de código)
- `depends_on`: Orden de inicio (nginx espera a php, php espera a postgres)
- `environment`: Variables de entorno
- `ports`: Exponer puertos al host (80 para web, 5173 para Vite)

### 6.3. El Wrapper `./mtav`

Para simplificar el uso de Docker, MTAV incluye un script wrapper `./mtav` que encapsula comandos comunes.

**Problema que resuelve:**

Comandos Docker son verbosos:
```bash
docker compose -p dev exec php php artisan migrate
docker compose -p dev exec php composer install
docker compose -p dev exec node npm run build
```

**Solución:**

```bash
./mtav artisan migrate
./mtav composer install
./mtav npm build
```

**Implementación del wrapper:**

```bash
#!/usr/bin/env bash

COMPOSE="docker compose -p dev -f .docker/compose.yml"

case "$1" in
    up)
        $COMPOSE up -d
        ;;
    down)
        $COMPOSE down
        ;;
    artisan)
        shift
        $COMPOSE exec php php artisan "$@"
        ;;
    composer)
        shift
        $COMPOSE exec php composer "$@"
        ;;
    npm)
        shift
        $COMPOSE exec node npm "$@"
        ;;
    shell)
        $COMPOSE exec "$2" sh
        ;;
    test)
        shift
        $COMPOSE exec php php artisan test "$@"
        ;;
    *)
        echo "Comando no reconocido: $1"
        ;;
esac
```

**Beneficios:**
- Comandos cortos y memorables
- Valida que los scripts funcionen (si falta algo, lo agrego al wrapper)
- Documentación implícita (los comandos disponibles son los que necesitas)
- Consistencia (siempre usa `-p dev` para el nombre del proyecto)

### 6.4. Flujos de Trabajo Docker

#### Inicio del Entorno (Primer Uso)

```bash
# 1. Clonar repositorio
git clone https://github.com/user/mtav.git
cd mtav

# 2. Construir imágenes
./mtav build

# 3. Iniciar contenedores
./mtav up

# 4. Instalar dependencias PHP
./mtav composer install

# 5. Instalar dependencias JavaScript
./mtav npm install

# 6. Configurar .env
cp .env.example .env
./mtav artisan key:generate

# 7. Ejecutar migraciones
./mtav artisan migrate

# 8. Cargar fixture (datos de prueba)
./mtav artisan db:seed

# 9. Compilar assets
./mtav npm run build

# 10. Listo, visitar http://localhost
```

Total: ~10-15 minutos (dependiendo de la velocidad de descarga de imágenes).

#### Trabajo Diario

```bash
# Iniciar entorno
./mtav up

# Desarrollo frontend (hot-reload)
./mtav npm run dev

# Ejecutar tests
./mtav test

# Ejecutar comando Artisan
./mtav artisan tinker

# Acceder al shell del contenedor
./mtav shell php
```

#### Reconstrucción (Cambios en Dockerfile)

```bash
# Reconstruir imagen específica
./mtav rebuild php

# Reconstruir todo
./mtav rebuild
```

### 6.5. Volúmenes y Persistencia

**Problema:** Los contenedores son efímeros (se pueden destruir y recrear). ¿Cómo persisten los datos?

**Solución: Volúmenes**

**1. Volumen de Código (Bind Mount)**

```yaml
volumes:
  - ./:/var/www/html
```

- Mapea la carpeta del proyecto (`./`) dentro del contenedor (`/var/www/html`)
- Cambios en el código local se reflejan inmediatamente en el contenedor
- No se pierde nada si el contenedor se elimina

**2. Volumen de Base de Datos (Named Volume)**

```yaml
volumes:
  postgres_data:

services:
  postgres:
    volumes:
      - postgres_data:/var/lib/postgresql/data
```

- `postgres_data` es un volumen gestionado por Docker
- Persiste datos de la base de datos incluso si el contenedor se destruye
- Para eliminar datos: `docker volume rm dev_postgres_data`

### 6.6. Networking

Docker Compose crea automáticamente una red para los contenedores del proyecto.

**Comunicación interna:**
- `php` puede conectarse a `postgres` usando el nombre del servicio: `DB_HOST=postgres`
- `nginx` puede hacer proxy a `php` con `fastcgi_pass php:9000`

**Conceptos:**
- Todos los contenedores en el mismo compose comparten una red virtual
- Usan DNS interno (nombres de servicios se resuelven a IPs internas)
- Aislamiento: Contenedores de otros proyectos no pueden acceder

**Exponer al host:**

```yaml
ports:
  - "80:80"      # Puerto 80 del contenedor → puerto 80 del host
  - "5432:5432"  # PostgreSQL accesible desde el host
```

### 6.7. Diferencias Desarrollo vs Producción

**Desarrollo (Docker Compose):**
- Volúmenes sincronizados (código editable en tiempo real)
- Hot-reload activado (Vite)
- Debuggers habilitados
- Logs verbosos
- No HTTPS (HTTP simple en localhost)

**Producción (Docker en servidor):**
- Código copiado dentro de la imagen (no volúmenes sincronizados)
- Assets precompilados (no Vite dev server)
- Optimizaciones activadas (OPcache, minificación)
- Logs estructurados (JSON)
- HTTPS obligatorio (Nginx con certificado SSL)

**Dockerfile de producción:**

```dockerfile
# Etapa 1: Build de assets
FROM node:20-alpine AS node_builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Etapa 2: Imagen PHP final
FROM php:8.3-fpm-alpine
WORKDIR /var/www/html

# Copiar código
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --optimize-autoloader

# Copiar assets compilados
COPY --from=node_builder /app/public/build ./public/build

# Optimizaciones Laravel
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
```

**Multi-stage build:**
- Etapa 1: Compila assets con Node.js
- Etapa 2: Copia solo los assets compilados (no incluye Node.js en imagen final)
- Resultado: Imagen más pequeña y rápida

### 6.8. Herramientas Docker Específicas de MTAV

#### Scripts en `.docker/scripts/`

**artisan.sh:**
```bash
#!/usr/bin/env bash
docker compose -p dev exec php php artisan "$@"
```

**composer.sh:**
```bash
#!/usr/bin/env bash
docker compose -p dev exec php composer "$@"
```

**npm.sh:**
```bash
#!/usr/bin/env bash
docker compose -p dev exec node npm "$@"
```

**test.sh:**
```bash
#!/usr/bin/env bash
docker compose -p dev exec php php artisan test "$@"
```

Estos scripts son llamados por el wrapper `./mtav`.

#### Configuración Nginx

`.docker/nginx/default.conf`:

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Explicación:**
- `root /var/www/html/public`: Carpeta raíz (Laravel)
- `try_files`: Intenta servir archivo estático, si no existe, pasa a `index.php`
- `fastcgi_pass php:9000`: Envía requests PHP al contenedor PHP-FPM
- `deny all` para archivos ocultos (`.env`, `.git`)

### 6.9. Debugging en Docker

#### Ver logs de un contenedor

```bash
docker compose -p dev logs php
docker compose -p dev logs postgres
docker compose -p dev logs -f nginx  # follow mode (tiempo real)
```

#### Inspeccionar contenedor en ejecución

```bash
./mtav shell php
# Dentro del contenedor:
php -v
php artisan --version
cat /var/www/html/.env
```

#### Ejecutar comandos one-off

```bash
docker compose -p dev exec php php -i  # phpinfo en CLI
docker compose -p dev exec postgres psql -U mtav -d mtav  # Acceder a PostgreSQL
```

### 6.10. Ventajas Logradas y Lecciones Aprendidas

**Ventajas:**
- ✅ Onboarding de nuevos desarrolladores: 15 minutos
- ✅ Consistencia total: mismo entorno en todos lados
- ✅ Aislamiento: No interfiere con otros proyectos
- ✅ Documentación implícita: `docker-compose.yml` documenta el stack
- ✅ Fácil actualizar versiones: cambiar `php:8.3` a `php:8.4` y reconstruir

**Desafíos:**
- ❌ Curva de aprendizaje inicial para desarrolladores sin experiencia en Docker
- ❌ Performance en Windows/macOS puede ser lenta (virtualización)
- ❌ Debugging es ligeramente más complejo (capas de abstracción)

**Lecciones:**
1. **Wrapper scripts son esenciales:** Sin `./mtav`, Docker es incómodo
2. **Named volumes para datos críticos:** Previene pérdida de datos accidental
3. **Multi-stage builds para producción:** Imágenes pequeñas = deploy rápido
4. **Documentar cada servicio:** Comentarios en `docker-compose.yml` ayudan

**Decisión validada:** Docker fue absolutamente la elección correcta para este proyecto. La inversión inicial en configuración se pagó múltiples veces en productividad y confiabilidad.

---

## Sección 7: Laravel, Vue.js e Inertia.js

### 7.1. Laravel: El Backend Framework

#### Arquitectura MVC en Laravel

Laravel implementa el patrón **Model-View-Controller (MVC)** con adaptaciones modernas.

**Model (Modelo):**

Representa entidades de negocio y su lógica de persistencia.

Ejemplo: `app/Models/Family.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = ['name', 'unit_type_id', 'project_id'];

    // Relación: Una familia pertenece a un proyecto
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Relación: Una familia tiene muchos miembros
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    // Relación: Una familia tiene muchas preferencias
    public function preferences(): HasMany
    {
        return $this->hasMany(FamilyPreference::class);
    }

    // Lógica de negocio
    public function hasCompletedPreferences(): bool
    {
        return $this->preferences()->exists();
    }
}
```

**Eloquent ORM:**

Laravel usa **Eloquent**, un ORM (Object-Relational Mapper) que permite trabajar con la base de datos usando objetos PHP en lugar de SQL crudo.

Ejemplo de query:

```php
// SQL tradicional
$families = DB::select('SELECT * FROM families WHERE project_id = ?', [1]);

// Eloquent
$families = Family::where('project_id', 1)->get();

// Con relaciones (Eager Loading)
$families = Family::with('members', 'preferences')->where('project_id', 1)->get();
```

**Benefits:**
- Legibilidad: El código se lee como inglés
- Seguridad: Previene SQL injection automáticamente
- Relaciones: Navegar entre entidades es trivial
- Scopes: Lógica de query reutilizable

**View (Vista):**

En MTAV, las "vistas" son componentes Vue.js (ver sección Vue más adelante). Laravel no renderiza HTML directamente, sino que pasa datos a Inertia, que los pasa a Vue.

**Controller (Controlador):**

Orquesta la lógica de la aplicación: recibe requests, consulta modelos, devuelve respuestas.

Ejemplo: `app/Http/Controllers/FamilyController.php`

```php
namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    public function index(): Response
    {
        // Obtener familias del proyecto del usuario autenticado
        $families = Family::with('members')
            ->where('project_id', auth()->user()->project_id)
            ->get();

        // Devolver a Inertia (que pasará a Vue)
        return Inertia::render('Families/Index', [
            'families' => $families,
        ]);
    }

    public function store(Request $request)
    {
        // Validar input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit_type_id' => 'required|exists:unit_types,id',
        ]);

        // Crear familia
        $family = Family::create([
            ...$validated,
            'project_id' => auth()->user()->project_id,
        ]);

        // Redireccionar con mensaje
        return redirect()->route('families.index')
            ->with('success', 'Familia creada exitosamente');
    }
}
```

**Flujo:**
1. Request llega a ruta definida en `routes/web.php`
2. Ruta apunta a método del controlador
3. Controlador ejecuta lógica
4. Controlador devuelve respuesta (Inertia response)
5. Inertia convierte a JSON y lo envía al frontend
6. Vue renderiza componente con los datos

#### Validación de Datos

Laravel proporciona un sistema de validación robusto.

**Validación en Controlador:**

```php
$request->validate([
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed',
    'firstname' => 'required|string|max:100',
]);
```

**Reglas disponibles:**
- `required`: Campo obligatorio
- `email`: Debe ser email válido
- `unique:table,column`: Valor único en la tabla
- `min:X`, `max:X`: Longitud mínima/máxima
- `confirmed`: Debe coincidir con `{field}_confirmation`
- `exists:table,column`: Debe existir en la tabla

**Form Requests (para validaciones complejas):**

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFamily extends FormRequest
{
    public function authorize(): bool
    {
        // ¿El usuario puede crear familias?
        return $this->user()->can('create', Family::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'unit_type_id' => 'required|exists:unit_types,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la familia es obligatorio.',
            'unit_type_id.exists' => 'El tipo de unidad seleccionado no es válido.',
        ];
    }
}
```

Uso en controlador:

```php
public function store(StoreFamily $request)
{
    // $request ya está validado y autorizado
    $family = Family::create($request->validated());
    // ...
}
```

#### Autorización con Policies

Laravel Policies definen quién puede hacer qué con cada modelo.

Ejemplo: `app/Policies/FamilyPolicy.php`

```php
namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    // ¿Puede el usuario ver esta familia?
    public function view(User $user, Family $family): bool
    {
        // Solo si pertenecen al mismo proyecto
        return $user->project_id === $family->project_id;
    }

    // ¿Puede el usuario actualizar esta familia?
    public function update(User $user, Family $family): bool
    {
        // Solo administradores del proyecto
        return $user->isAdmin() && $user->project_id === $family->project_id;
    }

    // ¿Puede el usuario crear familias?
    public function create(User $user): bool
    {
        // Solo administradores
        return $user->isAdmin();
    }

    // ¿Puede el usuario eliminar esta familia?
    public function delete(User $user, Family $family): bool
    {
        // Solo administradores del mismo proyecto
        return $user->isAdmin() && $user->project_id === $family->project_id;
    }
}
```

**Uso en controladores:**

```php
public function update(Request $request, Family $family)
{
    // Lanza excepción 403 si no autorizado
    $this->authorize('update', $family);

    $family->update($request->validated());
    return redirect()->route('families.show', $family);
}
```

**Uso en Blade/Vue:**

```php
@can('update', $family)
    <a href="{{ route('families.edit', $family) }}">Editar</a>
@endcan
```

#### Middleware

Los middlewares son "capas" que procesan requests antes de llegar al controlador.

**Middleware de Autenticación:**

```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');  // Solo usuarios autenticados
```

**Middleware de Autorización:**

```php
Route::resource('families', FamilyController::class)
    ->middleware('can:viewAny,App\Models\Family');
```

**Middleware Personalizado:**

Ejemplo: Verificar que el usuario pertenece al proyecto correcto.

```php
namespace App\Http\Middleware;

class EnsureUserBelongsToProject
{
    public function handle($request, $next)
    {
        $project = $request->route('project');

        if (auth()->user()->project_id !== $project->id) {
            abort(403, 'No tienes acceso a este proyecto');
        }

        return $next($request);
    }
}
```

#### Eloquent Global Scopes

Los Global Scopes aplican automáticamente restricciones a todas las queries de un modelo.

**Uso en MTAV: Project Scope**

```php
namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProjectScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Filtrar automáticamente por project_id del usuario autenticado
        if (auth()->check() && ! auth()->user()->isSuperadmin()) {
            $builder->where('project_id', auth()->user()->project_id);
        }
    }
}
```

**Aplicar al modelo:**

```php
class Family extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ProjectScope());
    }
}
```

**Efecto:**

```php
// Sin global scope, necesitarías:
$families = Family::where('project_id', auth()->user()->project_id)->get();

// Con global scope, esto es automático:
$families = Family::all();  // Solo familias del proyecto del usuario
```

**Beneficio:** Previene errores de seguridad (olvidar filtrar por proyecto).

#### Migraciones de Base de Datos

Las migraciones son "control de versiones" para la base de datos.

Ejemplo: `database/migrations/2024_01_01_create_families_table.php`

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('unit_type_id')->constrained()->onDelete('restrict');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
```

**Ejecutar migraciones:**

```bash
./mtav artisan migrate
```

**Ventajas:**
- Reproducible: Mismo esquema en dev, testing, producción
- Versionado: Cada cambio es un commit
- Rollback: `migrate:rollback` deshace cambios

### 7.2. Vue.js: El Frontend Framework

#### Componentes Vue en MTAV

Vue.js organiza la UI en componentes reutilizables.

**Ejemplo: Componente de Tarjeta de Familia**

`resources/js/Components/FamilyCard.vue`

```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    family: {
        id: number;
        name: string;
        members_count: number;
        project: {
            name: string;
        };
    };
}

defineProps<Props>();
</script>

<template>
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-xl font-semibold">{{ family.name }}</h3>
        <p class="text-gray-600">{{ family.members_count }} miembros</p>
        <p class="text-sm text-gray-500">{{ family.project.name }}</p>
        <Link
            :href="route('families.show', family.id)"
            class="mt-4 inline-block text-blue-600 hover:text-blue-800"
        >
            Ver detalles
        </Link>
    </div>
</template>
```

**Uso del componente:**

```vue
<script setup lang="ts">
import FamilyCard from '@/Components/FamilyCard.vue';

interface Props {
    families: Array<{
        id: number;
        name: string;
        members_count: number;
        project: { name: string };
    }>;
}

defineProps<Props>();
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <FamilyCard v-for="family in families" :key="family.id" :family="family" />
    </div>
</template>
```

#### Reactividad en Vue

Vue hace que la UI se actualice automáticamente cuando los datos cambian.

```vue
<script setup lang="ts">
import { ref } from 'vue';

const count = ref(0);

function increment() {
    count.value++;  // UI se actualiza automáticamente
}
</script>

<template>
    <div>
        <p>Contador: {{ count }}</p>
        <button @click="increment">Incrementar</button>
    </div>
</template>
```

**`ref` vs `reactive`:**

```typescript
// ref: para valores primitivos
const count = ref(0);
count.value = 5;

// reactive: para objetos
const state = reactive({
    user: null,
    loading: false,
});
state.user = { name: 'Juan' };
```

#### Composables (Lógica Reutilizable)

Composables extraen lógica reutilizable.

**Ejemplo: `useAuth.ts`**

```typescript
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth.user);
    const isAdmin = computed(() => user.value?.role === 'admin');
    const isSuperadmin = computed(() => user.value?.is_superadmin);

    return {
        user,
        isAdmin,
        isSuperadmin,
    };
}
```

**Uso:**

```vue
<script setup lang="ts">
import { useAuth } from '@/Composables/useAuth';

const { user, isAdmin } = useAuth();
</script>

<template>
    <div>
        <p>Bienvenido, {{ user.firstname }}!</p>
        <button v-if="isAdmin">Panel de Admin</button>
    </div>
</template>
```

### 7.3. Inertia.js: El Puente

#### Cómo Funciona Inertia

**Request tradicional (SSR):**

```
Browser → GET /families → Laravel devuelve HTML completo → Browser renderiza
```

**Request SPA tradicional:**

```
Browser → GET /api/families → Laravel devuelve JSON → Vue procesa JSON → Vue renderiza
```

**Request con Inertia:**

```
Browser → GET /families → Laravel devuelve JSON (vía Inertia) →
Inertia intercepta → Pasa datos a Vue → Vue renderiza → Sin recarga de página
```

**Código Laravel:**

```php
public function index()
{
    return Inertia::render('Families/Index', [
        'families' => Family::all(),
    ]);
}
```

**Código Vue:**

```vue
<script setup lang="ts">
interface Props {
    families: Array<Family>;
}

defineProps<Props>();
</script>

<template>
    <div v-for="family in families" :key="family.id">
        {{ family.name }}
    </div>
</template>
```

**Navegación:**

```vue
<Link :href="route('families.show', family.id')">
    Ver familia
</Link>
```

Cuando se hace clic:
1. Inertia intercepta el clic
2. Hace request AJAX a `/families/{id}`
3. Laravel devuelve datos JSON
4. Vue renderiza el componente `Families/Show` con esos datos
5. **Sin recarga de página** (experiencia SPA)

#### Formularios con Inertia

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
});

function submit() {
    form.post(route('families.store'), {
        onSuccess: () => {
            // Formulario enviado exitosamente
            form.reset();
        },
    });
}
</script>

<template>
    <form @submit.prevent="submit">
        <input v-model="form.name" type="text" />
        <span v-if="form.errors.name" class="text-red-600">
            {{ form.errors.name }}
        </span>

        <input v-model="form.email" type="email" />
        <span v-if="form.errors.email" class="text-red-600">
            {{ form.errors.email }}
        </span>

        <button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Guardando...' : 'Guardar' }}
        </button>
    </form>
</template>
```

**`useForm` proporciona:**
- `form.processing`: Indica si el request está en curso
- `form.errors`: Errores de validación del backend
- `form.post/put/delete`: Métodos HTTP
- `form.reset()`: Limpia el formulario

#### Shared Data (Datos Globales)

Datos que todas las páginas necesitan (usuario autenticado, flash messages) se comparten vía middleware.

`app/Http/Middleware/HandleInertiaRequests.php`:

```php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'firstname' => $request->user()->firstname,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
            ] : null,
        ],
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error' => fn () => $request->session()->get('error'),
        ],
    ]);
}
```

Acceso en Vue:

```vue
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
const flashSuccess = page.props.flash.success;
</script>
```

### 7.4. TypeScript en Vue

TypeScript añade tipos estáticos a JavaScript, previniendo errores.

**Ejemplo sin TypeScript:**

```javascript
function greet(user) {
    return `Hello, ${user.name}`;
}

greet({ firstname: 'Juan' });  // Error: user.name es undefined
```

**Con TypeScript:**

```typescript
interface User {
    name: string;
}

function greet(user: User): string {
    return `Hello, ${user.name}`;
}

greet({ firstname: 'Juan' });  // Error en editor: falta 'name'
```

**En componentes Vue:**

```vue
<script setup lang="ts">
interface Family {
    id: number;
    name: string;
    members_count: number;
}

interface Props {
    families: Family[];
}

const props = defineProps<Props>();

// TypeScript sabe que props.families es un array de Family
// El editor autocompleta: props.families[0].name
</script>
```

**Beneficios:**
- Prevención de errores en tiempo de escritura
- Autocompletado inteligente
- Refactorización segura
- Documentación implícita

---

## Sección 8: Testing con Pest PHP

### 8.1. Filosofía: Tests como Aplicación

En MTAV, los tests no son un "extra", sino **una aplicación en sí misma**.

**Principio fundamental:**

> No se puede construir una aplicación de calidad sin una base sólida. Lo mismo aplica a los tests: necesitas arquitectura, herramientas y patrones correctos.

### 8.2. La Metáfora del Toolbox

**Problema sin herramientas:**

Test típico sin helpers:

```php
it('prevents members from viewing other projects families', function () {
    // ARRANGE (20 líneas para configurar el escenario)
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();
    $family1 = Family::factory()->create(['project_id' => $project1->id]);
    $family2 = Family::factory()->create(['project_id' => $project2->id]);
    $member = Member::factory()->create(['family_id' => $family1->id]);
    $this->actingAs(User::find($member->user_id));

    // ACT (ejecutar la acción)
    $response = $this->get(route('families.index'));

    // ASSERT (verificar)
    $response->assertSuccessful();
    $response->assertSee($family1->name);
    $response->assertDontSee($family2->name);
});
```

**Problema:** 20+ líneas para un test simple. Si tienes 100 tests, son 2000 líneas de setup repetitivo.

**Solución: Construir herramientas (helpers)**

Con fixture y helpers:

```php
it('prevents members from viewing other projects families', function () {
    $response = $this->visitRoute('families.index', asMember: 102);

    expect($response)->toShowOnlyFamiliesFromProject(1);
});
```

**Reducción:** De 20 líneas a 2. Más legible, más mantenible.

### 8.3. Herramientas Construidas para MTAV

#### 8.3.1. Fixture: `universe.sql`

**Problema:** Cada test necesita datos (proyectos, familias, miembros). Crearlos con factories es lento y verboso.

**Solución:** Una "base de datos de prueba" preconstruida con escenarios comunes.

`tests/_fixtures/universe.sql`:

```sql
-- Proyectos
INSERT INTO projects (id, name) VALUES (1, 'Cooperativa Los Pinos');
INSERT INTO projects (id, name) VALUES (2, 'Cooperativa El Roble');

-- Tipos de Unidad
INSERT INTO unit_types (id, name, project_id) VALUES (1, '2 dorm', 1);
INSERT INTO unit_types (id, name, project_id) VALUES (2, '3 dorm', 1);

-- Familias
INSERT INTO families (id, name, unit_type_id, project_id) VALUES (10, 'Familia Pérez', 1, 1);
INSERT INTO families (id, name, unit_type_id, project_id) VALUES (11, 'Familia González', 1, 1);
INSERT INTO families (id, name, unit_type_id, project_id) VALUES (20, 'Familia Rodríguez', 1, 2);

-- Miembros
INSERT INTO members (id, user_id, family_id, firstname, lastname, email)
VALUES (102, 102, 10, 'Juan', 'Pérez', 'juan@example.com');

INSERT INTO members (id, user_id, family_id, firstname, lastname, email)
VALUES (103, 103, 11, 'María', 'González', 'maria@example.com');

-- Usuarios
INSERT INTO users (id, email, password, role, project_id)
VALUES (102, 'juan@example.com', '$2y$10$...', 'member', 1);
```

**Beneficios:**
- ✅ Datos consistentes en todos los tests
- ✅ IDs predecibles (miembro 102 siempre es Juan Pérez)
- ✅ Documentación: Leyendo el fixture entiendes los escenarios

**Carga del fixture:**

`tests/TestCase.php`:

```php
protected function setUp(): void
{
    parent::setUp();

    // Cargar fixture una vez por test suite
    if (! self::$fixtureLoaded) {
        DB::unprepared(file_get_contents(__DIR__ . '/_fixtures/universe.sql'));
        self::$fixtureLoaded = true;
    }
}
```

**Transacciones:** Cada test corre en una transacción que se rollbackea al terminar. El fixture permanece, cambios de cada test desaparecen.

#### 8.3.2. Helper: `visitRoute()`

**Propósito:** Visitar una ruta autenticado como un usuario específico.

`tests/Concerns/Http.php`:

```php
protected function visitRoute(
    string $route,
    int $asUser = null,
    int $asMember = null,
    int $asAdmin = null,
    bool $redirects = true
): TestResponse {
    // Autenticar
    if ($asMember) {
        $this->actingAs(User::find(Member::find($asMember)->user_id));
    } elseif ($asAdmin) {
        $this->actingAs(User::find($asAdmin));
    }

    // Visitar ruta
    $response = $this->get(route($route));

    // Manejar redirecciones
    if ($redirects) {
        $response->assertSuccessful();
    }

    return $response;
}
```

**Uso:**

```php
it('shows family details to members', function () {
    $response = $this->visitRoute('families.show', [10], asMember: 102);

    $response->assertSee('Familia Pérez');
});
```

#### 8.3.3. Custom Expectations

**Propósito:** Aserciones reutilizables específicas del dominio.

`tests/Helpers/expectations.php`:

```php
expect()->extend('toShowOnlyFamiliesFromProject', function (int $projectId) {
    $familiesFromProject = Family::where('project_id', $projectId)->pluck('name');
    $familiesFromOther = Family::where('project_id', '!=', $projectId)->pluck('name');

    foreach ($familiesFromProject as $name) {
        $this->value->assertSee($name);
    }

    foreach ($familiesFromOther as $name) {
        $this->value->assertDontSee($name);
    }

    return $this;
});
```

**Uso:**

```php
it('shows only families from user project', function () {
    $response = $this->visitRoute('families.index', asMember: 102);

    expect($response)->toShowOnlyFamiliesFromProject(1);
});
```

### 8.4. Estructura de Tests en MTAV

```
tests/
  TestCase.php           # Base test case
  Pest.php               # Configuración Pest
  _fixtures/
    universe.sql         # Datos de prueba
  Concerns/
    Http.php             # Helpers HTTP (visitRoute, sendPostRequest)
    Utilities.php        # Trait que compone otros concerns
  Helpers/
    expectations.php     # Custom expectations
  Feature/
    Auth/
      LoginTest.php
      InvitationTest.php
    Families/
      CreateFamilyTest.php
      ViewFamilyTest.php
    Preferences/
      ManagePreferencesTest.php
  Unit/
    Models/
      FamilyTest.php
```

### 8.5. Tipos de Tests

#### Feature Tests (Integración)

Prueban flujos completos (request → respuesta).

```php
it('allows admin to create family', function () {
    $response = $this->sendPostRequest(
        'families.store',
        asAdmin: 1,
        data: [
            'name' => 'Nueva Familia',
            'unit_type_id' => 1,
        ]
    );

    $response->assertRedirect(route('families.index'));
    $this->assertDatabaseHas('families', ['name' => 'Nueva Familia']);
});
```

#### Unit Tests (Aislados)

Prueban lógica de modelos/clases sin DB o HTTP.

```php
it('calculates satisfaction score correctly', function () {
    $family = new Family();
    $family->assigned_unit_preference_rank = 1;

    expect($family->satisfactionScore())->toBe(100);
});
```

### 8.6. Convenciones y Reglas

**1. Tests cortos (idealmente 2 líneas):**

```php
// ✅ BIEN
it('requires password', function () {
    $response = $this->sendPostRequest('register', data: [], redirects: false);
    $response->assertInvalid('password');
});

// ❌ MAL (demasiado largo, extraer a helpers)
it('requires password', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $response = $this->post(route('register'), [
        'firstname' => 'John',
        'lastname' => 'Doe',
        // password omitido
    ]);
    $response->assertSessionHasErrors('password');
});
```

**2. Una aserción por test:**

```php
// ✅ BIEN (un test, una aserción)
it('redirects authenticated user from login', function () {
    $response = $this->visitRoute('login', asAdmin: 1, redirects: false);
    $response->assertRedirect('home');
});

// ❌ EVITAR (múltiples aserciones mezcladas)
it('handles authentication', function () {
    $response = $this->visitRoute('login', asAdmin: 1, redirects: false);
    $response->assertRedirect('home');
    $response->assertSessionHas('success');
    $this->assertAuthenticatedAs(User::find(1));
});
```

**3. Nombres descriptivos:**

```php
// ✅ BIEN
it('prevents members from deleting families')
it('allows admin to invite members')
it('requires email confirmation to match')

// ❌ MAL
it('test family deletion')
it('checks invites')
it('validates')
```

### 8.7. Test Hooks y Setup

**Pest permite hooks:**

```php
beforeEach(function () {
    // Ejecutar antes de cada test
    $this->artisan('config:clear');
});

afterEach(function () {
    // Ejecutar después de cada test
    Log::info('Test completed');
});
```

**Grupos:**

```php
it('critical functionality', function () {
    // ...
})->group('p0', 'critical');

it('nice to have', function () {
    // ...
})->group('p3', 'low-priority');
```

Ejecutar grupo específico:

```bash
./mtav test --group=p0
```

### 8.8. Coverage y Métricas

**Ejecutar con coverage:**

```bash
./mtav test --coverage
```

**Output:**

```
Tests:    145 passed
Duration: 12.5s
Coverage: 78.3%

Uncovered:
  app/Services/LotteryService.php (20%)
  app/Http/Controllers/EventController.php (45%)
```

**Objetivo:** >80% coverage para código crítico (modelos, controladores principales).

### 8.9. CI/CD Integration

Tests se ejecutan automáticamente en GitHub Actions:

`.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_PASSWORD: secret
        options: >-
          --health-cmd pg_isready
          --health-interval 10s

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3

      - name: Install dependencies
        run: composer install

      - name: Run tests
        run: php artisan test
```

**Beneficio:** Cada push verifica que nada se rompió.

