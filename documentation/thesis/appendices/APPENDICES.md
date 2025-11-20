# Apéndices

## Apéndice A: Fragmentos de Código Representativos

### A.1. Modelo Eloquent con Relaciones

**Archivo:** `app/Models/Family.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasProjectScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    use HasFactory, SoftDeletes, HasProjectScope;

    protected $fillable = [
        'name',
        'unit_type_id',
        'project_id',
        'unit_id',
    ];

    protected $casts = [
        'unit_type_id' => 'integer',
        'project_id' => 'integer',
        'unit_id' => 'integer',
    ];

    /**
     * Una familia pertenece a un proyecto
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Una familia pertenece a un tipo de unidad
     */
    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    /**
     * Una familia puede tener una unidad asignada (después del sorteo)
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Una familia tiene muchos miembros
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Verificar si la familia tiene una unidad asignada
     */
    public function hasAssignedUnit(): bool
    {
        return ! is_null($this->unit_id);
    }

    /**
     * Calcular puntaje de satisfacción basado en la preferencia asignada
     *
     * @return int Puntaje entre 20 y 100
     */
    public function satisfactionScore(): int
    {
        if (! $this->hasAssignedUnit()) {
            return 0;
        }

        $assignedRank = $this->preferences()
            ->orderBy('ranking')
            ->pluck('unit_id')
            ->search($this->unit_id);

        if ($assignedRank === false) {
            return 20; // Unidad no estaba en preferencias
        }

        return match ($assignedRank + 1) {
            1 => 100,
            2 => 80,
            3 => 60,
            4 => 40,
            default => 20,
        };
    }
}
```

---

### A.2. Policy de Autorización

**Archivo:** `app/Policies/FamilyPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier familia
     */
    public function viewAny(User $user): bool
    {
        // Todos los usuarios autenticados pueden ver familias
        // (El Global Scope filtra automáticamente por proyecto)
        return true;
    }

    /**
     * Determinar si el usuario puede ver esta familia
     */
    public function view(User $user, Family $family): bool
    {
        // Superadmins pueden ver todo
        if ($user->isSuperadmin()) {
            return true;
        }

        // Usuarios solo pueden ver familias de su proyecto
        return $user->project_id === $family->project_id;
    }

    /**
     * Determinar si el usuario puede crear familias
     */
    public function create(User $user): bool
    {
        // Solo administradores y superadmins
        return $user->isAdmin() || $user->isSuperadmin();
    }

    /**
     * Determinar si el usuario puede actualizar esta familia
     */
    public function update(User $user, Family $family): bool
    {
        // Superadmins pueden actualizar cualquier familia
        if ($user->isSuperadmin()) {
            return true;
        }

        // Administradores pueden actualizar familias de su proyecto
        return $user->isAdmin() && $user->project_id === $family->project_id;
    }

    /**
     * Determinar si el usuario puede eliminar esta familia
     */
    public function delete(User $user, Family $family): bool
    {
        // Mismas reglas que update
        return $this->update($user, $family);
    }

    /**
     * Determinar si el usuario puede gestionar preferencias de esta familia
     */
    public function managePreferences(User $user, Family $family): bool
    {
        // Superadmins pueden gestionar cualquier preferencia
        if ($user->isSuperadmin()) {
            return true;
        }

        // Miembros de la familia pueden gestionar sus propias preferencias
        if ($user->isMemberOf($family)) {
            return true;
        }

        // Administradores pueden gestionar preferencias de familias de su proyecto
        return $user->isAdmin() && $user->project_id === $family->project_id;
    }
}
```

---

### A.3. Componente Vue con TypeScript

**Archivo:** `resources/js/Components/FamilyCard.vue`

```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Family {
    id: number;
    name: string;
    members_count: number;
    unit_type: {
        name: string;
    };
    project: {
        name: string;
    };
}

interface Props {
    family: Family;
    showProject?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showProject: false,
});

const memberText = computed(() => {
    const count = props.family.members_count;
    return count === 1 ? '1 miembro' : `${count} miembros`;
});
</script>

<template>
    <div class="bg-white shadow-md rounded-lg p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="text-xl font-semibold text-gray-900 mb-2">
                    {{ family.name }}
                </h3>

                <div class="space-y-1 text-sm text-gray-600">
                    <p class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        {{ memberText }}
                    </p>

                    <p class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        {{ family.unit_type.name }}
                    </p>

                    <p v-if="showProject" class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                        </svg>
                        {{ family.project.name }}
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-200">
            <Link
                :href="route('families.show', family.id)"
                class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium transition-colors"
            >
                Ver detalles
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </Link>
        </div>
    </div>
</template>
```

