# Design System Refactoring Plan

## Overview

Comprehensive 10-phase plan to refactor the application's design system with a focus on accessibility, consistency, and maintainability. Uses `_next` suffix strategy for safe, iterative migration.

## Guiding Principles

1. **Accessibility First**: WCAG AA minimum, AAA where possible
2. **Target Audience**: Elderly, modest device users, people with disabilities
3. **Large Typography**: Maintain or increase current font sizes
4. **High Contrast**: Clear, readable text in all themes
5. **Preserve Dark Theme**: Background `hsl(210, 20%, 2%)` and sidebar `hsl(30, 30%, 6%)` are non-negotiable
6. **CSS Variables Only**: No `dark:` prefixes, theme-agnostic components
7. **Safe Migration**: `_next` suffix prevents breaking existing UI

## Phase 1: Create _next Color Palette

**Goal**: Define complete new color system in `resources/css/app.css`

### Surface Colors

```css
:root {
  /* Light theme */
  --surface_next: hsl(0 0% 100%);
  --surface-foreground_next: hsl(0 0% 3.9%);

  --surface-elevated_next: hsl(0 0% 98%);
  --surface-elevated-foreground_next: hsl(0 0% 3.9%);

  --surface-sunken_next: hsl(0 0% 96%);
  --surface-sunken-foreground_next: hsl(0 0% 20%);

  --surface-interactive_next: hsl(0 0% 100%);
  --surface-interactive-hover_next: hsl(0 0% 96%);
}

.dark {
  /* Dark theme - PRESERVE background and sidebar! */
  --surface_next: hsl(210, 20%, 2%);
  --surface-foreground_next: hsl(0 0% 98%);

  --surface-elevated_next: hsl(30, 30%, 6%);
  --surface-elevated-foreground_next: hsl(0 0% 98%);

  --surface-sunken_next: hsl(210, 20%, 1%);
  --surface-sunken-foreground_next: hsl(0 0% 85%);

  --surface-interactive_next: hsl(30, 30%, 8%);
  --surface-interactive-hover_next: hsl(30, 30%, 10%);
}
```

### Text Colors

```css
:root {
  --text_next: hsl(0 0% 3.9%);
  --text-muted_next: hsl(0 0% 45%);
  --text-subtle_next: hsl(0 0% 60%);
  --text-inverse_next: hsl(0 0% 98%);
  --text-link_next: hsl(221, 83%, 53%);
  --text-link-hover_next: hsl(221, 83%, 43%);
}

.dark {
  --text_next: hsl(0 0% 98%);
  --text-muted_next: hsl(0 0% 70%);
  --text-subtle_next: hsl(0 0% 55%);
  --text-inverse_next: hsl(0 0% 3.9%);
  --text-link_next: hsl(221, 83%, 70%);
  --text-link-hover_next: hsl(221, 83%, 80%);
}
```

### Interactive Colors

```css
:root {
  --interactive_next: hsl(221, 83%, 53%);
  --interactive-hover_next: hsl(221, 83%, 43%);
  --interactive-active_next: hsl(221, 83%, 38%);
  --interactive-foreground_next: hsl(0 0% 98%);

  --interactive-secondary_next: hsl(0 0% 96%);
  --interactive-secondary-hover_next: hsl(0 0% 90%);
  --interactive-secondary-foreground_next: hsl(0 0% 3.9%);
}

.dark {
  --interactive_next: hsl(221, 83%, 60%);
  --interactive-hover_next: hsl(221, 83%, 70%);
  --interactive-active_next: hsl(221, 83%, 75%);
  --interactive-foreground_next: hsl(0 0% 3.9%);

  --interactive-secondary_next: hsl(30, 30%, 10%);
  --interactive-secondary-hover_next: hsl(30, 30%, 14%);
  --interactive-secondary-foreground_next: hsl(0 0% 98%);
}
```

### Border Colors

```css
:root {
  --border_next: hsl(0 0% 89%);
  --border-strong_next: hsl(0 0% 70%);
  --border-subtle_next: hsl(0 0% 95%);
  --border-interactive_next: hsl(221, 83%, 53%);
}

.dark {
  --border_next: hsl(0 0% 20%);
  --border-strong_next: hsl(0 0% 35%);
  --border-subtle_next: hsl(0 0% 15%);
  --border-interactive_next: hsl(221, 83%, 60%);
}
```

### Semantic Colors

