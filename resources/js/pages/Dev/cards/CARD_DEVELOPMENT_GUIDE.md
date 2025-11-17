# Entity Card Development Guide

## Overview

This document tracks the current status of entity card development and provides guidance for creating meaningful and consistent card components across the MTAV application.

## Card Types Explained

### IndexCard (IC)
- **Purpose**: Used in list/grid views for browsing multiple entities
- **Content**: Condensed, essential information for quick identification and comparison
- **Design**: Compact, uniform sizing, scannable layout
- **Goal**: Help users quickly identify, filter, and select items

### ShowCard (SC)
- **Purpose**: Used in detail views for focusing on individual entities
- **Content**: Comprehensive information, relationships, and available actions
- **Design**: Expansive layout, rich content, contextual information
- **Goal**: Provide complete context and enable detailed interaction

## Current Status

### ✅ Migrated to New Card System
- **Project IC/SC** - Completed with StatBox components and ContentGrid layouts
- **Member IC/SC** - Completed with modern card snippets and responsive design
- **Admin IC/SC** - Completed and production ready
- **Family IC/SC** - Completed with member avatar grids and clean layouts
- **Event IC/SC** - Completed with ContentGrid for date fields and proper structure
- **Media IC/SC** - Completed and production ready

### 🔄 Pending Migration
- **Unit IC/SC** - Still using legacy card system, needs migration
- **Unit Type IC/SC** - Still using legacy card system, needs migration

## Card Component Architecture

### Modern Card System Structure
```
Card
├── CardHeader (title, subtitle, badges)
├── CardContent (main content area)
│   ├── ContentGrid (responsive equal-width layouts)
│   ├── ContentDetail (icon + label + value)
│   ├── ContentHighlight (emphasized content blocks)
│   ├── ContentLine (simple label/value pairs)
│   └── StatBox (clickable stat displays with entity integration)
└── CardFooter (actions, metadata)
```

### Key Components

#### StatBox
- **Purpose**: Interactive stat displays with automatic pluralization
- **Features**: Entity-aware labeling, clickable navigation, prefetch support
- **Usage**: `<StatBox entity="member" :count="46" icon-color="text-blue-500" />`
- **Benefits**: Type-safe, translatable, consistent with useResources

#### ContentGrid
- **Purpose**: Responsive equal-width layouts for related content
- **Features**: CSS Grid auto-fit, configurable min-width, container queries
- **Usage**: `<ContentGrid><ContentDetail /><ContentDetail /></ContentGrid>`
- **Benefits**: No media queries needed, adapts to content naturally

#### ContentDetail
- **Purpose**: Icon + label + value displays for entity properties
- **Features**: Icon integration, fallback values, optional links
- **Usage**: Standard component for displaying entity fields

## Priority Tasks

### 🎯 Immediate Priority
1. **Unit IC/SC** - Migrate to new card system with proper layouts
2. **Unit Type IC/SC** - Migrate to new card system if they exist

### 📋 Architecture Improvements
1. **Enhanced StatBox** - Consider route parameter support for complex links
2. **ContentGrid Extensions** - Evaluate need for additional layout variants
3. **Performance Optimization** - Review prefetch strategies and loading states

## Entity Information & Design Guidelines

### Projects
**Current Implementation:**
- **IndexCard**: Clean layout with project stats and current status
- **ShowCard**: StatBox components for all child entities (families, members, admins, units, events, media)
- **Features**: Clickable stats link to respective entity indexes, entity-aware labeling

### Members/Families
**Current Implementation:**
- **IndexCard**: Contact information with family association
- **ShowCard**: ContentGrid layouts for dates and contact info, proper responsive design
- **Features**: Integrated filtering system, clean avatar displays

### Events
**Current Implementation:**
- **IndexCard**: Event details with date prominence
- **ShowCard**: ContentGrid for date ranges, location handling, RSVP integration
- **Features**: Proper date grouping, responsive layout

### Units (Pending Migration)
**What Units Represent:**
- Individual housing units within cooperative projects
- Physical spaces with specific attributes and ownership
- The basic unit of family assignment and occupancy

**Migration Plan:**
- Implement StatBox for unit metrics
- Use ContentGrid for specifications layout
- Add proper entity-aware labeling
- Integrate with modern card component system

### Unit Types (Pending Migration)
**What Unit Types Represent:**
- Template definitions for different unit categories
- Standardized specifications and pricing models
- Reusable blueprints for unit creation

**Migration Plan:**
- Follow same patterns as other entity cards
- Use StatBox for usage statistics
- Implement ContentDetail for specifications

## Design Principles

### Consistency Through Components
- Use StatBox for all entity statistics
- Use ContentGrid for responsive layouts of related fields
- Use ContentDetail for standard property displays
- Maintain visual hierarchy through consistent component usage

### Entity Integration
- Leverage useResources for automatic labeling and pluralization
- Use entity-aware routing for StatBox navigation
- Maintain type safety with proper AppEntity types

### Responsive Design
- ContentGrid handles responsive breakpoints automatically
- Container queries provide context-aware layouts
- No manual media queries needed for most layouts

### Performance
- Prefetch enabled on interactive components
- Lazy loading where appropriate
- Efficient re-rendering with proper key usage

## Development Process

### Migration Checklist for Remaining Cards:
- [ ] **Unit IC/SC**: Replace legacy structure with modern components
- [ ] **Unit Type IC/SC**: Implement if needed, using established patterns

### Quality Standards:
- [ ] Uses modern card component structure (Card/CardHeader/CardContent/CardFooter)
- [ ] Implements ContentGrid for responsive layouts where appropriate
- [ ] Uses StatBox for entity statistics with proper navigation
- [ ] Leverages useResources for entity-aware labeling
- [ ] Maintains consistent visual hierarchy and spacing
- [ ] Handles edge cases (missing data, long content)
- [ ] Works responsively without custom media queries
- [ ] Integrates properly with filtering systems

## Known Issues & Pending Work

### 🐛 Bugs to Fix
- **Project ShowCard StatBox links**: Links to entity indexes don't always work because some entities require a project to be selected. Some entities only allow single-project mode (project must be selected), while others work both in single-project mode and multi-project mode (e.g. members/families/admins can be listed without a project selected, showing resources from all existing projects)

### 📋 Pending Work
- **Events index filters**: Need Status, Type, Published (admins), Sorting (new Filter tool?)
- **Media previews**: Add tiny media previews to Member/Family show cards when files uploaded by them
- **Create/update forms**: Still out of the entity cards system, need to develop a generic solution, mixed with the Form component (that needs rework)

## Next Actions

1. **Complete Migration**: Finish Unit and Unit Type card migrations
2. **Fix StatBox routing**: Address project-dependent routing for entity indexes
3. **Events filtering**: Implement comprehensive filter system for events
4. **Media integration**: Add media previews to relevant show cards
5. **Form system**: Develop generic create/update form solution

---

*All major entity cards have been successfully migrated to the new card system. Focus now shifts to completing the remaining Unit/Unit Type migrations, fixing routing issues, and expanding card functionality.*