---

### A.4. Test con Pest PHP

**Archivo:** `tests/Feature/Families/ManagePreferencesTest.php`

```php
<?php

use App\Models\Family;
use App\Models\Unit;

describe('Family Preferences Management', function () {
    it('allows members to rate units for their family', function () {
        // ARRANGE: Member 102 belongs to Family 10
        // ACT: Submit preference for Unit 1
        $response = $this->sendPostRequest('preferences.store', asMember: 102, data: [
            'unit_id' => 1,
            'rating' => 5,
        ]);

        // ASSERT: Preference was created
        $this->assertDatabaseHas('family_preferences', [
            'family_id' => 10,
            'unit_id' => 1,
            'rating' => 5,
        ]);
    });

    it('prevents members from rating units outside their unit type', function () {
        // Family 10 has unit_type_id = 1
        // Unit 50 has unit_type_id = 2 (different)
        $response = $this->sendPostRequest('preferences.store', asMember: 102, data: [
            'unit_id' => 50,
            'rating' => 5,
        ], redirects: false);

        $response->assertInvalid('unit_id');
    });

    it('prevents members from rating units from other projects', function () {
        // Member 102 is in Project 1
        // Unit 200 is in Project 2
        $response = $this->sendPostRequest('preferences.store', asMember: 102, data: [
            'unit_id' => 200,
            'rating' => 4,
        ], redirects: false);

        $response->assertForbidden();
    });

    it('requires rating to be between 1 and 5', function () {
        $response = $this->sendPostRequest('preferences.store', asMember: 102, data: [
            'unit_id' => 1,
            'rating' => 10,  // Invalid: too high
        ], redirects: false);

        $response->assertInvalid('rating');
    });

    it('allows families to update existing preferences', function () {
        // Create initial preference
        $this->sendPostRequest('preferences.store', asMember: 102, data: [
            'unit_id' => 1,
            'rating' => 3,
        ]);

        // Update rating
        $preference = Family::find(10)->preferences()->where('unit_id', 1)->first();

        $response = $this->sendPutRequest("preferences.update", [$preference->id], asMember: 102, data: [
            'rating' => 5,
        ]);

        $this->assertDatabaseHas('family_preferences', [
            'id' => $preference->id,
            'rating' => 5,
        ]);
    });

    it('freezes preferences after lottery execution', function () {
        // Execute lottery (simplified)
        $project = Family::find(10)->project;
        $project->update(['lottery_executed_at' => now()]);

        // Try to create new preference
        $response = $this->sendPostRequest('preferences.store', asMember: 102, data: [
            'unit_id' => 2,
            'rating' => 4,
        ], redirects: false);

        $response->assertForbidden();
    });
})->group('preferences', 'p1');
```

---

## Apéndice B: Documentación Técnica

### B.1. Comando `./mtav` - Referencia Completa

El wrapper `./mtav` encapsula todos los comandos Docker necesarios para trabajar con el proyecto.

**Comandos disponibles:**

```bash
# Gestión de contenedores
./mtav up              # Iniciar todos los contenedores
./mtav down            # Detener todos los contenedores
./mtav restart         # Reiniciar contenedores
./mtav build           # Reconstruir imágenes
./mtav rebuild [service]  # Reconstruir servicio específico (php, node, etc.)

# Laravel Artisan
./mtav artisan [command]  # Ejecutar comando Artisan
./mtav artisan migrate    # Ejecutar migraciones
./mtav artisan db:seed    # Ejecutar seeders
./mtav artisan tinker     # Abrir REPL de Laravel

# Composer (PHP)
./mtav composer install        # Instalar dependencias
./mtav composer require pkg    # Añadir paquete
./mtav composer update         # Actualizar dependencias

# npm (Node.js)
./mtav npm install      # Instalar dependencias JS
./mtav npm run dev      # Iniciar Vite dev server
./mtav npm run build    # Compilar assets para producción

# Testing
./mtav test                    # Ejecutar todos los tests
./mtav test --pest             # Ejecutar solo tests Pest
./mtav test --group=p0         # Ejecutar grupo específico
./mtav test --coverage         # Tests con coverage

# Shells
./mtav shell php        # Acceder al shell del contenedor PHP
./mtav shell node       # Acceder al shell del contenedor Node
./mtav shell postgres   # Acceder al shell de PostgreSQL

# Logs
./mtav logs [service]   # Ver logs de un servicio
./mtav logs -f php      # Seguir logs en tiempo real
```