```css
:root {
  --success_next: hsl(142, 71%, 45%);
  --success-foreground_next: hsl(0 0% 98%);
  --success-subtle_next: hsl(142, 71%, 95%);
  --success-subtle-foreground_next: hsl(142, 71%, 25%);

  --warning_next: hsl(38, 92%, 50%);
  --warning-foreground_next: hsl(0 0% 3.9%);
  --warning-subtle_next: hsl(38, 92%, 95%);
  --warning-subtle-foreground_next: hsl(38, 92%, 30%);

  --error_next: hsl(0, 84%, 60%);
  --error-foreground_next: hsl(0 0% 98%);
  --error-subtle_next: hsl(0, 84%, 95%);
  --error-subtle-foreground_next: hsl(0, 84%, 35%);

  --info_next: hsl(199, 89%, 48%);
  --info-foreground_next: hsl(0 0% 98%);
  --info-subtle_next: hsl(199, 89%, 95%);
  --info-subtle-foreground_next: hsl(199, 89%, 28%);
}

.dark {
  --success_next: hsl(142, 71%, 50%);
  --success-foreground_next: hsl(0 0% 3.9%);
  --success-subtle_next: hsl(142, 71%, 15%);
  --success-subtle-foreground_next: hsl(142, 71%, 85%);

  --warning_next: hsl(38, 92%, 60%);
  --warning-foreground_next: hsl(0 0% 3.9%);
  --warning-subtle_next: hsl(38, 92%, 15%);
  --warning-subtle-foreground_next: hsl(38, 92%, 85%);

  --error_next: hsl(0, 84%, 65%);
  --error-foreground_next: hsl(0 0% 3.9%);
  --error-subtle_next: hsl(0, 84%, 15%);
  --error-subtle-foreground_next: hsl(0, 84%, 85%);

  --info_next: hsl(199, 89%, 58%);
  --info-foreground_next: hsl(0 0% 3.9%);
  --info-subtle_next: hsl(199, 89%, 15%);
  --info-subtle-foreground_next: hsl(199, 89%, 85%);
}
```

### Focus & Selection

```css
:root {
  --focus-ring_next: hsl(221, 83%, 53%);
  --focus-ring-offset_next: hsl(0 0% 100%);
  --selection_next: hsl(221, 83%, 90%);
  --selection-foreground_next: hsl(0 0% 3.9%);
}

.dark {
  --focus-ring_next: hsl(221, 83%, 60%);
  --focus-ring-offset_next: hsl(210, 20%, 2%);
  --selection_next: hsl(221, 83%, 30%);
  --selection-foreground_next: hsl(0 0% 98%);
}
```

### Register in Tailwind Theme

Add to `@theme` in `app.css`:

```css
@theme inline {
  /* Surface colors */
  --color-surface_next: var(--surface_next);
  --color-surface-foreground_next: var(--surface-foreground_next);
  --color-surface-elevated_next: var(--surface-elevated_next);
  --color-surface-elevated-foreground_next: var(--surface-elevated-foreground_next);
  /* ... register all _next colors ... */
}
```

**Deliverable**: Complete `_next` color palette in `app.css` with WCAG AA compliance verified

---

## Phase 2: Shared Components

**Goal**: Refactor core shared components to use `_next` colors

### Components to Update

1. `resources/js/components/Card.vue`
2. `resources/js/components/CardBox.vue`
3. `resources/js/components/Button.vue` (if exists)
4. `resources/js/components/Avatar.vue`
5. `resources/js/components/Badge.vue` (if exists)

### Changes Per Component

- Replace all color classes with `_next` equivalents
- Improve typography (font-size, line-height, letter-spacing)
- Refine spacing (padding, margin, gaps)
- Add/improve focus states (2px+ visible indicators)
- Add ARIA attributes where missing
- Ensure keyboard navigation works

**Example**: Card.vue

```vue
<!-- Before -->
<div class="bg-card text-card-foreground border border-border">

<!-- After -->
<div class="bg-surface-elevated_next text-surface-elevated-foreground_next border border-border_next">
```

**Deliverable**: All shared components using `_next` colors, accessibility audit passed

---

## Phase 3: Dashboard Complete

**Goal**: Ensure all dashboard components use `_next` colors

### Files to Update

- `resources/js/pages/Dashboard.vue` (already mostly done)
- Any remaining dashboard-specific components
- Ensure typography consistency across all cards
- Verify lazy loading still works
- Accessibility audit

**Deliverable**: Dashboard fully migrated to `_next` colors

---

## Phase 4: Families CRUD

**Goal**: Remove ALL hardcoded colors from Families interfaces

### Priority Files

1. `resources/js/pages/Families/Index.vue`
2. `resources/js/pages/Families/Show.vue`
3. `resources/js/pages/Families/Crud/IndexCard.vue` - **CRITICAL**: Remove `text-[#1b1b18]` and `dark:text-[#EDEDEC]`
4. `resources/js/pages/Families/Crud/CreateUpdate.vue`

### Focus Areas

- Replace hardcoded hex colors with semantic `_next` variables
- Improve card layouts (spacing, borders, shadows)
- Member list typography and contrast
- Form styling (labels, inputs, error states)
- Focus states on all interactive elements

**Deliverable**: Families CRUD fully accessible and using `_next` colors

---

## Phase 5: Members CRUD

