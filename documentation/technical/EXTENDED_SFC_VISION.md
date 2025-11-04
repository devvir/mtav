# Extended Single File Components: Beyond HTML/CSS/JS

**Author**: Based on discussions with project creator
**Date**: November 2025
**Status**: Vision Document / Proposal

## Executive Summary

Single File Components (SFCs) revolutionized frontend development by proving that **logical cohesion trumps language cohesion**. However, Vue stopped halfway. This document proposes extending the SFC paradigm to encompass the **entire feature stack**: backend logic, database queries, translations, configuration, and more.

The goal: **truly self-contained, portable, full-stack components** that can be packaged, shared, and installed as complete working features.

---

## Table of Contents

1. [The Problem with Current SFCs](#the-problem-with-current-sfcs)
2. [Core Philosophy: Feature Ownership](#core-philosophy-feature-ownership)
3. [Proposed Extensions](#proposed-extensions)
4. [Technical Implementation](#technical-implementation)
5. [Developer Experience](#developer-experience)
6. [Ecosystem Benefits](#ecosystem-benefits)
7. [Challenges & Solutions](#challenges--solutions)
8. [Roadmap](#roadmap)
9. [Community Strategy](#community-strategy)

---

## The Problem with Current SFCs

### What Vue Got Right

Vue's SFC model proved that separating code by **feature** (component) rather than by **language** (all JS in one folder, all CSS in another) leads to:

- Better maintainability
- Easier reasoning about code
- True component encapsulation
- Simpler refactoring

The mental model shifted from "I need to touch 3 different files" to "I edit one component."

### Where Vue Stopped Short

Current SFCs only address the **frontend portion** of a feature:

```vue
<template>
  <!-- HTML -->
</template>

<script setup>
  // JavaScript
</script>

<style scoped>
  /* CSS */
</style>
```

But a real feature involves much more:
- **Backend endpoints** to fetch/mutate data
- **Database queries** to retrieve information
- **Translations** for internationalization
- **Validation rules** for data integrity
- **Authorization logic** for security
- **Background jobs** for async processing
- **API documentation** for the endpoint
- **Tests** for the feature

Right now, these are scattered across:
- `app/Http/Controllers/` (backend)
- `routes/web.php` (routing)
- `app/Http/Requests/` (validation)
- `lang/` (translations)
- `database/migrations/` (schema)
- `tests/Feature/` (tests)
- `docs/api/` (documentation)

**This violates the core principle that made SFCs successful: logical cohesion.**

---

## Core Philosophy: Feature Ownership

### The New Paradigm

A component should **own its entire feature**, not just the UI layer.

**Example: Member Creation Feature**

Currently requires touching 6+ files:
```
resources/js/pages/Members/Create.vue          ← Frontend UI
app/Http/Controllers/MemberController.php      ← Backend logic
routes/web.php                                 ← Route definition
app/Http/Requests/CreateMemberRequest.php      ← Validation
lang/en.json                                   ← Translations
tests/Feature/MemberCreationTest.php           ← Tests
```

**With Extended SFCs**, everything lives in one place:
```vue
<!-- resources/js/pages/Members/Create.vue -->

<template>
  <form @submit.prevent="submit">
    <input v-model="form.name" :placeholder="t('name')" />
    <button>{{ t('submit') }}</button>
  </form>
</template>

<script setup lang="ts">
const form = useForm({ name: '' });
const submit = () => form.post(route('members.store'));
</script>

<style scoped>
/* Component styles */
</style>

<server lang="php/laravel" route="/members" method="POST" name="members.store">
use App\Models\Member;
use App\Services\InvitationService;

validate([
  'name' => 'required|string|min:2',
  'email' => 'required|email|unique:members',
]);

$member = app(InvitationService::class)->inviteMember($request->validated());

return redirect()
  ->route('members.show', $member)
  ->with('success', __('Member invitation sent!'));
</server>

<i18n>
{
  "en": {
    "name": "Full Name",
    "submit": "Invite Member"
  },
  "es_UY": {
    "name": "Nombre Completo",
    "submit": "Invitar Miembro"
  }
}
</i18n>

<test lang="php/pest">
it('creates a member and sends invitation', function () {
  Mail::fake();

  $response = $this->post(route('members.store'), [
    'name' => 'John Doe',
    'email' => 'john@example.com',
  ]);

  $response->assertRedirect();
  Mail::assertSent(MemberInvitationMail::class);
});
</test>
```

**Everything for this feature is in one file.** Change the validation? It's right there. Update the translation? Same file. Modify the backend logic? No need to navigate away.

---

## Proposed Extensions

### 1. `<server>` Block - Backend Logic

**Purpose**: Define server-side endpoints directly in the component.

**Syntax**:
```vue
<server lang="php/laravel" route="/api/users" method="GET" middleware="auth">
use App\Models\User;

$users = User::where('active', true)
  ->with('projects')
  ->paginate(20);

return response()->json($users);
</server>
```

**Attributes**:
- `lang`: Backend language/framework (php/laravel, python/django, go/gin, etc.)
- `route`: URL endpoint
- `method`: HTTP verb (GET, POST, PUT, DELETE)
- `middleware`: Authentication/authorization (optional)
- `name`: Named route (optional)

**Build Process**:
Vite plugin extracts this block and generates:
- Route definition in `routes/generated.php`
- Controller method or closure
- Proper imports and namespacing

**Benefits**:
- Co-located with frontend code
- Full backend IDE support (PHPStorm, Intelephense)
- Type-safe contracts (shared types between frontend and backend)
- Atomic refactoring (change API contract in one place)

---

### 2. `<sql>` / `<query>` Block - Database Layer

**Purpose**: Define data queries declaratively.

#### SQL Variant (for traditional databases):
```vue
<sql name="activeUsers" as="users">
SELECT u.*, p.name as project_name
FROM users u
LEFT JOIN projects p ON u.project_id = p.id
WHERE u.active = true
  AND u.created_at > :since
ORDER BY u.name ASC
</sql>

<template>
  <div v-for="user in users">
    {{ user.name }} - {{ user.project_name }}
  </div>
</template>
```

#### ORM Variant (Eloquent/Prisma):
```vue
<query lang="eloquent" name="activeUsers" as="users">
User::where('active', true)
  ->where('created_at', '>', $since)
  ->with('project:id,name')
  ->orderBy('name')
  ->get()
</query>
```

#### NoSQL Variant (Firestore/MongoDB):
```vue
<firestore collection="users" as="users" subscribe>
WHERE active == true
WHERE createdAt > {{since}}
ORDER BY name ASC
</firestore>
```

**Attributes**:
- `lang`: Query language (sql, eloquent, firestore, mongodb, prisma)
- `name`: Query identifier
- `as`: Variable name in template
- `subscribe`: Enable real-time updates (for Firebase/Supabase)

**Build Process**:
- Generates reactive data bindings
- Sets up/tears down subscriptions with component lifecycle
- Type inference from schema (TypeScript autocomplete)
- Query optimization hints
- Security rule validation (Firebase)

**Benefits**:
- Declarative data fetching
- Automatic loading states
- Type-safe queries
- Real-time subscriptions as first-class feature
- Backend-agnostic (switch from Firebase to Postgres by changing `lang`)

---

### 3. `<i18n>` Block - Translations

**Purpose**: Component-specific translations.

**Syntax**:
```vue
<i18n>
{
  "en": {
    "greeting": "Hello, {name}!",
    "button.submit": "Submit",
    "error.required": "This field is required"
  },
  "es_UY": {
    "greeting": "¡Hola, {name}!",
    "button.submit": "Enviar",
    "error.required": "Este campo es obligatorio"
  },
  "fr": {
    "greeting": "Bonjour, {name}!",
    "button.submit": "Soumettre",
    "error.required": "Ce champ est requis"
  }
}
</i18n>

<template>
  <p>{{ t('greeting', { name: user.name }) }}</p>
  <button>{{ t('button.submit') }}</button>
</template>
```

**Alternative YAML Syntax**:
```vue
<i18n lang="yaml">
en:
  greeting: "Hello, {name}!"
  button:
    submit: "Submit"
es_UY:
  greeting: "¡Hola, {name}!"
  button:
    submit: "Enviar"
</i18n>
```

**Build Process**:
- Extract translations at build time
- Generate type-safe `t()` function with autocomplete
- Tree-shake unused translations
- Support message interpolation
- Pluralization rules per locale

**Benefits**:
- True component encapsulation (styles, logic, AND text)
- No external translation files to manage
- Easy to see missing translations
- Sharable components include their translations
- Fallback to global translations for shared strings

**Status**: Already implemented and working! (See `playground/I18n.vue`)

---

### 4. `<validate>` Block - Validation Rules

**Purpose**: Define validation schemas.

**Syntax**:
```vue
<validate lang="zod" as="schema">
{
  name: z.string().min(2).max(100),
  email: z.string().email(),
  age: z.number().min(18).optional(),
  role: z.enum(['admin', 'member'])
}
</validate>

<script setup>
const form = useForm(schema, {
  name: '',
  email: '',
  age: null,
  role: 'member'
});
</script>
```

**Alternative**: Laravel-style validation
```vue
<validate lang="laravel">
name: required|string|min:2|max:100
email: required|email|unique:users
age: nullable|integer|min:18
role: required|in:admin,member
</validate>
```

**Build Process**:
- Generate frontend validation (zod/yup)
- Generate backend validation (Laravel FormRequest)
- Sync validation rules across stack
- Custom error messages per locale

**Benefits**:
- Single source of truth for validation
- No drift between frontend and backend rules
- Type-safe forms
- Automatic error message display

---

### 5. `<test>` Block - Component Tests

**Purpose**: Co-locate tests with the component.

**Syntax**:
```vue
<test lang="vitest">
import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

describe('MemberCreate', () => {
  it('renders form fields', () => {
    const wrapper = mount(MemberCreate);
    expect(wrapper.find('input[name="name"]').exists()).toBe(true);
  });

  it('validates required fields', async () => {
    const wrapper = mount(MemberCreate);
    await wrapper.find('form').trigger('submit');
    expect(wrapper.text()).toContain('required');
  });
});
</test>

<test lang="php/pest">
it('creates a member', function () {
  $response = $this->post(route('members.store'), [
    'name' => 'John',
    'email' => 'john@example.com'
  ]);

  $response->assertRedirect();
  expect(Member::count())->toBe(1);
});
</test>
```

**Build Process**:
- Extract to test files
- Run with appropriate test runner (Vitest, Pest, Jest)
- Support multiple test blocks for different layers
- Generate coverage reports per component

**Benefits**:
- Tests live with the code they test
- Easy to keep tests updated during refactoring
- Clear what's tested vs. not tested
- Full-stack testing in one file

---

### 6. `<config>` Block - Component Configuration

**Purpose**: Component-specific settings.

**Syntax**:
```vue
<config>
{
  "cache": {
    "ttl": 3600,
    "key": "members-list"
  },
  "permissions": ["view-members"],
  "rateLimit": {
    "max": 100,
    "window": "1m"
  }
}
</config>
```

**Build Process**:
- Apply caching rules to queries
- Enforce permission checks
- Configure rate limiting
- Feature flags

---

### 7. `<docs>` Block - API Documentation

**Purpose**: Document the component's API.

**Syntax**:
```vue
<docs lang="markdown">
# Member Creation

Creates a new member and sends an invitation email.

## Props
- `project` (Project): The project to add the member to

## Events
- `created` (Member): Emitted when member is successfully created

## API Endpoint
- `POST /members`
- Requires: `create-members` permission
- Rate limit: 10 requests/minute

## Example
```js
<MemberCreate :project="currentProject" @created="handleCreated" />
```
</docs>
```

**Build Process**:
- Generate component documentation site
- OpenAPI/Swagger spec for backend endpoints
- Type definitions for props/events
- Usage examples

---

## Technical Implementation

### Architecture Overview

```
┌─────────────────────────────────────┐
│   Component.vue (Source)            │
│                                     │
│  ┌──────────────────────────────┐  │
│  │ <template>                   │  │
│  │ <script setup>               │  │
│  │ <style scoped>               │  │
│  │ <server lang="php/laravel">  │  │
│  │ <sql lang="eloquent">        │  │
│  │ <i18n>                       │  │
│  │ <validate lang="zod">        │  │
│  │ <test lang="vitest">         │  │
│  └──────────────────────────────┘  │
└─────────────────────────────────────┘
              │
              ▼
    ┌─────────────────┐
    │  Vite Plugin    │
    │   Orchestrator  │
    └─────────────────┘
              │
        ┌─────┴──────┬──────────┬──────────┐
        ▼            ▼          ▼          ▼
   ┌────────┐  ┌────────┐ ┌────────┐ ┌────────┐
   │ Vue    │  │ PHP    │ │ i18n   │ │ Test   │
   │ Plugin │  │ Plugin │ │ Plugin │ │ Plugin │
   └────────┘  └────────┘ └────────┘ └────────┘
        │            │          │          │
        ▼            ▼          ▼          ▼
   ┌────────┐  ┌────────┐ ┌────────┐ ┌────────┐
   │ .vue   │  │ routes/│ │ lang/  │ │ tests/ │
   │ bundle │  │ gen.php│ │ gen.js │ │ *.test │
   └────────┘  └────────┘ └────────┘ └────────┘
```

### Vite Plugin System

**Core Plugin** (`vite-plugin-extended-sfc`):
```js
export default function extendedSFC(options) {
  return {
    name: 'extended-sfc',

    transform(code, id) {
      if (!id.endsWith('.vue')) return;

      const blocks = parseCustomBlocks(code);

      // Process each block type
      for (const block of blocks) {
        const handler = getHandler(block.type, block.lang);
        if (handler) {
          handler.process(block, options);
        } else if (options.strict) {
          throw new Error(`No handler for <${block.type}>`);
        }
        // In non-strict mode, unknown blocks are ignored
      }

      return transformedCode;
    }
  };
}
```

**Handler Plugin Interface**:
```js
export function createHandler(config) {
  return {
    name: 'handler-name',

    // Check if this handler can process the block
    canHandle(blockType, lang) {
      return blockType === 'server' && lang === 'php/laravel';
    },

    // Process the block
    async process(block, context) {
      const { content, attrs, filename } = block;

      // Extract and transform
      const route = generateRoute(content, attrs);

      // Write to appropriate location
      await context.writeFile('routes/generated.php', route);

      // Return metadata for frontend
      return {
        endpoint: attrs.route,
        method: attrs.method
      };
    },

    // Generate types
    async generateTypes(blocks, context) {
      return generateTypeDefinitions(blocks);
    }
  };
}
```

### Graceful Degradation

**Problem**: If user doesn't have the plugin installed, their build breaks.

**Solution**: Configurable modes

```js
// vite.config.js
import extendedSFC from 'vite-plugin-extended-sfc';

export default {
  plugins: [
    extendedSFC({
      mode: 'strict',        // Throw error on unknown blocks
      // mode: 'warn',       // Warn but continue
      // mode: 'ignore',     // Silently ignore

      handlers: [
        serverHandler({ framework: 'laravel' }),
        i18nHandler(),
        sqlHandler({ dialect: 'postgres' })
      ]
    })
  ]
};
```

**Unknown Block Handling**:
- **Strict mode**: Error on unknown blocks (development)
- **Warn mode**: Console warning (production builds)
- **Ignore mode**: Strip unknown blocks silently (library distribution)

**Plugin Discovery**:
```js
// Auto-discover handlers from dependencies
{
  autoDiscover: true,  // Scan node_modules for extended-sfc handlers
  allowList: ['*'],    // Or whitelist specific blocks
}
```

---

## Developer Experience

### IDE Support

**Goal**: Full IntelliSense for all block types.

**Requirements**:
- Syntax highlighting
- Autocomplete
- Type checking
- Error squiggles
- Go to definition
- Refactoring support

**Implementation**:

1. **VS Code Extension** (`vscode-extended-sfc`):
   - Language server for each block type
   - Delegates to TypeScript LS for `<script>`
   - Delegates to Intelephense for `<server lang="php">`
   - Delegates to SQL LS for `<sql>`
   - Custom grammar for `<i18n>`

2. **Type Generation**:
```typescript
// Auto-generated from <server> blocks
declare module '#routes' {
  export const routes: {
    'members.store': {
      method: 'POST',
      path: '/members',
      params: {},
      body: { name: string; email: string }
    }
  }
}

// Usage in component
import { routes } from '#routes';
form.post(routes['members.store'].path, {
  name: 'John',  // ✓ Type-safe
  email: 'john@example.com'  // ✓ Type-safe
  // age: 25  // ✗ Error: not in body type
});
```

3. **Unified Command Palette**:
   - "Extract to `<server>` block"
   - "Generate `<test>` for component"
   - "Add `<i18n>` locale"
   - "Preview generated backend code"

### Development Workflow

**Before** (8 steps):
1. Create Vue component
2. Add route to `routes/web.php`
3. Create controller method
4. Create FormRequest for validation
5. Add translations to `lang/en.json` and `lang/es_UY.json`
6. Write frontend test
7. Write backend test
8. Test manually

**After** (3 steps):
1. Create component with all blocks
2. Run dev server (auto-generates backend)
3. Test (tests are in the component)

**Hot Reload for Backend**:
```bash
# Watch for changes in <server> blocks
vite --watch-backend
```

When you edit a `<server>` block:
- Vite regenerates the route
- Backend reloads (using Laravel Octane or similar)
- Frontend HMR updates the API call
- No manual server restart

---

## Ecosystem Benefits

### 1. Component Marketplaces

**Problem Today**: Can't share full-stack components

**With Extended SFCs**:
```bash
npm install @acme/user-auth-component
```

This installs:
- Vue component (frontend UI)
- Backend endpoints (auth logic)
- Database migrations (users table)
- Translations (en, es, fr)
- Tests (full coverage)

**Everything just works.**

### 2. Framework Adapters

One component, multiple backends:

```vue
<!-- Laravel version -->
<server lang="php/laravel" route="/users">
  User::all()
</server>

<!-- Django version -->
<server lang="python/django" route="/users">
  User.objects.all()
</server>

<!-- Go version -->
<server lang="go/gin" route="/users">
  db.Find(&users)
</server>
```

Same frontend, backend adapts to your stack.

### 3. Starter Kits

Full-featured app templates:
```bash
npx create-extended-sfc@latest my-app --template=saas

# Includes:
# - Auth components (login, register, password reset)
# - User management (CRUD)
# - Billing integration (Stripe)
# - Email templates
# - All backend logic
# - Database schema
# - Tests
```

Not just UI components - complete features.

### 4. Educational Value

**Learning full-stack development** becomes much easier:

```vue
<!-- Students see the ENTIRE stack in one file -->
<template>
  <button @click="increment">Count: {{ count }}</button>
</template>

<server lang="php/laravel" route="/api/counter" method="POST">
  $counter = Counter::first();
  $counter->increment('value');
  return $counter->value;
</server>

<sql name="counter" as="count">
  SELECT value FROM counters LIMIT 1
</sql>
```

No jumping between files, no mental overhead. See the whole picture.

---

## Challenges & Solutions

### Challenge 1: Build Complexity

**Problem**: Processing multiple block types adds build time.

**Solution**:
- Parallel processing of independent blocks
- Incremental builds (only changed components)
- Caching transformed blocks
- Optional blocks (disable in development)

```js
{
  dev: {
    skipBlocks: ['test', 'docs']  // Skip in dev mode
  },
  build: {
    parallel: true,
    cache: '.vite/extended-sfc-cache'
  }
}
```

### Challenge 2: Debugging

**Problem**: Generated code is harder to debug.

**Solution**:
- Source maps for all block types
- "View Generated Code" command in IDE
- Debug mode that preserves readable output
- Stack traces map back to original blocks

```js
{
  debug: true,  // Generate readable code with comments
  sourceMaps: true
}
```

### Challenge 3: Team Adoption

**Problem**: Learning curve for new paradigm.

**Solution**:
- Gradual migration (both styles work)
- Code actions: "Move to `<server>` block"
- Documentation and tutorials
- VS Code snippets

```vue
<!-- Old way still works -->
<script setup>
  fetch('/api/users')
</script>

<!-- New way is opt-in -->
<server lang="php/laravel" route="/api/users">
  User::all()
</server>
```

### Challenge 4: Tooling Ecosystem

**Problem**: Existing tools expect separate files.

**Solution**:
- Generate virtual files for compatibility
- Adapters for existing tools (PHPStan, ESLint)
- Output to standard structure for CI/CD

```
.vite/
  generated/
    routes/
      members.php      ← Standard Laravel route file
    tests/
      MemberTest.php   ← Standard Pest test file
```

Existing tools see "normal" files.

### Challenge 5: Version Control

**Problem**: Large diffs in single file.

**Solution**:
- Git diff driver that understands blocks
- "Show block changes only" mode
- Block-level git blame

```bash
git diff --extended-sfc  # Show changes grouped by block
git blame --block=server Component.vue
```

---

## Roadmap

### Phase 1: Proof of Concept (Current)
- ✅ `<i18n>` block working
- ⏳ `<server>` block prototype
- ⏳ Documentation and examples

### Phase 2: Core Blocks (Q1 2026)
- `<server>` block (Laravel, Django, Express)
- `<sql>` block (Postgres, MySQL)
- `<validate>` block (Zod, Laravel)
- `<test>` block (Vitest, Pest)

### Phase 3: Advanced Features (Q2 2026)
- `<firestore>` block with real-time subscriptions
- `<config>` block for caching/permissions
- `<docs>` block with auto-generated sites
- Type generation and safety

### Phase 4: Tooling (Q3 2026)
- VS Code extension
- CLI tools
- Component marketplace
- Migration tools

### Phase 5: Community (Q4 2026)
- Official plugin registry
- Framework adapters (Rails, FastAPI, etc.)
- Educational resources
- Conference talks and workshops

---

## Community Strategy

### 1. Vite Community

**Goal**: Make custom blocks a first-class feature.

**Proposal to Vite Core**:
- Graceful handling of unknown blocks
- Plugin discovery protocol
- Block metadata API
- Official docs for custom block plugins

**Engagement**:
- RFC on Vite GitHub
- Demo at ViteConf
- Work with Vite maintainers

### 2. Vue Community

**Goal**: Position Extended SFCs as the natural evolution.

**Messaging**:
- "SFCs solved frontend cohesion. Let's solve full-stack cohesion."
- "We already trust build tools for CSS. Why not backend?"
- "This is what Vue 3's SFCs were preparing us for."

**Engagement**:
- Blog post series
- Vue Conf talk
- Community showcase projects
- Plugin marketplace

### 3. Framework Communities

**Goal**: Get framework maintainers on board.

**Approach**:
- Laravel: Show how it enhances Inertia.js
- Django: Python version of `<server>` block
- Rails: Hotwire integration
- FastAPI: Async Python backend blocks

**Benefits to Frameworks**:
- Better DX for their users
- Full-stack components showcase framework strengths
- Educational value (easier onboarding)

---

## Examples & Use Cases

### Use Case 1: Admin Dashboard

**Component**: `DashboardStats.vue`

```vue
<template>
  <div class="stats-grid">
    <StatCard
      v-for="stat in stats"
      :key="stat.label"
      :label="t(stat.label)"
      :value="stat.value"
      :trend="stat.trend"
    />
  </div>
</template>

<script setup lang="ts">
const { data: stats } = await useFetch(route('dashboard.stats'));
</script>

<server lang="php/laravel" route="/api/dashboard/stats" method="GET" middleware="auth" cache="5m">
use App\Models\{User, Project, Task};

return [
  [
    'label' => 'total_users',
    'value' => User::count(),
    'trend' => User::whereBetween('created_at', [now()->subWeek(), now()])->count()
  ],
  [
    'label' => 'active_projects',
    'value' => Project::where('status', 'active')->count(),
    'trend' => 5.2
  ],
  [
    'label' => 'tasks_completed',
    'value' => Task::where('status', 'completed')->count(),
    'trend' => -2.1
  ]
];
</server>

<i18n>
{
  "en": {
    "total_users": "Total Users",
    "active_projects": "Active Projects",
    "tasks_completed": "Completed Tasks"
  },
  "es_UY": {
    "total_users": "Usuarios Totales",
    "active_projects": "Proyectos Activos",
    "tasks_completed": "Tareas Completadas"
  }
}
</i18n>

<config>
{
  "cache": {
    "ttl": 300,
    "tags": ["dashboard", "stats"]
  },
  "permissions": ["view-dashboard"]
}
</config>

<test lang="vitest">
it('displays stats correctly', async () => {
  const wrapper = mount(DashboardStats);
  await nextTick();

  expect(wrapper.findAll('.stat-card')).toHaveLength(3);
});
</test>

<test lang="php/pest">
it('returns dashboard stats', function () {
  User::factory()->count(10)->create();
  Project::factory()->count(5)->create(['status' => 'active']);

  $response = $this->getJson(route('dashboard.stats'));

  $response->assertOk()
    ->assertJsonCount(3)
    ->assertJsonPath('0.value', 10);
});
</test>
```

**One file. Complete feature. Tests included. Fully translatable. Backend included.**

### Use Case 2: Real-time Chat

**Component**: `ChatRoom.vue`

```vue
<template>
  <div class="chat-room">
    <div class="messages">
      <Message
        v-for="msg in messages"
        :key="msg.id"
        :message="msg"
      />
    </div>

    <form @submit.prevent="sendMessage">
      <input v-model="newMessage" :placeholder="t('type_message')" />
      <button>{{ t('send') }}</button>
    </form>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ roomId: string }>();
const newMessage = ref('');

const sendMessage = () => {
  // Messages are automatically synced via <firestore subscribe>
  addMessage({ text: newMessage.value, userId: currentUser.id });
  newMessage.value = '';
};
</script>

<firestore collection="messages" as="messages" subscribe>
WHERE roomId == {{roomId}}
ORDER BY createdAt DESC
LIMIT 50
</firestore>

<firestore collection="messages" operation="add" as="addMessage">
{
  roomId: {{roomId}},
  text: {{text}},
  userId: {{userId}},
  createdAt: serverTimestamp()
}
</firestore>

<i18n>
{
  "en": {
    "type_message": "Type a message...",
    "send": "Send"
  },
  "es_UY": {
    "type_message": "Escribe un mensaje...",
    "send": "Enviar"
  }
}
</i18n>

<security lang="firestore">
// Firestore security rules
match /messages/{messageId} {
  allow read: if request.auth != null
    && resource.data.roomId in get(/users/$(request.auth.uid)).data.rooms;
  allow create: if request.auth != null
    && request.resource.data.userId == request.auth.uid;
}
</security>
```

**Real-time subscriptions, security rules, and UI in one file.**

### Use Case 3: E-commerce Product Card

**Component**: `ProductCard.vue`

```vue
<template>
  <article class="product-card">
    <img :src="product.image" :alt="product.name" />
    <h3>{{ product.name }}</h3>
    <p class="price">{{ formatPrice(product.price) }}</p>

    <button
      @click="addToCart"
      :disabled="!product.inStock"
    >
      {{ product.inStock ? t('add_to_cart') : t('out_of_stock') }}
    </button>
  </article>
</template>

<script setup lang="ts">
const props = defineProps<{ productId: string }>();

const addToCart = async () => {
  await post(route('cart.add'), { productId: props.productId });
  toast.success(t('added_to_cart'));
};
</script>

<sql name="product" as="product">
SELECT p.*, i.quantity > 0 as in_stock
FROM products p
LEFT JOIN inventory i ON p.id = i.product_id
WHERE p.id = :productId
</sql>

<server lang="php/laravel" route="/cart/add" method="POST" middleware="auth">
validate(['productId' => 'required|exists:products,id']);

$cart = auth()->user()->cart;
$cart->addItem($request->productId);

return response()->json(['success' => true]);
</server>

<i18n>
{
  "en": {
    "add_to_cart": "Add to Cart",
    "out_of_stock": "Out of Stock",
    "added_to_cart": "Added to cart!"
  },
  "es_UY": {
    "add_to_cart": "Agregar al Carrito",
    "out_of_stock": "Agotado",
    "added_to_cart": "¡Agregado al carrito!"
  }
}
</i18n>

<test lang="vitest">
it('disables button when out of stock', () => {
  const wrapper = mount(ProductCard, {
    props: {
      product: { inStock: false }
    }
  });

  expect(wrapper.find('button').attributes('disabled')).toBeDefined();
});
</test>

<test lang="php/pest">
it('adds product to cart', function () {
  $user = User::factory()->create();
  $product = Product::factory()->create();

  $response = $this->actingAs($user)
    ->postJson(route('cart.add'), ['productId' => $product->id]);

  $response->assertOk();
  expect($user->cart->items)->toHaveCount(1);
});
</test>

<docs lang="markdown">
# ProductCard

Displays a product with add-to-cart functionality.

## Props
- `productId` (string): The product ID to display

## Events
None

## Permissions
- Requires authentication to add to cart

## Database
Queries `products` and `inventory` tables.
</docs>
```

**Installable, testable, documented product card with backend logic included.**

---

## Advanced Concepts

### Cross-Component Dependencies

**Problem**: Component A needs data from Component B's backend.

**Solution**: Export/import blocks

```vue
<!-- UserProfile.vue -->
<server
  lang="php/laravel"
  route="/api/users/:id"
  export="getUserById"
>
use App\Models\User;

function getUserById($id) {
  return User::with('projects')->findOrFail($id);
}

return getUserById($userId);
</server>

<!-- UserProjects.vue -->
<server
  lang="php/laravel"
  route="/api/users/:id/projects"
  import="{ getUserById } from '../UserProfile.vue'"
>
$user = getUserById($userId);
return $user->projects;
</server>
```

Vite resolves imports and generates proper backend structure.

### Middleware Blocks

**Component-specific middleware**:

```vue
<middleware lang="php/laravel" name="check-subscription">
if (!auth()->user()->hasActiveSubscription()) {
  return redirect()->route('billing')
    ->with('warning', 'Subscription required');
}
</middleware>

<server route="/premium-feature" middleware="check-subscription">
// Only accessible to subscribed users
</server>
```

### Background Jobs

```vue
<job lang="php/laravel" queue="emails" trigger="onCreated">
use App\Mail\WelcomeEmail;

Mail::to($user->email)->send(new WelcomeEmail($user));
</job>
```

Runs when component emits `created` event.

### API Versioning

```vue
<server route="/api/v1/users" version="1">
// Old version
return User::all();
</server>

<server route="/api/v2/users" version="2">
// New version with pagination
return User::paginate(20);
</server>
```

Same component supports multiple API versions.

---

## Comparison with Existing Solutions

### vs. tRPC

**tRPC**: Type-safe API calls
```typescript
// server/router.ts
export const userRouter = router({
  getAll: publicProcedure.query(() => {
    return db.user.findMany();
  })
});

// client/component.tsx
const { data } = trpc.user.getAll.useQuery();
```

**Extended SFCs**: Same type-safety, colocated
```vue
<server route="/api/users" export="getAll">
return User::all();
</server>

<script setup>
const { data } = await useFetch(route('users.index'));
</script>
```

**Difference**: Extended SFCs keep backend in the component, tRPC separates it.

### vs. Blitz.js

**Blitz**: Zero-API layer
```typescript
// app/queries/getUsers.ts
export default async function getUsers() {
  return db.user.findMany();
}

// app/pages/users.tsx
const users = useQuery(getUsers);
```

**Extended SFCs**: Similar concept, more explicit
```vue
<server route="/api/users">
return User::all();
</server>
```

**Difference**: Blitz hides the API layer. Extended SFCs make it visible but colocated.

### vs. Remix Loaders

**Remix**: Loaders colocated with routes
```typescript
// routes/users.tsx
export async function loader() {
  return json(await db.user.findMany());
}

export default function Users() {
  const users = useLoaderData();
  return <div>{users.map(u => u.name)}</div>;
}
```

**Extended SFCs**: Similar, works with any framework
```vue
<server route="/users">
return User::all();
</server>

<template>
  <div v-for="user in users">{{ user.name }}</div>
</template>
```

**Difference**: Remix is React-only, file-based routing. Extended SFCs work with Vue/React/Svelte and any backend.

### vs. SvelteKit

**SvelteKit**: +page.server.js
```typescript
// +page.server.js
export async function load() {
  return { users: await db.user.findMany() };
}

// +page.svelte
<script>
  export let data;
</script>

{#each data.users as user}
  {user.name}
{/each}
```

**Extended SFCs**: Same idea, in one file
```vue
<server route="/users">
return User::all();
</server>

<template>
  <div v-for="user in users">{{ user.name }}</div>
</template>
```

**Difference**: SvelteKit uses separate files. Extended SFCs use blocks in same file.

---

## Open Questions

1. **Naming**: Is "Extended SFCs" the right term? Alternatives:
   - Full-Stack Components (FSC)
   - Unified Components
   - Multi-Layer Components
   - Complete Components

2. **Block Names**: Should we standardize?
   - `<server>` vs `<backend>` vs `<api>`
   - `<sql>` vs `<query>` vs `<data>`
   - `<validate>` vs `<schema>` vs `<rules>`

3. **Default Behavior**: When no handler is installed:
   - Strict mode (error)
   - Warn mode (console.warn)
   - Ignore mode (strip silently)
   - Which should be default?

4. **Framework Integration**: Should frameworks officially support this?
   - Laravel Vite plugin ships with `<server>` handler?
   - Django template adds Python block support?
   - Or keep it community-driven?

5. **Performance**: At what scale does this become a problem?
   - 100 components? 1000? 10,000?
   - Need benchmarks

6. **Security**: How do we prevent malicious blocks?
   - Sandboxing?
   - Code review requirements for npm packages?
   - Runtime checks?

---

## Call to Action

This document is a **living proposal**. Feedback, ideas, and critiques are welcome.

**Next Steps**:
1. Build working prototypes (in progress)
2. Create example projects
3. Write Vite RFC
4. Engage Vue community
5. Build the future of full-stack development

**Get Involved**:
- GitHub: (TBD - repository for extended-sfc)
- Discord: (TBD - community server)
- Twitter: #ExtendedSFCs

---

## Conclusion

Single File Components proved that **logical cohesion beats language separation**. But we stopped at the frontend.

Full-stack development is still fragmented:
- Frontend here
- Backend there
- Tests somewhere else
- Translations in another place

**Extended SFCs complete the vision**: One component, one file, entire feature.

The technology exists. The patterns are proven. The community is ready.

**Let's build components that are truly complete.**

---

## Appendix A: Plugin API Reference

*(To be completed with working implementation)*

## Appendix B: Migration Guide

*(To be completed once stable)*

## Appendix C: Best Practices

*(To be evolved with community experience)*

---

**Document Version**: 1.0
**Last Updated**: November 2025
**Status**: Draft for Review
