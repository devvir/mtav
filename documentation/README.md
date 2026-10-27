# MTAV Documentation

Index of the project's documentation. The code is the source of truth for implementation details.

## `thesis/` — Proyecto de Grado

The degree thesis (Spanish), the main long-form documentation of the project:

- `thesis/parte-0-resumen.md` — metadatos (frontmatter) + Resumen. (El índice se genera automáticamente al exportar con `pandoc --toc`; ya no se mantiene un índice anotado a mano.)
- `thesis/parte-1-introduccion.md` … `parte-4-ingenieria.md` — the four parts.
- `thesis/bibliografia.md` — Bibliografía (sección propia, antes de los apéndices).
- `thesis/apendices.md` — Apéndices A–H (mostly placeholders for now).
- `thesis/referencias/` — cited sources and trimmed reference notes that feed the thesis
  (Fierro 2024 PDF + bibliography; authorization/policies; JSON resources).

## `ai/` — for AI agents

- `ai/thesis/README.md` — **orientation for any agent working on the thesis** (file map, workflow,
  rules, status). Start here for thesis work.

## `technical/` — developer guides

Working-with-the-codebase references: `architecture.md` and `testing.md`.

## `planning/` — work in progress

- `planning/refactor.md` — the ongoing refactor: business rules, decisions, work order.

## Docker & environments

All infrastructure docs live in **`../docker/README.md`**: environments
(dev/testing/staging/prod), the `mtav` CLI, full technical spec, and gotchas.

## Other

- `export/` — exported deliverables (e.g. `.docx`).
- Root `../README.md` — project overview / quick start.
