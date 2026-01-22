#!/usr/bin/env python3
"""Generate a database schema diagram as SVG"""

import json
from pathlib import Path

# Table definitions with columns and types
tables = {
    "users": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("name", "string", False, False, ""),
            ("email", "string", False, True, "UQ"),
            ("email_verified_at", "timestamp", False, False, ""),
            ("password", "string", False, False, ""),
            ("remember_token", "string", False, False, ""),
            ("is_admin", "boolean", False, False, ""),
            ("firstname", "string", False, False, ""),
            ("lastname", "string", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
            ("deleted_at", "timestamp", False, False, ""),
        ]
    },
    "projects": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("name", "string", False, True, "UQ"),
            ("description", "string", False, False, ""),
            ("organization", "string", False, False, ""),
            ("active", "boolean", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
            ("deleted_at", "timestamp", False, False, ""),
        ]
    },
    "project_user": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("user_id", "bigint", False, False, "FK"),
            ("project_id", "bigint", False, False, "FK"),
            ("active", "boolean", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
        ]
    },
    "unit_types": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("project_id", "bigint", False, False, "FK"),
            ("name", "string", False, True, "UQ"),
            ("description", "text", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
            ("deleted_at", "timestamp", False, False, ""),
        ]
    },
    "families": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("project_id", "bigint", False, False, "FK"),
            ("unit_type_id", "bigint", False, False, "FK"),
            ("name", "string", False, True, "UQ"),
            ("avatar", "string", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
            ("deleted_at", "timestamp", False, False, ""),
        ]
    },
    "units": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("project_id", "bigint", False, False, "FK"),
            ("unit_type_id", "bigint", False, False, "FK"),
            ("family_id", "bigint", False, False, "FK"),
            ("plan_item_id", "bigint", False, False, "FK"),
            ("identifier", "string", False, True, "UQ"),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
            ("deleted_at", "timestamp", False, False, ""),
        ]
    },
    "unit_preferences": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("family_id", "bigint", False, False, "FK"),
            ("unit_id", "bigint", False, False, "FK"),
            ("order", "integer", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
        ]
    },
    "plans": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("project_id", "bigint", False, False, "FK"),
            ("polygon", "json", False, False, ""),
            ("width", "decimal", False, False, ""),
            ("height", "decimal", False, False, ""),
            ("unit_system", "enum", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
        ]
    },
    "plan_items": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("plan_id", "bigint", False, False, "FK"),
            ("unit_id", "bigint", False, False, "FK"),
            ("polygon", "json", False, False, ""),
            ("metadata", "json", False, False, ""),
            ("floor", "integer", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
        ]
    },
    "events": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("type", "enum", False, False, ""),
            ("creator_id", "bigint", False, False, "FK"),
            ("project_id", "bigint", False, False, "FK"),
            ("title", "string", False, False, ""),
            ("description", "text", False, False, ""),
            ("location", "string", False, False, ""),
            ("start_date", "datetime", False, False, ""),
            ("end_date", "datetime", False, False, ""),
            ("is_published", "boolean", False, False, ""),
            ("rsvp", "boolean", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
            ("deleted_at", "timestamp", False, False, ""),
        ]
    },
    "event_rsvp": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("event_id", "bigint", False, False, "FK"),
            ("user_id", "bigint", False, False, "FK"),
            ("status", "boolean", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
        ]
    },
    "media": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("owner_id", "bigint", False, False, "FK"),
            ("project_id", "bigint", False, False, "FK"),
            ("path", "string", False, False, ""),
            ("thumbnail", "string", False, False, ""),
            ("description", "text", False, False, ""),
            ("alt_text", "string", False, False, ""),
            ("width", "unsigned int", False, False, ""),
            ("height", "unsigned int", False, False, ""),
            ("category", "string", False, False, ""),
            ("mime_type", "string", False, False, ""),
            ("file_size", "unsigned bigint", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
            ("deleted_at", "timestamp", False, False, ""),
        ]
    },
    "logs": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("creator_id", "bigint", False, False, "FK"),
            ("project_id", "bigint", False, False, "FK"),
            ("event", "string", False, False, ""),
            ("data", "json", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
        ]
    },
    "lottery_audits": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("project_id", "bigint", False, False, "FK"),
            ("lottery_id", "bigint", False, False, "FK"),
            ("type", "enum", False, False, ""),
            ("audit", "json", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
            ("deleted_at", "timestamp", False, False, ""),
        ]
    },
    "notifications": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("title", "string", False, False, ""),
            ("message", "text", False, False, ""),
            ("type", "enum", False, False, ""),
            ("target", "enum", False, False, ""),
            ("target_id", "integer", False, False, ""),
            ("data", "json", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
        ]
    },
    "notification_read": {
        "columns": [
            ("id", "bigint", True, False, "PK"),
            ("user_id", "bigint", False, False, "FK"),
            ("notification_id", "bigint", False, False, "FK"),
            ("read_at", "datetime", False, False, ""),
            ("created_at", "timestamp", False, False, ""),
            ("updated_at", "timestamp", False, False, ""),
        ]
    },
}