**Goal**: Refactor Members interfaces

### Files to Update

1. `resources/js/pages/Members/Index.vue`
2. `resources/js/pages/Members/Show.vue`
3. `resources/js/pages/Members/Crud/*`

### Focus Areas

- Consistent card styling with Families
- Form accessibility (labels, ARIA, validation)
- Member metadata display (improve readability)
- Action buttons (clear, high contrast)

**Deliverable**: Members CRUD accessible and consistent

---

## Phase 6: Units & UnitTypes CRUD

**Goal**: Complete Units interfaces (already started)

### Files to Update

1. `resources/js/pages/Units/Index.vue`
2. `resources/js/pages/Units/Crud/*`
3. `resources/js/pages/UnitTypes/Index.vue`
4. `resources/js/pages/UnitTypes/Crud/*`

### Notes

- Show pages already refactored
- Focus on Index and CRUD forms
- Ensure consistency with Families/Members patterns

**Deliverable**: Units fully migrated

---

## Phase 7: Admins, Projects, Logs

**Goal**: Refactor remaining entity interfaces

### Files to Update

1. `resources/js/pages/Admins/*`
2. `resources/js/pages/Projects/*`
3. `resources/js/pages/Logs/*`

**Deliverable**: All entity interfaces consistent

---

## Phase 8: Forms & Inputs

**Goal**: Standardize all form components

### Components to Audit

1. Text inputs
2. Textareas
3. Selects
4. Checkboxes
5. Radio buttons
6. Date pickers
7. File uploads

### Requirements

- Minimum 44px height on mobile
- Clear labels (not just placeholders)
- Visible focus indicators
- Error states with ARIA
- Helper text for complex inputs
- Disabled states clear but accessible

**Deliverable**: Form component library

---

## Phase 9: Navigation & Layout

**Goal**: Refactor navigation components

### Components

1. Main navigation/sidebar
2. Breadcrumbs (already improved)
3. Tabs (if any)
4. Pagination
5. Modals (ModalLink usage)

### Requirements

- Keyboard navigation complete
- Skip links for screen readers
- Current page clear indication
- Accessible close buttons in modals
- Focus trapping in modals

**Deliverable**: Fully accessible navigation

---

## Phase 10: Final Cleanup & Documentation

**Goal**: Complete migration and remove old colors

### Tasks

1. **Global Search & Replace**: Replace old color names with `_next` equivalents throughout codebase
2. **Remove `_next` Suffix**: Once verified, remove `_next` from all variable names
3. **Delete Unused Colors**: Remove old color definitions from `app.css`
4. **Contrast Audit**: Run full WCAG audit on entire app
5. **Documentation**: Update component docs with accessibility notes
6. **Testing Checklist**: Run through full accessibility checklist
7. **Browser Testing**: Test on multiple browsers, including older ones
8. **Device Testing**: Test on old Android/iOS devices if possible

**Deliverable**: Production-ready accessible design system

---

## Testing Strategy

### Per Phase

- [ ] Visual regression testing (compare screenshots)
- [ ] Keyboard navigation test
- [ ] Screen reader test (NVDA/JAWS/VoiceOver)
- [ ] Color contrast verification
- [ ] Mobile touch target verification
- [ ] Browser zoom to 200% test

### Tools

- **Contrast**: WebAIM Contrast Checker, Stark plugin
- **Screen Readers**: NVDA (Windows), JAWS (Windows), VoiceOver (Mac/iOS)
- **Automation**: axe DevTools, Lighthouse accessibility audit
- **Color Blindness**: Color Oracle, Sim Daltonism

---

## Success Metrics

- [ ] Zero WCAG AA violations
- [ ] All text ≥ 4.5:1 contrast (7:1 for body text preferred)
- [ ] All interactive elements ≥ 44x44px on mobile
- [ ] 100% keyboard navigable
- [ ] Zero hardcoded colors in components
- [ ] Consistent typography scale throughout
- [ ] Screen reader friendly (meaningful labels, landmarks)
- [ ] Works at 200% browser zoom
- [ ] Fast on old devices (no performance regressions)

---

## Timeline Estimate

- **Phase 1**: 2-3 hours (color palette creation & testing)
- **Phase 2**: 2-3 hours (shared components)
- **Phase 3**: 1 hour (dashboard completion)
- **Phase 4**: 3-4 hours (Families CRUD)
- **Phase 5**: 3-4 hours (Members CRUD)
- **Phase 6**: 3-4 hours (Units CRUD)
- **Phase 7**: 3-4 hours (Admins/Projects/Logs)
- **Phase 8**: 2-3 hours (Forms standardization)
- **Phase 9**: 2-3 hours (Navigation)
- **Phase 10**: 3-4 hours (Cleanup & testing)

**Total**: ~25-35 hours of focused work

---

**Last Updated**: October 30, 2025
**Status**: Ready to begin Phase 1
