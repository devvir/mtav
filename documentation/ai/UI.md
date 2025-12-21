<!-- Copilot - Pending review -->
# Frontend UI Architecture - MTAV

**Last Updated:** December 2025
**Purpose:** Quick reference for frontend development and UI testing

An agent reading this document should understand the frontend structure well enough to identify key areas for testing and implement meaningful UI tests.

---

## Technology Stack

**Core Stack:**
- **Vue 3** - UI framework (Composition API only, no Options API)
- **TypeScript** - Type safety (strict mode enabled)
- **Vite** - Build tooling and dev server
- **Inertia.js** - Server-driven SPA (frontend for Laravel backend)
- **Tailwind CSS** - Utility-first styling

**Build & Testing:**
- **Vitest** - Unit/component tests (Vue components + composables, NOT E2E)
- **Pest Browser** (Playwright) - E2E browser testing (PHP-based via Pest)
- **Inertia useForm()** - Form state management (built-in to Inertia.js)

**Notable Libraries:**
- **Vue Konva** - Canvas rendering (spatial/lottery visualization)
- **Inertia UI Modal** - Modal/slideover support
- **Ziggy** - Type-safe route generation
- **Laravel Echo** - Real-time WebSocket updates

---

## Project Structure

```
resources/js/
├── app.ts              # Entry point - initializes Vue app, Inertia
├── components/         # Vue components (organized by feature)
├── composables/        # Reusable Vue composition functions (auto-imported)
├── layouts/            # Page layout templates (app sidebar, auth, settings)
├── pages/              # Full-page components (routed via Inertia)
├── state/              # Composables for UI state (theme, current project)
├── store/              # Storage utilities (localStorage, cookies)
├── lib/                # Utility functions (non-reactive)
├── plugins/            # Vue plugins
├── types/              # TypeScript type definitions
└── css/                # Global styles
```

**Key Rule:** Components auto-imported via Unplugin Vue Components - no manual imports needed for top-level components.

---

## Component Architecture

### Folder Organization

**`components/ui/`** - ⚠️ **DO NOT MODIFY**
- Third-party UI component library (Headless UI wrappers)
- Contains: buttons, inputs, dropdowns, cards, badges, etc.
- These are production dependencies - treat as read-only

**`components/`** (Custom Components)
- **`layout/`** - Navigation, sidebars, page containers
- **`forms/`** - Form system (generic Form component + fields)
- **`entities/`** - Entity-specific components (projects, families, events, lottery, etc.)
- **`dashboard/`** - Dashboard-specific components
- **`dropdown/`** - Custom dropdown wrapper (Radix UI based)
- **`filtering/`** - Filter UI components
- **`pagination/`** - Pagination controls
- **`alert/`** - Alert/toast messages
- **`avatar/`** - User avatars
- **`badge/`** - Status badges
- **`card/`** - Card containers
- **`flash/`** - Flash message system
- **`lottery/`** - Lottery system (preferences, management)
- **`plans/`** - Project plan visualization
- **`spatial/`** - Spatial/canvas components
- **`shared/`** - Shared utility components
- **`switches/`** - Toggle switches

### Form System

**Two approaches:**

#### Approach 1: FormService (Recommended for Entity CRUD)

**Purpose:** Automatically generate form specs from Laravel FormRequest validation rules

**How it works:**
1. Backend has FormRequest class: `App\Http\Requests\Create{Model}Request` or `Update{Model}Request`
2. FormRequest contains validation rules
3. Controller calls `FormService::make($model, FormType::CREATE)` → returns form data with auto-generated specs
4. Frontend receives `formSpecs` via Inertia props
5. `Form.vue` renders fields with proper types, constraints, validation

**The Source of Truth:** FormRequest validation rules
- Automatic type inference (email rule → email input)
- Automatic constraints (max:255 → maxLength)
- Automatic required flags
- Dependent selects resolved (e.g., family filtered by project)

**Example (Backend):**
```php
class CreateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'unit_type_id' => 'required|exists:unit_types,id',
        ];
    }
}
```

Controller:
```php
public function create()
{
    $form = FormService::make(new Project, FormType::CREATE);

    return inertia('Project/Create', [
        'formSpecs' => $form->specs(),
        'formAction' => $form->formAction(),
        'formTitle' => $form->formTitle(),
    ]);
}
```