---

### B.2. Estructura de Directorios

```
mtav/
├── .docker/                 # Configuración Docker
│   ├── compose.yml          # Docker Compose orchestration
│   ├── Dockerfile.php       # Imagen PHP
│   ├── Dockerfile.node      # Imagen Node.js
│   ├── nginx/
│   │   └── default.conf     # Configuración Nginx
│   └── scripts/             # Scripts auxiliares
│       ├── artisan.sh
│       ├── composer.sh
│       └── npm.sh
├── app/                     # Código Laravel
│   ├── Http/
│   │   ├── Controllers/     # Controladores
│   │   ├── Middleware/      # Middleware custom
│   │   └── Requests/        # Form Requests
│   ├── Models/              # Modelos Eloquent
│   │   └── Concerns/        # Traits reutilizables
│   ├── Policies/            # Políticas de autorización
│   └── Services/            # Lógica de negocio
│       └── Lottery/         # Implementaciones de sorteo
├── config/                  # Archivos de configuración
├── database/
│   ├── migrations/          # Migraciones SQL
│   ├── seeders/             # Seeders
│   └── factories/           # Factories para tests
├── documentation/           # Documentación del proyecto
│   ├── ai/                  # Knowledge base para IA
│   ├── guides/              # Guías de usuario
│   └── thesis/              # Documentos de tesis
├── resources/
│   ├── js/                  # Código Vue.js
│   │   ├── Components/      # Componentes reutilizables
│   │   ├── Layouts/         # Layouts de página
│   │   ├── Pages/           # Páginas Inertia
│   │   └── app.ts           # Entry point
│   ├── css/                 # Estilos
│   └── views/               # Vistas Blade (mínimas)
├── routes/
│   ├── web.php              # Rutas web principales
│   └── api.php              # Rutas API (si hay)
├── tests/
│   ├── _fixtures/           # Datos de prueba (universe.sql)
│   ├── Concerns/            # Helpers de test (traits)
│   ├── Helpers/             # Funciones auxiliares
│   ├── Feature/             # Tests de integración
│   └── Unit/                # Tests unitarios
├── .env.example             # Template de variables de entorno
├── composer.json            # Dependencias PHP
├── package.json             # Dependencias JavaScript
├── phpunit.xml              # Configuración PHPUnit
├── vite.config.ts           # Configuración Vite
└── mtav                     # Wrapper script
```

---

### B.3. Variables de Entorno (.env)

```bash
# Aplicación
APP_NAME=MTAV
APP_ENV=local
APP_KEY=base64:GENERADO_POR_ARTISAN_KEY_GENERATE
APP_DEBUG=true
APP_URL=http://localhost

# Base de Datos
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=mtav
DB_USERNAME=mtav
DB_PASSWORD=secret

# Caché y Sesiones
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

# Email (para invitaciones)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@mtav.example.com
MAIL_FROM_NAME="${APP_NAME}"

# Sorteo (futuro)
LOTTERY_SERVICE=dummy  # dummy | production
LOTTERY_API_URL=https://optimization-api.example.com
LOTTERY_API_KEY=secret_key

# Superadmins (emails separados por coma)
SUPERADMIN_EMAILS=superadmin@example.com
```

---

## Apéndice C: Ejemplos de Diálogos con IA

### C.1. Diálogo: Implementación de Sistema de Preferencias

**Humano:**
```
Necesito implementar el sistema para que las familias expresen sus preferencias
sobre las unidades. Cada familia puede calificar unidades con estrellas (1-5) y
ordenarlas por prioridad. Las preferencias deben estar limitadas a unidades del
mismo tipo que la familia y del mismo proyecto.
```

**Copilot:**
```
Sugiero crear:
1. Migración para tabla family_preferences
2. Policy para verificar permisos
3. Controlador con validación
4. Componentes Vue para UI

¿Empiezo con la migración?
```

**Humano:**
```
Sí, genera la migración primero
```

**Copilot genera:**
```php
Schema::create('family_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('family_id')->constrained()->onDelete('cascade');
    $table->foreignId('unit_id')->constrained()->onDelete('cascade');
    $table->integer('rating')->default(3);
    $table->integer('ranking')->nullable();
    $table->timestamps();

    $table->unique(['family_id', 'unit_id']);
});
```

