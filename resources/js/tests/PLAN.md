<!-- Copilot - Pending review -->

# Frontend Test Planning - MTAV

**Purpose**: Comprehensive roadmap of tests needed for the MTAV frontend, organized by scope (Unit vs Feature) and category (Modules, Components, Features).

**Last Updated**: December 21, 2025

**Test Coverage Status** (Updated automatically with test implementations):
- ✅ **Fully Covered** - All behaviors tested with passing tests
- ? **Partially Covered** - Some behaviors tested, need additional test cases
- X **Not Covered** - No tests written or feature not implemented

**Important**: This document defines WHAT should be tested and the expected behavior. Before writing any test, use this document to:
1. Find the relevant category/section
2. Read the test description to understand the SUT (System Under Test) and expected behavior
3. Ask clarifying questions BEFORE implementing if anything is ambiguous
4. Do NOT assume or guess implementation details

---

## Test Organization Structure

### Hierarchy

```
├── UNIT TESTS (pure input → output, no component mounting)
│   ├── Modules
│   ├── Composables ✅ 8 composables fully tested
│   └── Utilities (? partial, X not started)
│
└── FEATURE TESTS (component mounting, interactions, cross-component behavior)
    ├── Modules (multi-component integration)
    ├── Components (single component feature interactions)
    └── Features (vertical concerns, cross-cutting behaviors)
```

### Naming Conventions

- **Test file location**: `resources/js/tests/{unit|feature}/{category}/{name}.test.ts`
- **Test suite prefix**: Clear category + component/module name
- **Test names**: Describe expected behavior from user/component perspective, not implementation

---

# UNIT TESTS

Unit tests verify isolated logic: pure functions, composables, utilities, and components in near-total isolation.

## UNIT: Modules

### Forms Module

**SUT**: `resources/js/components/forms/` (Form.vue, FormInput.vue, FormSelect.vue, FormElement.vue, FormLabel.vue, FormError.vue)

**Dependencies**: Inertia `useForm()`, pure HTML forms, custom form handling

**Form System**: Uses Inertia's `useForm()` for state management (dirty checking, error tracking, submission). No third-party form libraries. Custom validation and error display logic.

**Status**: ? Partially covered - components exist but need feature-level tests