Frontend automatically receives fully-typed FormSpecs:
```typescript
{
  name: {
    element: 'input',
    type: 'text',
    label: 'Name',
    required: true,
    maxLength: 255,
    value: '',
  },
  unit_type_id: {
    element: 'select',
    label: 'Unit Type',
    required: true,
    selected: null,
    options: { 1: 'Apartment', 2: 'House' },
  }
}
```

#### Approach 2: Manual FormSpecs (for Custom Forms)

**Purpose:** For forms that don't follow standard CRUD patterns (e.g., profile page, search filters)

**Example:**
```vue
<Form
  type="update"
  action="profile.update"
  :specs="{
    name: {
      element: 'input',
      label: 'Full Name',
      value: user.name,
    },
    preferences: {
      element: 'select',
      label: 'Theme',
      selected: user.theme,
      options: { light: 'Light', dark: 'Dark' }
    }
  }"
  title="Edit Profile"
/>
```

### Form Component: `Form.vue`

**Props:**
- `type: 'create' | 'update' | 'delete'` - Determines HTTP method and button text
- `action: string` - Route name (e.g., `'projects.store'`)
- `params: unknown` - Route parameters (e.g., `{ project: 123 }`)
- `title: string` - Form title displayed in modal
- `specs?: FormSpecs` - Field definitions (from FormService or manual)
- `buttonText?: string` - Custom submit button text

**How it works:**
1. Converts `specs` into Inertia form object
2. Renders `FormInput` or `FormSelect` for each field
3. Handles dependent selects automatically (watchers track parent changes)
4. Validates on submit, shows field errors
5. Wraps in modal (can be full page or modal)
6. Calls backend via Inertia, handles response

**Related Components:**
- `FormInput.vue` - Text input field
- `FormSelect.vue` - Select dropdown field
- `FormLabel.vue`, `FormError.vue` - Form UI primitives
- `FormSubmit.vue` - Submit button

**Smart Features:**
- Dependent selects with automatic filtering
- Modal-aware (prevents close while dirty)
- Error display and auto-clear on input
- Proper types and constraints passed to inputs

### Entity Components

**Pattern:** Each entity (Project, Family, Member, Event, etc.) has a folder with:
- `IndexPage.vue` - List view
- `ShowPage.vue` - Detail view
- `FormPage.vue` - Create/edit view
- `_shared/` - Shared entity-specific components

**Example:** `components/entities/project/`
- Shows all project-related UI components
- Other entities follow same pattern

### Lottery System

**Location:** `components/lottery/`

**Key Components:**
- `admin/LotteryManagement.vue` - Admin: lottery configuration and execution
- `member/PreferencesManager.vue` - Member: manage unit preferences
- Supporting components for drag-and-drop, unit selection, etc.

**Core Feature:** Preferences drag-and-drop with responsive grid layout

---

## State Management

**Pattern:** Mostly Inertia props with composable layer on top

### Inertia Props (Source of Truth)

**Backend → Frontend data flow:**
- Controllers send data via Inertia props
- Frontend receives via `usePage()` composable
- Data is reactive and updates on page transitions

**Auto-fetched on navigation:**
- `auth` - Current user, role flags, abilities
- `project` - Currently selected project
- Form data for pre-population
- Resource collections (projects, families, events, etc.)

**Usage in components:**
```typescript
const page = usePage();
const projects = computed(() => page.props.projects);
const formSpecs = computed(() => page.props.formSpecs);
```

### LocalStorage (Session State)

**Purpose:** UI state that doesn't need to persist across devices

**Composable:** `useLocalState(key, defaultValue)`
```typescript
const sidebarCollapsed = useLocalState('sidebar.collapsed', false);
```

**Use cases:**
- Sidebar collapsed/expanded state
- Filter preferences (within session)
- Temporary UI toggles

### Cookie Storage (Shared State)

**Pattern:** Pure UI state + shared state that backend knows about

**Utilities:** `setCookie()`, `getCookie()` from `@/lib/utils`

**Pure UI State (Theme):**
- Stored in localStorage (never sent to backend)
- Composable: `useTheme()` with `isDark`, `toggleTheme()`, `setTheme()`
- Persists across devices

**Shared State (Current Project):**
- Read/written in backend (user preferences)
- Just read in frontend
- Examples: selected project, last viewed page, user preferences
- Backend controls when/how these change