**Humano revisa y ajusta:**
```
Bien, pero añade índice en ranking también para ordenar rápido
```

**Copilot ajusta:**
```php
$table->index('ranking');
```

---

### C.2. Diálogo: Debugging de Test Fallido

**Humano:**
```
Este test falla:

it('prevents members from viewing families from other projects', function () {
    $response = $this->visitRoute('families.index', asMember: 102);
    expect($response)->toShowOnlyFamiliesFromProject(1);
});

Error: Expected to not see "Familia Rodríguez" but it appears in response.
```

**Copilot:**
```
El Global Scope puede no estar aplicándose. Verifica:
1. ¿El modelo Family tiene el trait HasProjectScope?
2. ¿El usuario autenticado tiene project_id asignado?
3. ¿El Global Scope está registrado correctamente?
```

**Humano investiga:**
```
El problema era que en el fixture, el member 102 no tenía project_id.
Lo arreglé actualizando universe.sql
```

---

## Apéndice D: Capturas de Pantalla

*[Aquí se incluirían capturas de pantalla de la aplicación]*

### D.1. Pantalla de Login

*[Captura: Formulario de login con campos email y contraseña]*

### D.2. Dashboard de Miembro

*[Captura: Vista principal con información de familia y proyecto]*

### D.3. Gestión de Preferencias

*[Captura: Interface para calificar unidades con estrellas]*

### D.4. Dashboard de Admin

*[Captura: Panel administrativo con lista de familias]*

### D.5. Resultados del Sorteo

*[Captura: Tabla mostrando asignaciones familia → unidad con métricas de satisfacción]*

---

## Apéndice E: Guías de Usuario

*[Referencias a los documentos en `documentation/guides/`]*

- **Guía del Miembro (Español):** `documentation/guides/es_UY/member-guide.md`
- **Guía del Miembro (Inglés):** `documentation/guides/en/member-guide.md`
- **Manual del Administrador (Español):** `documentation/guides/es_UY/admin-manual.md`
- **Manual del Administrador (Inglés):** `documentation/guides/en/admin-manual.md`

---

## Apéndice F: Preguntas Frecuentes (FAQ)

### Para Miembros

**P: ¿Cómo cambio mi contraseña?**

R: Accede a tu perfil (icono de usuario en la esquina superior derecha) → Configuración → Cambiar contraseña.

**P: ¿Puedo cambiar mis preferencias después de enviarlas?**

R: Sí, hasta que el administrador ejecute el sorteo. Una vez ejecutado, las preferencias se congelan y no se pueden modificar.

**P: ¿Qué pasa si no expreso preferencias?**

R: El sistema te asignará una unidad disponible de tu tipo, pero sin considerar tus preferencias. Es muy recomendable expresarlas.

**P: ¿Cuántas unidades debo calificar?**

R: Recomendamos calificar al menos 5 unidades para tener opciones. Puedes calificar todas las que desees.

---

### Para Administradores

**P: ¿Puedo ejecutar el sorteo más de una vez?**

R: No. El sorteo solo se puede ejecutar una vez por proyecto. Si necesitas repetirlo, contacta al soporte técnico.

**P: ¿Cómo invito a un nuevo miembro?**

R: Panel de Admin → Miembros → Invitar → Completa formulario con email y datos → El miembro recibirá un correo.

**P: ¿Puedo ver las preferencias de las familias antes del sorteo?**

R: Sí, los administradores tienen acceso a todas las preferencias para verificar que estén completas.

**P: ¿Qué hago si una familia cambia de tipo de unidad después de registrarse?**

R: Contacta al soporte técnico. Este cambio requiere verificación manual porque puede afectar el sorteo.

---

### Técnicas

**P: ¿El sistema funciona en dispositivos móviles?**

R: Sí, la interfaz es completamente responsive y funciona en smartphones y tablets.

**P: ¿Qué navegadores son compatibles?**

R: Chrome, Firefox, Safari y Edge en sus versiones recientes (últimos 2 años).

**P: ¿Los datos están respaldados?**

R: Sí, se realizan backups automáticos diarios de la base de datos.

**P: ¿Es seguro el sistema?**

R: Sí, usa HTTPS, autenticación robusta, y cumple con estándares de seguridad web modernos.