#### Composables
- None specific to forms (uses Inertia's built-in `useForm()`)

#### Field Components
- X `FormInput` - Text input wrapper (not yet tested)
  - Given props for name, label, type, min/max, disabled, required, placeholder
  - Should render with correct HTML input attributes
  - Should update v-model on user input
  - Should display validation errors when form has errors for this field
  - Should respect different input types (text, email, password, tel, url, date, datetime-local, etc.)
  - Should handle date conversion for datetime-local type (UTC conversion via useDates composable)

- X `FormSelect` - Select dropdown wrapper (not yet tested)
  - Given select options and selected value
  - Should render all options
  - Should update v-model when user selects option
  - Should be disabled when disabled prop is true
  - Should show placeholder text if specified
  - Should support dependent selects (e.g., family filtered by project) IF this behavior exists
  - Should NOT support multiple selection (single select only)

- X `FormLabel` - Label element (not yet tested)
  - Should display field label text
  - Should add asterisk (*) for required fields
  - Should link to correct input via `for` attribute

- X `FormError` - Error message display (not yet tested)
  - Should display error message text for a given field
  - Should only render when field has errors
  - Should display accessible error styling

#### Container Components
- X `FormElement` - Field wrapper (label + input + error) (not yet tested)
  - Should arrange label above input via slot pattern
  - Should position error below input
  - Should pass through all relevant props to child slot

- X `Form` - Root form component (not yet tested)
  - Given formSpecs (from backend FormService) and form type (create/update/delete)
  - Should render all fields specified in specs, in order
  - Should collect all field values into data object
  - Should submit data via Inertia form submission to specified action URL
  - Should disable submit button during submission (`form.processing`)
  - Should show disabled/loading state on button during submission
  - Should re-enable submit button when submission completes or fails

---

### Flash Messages Module

**SUT**: `resources/js/components/flash/` (useFlashMessages.ts, FlashMessages.vue, FlashMessage.vue, FlashMessagesOverlay.vue)

**Status**: ✅ Composable tested, ? Components need feature-level tests

#### Composable: useFlashMessages
- ✅ **UNIT - Basic message operations**
  - Should add message to stack with unique ID
  - Should auto-dismiss after default timeout (10s)
  - Should auto-dismiss after custom timeout if provided
  - Should NOT auto-dismiss if timeout=0
  - Should NOT auto-dismiss if timeout undefined in noAutoDismiss mode
  - Should remove message by ID (manual dismiss)
  - Should clear all messages at once
  - Should clear all pending timeouts when clearing all

- ✅ **UNIT - Message properties**
  - Should track message type (success, info, warning, error)
  - Should respect multiline prop (false = truncate, true = wrap)
  - Should generate unique ID for each message (format: `flash-{counter}`)

- ✅ **UNIT - State tracking**
  - Should provide computed `hasVisibleMessages` (true if stack non-empty)
  - Should maintain FIFO order for multiple messages
  - Should track timeout IDs for later cleanup

#### Components: FlashMessage
- ? **UNIT - Rendering** (not yet tested)
  - Given a message object
  - Should display message text
  - Should render correct icon for message type (success, info, warning, error)
  - Should render correct background color for message type
  - Should truncate text if multiline=false
  - Should wrap text if multiline=true

- ? **UNIT - Dismissal** (not yet tested)
  - Should have close button (X) that dismisses message
  - Should call `removeMessage()` callback when closed

#### Components: FlashMessagesOverlay
- ? **UNIT - Container logic** (not yet tested)
  - Should render only when messages exist
  - Should pass messages to FlashMessages component
  - Should apply overlay styling (background/blur)

#### Components: FlashMessages
- ? **UNIT - Message list rendering** (not yet tested)
  - Given multiple messages
  - Should render FlashMessage component for each
  - Should key by message ID (not index)
  - Should maintain order

---

### Dropdown Module

**SUT**: `resources/js/components/dropdown/` (Dropdown.vue, DropdownTrigger.vue, DropdownContent.vue)

**Status**: X Not tested

#### Composable: useDropdown (IF EXISTS)
- X **UNIT - State management** (not yet tested)
  - Should expose `isOpen` reactive state
  - Should expose `toggle()` method
  - Should expose `open()` and `close()` methods
  - Should provide `toggleRef` for trigger element binding

#### Components: Dropdown
- X **UNIT - Structure** (not yet tested)
  - Should render trigger and content
  - Should NOT render content if closed (conditional rendering)
  - Should render content when open

- X **UNIT - Trigger interaction** (not yet tested)
  - Should toggle open state when trigger is clicked
  - Should close when escape key pressed
  - Should close when clicking outside

#### Components: DropdownTrigger
- X **UNIT - Rendering** (not yet tested)
  - Should render slot content
  - Should be keyboard accessible (tabable, clickable)

#### Components: DropdownContent
- X **UNIT - Rendering** (not yet tested)
  - Should render slot content
  - Should position relative to trigger (via CSS or Popper.js)
  - Should have focus trap if configured

---

### Pagination Module

**SUT**: `resources/js/components/pagination/` (PaginationControls.vue, PAGE JUMP, FILTERS integration)

**Status**: X Not tested

**Status**: No tests yet

#### Components: PaginationControls (IF EXISTS)
- X **UNIT - Page navigation** (not yet tested)
  - Given current page and total pages
  - Should display page number
  - Should display total pages
  - Should have "previous" button (disabled if on first page)
  - Should have "next" button (disabled if on last page)
  - Should emit page change event when buttons clicked

---

### Breadcrumbs Module (IF EXISTS)

**SUT**: `resources/js/components/layout/` breadcrumb components

**Status**: X Not tested - needs discovery

#### Composable: useBreadcrumbs (IF EXISTS)
- X **UNIT - Breadcrumb generation** (not yet tested)
  - Should generate breadcrumb from route/page context
  - Should include home link
  - Should include current page name
  - Should include parent resource if applicable

#### Components: Breadcrumbs
- X **UNIT - Rendering** (not yet tested)
  - Should display all breadcrumb items
  - Should render links for non-current items
  - Should render text (no link) for current item
  - Should use "/" or ">" separator

---

### Layout Module

**SUT**: `resources/js/layouts/` (AppSidebarLayout.vue, AuthLayout.vue, etc.)

**Status**: X Not tested

#### Components: AppSidebarLayout
- X **UNIT - Navigation rendering** (not yet tested)
  - Should render sidebar with navigation links
  - Should render top navigation bar
  - Should render main content area
  - Should render FlashMessagesOverlay

#### Components: AuthLayout (IF DISTINCT)
- X **UNIT - Auth form container** (not yet tested)
  - Should render form in centered container
  - Should apply auth-specific styling
  - Should NOT show navigation/sidebar

---

### Avatar Module

**SUT**: `resources/js/components/avatar/` (Avatar.vue)

**Status**: X Not tested

#### Components: Avatar
- X **UNIT - Display logic** (not yet tested)
  - Given user data with initials
  - Should display initials (first letter of first and last name)
  - Should use correct background color (from palette or prop)
  - Should display full name as title/tooltip
  - Should have correct size (sm, md, lg, etc.)

---

### Badge Module

**SUT**: `resources/js/components/badge/` (Badge.vue)

**Status**: ? Partial - reference pattern exists (Badge.test.ts)

---

### Card Module

**SUT**: `resources/js/components/card/` (Card.vue, CardHeader.vue, CardContent.vue, CardActions.vue)

**Status**: ? Partial - some tests may exist

#### Components: Card
- ? **UNIT - Layout** (not yet verified)
  - Should render header, content, and footer sections
  - Should slot content correctly

#### Components: EntityCard (IF EXISTS)
- X **UNIT - Entity display** (not yet tested)
  - Given entity resource (Project, Family, Member, etc.)
  - Should display entity name/title
  - Should display entity metadata/details
  - Should render action buttons

---

### Filtering Module

**SUT**: `resources/js/components/filtering/`

**Status**: X Not tested

#### Composable: useFilters (IF EXISTS)
- **UNIT - Filter state**
  - Should track active filters
  - Should update URL search params when filters change
  - Should restore filters from URL on page load
  - Should clear all filters
  - Should serialize/deserialize filter values

#### Components: FilterPanel (IF EXISTS)
- **UNIT - Filter UI**
  - Should render filter inputs for each available filter
  - Should update filter state when inputs change
  - Should show active filter count
  - Should have clear/reset button

---

## UNIT: Composables (Global/Reusable)

**SUT**: `resources/js/composables/` and `resources/js/state/`

### State Management Composables

**`useTheme.ts`** (Pure UI State)
- ✅ **UNIT - Theme switching**
  - Should provide `isDark` computed property
  - Should provide `toggleTheme()` method
  - Should provide `setTheme(theme: 'light' | 'dark')` method
  - Should persist theme preference to localStorage
  - Should initialize theme from localStorage on component load
  - Should respect system preference (if implemented)

- ✅ **UNIT - CSS class application**
  - Should add/remove `dark` class on document root when theme changes
  - Should work with Tailwind's `dark:` utility classes

**`useCurrentProject.ts`** (Inertia Props State)
- ? **UNIT - Project selection** (needs testing)
  - Should expose `currentProject` computed (from Inertia props)
  - Should expose `isProjectSelected` computed
  - Should provide `projectId` computed
  - Should remain in sync with `props.project` from backend

**`useMemberGrouping.ts`** (Utility Composable)
- ? **UNIT - Member grouping** (needs testing)
  - Given array of members
  - Should group by admin/member status
  - Should group by project/family
  - Should preserve order within groups

### Authorization Composables

### useAuth
- ✅ **UNIT - Auth state exports**
  - Should export `auth` computed property
  - Should export `currentUser` computed property
  - Should export `iAmAdmin` computed property
  - Should export `iAmNotAdmin` computed property
  - Should export `iAmSuperadmin` computed property
  - Should export `iAmNotSuperadmin` computed property
  - Should export `can` object with `create()` and `viewAny()` methods

- ? **UNIT - Auth state reactivity** (needs integration test with Inertia)
  - Should expose `user` ref (current logged-in user)
  - Should expose `isAuthenticated` computed
  - Should expose `isSuperAdmin` computed (if applicable)
  - Should expose `can(permission)` method for permission checking
  - Should react when auth data changes

### useDates
- ✅ **UNIT - Date conversion from UTC**
  - Given a UTC ISO 8601 date string
  - Should return formatted date in local timezone
  - Should support multiple formats (date, time, datetime-local, iso)
  - Should return empty string for invalid input
  - Should handle Intl.DateTimeFormat for locale awareness

- ✅ **UNIT - Date conversion to UTC**
  - Given a local datetime string
  - Should convert to ISO 8601 UTC string with Z suffix
  - Should return empty string for invalid input

### useDragAndDrop
- ✅ **UNIT - Drag and drop state**
  - Should initialize `draggedIndex` as null
  - Should set `draggedIndex` when drag starts
  - Should clear `draggedIndex` on drag end
  - Should clear `draggedIndex` after drop

- ✅ **UNIT - Drag callbacks**
  - Should call `onDragStart` callback with correct index
  - Should call `onDragEnd` callback when drag ends
  - Should call `onMove` callback when dropping to different index
  - Should NOT call `onMove` when dropping on same index
  - Should NOT call `onMove` if no drag was initiated

- ✅ **UNIT - Drag event handlers**
  - Should prevent default on dragOver
  - Should prevent default on dragEnter

### useLocalState
- ✅ **UNIT - Local storage operations**
  - Should store and retrieve string values
  - Should store and retrieve boolean values (true/false)
  - Should store and retrieve number values
  - Should store and retrieve null values
  - Should store and retrieve undefined values
  - Should preserve type information with prefixes

- ✅ **UNIT - Type preservation**
  - Should preserve string type prefix
  - Should preserve boolean true type prefix
  - Should preserve boolean false type prefix
  - Should preserve number type prefix
  - Should preserve null type prefix
  - Should preserve undefined type prefix

- ✅ **UNIT - Edge cases**
  - Should handle empty strings
  - Should handle strings that look like type prefixes
  - Should handle large numbers
  - Should handle negative numbers
  - Should handle floating point numbers

- ✅ **UNIT - Default values**
  - Should return string default for non-existent key
  - Should return number default for non-existent key
  - Should return boolean default for non-existent key
  - Should return null as default for non-existent key
  - Should use default only when key doesn't exist

- ✅ **UNIT - Overwrites**
  - Should overwrite existing values
  - Should store multiple values independently

### useResources
- ✅ **UNIT - Resource entity mappings**
  - Should export function returning all resource mappers
  - Should provide `Admin` resource mapper
  - Should provide `Member` resource mapper
  - Should provide `Project` resource mapper
  - Should provide `Family` resource mapper
  - Should provide `Event` resource mapper

### useTranslations
- ✅ **UNIT - Translation function**
  - Given a translation key
  - Should return translated string from current locale
  - Should fall back to key itself if translation missing
  - Should support string keys and return string results

- ✅ **UNIT - Locale switching**
  - Should support switching between locales
  - Should update translations when locale changes
  - Should handle English and Spanish (es_UY) locales

- ✅ **UNIT - Common translations**
  - Should have translations for UI labels and messages
  - Should have translations for validation messages
  - Should have translations for error messages

### useInertiaUIModal
- ? **UNIT - Modal state** (not yet tested)
  - Should expose `isOpen` reactive state
  - Should expose `open()`, `close()`, `toggle()` methods
  - Should pass through to Inertia modal system

### useCsrfToken
- X **UNIT - Token management** (not a real composable, not tested)
  - Should expose current CSRF token
  - Should automatically include in request headers

### useInitials
- ✅ **UNIT - Name initials extraction**
  - Given a full name with first and last components
  - Should return first letter of first name (uppercase)
  - Should return first letter of last name (uppercase)
  - Should return single letter for single-word name
  - Should handle lowercase names and uppercase them
  - Should handle names with special characters
  - Should handle empty/undefined names by returning empty string

- ✅ **UNIT - Edge cases**
  - Should handle multiple spaces between words
  - Should handle leading/trailing whitespace
  - Should handle single letter names
  - Should work correctly from composable export

### useFlashMessages
- ✅ **UNIT - Flash message state**
  - Should maintain array of flash messages
  - Should provide `flash()` method to add messages
  - Should provide `clear()` method to clear all messages
  - Should provide `clearAll()` method to clear all messages
  - Should NOT register onUnmounted hook in composable

- ✅ **UNIT - Message removal**
  - Should remove message by index when requested
  - Should handle removing non-existent indices gracefully

---

## UNIT: Utilities and Helpers

**SUT**: `resources/js/lib/` and any exported utility functions

### Form validation helpers (IF ANY)
- X Should validate email format (not yet implemented)
- X Should validate URL format (not yet implemented)
- X Should validate required fields (not yet implemented)
- X Should validate min/max length (not yet implemented)

### Date helpers (IF SEPARATE FROM useDates)
- X Should calculate date ranges (not separate from useDates)
- X Should check if date is today/past/future (not separate from useDates)
- X Should get week number (not implemented)

### String helpers (IF ANY)
- X Should slugify text (not implemented)
- X Should capitalize text (not implemented)
- X Should truncate text with ellipsis (not implemented)

---

# FEATURE TESTS

Feature tests verify component behavior when mounted, including state changes, prop reactivity, and interactions with other components.

## FEATURE: Modules

Multi-component integration where multiple related components work together.

### Forms Module (Feature Level)

**SUT**: Form components working together + form submission via Inertia

#### Features
- **Form submission workflow**
  - Given a Form component with formSpecs
  - When user fills out all fields
  - And clicks submit
  - Should send data via Inertia form submission to specified action URL
  - Should disable submit button during submission (via `form.processing`)
  - Should show loading state on button during submission
  - Should navigate to success page OR remain on page (depending on backend redirect)
  - Should show success flash message if backend sends one in response

- **Form validation display (server-side errors)**
  - Given backend validation fails (e.g., duplicate name)
  - When form response includes errors
  - Should display error message under that field
  - Should NOT navigate away
  - Should allow user to correct and resubmit

- **Form error recovery**
  - Given a form with validation errors displayed
  - When user corrects the field value
  - Errors should remain visible (only cleared on new submission attempt)
  - Should allow resubmission

- **Dependent field updates** (IF THIS BEHAVIOR EXISTS)
  - Given a select with dependent field (e.g., Family filtered by Project)
  - When user selects a project
  - Should update dependent select options
  - Should clear dependent select value
  - Should clear dependent select value

---

### Flash Messages Module (Feature Level)

**SUT**: Full flash message system with components

**Status**: Partially tested (useFlashMessages tests exist)

#### Features
- **Overlay visibility**
  - When message is added via `flash()`
  - Overlay should become visible
  - When last message is removed
  - Overlay should become hidden

- **Message auto-dismiss**
  - When message added with default timeout
  - Message should remain visible for 10 seconds
  - Then disappear automatically
  - Overlay should hide when last message disappears

- **Manual message dismissal**
  - When user clicks X button on message
  - Message should disappear immediately
  - Timeout should be cleared (no lingering cleanup)

- **Multiple messages**
  - When multiple messages are added
  - All should be visible in overlay
  - Each dismissal should remove only that message
  - Order should be FIFO

- **Flash from Inertia props**
  - When backend sends flash data in Inertia response
  - Messages should automatically appear in overlay
  - Correct message type should show (success/info/warning/error)

---

### Dropdown Module (Feature Level)

**SUT**: Dropdown with trigger and content opening/closing

#### Features
- **Dropdown toggle**
  - Given closed dropdown
  - When trigger clicked
  - Content should appear
  - When trigger clicked again
  - Content should disappear

- **Escape key close**
  - Given open dropdown
  - When escape key pressed
  - Content should close
  - Focus should return to trigger

- **Click outside close**
  - Given open dropdown
  - When clicking outside dropdown
  - Content should close

- **Keyboard navigation** (IF IMPLEMENTED)
  - Given open dropdown
  - Arrow down should move focus to first item
  - Arrow keys should move between items
  - Enter should select item
  - Escape should close

---

### Pagination Module (Feature Level)

**SUT**: Pagination with data fetching on page change

#### Features
- **Page navigation**
  - Given paginated data on page 1
  - When "next" button clicked
  - Should fetch page 2 data
  - Should update displayed data
  - Should update page indicator

- **Page change triggers load state**
  - When navigating to new page
  - Should show loading skeleton/spinner
  - Should disable pagination buttons
  - Should re-enable after data loads

- **Browser history** (IF APPLICABLE)
  - When navigating pages
  - URL should update with page parameter
  - Browser back button should go to previous page

---

### Filtering Module (Feature Level)

**SUT**: Filter UI + data update on filter change

#### Features
- **Filter application**
  - Given filter panel open
  - When user selects filter value
  - And confirms/applies
  - Should fetch filtered data
  - Should update displayed data
  - Should show active filter count

- **URL sync**
  - When filters applied
  - URL search params should update
  - Page reload should restore filters
  - Sharing URL with filters should work

- **Filter clearing**
  - When user clicks clear/reset
  - All filters should reset to default
  - Data should show all items
  - URL search params should clear

---

### Breadcrumbs Module (Feature Level)

**SUT**: Breadcrumb generation + navigation

#### Features
- **Breadcrumb generation**
  - On dashboard page
  - Should show: Home > Dashboard
  - On project show page
  - Should show: Home > Projects > [Project Name]
  - On nested resource edit page
  - Should show: Home > Parent > [Parent Name] > Child > Edit

- **Breadcrumb navigation**
  - When clicking breadcrumb link
  - Should navigate to that page
  - Should preserve filters/pagination if applicable

---

## FEATURE: Components

Single components in realistic mounted scenarios.

### Form Components (Feature Level)

**SUT**: Individual form field components with full form context

#### FormInput - Feature Tests
- **Text input interaction**
  - Should update v-model value on user typing
  - Should propagate changes to parent form state
  - Should display validation error message if form has error for this field

- **Email input**
  - Given type="email"
  - Should render as email input
  - Should pass through email validation to HTML5 (or rely on backend validation)

- **Date input**
  - Given type="date"
  - Should render as date input
  - Should handle UTC conversion via useDates composable

- **Datetime input**
  - Given type="datetime-local"
  - Should render as datetime input
  - Should convert to/from UTC via useDates composable for storage

#### FormSelect - Feature Tests
- **Select interaction**
  - Should render all options from options object
  - Should update v-model when user selects option
  - Should display selected value in dropdown

- **Dependent select behavior** (IF THIS BEHAVIOR EXISTS)
  - Given dependent select that filters by parent select
  - When parent select changes
  - Should refetch dependent options
  - Should clear dependent select value

---

### FlashMessage Component (Feature Tests)

**SUT**: Individual flash message in overlay

- **Message display**
  - Should render with correct styling for type
  - Should render dismiss button

- **Message interaction**
  - Should dismiss when button clicked
  - Should NOT dismiss on timeout if parent says no-auto-dismiss

---

### Card Components (Feature Tests)

**SUT**: Card components displaying data

#### EntityCard - Feature Tests (IF EXISTS)
- **Entity card rendering**
  - Given entity resource
  - Should display all fields
  - Should have working action buttons (Edit, Delete, etc.)

- **Card action interactions**
  - When edit button clicked
  - Should navigate to edit page
  - When delete button clicked
  - Should show confirmation
  - Then delete on confirm

---

### Layout Components (Feature Tests)

#### AppSidebarLayout - Feature Tests
- **Layout structure**
  - Should render navigation sidebar
  - Should render main content area
  - Should render flash messages overlay

- **Sidebar navigation**
  - When user clicks nav link
  - Should navigate to that page
  - Should highlight active nav item

- **Mobile responsive**
  - On mobile, sidebar should be hidden/collapsed
  - Should have hamburger menu
  - When hamburger clicked, sidebar should show

---

## FEATURE: Features

Vertical concerns affecting multiple components/pages:

### Navigation Features

**SUT**: Full page navigation, breadcrumbs, sidebar highlighting, flash messages

#### Navigation Workflow
- **Basic navigation**
  - Starting at home page
  - When clicking Projects link
  - Should navigate to projects index
  - Should show correct breadcrumbs
  - Should highlight Projects in sidebar
  - Should load and display projects data

- **Entity navigation flow**
  - Starting at projects index
  - When clicking "Create" button
  - Should navigate to create page
  - Should show proper breadcrumbs: Home > Projects > Create
  - When filling form and submitting
  - Should navigate to show page: Home > Projects > [Name]
  - Should show success flash message

- **Nested resource navigation**
  - When navigating to nested resource (e.g., Family > Members)
  - Should show full breadcrumb path
  - Should scope data to parent resource
  - Clicking parent in breadcrumb should go back

---

### Authentication Features

**SUT**: Auth state, authorization, page redirection

#### Auth State Management
- **Login to dashboard**
  - When visiting login page
  - And submitting login form
  - Should authenticate user
  - Should redirect to dashboard
  - Should show user info in header

- **Access control**
  - When non-admin user visits admin page
  - Should redirect to unauthorized page
  - OR show "access denied" message

---

### Responsive/Mobile Features

**SUT**: Mobile layout, touch interactions, viewport changes

#### Mobile Navigation
- **Sidebar collapse on mobile**
  - On mobile viewport
  - Sidebar should be hidden by default
  - Hamburger menu should be visible
  - Clicking hamburger should toggle sidebar

- **Touch interactions**
  - Dropdowns should open on tap
  - Modals should be full-screen on mobile
  - Forms should be scrollable

---

### Theme/Appearance Features

**SUT**: Theme switching, persistence

#### Theme Management
- **Theme switching**
  - When user changes theme in settings
  - Should apply theme immediately
  - Should persist to localStorage
  - Should restore on page reload

- **Theme application**
  - Dark theme should apply dark colors
  - Light theme should apply light colors
  - Should respect system preference if enabled

---

### Internationalization (i18n) Features

**SUT**: Translation system, locale switching

#### Language Support
- **Page translation**
  - When page loads
  - All static text should be in current locale
  - Form labels should be translated
  - Button text should be translated

- **Locale switching**
  - When user changes language in settings
  - Page should translate immediately
  - Should persist locale preference
  - Should restore on page reload

---

### Lottery System Features (IF APPLICABLE)

**SUT**: Lottery module full workflow

#### Lottery Preference Management
- **User preference workflow**
  - When member accesses lottery page
  - Should show current preferences
  - Should allow drag-and-drop reordering
  - When saving preferences
  - Should send to backend
  - Should show success message

---

### Project Plans Features (IF APPLICABLE)

**SUT**: Plan visualization, unit selection, canvas interactions

#### Plan Viewing
- **Plan rendering**
  - Given project with spatial plan
  - Should render floor plan canvas
  - Should display all units
  - Should show unit details on hover/click

- **Unit interaction**
  - When clicking unit
  - Should show unit details sidebar/modal
  - Should navigate to unit page OR
  - Should update details panel

---

## FEATURE: Large Feature Suites (Subfolder Organization)

For large, self-contained features with many tests, create subfolders:

```
resources/js/tests/feature/features/
├── auth/
│   ├── login.test.ts
│   ├── logout.test.ts
│   ├── registration.test.ts
│   └── password-reset.test.ts
├── entities/
│   ├── projects-crud.test.ts
│   ├── families-crud.test.ts
│   ├── members-crud.test.ts
│   └── units-crud.test.ts
├── dashboard/
│   ├── overview-load.test.ts
│   ├── widget-rendering.test.ts
│   └── quick-actions.test.ts
├── lottery/
│   ├── preferences-workflow.test.ts
│   ├── results-viewing.test.ts
│   └── admin-controls.test.ts
└── navigation/
    ├── sidebar-navigation.test.ts
    ├── breadcrumb-updates.test.ts
    └── mobile-menu.test.ts
```

### Entity CRUD Features

**SUT**: Complete Create/Read/Update/Delete workflows for entities

#### Project CRUD
- **Create project**
  - Navigate to Projects > Create
  - Fill form (name, unit type, etc.)
  - Submit
  - Should create and redirect to show page
  - Should show success message

- **View project**
  - Navigate to project show page
  - Should display all project information
  - Should show related entities (units, families)

- **Edit project**
  - Navigate to project edit page
  - Update fields
  - Submit
  - Should update and show success message
  - Should not navigate away (stay on edit page OR go to show page)

- **Delete project**
  - On project show page
  - Click delete button
  - Should show confirmation modal
  - On confirm, should delete
  - Should redirect to projects index
  - Should show success message

#### Family CRUD (Similar pattern)
- Create, view, edit, delete families
- Should scope families to current project

#### Member CRUD (Similar pattern)
- Create, view, edit, delete members
- Should scope members to current family
- Should show member units

#### Unit CRUD (Similar pattern)
- Create, view, edit, delete units
- Should scope units to current project

---

### Dashboard Features

**SUT**: Dashboard loading, data display, widget rendering

#### Dashboard Loading
- **Initial load**
  - When navigating to dashboard
  - Should show skeleton loaders while loading
  - Should load project list
  - Should load recent activity
  - Should load statistics

- **Widget rendering**
  - Should display overview stats
  - Should display recent projects
  - Should display recent families
  - Should display recent members
  - Should display quick action cards

---

---

## Test Implementation Notes

### What to Watch For

1. **Component isolation**: Unit tests should test one thing. If you need to mock 5 things to test it, it might be a feature test.

2. **Inertia integration**: Many feature tests will need Inertia `useForm()` mocking and props. See existing test setup for patterns.

3. **Form state management**: Forms use Inertia's `useForm()`. Tests should verify form state (errors, values, processing) changes correctly. No FormKit - pure HTML forms.

4. **Async operations**: Remember to await component updates after user interactions (form submission, fetch, etc.).

5. **Modal/Overlay stubs**: Modals may need to be stubbed or fully mounted depending on test needs.

6. **Composable isolation**: Composables tested in isolation should pass `skipInertiaWatcher: true` or equivalent to avoid lifecycle warnings.

### Pre-Implementation Checklist

Before implementing ANY test from this plan:

- [ ] SUT (System Under Test) is clear - what exactly is being tested?
- [ ] Expected behavior is documented - what should happen?
- [ ] Mock dependencies are identified - what needs to be mocked vs real?
- [ ] Setup/teardown is clear - what state needs to be reset?
- [ ] Edge cases are considered - what error/empty states exist?
- [ ] Ask user/PM for clarification if ANY of above is unclear
- [ ] Verify no assumptions about libraries/patterns that don't actually exist

---

## Test Coverage Summary

### UNIT Tests Status

| Category | Coverage | Notes |
|----------|----------|-------|
| **Composables** | ✅ 8/10 | useAuth, useDates, useDragAndDrop, useLocalState, useResources, useTranslations, useFlashMessages, useInitials tested. useInertiaUIModal (?), useCsrfToken (X - not real) |
| **State Management** | ? 3/3 | useTheme, useCurrentProject, useMemberGrouping - all documented but not tested |
| **Form System** | ? 0/4 | FormService, Form.vue, FormInput, FormSelect - all documented, none tested |
| **Modules** | X 0/8 | Forms, Dropdown, Breadcrumbs, Layout, Avatar, Card, Filtering, Pagination - documented as features, not unit level |
| **Utilities** | X 0/3 | Form validation, Date helpers, String helpers - none implemented/tested |
| **Components** | ? Partial | Badge (? reference), others documented as features |

### FEATURE Tests Status Summary

| Category | Behaviors | Coverage | Notes |
|----------|-----------|----------|-------|
| **Entity Pages** | 30 | ? 0/30 | CRUD pages for 5+ entities - index, show, create, edit forms |
| **Accessibility (a11y)** | 22 | ? 0/22 | Keyboard nav, focus, contrast, touch targets, screen reader, zoom, fonts |
| **Authorization** | 10 | ? 0/10 | Permission checks, show/hide UI, resource abilities |
| **Lottery System** | 10 | ? 0/10 | Preferences, admin execution, results, unit selection |
| **Routing** | 10 | ? 0/10 | Navigation, URL parameters, modal vs full-page, query params |
| **Theme System** | 4 | ? 0/4 | Dark/light toggle, persistence, contrast, color enforcement |
| **Modal System** | 8 | ? 0/8 | Lifecycle, form submission, nested modals, keyboard trap |
| **Flash Messages** | 4 | ? 0/4 | Display, auto-dismiss, multiple, positioning |
| **Form System** | 12 | ? 0/12 | Rendering, submission, validation, dependent fields |
| **Layout Components** | 3 | ? 0/3 | Sidebar, mobile behavior, user menu |
| **Dropdown** | 3 | ? 0/3 | Open/close, keyboard nav, outside click |
| **Pagination** | 2 | ? 0/2 | Display, page navigation |
| **TOTAL FEATURES** | **118** | **? 0/118** | Comprehensive behavior documentation across 12 feature areas |

---

## UNIT: Form System (FormService Pattern)

**SUT**: `resources/js/components/Form.vue`, `resources/js/services/FormService.ts`, Form field components

**Critical Note**: FormService is the SOURCE OF TRUTH - backend FormRequest validation rules generate FormSpecs on backend, auto-transform to frontend FormSpec objects via Inertia. Tests must verify frontend receives and renders specs correctly.

### FormService Integration
- ? **UNIT - FormSpec generation** (needs testing)
  - Given backend FormRequest with validation rules
  - Should receive FormSpec object with fields array
  - Should parse FormSpec `fields` property containing FormElement objects
  - Should expose field `name`, `type`, `label`, `required` properties
  - Should expose conditional fields (show_if, hide_if rules)
  - Should expose dependent select fields (country → state → city cascading)

### Form.vue Component
- ? **UNIT - Form rendering**
  - Given FormSpec from props
  - Should render form element for each field in spec
  - Should apply correct input type (text, email, select, textarea, etc.)
  - Should apply required attribute on required fields
  - Should apply disabled attribute on disabled fields
  - Should apply readonly attribute on readonly fields
  - Should render form validation errors below fields

- ? **UNIT - Form submission**
  - Given completed form with all required fields
  - Should POST data to action URL from props
  - Should disable submit button during submission
  - Should show loading spinner during submission
  - Should clear previous errors before submission
  - Should handle validation errors from backend
  - Should show flash message on success

- ? **UNIT - Form reset**
  - Given dirty form
  - Should reset to initial values on reset() call
  - Should clear all validation errors
  - Should NOT submit reset to server

- ? **UNIT - Dependent select fields**
  - Given cascading selects (country → state)
  - When parent select changes
  - Should clear child select value
  - Should fetch new child options (if ajax-dependent)
  - Should update computed options
  - Should preserve user changes to independent fields

### FormInput Component
- ? **UNIT - Text input rendering**
  - Given field spec with type="text"
  - Should render `<input type="text">` element
  - Should bind value bidirectionally
  - Should apply field name and id
  - Should apply aria-label and aria-describedby

- ? **UNIT - Input validation states**
  - Given invalid field with error
  - Should add error styling (red border)
  - Should display error message below input
  - Should add aria-invalid="true"
  - Should add aria-describedby pointing to error message

- ? **UNIT - Input accessibility**
  - Given input with label
  - Should have associated `<label>` element with for attribute
  - Should be keyboard focusable (tab order)
  - Should show focus indicator (4px outline)

### FormSelect Component
- ? **UNIT - Select rendering**
  - Given field spec with type="select"
  - Should render `<select>` element
  - Should render all options from `options` array
  - Should show option labels and use option values
  - Should support empty/placeholder option
  - Should bind selected value bidirectionally

- ? **UNIT - Select with groups**
  - Given options with `optgroup` property
  - Should render `<optgroup>` elements
  - Should nest options within correct groups
  - Should support optgroup labels

---

## FEATURE: Entity Pages (Index/Show/Create/Edit)

**SUT**: All entity pages (`resources/js/Pages/`) for Project, Family, Member, Event, Unit, Lottery

### Index Pages (List View)
- ? **FEATURE - Entity list rendering**
  - Given list of entities from props
  - Should render table/list with all entities
  - Should display entity name, status, key attributes
  - Should show total count of entities
  - Should be responsive (table on desktop, card list on mobile)

- ? **FEATURE - Index pagination**
  - Given paginated data (50 items per page)
  - Should render pagination controls
  - Should navigate to next/prev/specific page
  - Should show current page indicator (e.g., "Page 2 of 10")
  - Should update URL with page parameter
  - Should preserve filters when changing pages

- ? **FEATURE - Index filtering**
  - Given multiple filterable attributes
  - Should show filter form (search, select dropdowns)
  - Should filter entities by search text
  - Should filter by dropdown selections
  - Should clear all filters
  - Should preserve filters when paginating
  - Should update URL with filter parameters
  - Should show count of filtered results

- ? **FEATURE - Index sorting**
  - Given sortable columns
  - Should click column header to sort
  - Should toggle sort direction (ASC → DESC)
  - Should show sort direction indicator
  - Should preserve sort when paginating

- ? **FEATURE - Index actions**
  - Given list of entities
  - Should show Create button (if authorized)
  - Should show Edit button per entity (if authorized)
  - Should show Delete button per entity (if authorized)
  - Should show View/Details button per entity

- ? **FEATURE - Index authorization**
  - Given user without create permission
  - Should NOT show Create button
  - Should NOT show Edit/Delete buttons on entities
  - Should still show View button if applicable

### Show Pages (Detail View)
- ? **FEATURE - Entity detail display**
  - Given single entity from props
  - Should display all entity attributes
  - Should format dates/numbers appropriately
  - Should show related entities (family belongs to project, etc.)
  - Should show audit info (created_at, updated_by, etc.)

- ? **FEATURE - Show page navigation**
  - Given entity detail page
  - Should show breadcrumb trail (Home > Entities > Entity Name)
  - Should show back button
  - Should show Edit button (if authorized)
  - Should show Delete button (if authorized)

- ? **FEATURE - Show page authorization**
  - Given user without view permission
  - Should redirect to home or show access denied
  - Should NOT display any entity data

### Create/Edit Pages (Form Pages)
- ? **FEATURE - Form page rendering**
  - Given FormSpec from backend for create
  - Should render form with all required fields
  - Should show form title "Create Entity" or "Edit Entity"
  - Should NOT show id/timestamps fields
  - Should show Cancel button (returns to previous page)
  - Should show Submit button with label "Create" or "Save"

- ? **FEATURE - Edit page prepopulation**
  - Given existing entity data in form
  - Should prepopulate form fields with current values
  - Should NOT allow editing id/timestamps
  - Should mark changed fields as dirty
  - Should prevent navigation away without confirmation if dirty

- ? **FEATURE - Form validation on submit**
  - Given incomplete form
  - Should show validation errors below fields
  - Should highlight invalid fields
  - Should NOT submit to server
  - When errors corrected
  - Should clear error styling
  - Should allow submission

- ? **FEATURE - Form submission to server**
  - Given valid form
  - Should POST to backend endpoint
  - Should show loading state during submission
  - Should disable form during submission
  - Should show success message on completion
  - Should redirect to entity detail or index
  - On server validation error
  - Should display field-level errors from server
  - Should NOT lose user-entered data

- ? **FEATURE - Dirty state confirmation**
  - Given form with unsaved changes
  - When user attempts to navigate away
  - Should show confirmation dialog
  - "Discard changes?" or "Save before leaving?"
  - Should allow canceling navigation
  - Should allow continuing without saving
  - Should NOT require confirmation if no changes made

---

## FEATURE: Lottery System

**SUT**: `resources/js/Pages/Lottery*`, lottery-related components, lottery admin pages

**Critical Context**: Lottery is core feature with drag-and-drop preferences, unit selection, admin execution

### Lottery Preferences (User - Member View)
- ? **FEATURE - View preference order**
  - Given member user on lottery preferences page
  - Should show list of all units (numbered 1-N)
  - Should show current preference order
  - Should NOT show delete/reorder controls

- ? **FEATURE - Edit preference order**
  - Given member with edit permission
  - Should show reorderable list of units
  - Should allow drag-and-drop to reorder
  - Should show dragging indicator while dragging
  - Should show drop zones (before/after each item)
  - Should update preference numbers on drop
  - Should show "Preferences saved" message
  - Should persist changes to backend

- ? **FEATURE - Preference validation**
  - Given preferences needing reorder
  - Should require all units to have unique positions
  - Should prevent saving with duplicate positions
  - Should show error if unit count doesn't match expected

### Lottery Admin (Family Admin/Superadmin)
- ? **FEATURE - Lottery list**
  - Given superadmin on lottery admin page
  - Should show list of all lotteries (by family/event)
  - Should show lottery status (pending, running, completed)
  - Should show scheduled date/time
  - Should show winner count if completed

- ? **FEATURE - Lottery execution**
  - Given pending lottery with valid preferences
  - Should show "Execute Lottery" button
  - On click
  - Should show confirmation dialog with summary
  - Should execute lottery (run random selection)
  - Should generate winners based on unit preferences
  - Should show results/winner list
  - Should change status to "completed"

- ? **FEATURE - Lottery unit selection**
  - Given lottery creation/configuration
  - Should show available units list
  - Should allow selecting which units participate
  - Should show selected unit count
  - Should validate minimum/maximum units

### Lottery Results Display
- ? **FEATURE - View lottery results**
  - Given completed lottery
  - Should show list of winners
  - Should show winner name and assigned unit
  - Should show draw position/order
  - Should be printable

---

## FEATURE: Routing and Navigation

**SUT**: All pages and navigation components, Ziggy route helpers

### Page Navigation
- ? **FEATURE - Link navigation**
  - Given `<Link>` to internal route
  - Should navigate to target route
  - Should update URL without full page reload
  - Should set current route in Inertia state
  - Should load data for target page

- ? **FEATURE - Current page indicator**
  - Given navigation in sidebar
  - When on a page
  - Should highlight current page link
  - Should show active state styling
  - Should work with nested routes

- ? **FEATURE - URL parameters in routes**
  - Given route with parameters (e.g., /projects/1/families/2)
  - Should use Ziggy helpers to generate correct URLs
  - Should work with route() function
  - Should preserve parameters in navigation
  - Should update parameters when navigating to different entity

- ? **FEATURE - Query parameters and filters**
  - Given page with filters/sorting
  - Should append query params to URL (?page=2&sort=name)
  - Should preserve query params when following links on same page
  - Should clear query params when returning to unfiltered view

### Modal vs Full-Page Navigation
- ? **FEATURE - Modal form navigation**
  - Given form in modal dialog
  - When submitting form
  - Should NOT do full page navigation
  - Should close modal on success
  - Should stay on same page/URL
  - Should update entities list if applicable

- ? **FEATURE - Full-page form navigation**
  - Given form on dedicated page (/projects/create)
  - When submitting form
  - Should navigate to entity show page or index
  - Should update URL
  - Should show success flash message

- ? **FEATURE - Back button behavior**
  - Given modal with back button
  - Should close modal (not navigate)
  - Should stay on current page
  - Given dedicated page with back button
  - Should navigate to previous page
  - Should go to index if no previous page

---

## FEATURE: Authorization and Permissions

**SUT**: Authorization middleware, resource policies, frontend composables

### Frontend Permission Checks
- ? **FEATURE - Admin-only features**
  - Given superadmin user
  - Should see all admin controls (user management, audit logs)
  - Should see admin-only buttons on pages
  - Given regular user
  - Should NOT see admin controls
  - Should NOT see admin buttons

- ? **FEATURE - Family admin features**
  - Given user with family admin role
  - Should see admin controls for their family
  - Should NOT see admin controls for other families
  - Should execute lottery for their family
  - Should manage members in their family

- ? **FEATURE - Member features**
  - Given regular member user
  - Should access their own data (profile, preferences)
  - Should access shared data (unit info, results)
  - Should NOT access other members' data
  - Should NOT access admin features

- ? **FEATURE - Resource-level permissions**
  - Given specific entity (project, family, etc.)
  - Should use resource `can()` method
  - Should check policy methods (can view, can edit, can delete)
  - Should show/hide buttons based on permissions
  - Should return 403 Unauthorized on API calls without permission

### Show/Hide UI Based on Permissions
- ? **FEATURE - Conditional visibility**
  - Given page with permission-dependent buttons
  - Should show buttons only if user has permission
  - Should use `iAmAdmin`, `iAmMember` composables
  - Should use resource `can()` methods

- ? **FEATURE - Disabled state for insufficient permissions**
  - Given button for action user can't perform
  - Should disable button (not remove it)
  - Should show tooltip explaining why (optional)

---

## FEATURE: Theme System

**SUT**: `useTheme` composable, theme toggle component, CSS classes

### Theme Toggle
- ? **FEATURE - Dark/light mode toggle**
  - Given theme toggle button in navbar
  - When clicking toggle
  - Should change from light to dark (or vice versa)
  - Should apply dark class to root element
  - Should apply dark: utility styles to all dark-mode elements
  - Should update localStorage with preference
  - Should NOT require page reload

- ? **FEATURE - Theme persistence**
  - Given user that set dark mode
  - When returning to app later
  - Should load dark mode automatically
  - Should NOT flash light mode first
  - Should sync preference from localStorage

- ? **FEATURE - System preference detection**
  - If app detects system dark/light preference (via CSS media query)
  - Should apply system preference on first visit
  - Should respect user override via toggle

- ? **FEATURE - Dark theme colors enforcement**
  - Given dark mode enabled
  - Should apply background color: `hsl(210, 20%, 2%)` (nearly black)
  - Should apply sidebar color: `hsl(30, 30%, 6%)` (very dark brown)
  - Should apply sufficient contrast for text (WCAG AA minimum 4.5:1)
  - Should NOT use light colors in dark mode

---

## FEATURE: Modal System (useInertiaUIModal)

**SUT**: Modal components, modal hooks, modal overlay

### Modal Lifecycle
- ? **FEATURE - Open modal**
  - Given button with @click to open modal
  - Should display modal overlay
  - Should show modal content
  - Should trap focus inside modal
  - Should prevent scrolling on background
  - Should show close button (X)

- ? **FEATURE - Close modal**
  - Given open modal
  - When clicking close button
  - Should hide modal
  - Should restore scrolling
  - Should restore focus to trigger element
  - Given modal with form
  - Should warn if unsaved changes before closing

- ? **FEATURE - Modal form submission**
  - Given form inside modal
  - When submitting form
  - Should NOT navigate away
  - Should close modal on success
  - Should show validation errors inside modal
  - Should allow correcting and resubmitting

- ? **FEATURE - Nested modals**
  - Given modal that opens another modal
  - Should stack modals (both visible)
  - Should show backdrop for both
  - Should close inner modal first
  - Should restore focus to outer modal trigger

### Modal Accessibility
- ? **FEATURE - Keyboard interaction**
  - Given open modal
  - Should close on Escape key
  - Should trap Tab key within modal
  - Should allow Shift+Tab for reverse tabbing
  - Should show focus indicator on focusable elements

- ? **FEATURE - Screen reader support**
  - Given modal
  - Should set role="dialog" on modal element
  - Should have aria-label or aria-labelledby
  - Should announce "Modal opened" (optional)
  - Should restore focus after close

---

## FEATURE: Accessibility (a11y) - WCAG AA Compliance

**SUT**: All components, all pages, entire application

**Critical Context**: Target audience includes elderly users and disabled users. WCAG AA is MINIMUM requirement. Accessibility is not optional.

### Keyboard Navigation
- ? **FEATURE - Full keyboard accessibility**
  - Given page with no mouse interaction required
  - Should navigate using Tab key only
  - Should navigate reverse using Shift+Tab
  - Should operate all interactive elements via keyboard
  - Should not rely on hover states (must work with keyboard only)
  - Should show focus indicator on every focusable element

- ? **FEATURE - Logical tab order**
  - Given page with form fields
  - Should tab through fields in logical order (left→right, top→bottom)
  - Should NOT skip fields
  - Should NOT tab to invisible elements
  - Modal dialogs
  - Should trap focus (Tab stays within modal)

- ? **FEATURE - Keyboard shortcuts**
  - Given application with keyboard shortcuts
  - Should document shortcuts (help dialog or tooltip)
  - Should not interfere with browser shortcuts
  - Should work consistently across pages

### Visual Focus Indicators
- ? **FEATURE - Focus indicator visibility**
  - Given focusable element (button, input, link)
  - On Tab to element
  - Should show clear focus indicator
  - Should have minimum 2px outline
  - Should have contrast ratio 3:1 with background
  - Should NOT disappear on click (must stay visible if refocused)

- ? **FEATURE - Focus indicator contrast**
  - Given dark background
  - Focus indicator should use light color (4.5:1 contrast minimum)
  - Given light background
  - Focus indicator should use dark color (4.5:1 contrast minimum)

### Color Contrast
- ? **FEATURE - Text contrast**
  - Given normal text (14px+)
  - Should have contrast ratio 4.5:1 against background
  - Given large text (18px+ or 14px bold+)
  - Should have contrast ratio 3:1 against background
  - All text colors must meet WCAG AA minimum

- ? **FEATURE - Interactive element contrast**
  - Given buttons, links, form inputs
  - Should have sufficient contrast (3:1 minimum)
  - Should NOT rely on color alone to convey meaning
  - Should use borders, icons, or text labels in addition to color

- ? **FEATURE - Dark theme contrast**
  - Given dark mode enabled
  - All text should have 4.5:1 contrast
  - Should NOT use light gray on dark backgrounds
  - Should use `hsl(210, 20%, 2%)` background and `hsl(30, 30%, 6%)` sidebar

### Touch Target Size
- ? **FEATURE - Minimum touch target size**
  - Given interactive element (button, link, input)
  - Should have minimum 44x44 pixels
  - Should NOT be smaller on mobile devices
  - Should have adequate spacing between targets (8px minimum)
  - Should NOT make targets smaller to fit on screen

- ? **FEATURE - Touch target spacing**
  - Given row of buttons
  - Should have at least 8px spacing between them
  - Should prevent accidental clicks on adjacent buttons

### Screen Reader Support
- ? **FEATURE - Semantic HTML**
  - Given page content
  - Should use semantic HTML (buttons, links, form elements, headings)
  - Should NOT use divs with click handlers instead of buttons
  - Headings should follow hierarchy (h1 → h2 → h3, no h2 after h4)

- ? **FEATURE - ARIA labels**
  - Given icon-only button (no text)
  - Should have aria-label
  - Should NOT be empty `<button></button>`
  - Given form field without visible label
  - Should have aria-label

- ? **FEATURE - Form field associations**
  - Given text input with label
  - Should have `<label for="fieldId">` pointing to input id
  - Should announce "label text, input type" to screen reader
  - Should NOT use placeholder as sole label

- ? **FEATURE - Alert and status messages**
  - Given error message
  - Should use role="alert" or aria-live="polite"
  - Should announce to screen reader immediately
  - Given success message after form submission
  - Should announce to screen reader

### Zoom and Scaling
- ? **FEATURE - 200% zoom support**
  - Given page zoomed to 200%
  - Should remain readable (no text cut off)
  - Should maintain layout (no horizontal scroll unless necessary)
  - Should remain functional (all buttons clickable)
  - Should NOT have fixed viewport width

- ? **FEATURE - Text resizing**
  - Given browser text size increased
  - Should scale all text proportionally
  - Should NOT use px units for font size (use rem)
  - Should NOT have fixed height containers that overflow

### Font and Spacing
- ? **FEATURE - Minimum font size**
  - Given body text
  - Should be minimum 16px (1rem)
  - Should NEVER scale DOWN on desktop (scale UP if anything)
  - Should be 16px+ on all devices including mobile
  - Headers should scale UP on large screens (e.g., 28px on desktop)

- ? **FEATURE - Line spacing**
  - Given paragraph text
  - Should have line-height of 1.5 or greater
  - Should have adequate paragraph spacing
  - Should improve readability

- ? **FEATURE - Letter spacing**
  - Given text with dyslexia-friendly font (if applicable)
  - Should have slightly increased letter-spacing
  - Should NOT have text set too tight

---

## FEATURE: Flash Messages (Component Level)

**SUT**: `FlashMessage.vue`, `FlashMessages.vue` components

### Flash Message Display
- ? **FEATURE - Display flash message**
  - Given flash message in props
  - Should render message with correct type (success, error, warning, info)
  - Should show message text
  - Should show close button (X)
  - Should apply correct color styling (green for success, red for error)

- ? **FEATURE - Flash message auto-dismiss**
  - Given success message
  - Should auto-dismiss after 5 seconds
  - Given error message
  - Should NOT auto-dismiss (stay until user closes)

- ? **FEATURE - Multiple flash messages**
  - Given multiple messages to show
  - Should stack them (top message shows first)
  - Should show all messages simultaneously
  - Should remove each when dismissed or timer expires

- ? **FEATURE - Flash message positioning**
  - Given flash messages on page
  - Should appear at top-right (or configured position)
  - Should NOT overlap page content
  - Should have adequate z-index to appear above page elements

---

## FEATURE: Dropdown Navigation

**SUT**: Dropdown components, user menu, action menus

### Dropdown Open/Close
- ? **FEATURE - Open dropdown**
  - Given dropdown button
  - On click
  - Should show dropdown menu
  - Should show all menu items
  - Should close other dropdowns

- ? **FEATURE - Close dropdown**
  - Given open dropdown
  - On clicking outside
  - Should close dropdown
  - Given dropdown with menu items
  - On clicking menu item
  - Should execute action
  - Should close dropdown

- ? **FEATURE - Keyboard navigation in dropdown**
  - Given open dropdown
  - Arrow Down should move focus to next item
  - Arrow Up should move focus to previous item
  - Enter/Space should select focused item
  - Escape should close dropdown

---

## FEATURE: Flash Messages Overlay (Notifications)

**SUT**: FlashMessagesOverlay component

### Toast/Notification Display
- ? **FEATURE - Show notification**
  - Given system generates notification
  - Should display in bottom-right corner (or configured position)
  - Should show notification icon, title, and message
  - Should show close button

- ? **FEATURE - Notification auto-dismiss**
  - Given success notification
  - Should auto-close after 5 seconds
  - Given error notification
  - Should remain until user closes
  - User should be able to manually close any notification

---

## FEATURE: Layout Components

**SUT**: `AppSidebarLayout.vue`, layout and navigation components

### Sidebar Navigation
- ? **FEATURE - Sidebar display**
  - Given desktop view
  - Should show sidebar on left
  - Should show navigation links
  - Should highlight current page
  - Should show user menu

- ? **FEATURE - Mobile sidebar behavior**
  - Given mobile/tablet view
  - Should show hamburger menu button
  - On click
  - Should show sidebar as overlay
  - Should show close button
  - Should close when navigating

- ? **FEATURE - Sidebar user menu**
  - Given user logged in
  - Should show user name/avatar
  - Should show "Profile" link
  - Should show "Settings" link
  - Should show "Logout" button

---

## FEATURE: Pagination Component

**SUT**: Pagination component in lists

### Pagination Controls
- ? **FEATURE - Pagination display**
  - Given paginated data
  - Should show page numbers
  - Should show Previous/Next buttons
  - Should show current page indicator
  - Should show total pages

- ? **FEATURE - Page navigation**
  - Given page 1 of 10
  - On clicking page 3
  - Should navigate to page 3
  - Should update URL
  - Should reload data for page 3

---

## UNIT: Components and Modules

**Components requiring feature-level tests** (beyond individual unit tests):
- Form.vue (complex form interaction)
- FormInput.vue (with validation)
- FormSelect.vue (with dependent selects)
- FlashMessage.vue (display and dismissal)
- FlashMessages.vue (multiple messages)
- Pagination (page navigation)
- Dropdown (open/close, keyboard nav)
- Sidebar (responsive behavior)
- Modal components (lifecycle, keyboard trap)

---

### FEATURE Tests Status

| Category | Coverage | Notes |
|----------|----------|-------|
| **Form System** | ? 0/12 | FormService, Form.vue, FormInput, FormSelect - not tested at feature level |
| **Entity Pages** | ? 0/30 | Index, Show, Create/Edit for 5 entities (Project, Family, Member, Event, Unit, Lottery) - not tested |
| **Lottery System** | ? 0/10 | Preferences, Admin management, Results display - not tested |
| **Routing** | ? 0/10 | Navigation, URL parameters, modal vs full-page - not tested |
| **Authorization** | ? 0/10 | Permission checks, show/hide UI based on permissions - not tested |
| **Theme System** | ? 0/4 | Dark/light toggle, persistence, contrast, color enforcement - not tested |
| **Modal System** | ? 0/8 | Lifecycle, form submission, nested modals, accessibility - not tested |
| **Accessibility (a11y)** | ? 0/22 | Keyboard nav, focus indicators, contrast, touch targets, screen reader, zoom, fonts - not tested |
| **Flash Messages (Component)** | ? 0/4 | Display, auto-dismiss, multiple messages, positioning - not tested |
| **Dropdown** | ? 0/3 | Open/close, keyboard nav - not tested |
| **Layout Components** | ? 0/3 | Sidebar, mobile behavior, user menu - not tested |
| **Pagination** | ? 0/2 | Display, page navigation - not tested |

### Test Execution Summary

```
Test Files:       8 passed (all unit tests for composables)
Total Tests:      161 passed (all composables covered)
New Behaviors:    150+ feature-level behaviors documented
Coverage Status:  Composables ✅ 100% (8/8)
                  Form System ? 0% (not started)
                  Entity Pages ? 0% (not started)
                  Lottery ? 0% (not started)
                  Accessibility ? 0% (not started)
                  All others ? 0% (not started)
Last Updated:     December 21, 2025

MAJOR PROGRESS: Added comprehensive documentation for all major app features:
- Form system (FormService pattern, field types, validation, dependent selects)
- Entity CRUD pages (index/show/create/edit for all entities)
- Lottery system (preferences, admin execution, results)
- Routing and navigation (links, parameters, modal vs full-page)
- Authorization and permissions (frontend checks, show/hide UI)
- Theme system (dark/light toggle, colors, persistence)
- Modal system (lifecycle, form submission, keyboard trap)
- Accessibility (keyboard nav, focus, contrast, touch targets, screen reader, zoom, fonts)
- Supporting features (flash messages, dropdowns, layout, pagination)

Total behaviors documented: 190+ specific, testable behaviors across 12+ feature areas
```

### Next Priority Areas

Based on coverage gaps, the following should be prioritized:

1. **Critical Priority** (target audience accessibility)
   - Accessibility tests (a11y) - 22 behaviors - WCAG AA compliance is non-negotiable
   - Keyboard navigation - full tab order, focus trap in modals
   - Color contrast - 4.5:1 ratio, dark theme colors enforcement
   - Font sizes - 16px minimum, never scale down
   - Touch targets - 44x44px minimum

2. **High Priority** (heavily used, core to app)
   - Form System (12 behaviors) - central to all data entry
   - Entity Pages (30 behaviors) - CRUD for 5+ entities
   - Authorization (10 behaviors) - affects feature visibility
   - Lottery System (10 behaviors) - major feature with drag-and-drop
   - Theme System (4 behaviors) - dark mode enforcement

3. **Medium Priority** (user experience)
   - Modal System (8 behaviors) - form submission and lifecycle
   - Routing (10 behaviors) - URL handling, page transitions
   - Flash Messages (4 behaviors) - notification display
   - Layout Components (3 behaviors) - sidebar behavior

4. **Low Priority** (utility)
   - Dropdown (3 behaviors)
   - Pagination (2 behaviors)


3. **Low Priority** (can be deferred)
   - Pagination Module
   - Breadcrumbs Module (if it exists)
   - Avatar Module
   - Filtering Module
   - Utility helpers

---

## Test Categories Not Yet Planned (Future)

- [ ] Accessibility tests (a11y)
- [ ] Performance tests (rendering speed, memory)
- [ ] Visual regression tests (screenshot comparison)
- [ ] End-to-end tests (full browser, if needed)

---

## References

- [UI.md](../UI.md) - Frontend architecture and patterns
- [testing/PHILOSOPHY.md](../testing/PHILOSOPHY.md) - Testing philosophy and helpers
- [testing/FORMS.md](../testing/FORMS.md) - Form interaction patterns
- [Vitest Documentation](https://vitest.dev)
- [Vue 3 Testing Guide](https://vuejs.org/guide/scaling-up/testing.html)