**Key principle:** Backend is authoritative for shared state. Frontend only reads and displays.

---

## Composables

**Auto-imported:** No need to manually import composables (unplugin-vue-components)

### Authentication & Authorization

**`useAuth.ts`**
- Provides: `iAmAdmin`, `iAmMember`, `iAmSuperadmin`, `currentUser`
- Also: `can.create(resource)`, `can.viewAny(resource)` - frontend authorization checks
- Never pass auth data from backend - always use these composables

### UI State Composables

**`state/useTheme.ts`** - Pure UI state (localStorage)
- `useTheme()` - Returns `isDark`, `toggleTheme()`, `setTheme()`
- `initializeTheme()` - Called on app init to restore saved preference
- 100% frontend-managed, never sent to backend

**`state/useCurrentProject.ts`** - Reads from Inertia props
- `currentProject` - Currently selected project (from props.project)
- `projectIsSelected` - Computed boolean
- Backend controls value; frontend just reads

**`state/useMemberGrouping.ts`** - Utility composable
- `useMemberGrouping()` - Groups members for display

### Translation & Data Utilities

**`useTranslations.ts`**
- `_('text')` - Translate English text to current language
- `locale` - Current locale (reactive)
- `setLocale()` - Change language
- **Frontend localization:** Keys are English text, translations in `lang/es_UY.json`

**`useResources.ts`**
- Utility for working with Inertia resources (API data)

### Utilities

**`useDates.ts`**
- `fromUTC(utcString)` - Convert UTC to local timezone for display
- `toUTC(localString)` - Convert local input to UTC for server

**`useDragAndDrop.ts`**
- Helper for drag-and-drop operations (used in lottery preferences)

**`useInitials.ts`**
- Extract initials from names (for avatars)

**`useLocalState.ts`**
- Simple localStorage wrapper for component state

**`useCsrfToken.ts`**
- CSRF token management, auto-refresh
- `autoRefreshCsrfToken()` - Called on app init

**`useDates.ts`**
- Timezone-aware date formatting and parsing

### Modals

**`useInertiaUIModal.ts`**
- `preventFormClosure()` - Prevent modal close while form is dirty
- Integration with inertiaui-modal library

### Flash Messages

**`components/flash/useFlashMessages.ts`**
- `useFlashMessages()` - Access flash message state
- Messages set by backend via Inertia props

---

## Testing

### Three Levels of Testing

**1. Unit/Feature Tests** - Pest (PHP, functional style)
- `tests/Feature/` - Integration tests with database
- `tests/Unit/` - Isolated unit tests
- Uses `universe.sql` fixture for fast, consistent data
- Run via `mtav pest --filter TestName`

**2. E2E Browser Tests** - Pest Browser (Playwright-based)
- `tests/Browser/` - Full browser workflows
- PHP-based via Pest Browser plugin
- Connects to Playwright server
- Uses `universe.sql` fixture
- Run via `mtav e2e`

**Example:**
```php
// tests/Browser/Invitation/InviteMemberTest.php
test('member can be invited', function () {
    visit('/login');
    screenshot();  // Take screenshot
    assertSee('email');
});
```

**3. Component/UI Tests** - Vitest (Vue components, NOT E2E)
- Component rendering
- User interactions (click, input)
- Props and v-model binding
- Event emissions
- Error states
- Composable logic

### Testing Best Practices

**For All Tests:**
- Use `universe.sql` fixture (20-30x faster than factories)
- Read fixture to know available test data (projects, families, members, etc.)
- Only create NEW records when testing creation itself

**Waiting for Data:**
- Pages use `.animate-pulse` CSS class while loading deferred data
- Wait for class to disappear instead of arbitrary delays
- Example: `cy.contains('.animate-pulse').should('not.exist')`
- Pattern: `cy.get('.animate-pulse').should('not.exist')`

**Form Testing (Playwright):**
- Fill form fields
- Submit and check for success/error
- Verify validation messages
- Check redirect or modal close

**Authorization Testing:**
- Test with different user roles (member vs admin vs superadmin)
- Verify buttons/features hidden from unauthorized users
- Use universe.sql fixture for pre-populated test data

---

## Entities & Data Flow

### Entity Definitions

**Location:** `resources/js/types/index.d.ts`

