<!-- Copilot - Pending review -->

# MTAV Database Schema Diagram

This document contains detailed database schema diagrams showing all tables, columns, and relationships.

## Visual Schema Diagram

See [SCHEMA_DIAGRAM.svg](SCHEMA_DIAGRAM.svg) for a complete visual representation of all tables with their columns and types.

## Table Definitions

### Core Tables

#### **users**
```
┌─────────────────────────────────┐
│           users                 │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ name            | string         │
│ email           | string [UQ]    │
│ email_verified_ | timestamp      │
│   at            |                │
│ password        | string         │
│ remember_token  | string         │
│ is_admin        | boolean        │
│ firstname       | string         │
│ lastname        | string         │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
│ deleted_at      | timestamp      │
└─────────────────────────────────┘
```

**Note:** Uses single table inheritance with `is_admin` flag:
- `is_admin = true` → Admin
- `is_admin = false` → Member

#### **projects**
```
┌─────────────────────────────────┐
│           projects              │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ name            | string [UQ]    │
│ description     | string         │
│ organization    | string         │
│ active          | boolean        │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
│ deleted_at      | timestamp      │
└─────────────────────────────────┘
```

#### **project_user** (Pivot Table)
```
┌─────────────────────────────────┐
│         project_user            │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 user_id      | bigint [FK]    │
│ 🔗 project_id   | bigint [FK]    │
│ active          | boolean        │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
└─────────────────────────────────┘
```

### Unit Management Tables

#### **unit_types**
```
┌─────────────────────────────────┐
│         unit_types              │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 project_id   | bigint [FK]    │
│ name            | string [UQ]    │
│ description     | text           │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
│ deleted_at      | timestamp      │
└─────────────────────────────────┘
```

#### **families**
```
┌─────────────────────────────────┐
│          families               │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 project_id   | bigint [FK]    │
│ 🔗 unit_type_id | bigint [FK]    │
│ name            | string [UQ]    │
│ avatar          | string         │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
│ deleted_at      | timestamp      │
└─────────────────────────────────┘
```

#### **units**
```
┌─────────────────────────────────┐
│           units                 │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 project_id   | bigint [FK]    │
│ 🔗 unit_type_id | bigint [FK]    │
│ 🔗 family_id    | bigint [FK]    │
│ 🔗 plan_item_id | bigint [FK]    │
│ identifier      | string [UQ]    │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
│ deleted_at      | timestamp      │
└─────────────────────────────────┘
```

#### **unit_preferences** (Pivot Table)
```
┌─────────────────────────────────┐
│      unit_preferences           │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 family_id    | bigint [FK]    │
│ 🔗 unit_id      | bigint [FK]    │
│ order           | integer        │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
└─────────────────────────────────┘
```

### Planning Tables

#### **plans**
```
┌─────────────────────────────────┐
│           plans                 │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 project_id   | bigint [FK]    │
│ polygon         | json           │
│ width           | decimal(10,2)  │
│ height          | decimal(10,2)  │
│ unit_system     | enum           │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
└─────────────────────────────────┘
```

#### **plan_items**
```
┌─────────────────────────────────┐
│         plan_items              │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 plan_id      | bigint [FK]    │
│ 🔗 unit_id      | bigint [FK]    │
│ polygon         | json           │
│ metadata        | json           │
│ floor           | integer        │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
└─────────────────────────────────┘
```

### Event Tables

#### **events**
```
┌─────────────────────────────────┐
│           events                │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ type            | enum           │
│ 🔗 creator_id   | bigint [FK]    │
│ 🔗 project_id   | bigint [FK]    │
│ title           | string         │
│ description     | text           │
│ location        | string         │
│ start_date      | datetime       │
│ end_date        | datetime       │
│ is_published    | boolean        │
│ rsvp            | boolean        │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
│ deleted_at      | timestamp      │
└─────────────────────────────────┘
```

#### **event_rsvp** (Pivot Table)
```
┌─────────────────────────────────┐
│         event_rsvp              │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 event_id     | bigint [FK]    │
│ 🔗 user_id      | bigint [FK]    │
│ status          | boolean        │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
└─────────────────────────────────┘
```

### Media & Logging Tables

#### **media**
```
┌─────────────────────────────────┐
│            media                │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 owner_id     | bigint [FK]    │
│ 🔗 project_id   | bigint [FK]    │
│ path            | string         │
│ thumbnail       | string         │
│ description     | text           │
│ alt_text        | string         │
│ width           | unsigned int   │
│ height          | unsigned int   │
│ category        | string         │
│ mime_type       | string         │
│ file_size       | unsigned big.. │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
│ deleted_at      | timestamp      │
└─────────────────────────────────┘
```

#### **logs**
```
┌─────────────────────────────────┐
│            logs                 │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 creator_id   | bigint [FK]    │
│ 🔗 project_id   | bigint [FK]    │
│ event           | string         │
│ data            | json           │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
└─────────────────────────────────┘
```

#### **lottery_audits**
```
┌─────────────────────────────────┐
│       lottery_audits            │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 project_id   | bigint [FK]    │
│ 🔗 lottery_id   | bigint [FK]    │
│ type            | enum           │
│ audit           | json           │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
│ deleted_at      | timestamp      │
└─────────────────────────────────┘
```

### Notification Tables

#### **notifications**
```
┌─────────────────────────────────┐
│       notifications             │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ title           | string         │
│ message         | text           │
│ type            | enum           │
│ target          | enum           │
│ target_id       | integer        │
│ data            | json           │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
└─────────────────────────────────┘
```

#### **notification_read** (Pivot Table)
```
┌─────────────────────────────────┐
│      notification_read          │
├─────────────────────────────────┤
│ 🔑 id           | bigint         │
│ 🔗 user_id      | bigint [FK]    │
│ 🔗 notification | bigint [FK]    │
│ read_at         | datetime       │
│ created_at      | timestamp      │
│ updated_at      | timestamp      │
└─────────────────────────────────┘
```

## Key Relationships

| From | To | Relationship | Cardinality |
|------|----|-----------   |-------------|
| projects | families | has | 1:n |
| projects | unit_types | defines | 1:n |
| projects | units | contains | 1:n |
| projects | plans | has_one | 1:1 |
| projects | events | hosts | 1:n |
| projects | media | owns | 1:n |
| projects | logs | records | 1:n |
| projects | lottery_audits | audits | 1:n |
| unit_types | families | has | 1:n |
| unit_types | units | has_many | 1:n |
| families | units | contains | 1:n |
| families | unit_preferences | has | 1:n |
| units | plan_items | references | 1:1 |
| plans | plan_items | contains | 1:n |
| events | event_rsvp | has_many | 1:n |
| events | lottery_audits | audits | 1:n |
| event_rsvp | users | rsvps | n:m |
| media | users | owner | n:1 |
| logs | users | creator | n:1 |
| notifications | notification_read | has_many | 1:n |
| notification_read | users | read_by | n:1 |

## Legend

- 🔑 **PK** - Primary Key (unique identifier)
- 🔗 **FK** - Foreign Key (reference to another table)
- **[UQ]** - Unique Constraint
- **soft deletes** - Records marked as deleted rather than actually removed
- **timestamps** - `created_at` and `updated_at` for tracking changes

