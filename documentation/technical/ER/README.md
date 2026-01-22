<!-- Copilot - Pending review -->

# Entity-Relationship (ER) Diagrams

This folder contains comprehensive database schema documentation and diagrams for the MTAV project.

## Files

### Visual Diagrams

- **[SCHEMA_DIAGRAM.svg](SCHEMA_DIAGRAM.svg)** - Main visual database schema diagram showing:
  - All 16 database tables as blue rectangles
  - Column definitions with data types
  - Primary Keys (🔑) highlighted in gold
  - Foreign Keys (🔗) highlighted in light blue
  - Relationship arrows between tables with cardinality notation
  - Legend explaining the notation

### Documentation Files

- **[SCHEMA_DETAILED.md](SCHEMA_DETAILED.md)** - Comprehensive text-based schema documentation including:
  - ASCII-style representations of each table
  - All column definitions with types and constraints
  - Relationship matrix with cardinality information
  - Detailed legend and explanation of notation

- **[ER_DIAGRAM.md](ER_DIAGRAM.md)** - High-level ER diagram with entity descriptions:
  - Mermaid-based conceptual diagram
  - Complete entity relationship overview
  - Detailed descriptions of each model
  - Design pattern explanations

- **[SCHEMA_DIAGRAM.md](SCHEMA_DIAGRAM.md)** - PlantUML source for entity-relationship diagrams:
  - Alternative diagram format using PlantUML syntax
  - Table and relationship definitions in DSL format

### Supporting Files

- **generate_schema.py** - Python script that generates the SVG diagram
  - Reads table and relationship definitions
  - Generates visual SVG with relationship arrows
  - Can be re-run to update the diagram if table definitions change

- **er-diagram.mmd** - Mermaid diagram source file (plain text)

- **ER_DIAGRAM.png** - PNG export of the conceptual ER diagram

## How to Update Diagrams

If you modify the database schema (add/remove tables or columns), you can regenerate the SVG diagram:

```bash
cd documentation/technical/ER
python3 generate_schema.py
```

This will regenerate `SCHEMA_DIAGRAM.svg` with the updated table definitions.

## Relationship Notation

### Cardinality Indicators

- **Red numbers (left side)** - Cardinality of the left/parent table
  - `1` = One (required)
  - `n` = Many (zero or more)

- **Blue numbers (right side)** - Cardinality of the right/child table
  - `1` = One (required)
  - `n` = Many (zero or more)

### Common Relationships

- `1:n` (One-to-Many) - One parent has many children
- `1:1` (One-to-One) - Exact one-to-one relationship
- `n:m` (Many-to-Many) - Through pivot/junction tables

## Key Tables

### Core Entity Tables
- **users** - User accounts (Admin and Member via inheritance)
- **projects** - Main organizational unit
- **families** - Family units within a project
- **units** - Physical units (apartments, rooms)
- **unit_types** - Classification of unit types

### Planning & Layout
- **plans** - Floor plans/layouts
- **plan_items** - Individual unit placements on plans

### Events & Activities
- **events** - Events, activities, lotteries
- **event_rsvp** - RSVP tracking (pivot table)

### Media & Logging
- **media** - File uploads and assets
- **logs** - System logs and audit trail
- **lottery_audits** - Lottery execution audit records

### Notifications
- **notifications** - User notifications
- **notification_read** - Notification read status tracking (pivot table)

### Relationships
- **project_user** - Users assigned to projects (pivot table)
- **unit_preferences** - Family unit preferences (pivot table)

## Design Patterns Used

1. **Single Table Inheritance** - User model with `is_admin` flag differentiates Admin/Member
2. **Pivot Tables** - Many-to-many relationships use junction tables
3. **Soft Deletes** - Records marked deleted, not actually removed (deleted_at column)
4. **Project Scoping** - Most entities scoped to a project for multi-tenancy
5. **Timestamps** - All tables include created_at/updated_at for auditing
6. **Foreign Keys** - Cascade deletes or restrict deletes per relationship type

## Database Features

- **Soft Deletes** - Most entities support logical deletion via deleted_at
- **Timestamps** - All tables track creation and modification time
- **Unique Constraints** - Enforce data uniqueness (e.g., email, project names)
- **Relationships** - Foreign key constraints with appropriate cascade strategies
- **JSON Fields** - Flexible data storage for polygon coordinates, metadata, audit data