**Key Types:**
- `Project`, `Family`, `Member`, `Admin`, `User`
- `Unit`, `UnitType`
- `Event`, `Lottery` (extends Event)
- `Media`, `Log`

**Critical Facts:**
- All entities are **automatically converted** to JsonResource on backend
- Frontend receives transformed data with:
  - `abilities` - Authorization metadata (can view/update/delete/restore)
  - `can` - Shorthand for frontend authorization checks
  - Relationships are **conditionally loaded** (use `.whenLoaded()` pattern)

### Resource Transformation

**How it works:**
1. Controller returns a model/collection
2. Laravel automatically converts via `ConvertsToJsonResource` trait
3. Frontend receives transformed JsonResource representation

**Never do this:**
```typescript
// ❌ WRONG - Will double-convert
import { JsonResource } from '@/resources';
JsonResource.make(model);
```

**Do this instead:**
```typescript
// ✅ CORRECT - Return model directly from controller
return model; // Auto-converts to JsonResource
```

### Data Fetching

**Pattern:** Server-driven (no direct API calls from frontend)

- Inertia handles page loads and form submissions
- `usePage()` provides current page props
- Links trigger full page refreshes via Inertia
- `useForm()` from Inertia handles form submission with validation

---

## Forms & Validation

### Form Submission Flow

**Step 1:** Create form with `useForm()`
```typescript
const form = useForm({ name: '', project_id: null });
```

**Step 2:** Submit via Inertia
```typescript
form.submit('post', route('projects.store'), {
  preserveScroll: true,
  preserveState: true,
  onSuccess: () => { /* Handle success */ },
});
```

**Step 3:** Validation errors automatically populated
```typescript
form.errors.name; // "The name is required"
```

### Form Field Definitions

**FormSpecs interface:**
```typescript
{
  fieldName: {
    element: 'input' | 'select',
    label: 'Field Label',
    value: 'default',  // for input
    selected: null,    // for select
    options: [],       // for select
    placeholder: '',
    filteredBy: 'parentField', // for dependent selects
  }
}
```

---

## Authorization & Permissions

### Frontend Authorization

**Never check authorization in backend logic - use composables:**

```typescript
// ✅ CORRECT - Check in template/composable
if (iAmAdmin.value) { /* show admin panel */ }

// ❌ WRONG - Backend handles authorization
// (Don't pass is_admin from controller)
```

**Resource Abilities:**
Each resource includes `abilities` object:
```typescript
const project = ref<Project>({ ...data, abilities: { view: true, update: false, delete: false } });

// Show buttons conditionally
if (project.value.abilities.update) { /* show edit button */ }
```

### User Roles

**Defined in `useAuth.ts`:**
- `iAmAdmin` - Has `is_admin = true` flag
- `iAmMember` - Has `is_admin = false` flag
- `iAmSuperadmin` - Email in `config('auth.superadmins')`

---

## Routing & Navigation

### Type-Safe Routes

**Via Ziggy package:**
```typescript
// ✅ Type-safe route generation
route('projects.show', { project: 123 });

// Auto-imported via vite.config.ts
```

**Link Navigation:**
```vue
<!-- Inertia Link auto-imported -->
<Link href="/projects">Projects</Link>
```

### Modal Routes

**Special handling for modal views:**
- Some routes open in modal instead of full page
- Configured via Inertia UI Modal
- Component wrapping handled automatically

---

## Accessibility Requirements

**Target Audience:** Elderly users, people with disabilities, old devices

### Non-Negotiable Requirements

**Typography:**
- **16px minimum** body text (NEVER smaller)
- Scale UP on larger screens
- Generous line-height (1.5-1.75)
- Clear, readable fonts

**Color & Contrast:**
- **WCAG AA minimum:** 4.5:1 contrast ratio for text
- **AAA preferred:** 7:1 contrast ratio
- High contrast mode support
- **Preserve dark theme colors:**
  - Background: `hsl(210, 20%, 2%)`
  - Sidebar: `hsl(30, 30%, 6%)`

**Interactive Elements:**
- **Touch targets:** Minimum 44x44px on mobile
- **Focus indicators:** Visible 2px+ rings, high contrast
- **Keyboard navigation:** Full functionality without mouse
- **Clear affordances:** Buttons look clickable, links obvious

