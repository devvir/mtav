# Dashboard Components Structure
<!-- Copilot - pending review -->

This document describes the organization of the Dashboard components for maintainability.

## Folder Structure

```
Dashboard/
├── shared/              # Components used across multiple sections
│   ├── SectionHeader.vue    # Reusable section header with optional "View all" link
│   └── PersonCard.vue       # Generic card for displaying person info (family/member/admin)
│
├── overview/            # Overview stats section
│   └── OverviewSection.vue  # 7 stat cards showing project metrics
│
├── families/            # Families section
│   ├── FamiliesSection.vue  # Container for families preview
│   └── FamilyCard.vue       # Individual family card
│
├── members/             # Members section
│   ├── MembersSection.vue   # Container for members preview
│   └── MemberCard.vue       # Individual member card (rounded-full avatar)
│
├── admins/              # Administrators section
│   ├── AdminsSection.vue    # Container for admins list
│   └── AdminCard.vue        # Individual admin card
│
├── gallery/             # Gallery section
│   └── GallerySection.vue   # Stacked photo preview with rotation effect
│
├── events/              # Events section
│   └── EventsSection.vue    # Events placeholder/empty state
│
└── units/               # Units by Type section
    ├── UnitsSection.vue     # Container for all unit types
    ├── UnitTypeCard.vue     # Card showing unit type with nested units
    └── UnitCard.vue         # Individual unit card
```

## Component Responsibilities

### Shared Components

**SectionHeader.vue**
- Displays section title (clickable if viewAllHref is provided)
- Optional "View all" link with custom text
- Entire header area is interactive for better UX
- Used consistently across all sections

**PersonCard.vue**
- Generic card for displaying people (families, members, admins)
- Uses ModalLink for modal navigation
- Supports avatar with optional src
- Configurable border radius (default `rounded-lg` or `rounded-full`)
- Shows name and subtitle

### Section Components

Each section follows a similar pattern:
1. **Section Container** (`*Section.vue`) - Handles layout and data passing
2. **Item Card** (`*Card.vue`) - Renders individual items

### Layout Strategy

**Gallery & Events**: Side by side on large screens (`@4xl:grid-cols-2`)

**Families/Admins & Members**:
- Two column layout on large screens
- Left column: Families (flex-1) + Admins stacked
- Right column: Members
- Smart family count calculation to match heights (accounting for extra header)

**Units**: Full width, nested structure showing unit types with their units (labeled as "Project Units")

## Navigation Pattern

- **Modal Navigation**: Family, Member, and Admin cards use `ModalLink` to open details in modals
- **Clickable Headers**: Section headers are fully clickable when they have a "View all" link
- **View All Links**: Explicit buttons for quick navigation to full lists

## Icon Changes

- **Families**: Uses `UsersRound` (family-ish icon showing multiple people)
- **Members**: Uses `Users`
- **Units**: Uses `Building2`
- **Unit Types**: Uses `Layers`
- **Admins**: Uses `Shield`
- **Gallery**: Uses `ImageIcon`
- **Events**: Uses `Calendar`

## Height Matching Logic

The families count is dynamically calculated to ensure the left column (Families + Admins) matches the height of the right column (Members):

```typescript
const familiesToShow = computed(() => {
  const adminRows = Math.ceil(props.admins.length / 2);
  const memberRows = Math.ceil(props.members.length / 2);

  // Subtract 1 row for the extra section header
  const availableRowsForFamilies = Math.max(0, memberRows - adminRows - 1);
  const maxFamiliesToShow = availableRowsForFamilies * 2; // 2 columns

  return Math.min(props.families.length, maxFamiliesToShow);
});
```

## Future Maintenance

When modifying the dashboard:
1. Keep components small and focused
2. Use shared components where possible
3. Follow the established folder structure
4. Each section should be independently maintainable
5. Keep business logic in the main Dashboard.vue
6. UI components should receive data as props
