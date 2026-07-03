# MTAV Documentation

Index of the project's documentation. (Older AI "knowledge base" docs were removed in 2026‑07; the
degree thesis is now the authoritative description of the system, and the code is the source of
truth for implementation details.)

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
- `ai/CODE-REVIEW.md` — code‑quality review (Opus, 2026‑05).
- `ai/testing/` — testing notes (pending a focused review pass).

## `technical/` — developer & DevOps guides

Working-with-the-codebase references: `docker.md`, `deployment.md`, `builds.md`,
`build-images.md`, `scripts.md`, `troubleshooting.md`, `testing.md`, `forms-service.md`,
`form-requests.md`, `BROADCASTING.md`, and `ER/` (entity‑relationship diagrams).

## Other

- `export/` — exported deliverables (e.g. `.docx`).
- `TODO.md` — project todo list.
- Root `../README.md` — project overview / quick start.