**Testing Checklist:**
- [ ] Keyboard navigation works completely
- [ ] Focus visible on all interactive elements
- [ ] Text contrast meets WCAG AA minimum
- [ ] Touch targets 44x44px on mobile
- [ ] Works with 200% browser zoom
- [ ] Screen reader announces meaningful content

---

## CSS & Styling

**Framework:** Tailwind CSS (utility-first)

**Color Palette:**
- Uses Tailwind defaults with custom colors
- Dark theme support via `dark:` prefix
- See `resources/css/app.css` for custom variables

**Layout System:**
- CSS Grid and Flexbox
- Responsive breakpoints: `sm`, `md`, `lg`, `xl`, `2xl`
- Container queries for component-level responsiveness (`@container`)

**Spacing Scale:**
- Tailwind default spacing (4px base unit)
- Custom variable names for typography
- Semantic use: `gap-wide-y` for vertical spacing

---

## Testing UI Components

### What to Test

**High-Priority Areas:**
1. Form components (input validation, submission, error display)
2. Entity list pages (filtering, pagination, authorization)
3. Entity detail pages (data display, edit button visibility)
4. Authorization (only admins see admin features)
5. Modals (open/close, form submission in modals)
6. Lottery system (preference management, drag-and-drop)

**Medium-Priority Areas:**
1. Navigation (links work, current page highlighted)
2. Flash messages (success/error display)
3. Theme switching (dark/light toggle)
4. Responsive layouts (mobile vs desktop)
5. Accessibility (keyboard nav, contrast)

---

## Performance Considerations

### Old Device Support

**Constraints:**
- Avoid heavy animations
- Large bundle size impact
- Lazy loading preferred
- Progressive data loading

**Optimization:**
- Conditional imports where possible
- Defer non-critical components
- Image lazy loading (if applicable)

### Bundle Size

**Current Dependencies:**
- Vue 3 core
- Inertia.js
- Tailwind CSS
- Form utilities
- Modal library
- Canvas library (for spatial rendering)

---

## Common Development Tasks

### Adding a New Page

1. Create `.vue` file in `resources/js/pages/`
2. Use default layout or specify custom layout
3. Receive props from backend via Inertia
4. Import and use components
5. Add route in Laravel backend

### Adding a New Component

1. Create `.vue` file in appropriate `components/` subfolder
2. Use `<script setup lang="ts">` with TypeScript
3. Auto-imported - no manual imports needed
4. Test with Vitest (component test)

### Adding a Composable

1. Create `.ts` file in `composables/` folder
2. Export function or computed refs
3. Auto-imported in components
4. Test with Vitest

### Translating UI Text

1. Use `_('English text')` from `useTranslations`
2. Add Spanish translation to `lang/es_UY.json` (alphabetical order)
3. Keep `lang/en.json` as empty `{}`

---

## Debugging Tips

**Vue DevTools:**
- Inspect component state (props, data, computed)
- Check Pinia store state
- Timeline of component updates

**Console Logging:**
- `usePage()` to inspect Inertia props
- `useAuth()` to check auth state
- Form errors: `form.errors`

**Browser DevTools:**
- Network tab: Check API calls (none - all server-driven)
- Application → Local Storage: Theme preference, UI state
- Accessibility Inspector: Check contrast, focus indicators

---

## Key Constraints & Gotchas

**Composition API Only:** No Options API, no class components

**Auto-imports:** Many items are globally available:
- Components (no import needed)
- Composables (no import needed)
- Inertia utilities: `useForm`, `usePage`, `Link`
- Ziggy: `route()`

**Resource Transformation:** NEVER manually convert models to resources - happens automatically

**Authorization:** Check `iAmAdmin`, `iAmMember` in composables, not controller props

**Modals:** Forms in modals must emit close event or modal won't close

**Form Submission:** Always use Inertia's `useForm()` and `.submit()` - don't use fetch

**Timezone:** Always use `fromUTC()` for display, `toUTC()` for input to server

---

## Learning Resources

See `KNOWLEDGE_BASE.md` for:
- Authorization architecture
- Resource transformation patterns
- Accessibility guidelines in detail

See `testing/E2E.md` for:
- Pest Browser (Playwright) testing setup
- How to run E2E tests
- Playwright server configuration

See `testing/PHILOSOPHY.md` for:
- Test organization
- Universe fixture documentation
- Test helpers and utilities