# Relationships: (from_table, from_column, to_table, to_column, cardinality)
relationships = [
    ("projects", "id", "families", "project_id", "1:n"),
    ("projects", "id", "unit_types", "project_id", "1:n"),
    ("projects", "id", "units", "project_id", "1:n"),
    ("projects", "id", "plans", "project_id", "1:1"),
    ("projects", "id", "events", "project_id", "1:n"),
    ("projects", "id", "media", "project_id", "1:n"),
    ("projects", "id", "logs", "project_id", "1:n"),
    ("projects", "id", "lottery_audits", "project_id", "1:n"),
    ("unit_types", "id", "families", "unit_type_id", "1:n"),
    ("unit_types", "id", "units", "unit_type_id", "1:n"),
    ("families", "id", "units", "family_id", "1:n"),
    ("families", "id", "unit_preferences", "family_id", "1:n"),
    ("units", "id", "plan_items", "unit_id", "1:1"),
    ("plans", "id", "plan_items", "plan_id", "1:n"),
    ("users", "id", "events", "creator_id", "1:n"),
    ("users", "id", "media", "owner_id", "1:n"),
    ("users", "id", "logs", "creator_id", "1:n"),
    ("users", "id", "event_rsvp", "user_id", "1:n"),
    ("users", "id", "notification_read", "user_id", "1:n"),
    ("events", "id", "event_rsvp", "event_id", "1:n"),
    ("events", "id", "lottery_audits", "lottery_id", "1:n"),
    ("unit_preferences", "unit_id", "units", "id", "n:1"),
    ("notifications", "id", "notification_read", "notification_id", "1:n"),
]

def generate_svg():
    """Generate SVG diagram from table definitions with relationships"""

    # SVG dimensions and styling
    table_width = 240
    table_height_base = 30
    col_height = 16
    padding = 10
    spacing = 60

    # Calculate positions
    positions = {}
    x_step = table_width + spacing
    y_step = 380

    cols_per_row = 4
    col = 0
    row = 0
    x_pos = 50
    y_pos = 60

    for table_name in sorted(tables.keys()):
        positions[table_name] = (x_pos, y_pos)
        col += 1
        x_pos += x_step
        if col >= cols_per_row:
            col = 0
            x_pos = 50
            row += 1
            y_pos += y_step

    # Calculate SVG dimensions
    total_width = cols_per_row * x_step + 100
    total_height = (row + 1) * y_step + 150

    svg_content = f'''<svg xmlns="http://www.w3.org/2000/svg" width="{total_width}" height="{total_height}" viewBox="0 0 {total_width} {total_height}">
  <defs>
    <style>
      .table-title {{ font-weight: bold; font-size: 12px; fill: #fff; }}
      .column-name {{ font-size: 10px; fill: #333; font-family: monospace; }}
      .relation-line {{ stroke: #666; stroke-width: 1.5; fill: none; stroke-dasharray: 5,5; }}
      .relation-label {{ font-size: 9px; fill: #666; font-weight: bold; }}
      .title {{ font-size: 18px; font-weight: bold; fill: #000; }}
    </style>
  </defs>

  <!-- Background -->
  <rect width="{total_width}" height="{total_height}" fill="#f9f9f9"/>

  <!-- Title -->
  <text x="20" y="35" class="title">MTAV Database Schema</text>

  <!-- Relationships layer (drawn first so they appear behind tables) -->
'''

    # Draw relationships
    drawn_relationships = set()
    for from_table, from_col, to_table, to_col, cardinality in relationships:
        if from_table not in positions or to_table not in positions:
            continue

        # Avoid drawing the same relationship twice
        rel_key = tuple(sorted([from_table, to_table]))
        if rel_key in drawn_relationships and from_table == to_table:
            continue
        drawn_relationships.add(rel_key)

        x1, y1 = positions[from_table]
        x2, y2 = positions[to_table]

        # Adjust coordinates to connect from table edges
        mid_y1 = y1 + 15
        mid_y2 = y2 + 15

        # Draw connecting line
        svg_content += f'  <path d="M {x1 + table_width} {mid_y1} L {x2} {mid_y2}" class="relation-line" stroke-dasharray="0" stroke="#999"/>\n'

        # Add cardinality labels
        mid_x = (x1 + x2) / 2
        mid_y = (mid_y1 + mid_y2) / 2

        # Start cardinality
        parts = cardinality.split(":")
        if len(parts) == 2:
            svg_content += f'  <text x="{x1 + table_width + 5}" y="{mid_y1 - 3}" class="relation-label" style="fill: #d32f2f;">{parts[0]}</text>\n'
            svg_content += f'  <text x="{x2 - 15}" y="{mid_y2 - 3}" class="relation-label" style="fill: #1976d2;">{parts[1]}</text>\n'

    svg_content += '\n  <!-- Tables layer -->\n'

    # Generate table rectangles
    for table_name, table_info in sorted(tables.items()):
        x, y = positions[table_name]
        columns = table_info["columns"]
        table_height = table_height_base + len(columns) * col_height

        # Table border
        svg_content += f'''  <rect x="{x}" y="{y}" width="{table_width}" height="{table_height}" fill="#E3F2FD" stroke="#0277BD" stroke-width="2" rx="3"/>
'''

        # Table title (header)
        svg_content += f'''  <rect x="{x}" y="{y}" width="{table_width}" height="{table_height_base}" fill="#0277BD" rx="3"/>
  <text x="{x + 8}" y="{y + 19}" class="table-title">{table_name}</text>
  <line x1="{x}" y1="{y + table_height_base}" x2="{x + table_width}" y2="{y + table_height_base}" stroke="#0277BD" stroke-width="1"/>
'''

        # Columns
        for idx, (col_name, col_type, is_pk, is_uq, modifier) in enumerate(columns):
            col_y = y + table_height_base + idx * col_height + 2
            col_display = col_name

            if is_pk:
                col_display = f"🔑 {col_name}"
            elif "FK" in modifier:
                col_display = f"🔗 {col_name}"

            # Column background (highlight for PK/FK)
            if is_pk:
                svg_content += f'''  <rect x="{x+1}" y="{col_y}" width="{table_width-2}" height="{col_height-1}" fill="#FFF9C4" opacity="0.6"/>
'''
            elif "FK" in modifier:
                svg_content += f'''  <rect x="{x+1}" y="{col_y}" width="{table_width-2}" height="{col_height-1}" fill="#B3E5FC" opacity="0.5"/>
'''

            svg_content += f'''  <text x="{x + 5}" y="{col_y + 11}" class="column-name">{col_display}</text>
'''

    svg_content += '''
  <!-- Legend -->
  <g transform="translate(20, ''' + str(total_height - 60) + ''')">
    <text style="font-size: 11px; font-weight: bold; fill: #000;">Legend:</text>
    <text x="0" y="16" style="font-size: 10px; fill: #333;">🔑 Primary Key</text>
    <text x="100" y="16" style="font-size: 10px; fill: #333;">🔗 Foreign Key</text>
    <text x="200" y="16" style="font-size: 10px; fill: #d32f2f;">Red (1) / Blue (n) Cardinality</text>
    <line x1="0" y1="28" x2="30" y2="28" stroke="#999" stroke-width="1.5"/>
    <text x="35" y="31" style="font-size: 10px; fill: #333;">Relationship</text>
  </g>
</svg>'''
    return svg_content

if __name__ == "__main__":
    svg = generate_svg()
    output_path = Path(__file__).parent / "SCHEMA_DIAGRAM.svg"
    output_path.write_text(svg)
    print(f"Schema diagram generated: {output_path}")
