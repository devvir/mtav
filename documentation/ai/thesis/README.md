# Thesis — agent orientation (start here)

> The single agent-facing doc for the MTAV thesis. It says where things are, how we write, and
> the rules. It deliberately does **not** repeat thesis content (that lives in the thesis) or the
> working rules in detail (those live in memory). English; the thesis itself is Spanish.

## The one-paragraph situation

MTAV is Diego Barreiro's degree thesis (Ingeniería en Computación, FING-Udelar; tutor Héctor
Cancela). MTAV en línea is a web/mobile platform that lets housing cooperatives run their own
unit-assignment lottery, replacing the original desktop MTAV (built by a FING team; used in real
projects). Original contributions: the platform, and a **binary-search replacement for Phase 1** of
the two-phase optimization (equivalent to the original, but not derailed by degenerate cases).

## Where everything lives (authoritative sources)

The thesis itself is the source of truth for system description — don't re-document it here. There
is no maintained index file: the table of contents is generated at export time (`pandoc --toc`).
Structure:

| File | Content |
|---|---|
| `documentation/thesis/parte-0-resumen.md` | YAML metadata (title/author) + Resumen (abstract). First file in the merge. |
| Export | `pandoc parte-0-resumen.md parte-1-introduccion.md parte-2-descripcion-funcional.md parte-3-el-sorteo.md parte-4-ingenieria.md bibliografia.md apendices.md --toc --toc-depth=2 -o ../export/mtav-borrador.docx` → upload to Google Docs. (Bibliografía is its own top-level section, before the appendices; each part file begins with an openxml page break.) |
| `documentation/thesis/parte-1-introduccion.md` | Parte I — Introducción y Contexto (§1–4). |
| `documentation/thesis/parte-2-descripcion-funcional.md` | Parte II — La Aplicación (§5–10). |
| `documentation/thesis/parte-3-el-sorteo.md` | Parte III — El Sorteo (§11–18). |
| `documentation/thesis/parte-4-ingenieria.md` | Parte IV — Ingeniería del sistema (§19–22). |
| `documentation/thesis/bibliografia.md` | Bibliografía (top-level section, real entries; a few `[NOTA]`s for access dates / citation style). |
| `documentation/thesis/apendices.md` | Apéndices A–H (A holds the data-model derivation; rest are placeholders). |
| `documentation/thesis/referencias/antecedentes-y-bibliografia.md` | Distilled Fierro facts + citation list → feeds §2 and Appendix L. |
| `app/Services/Lottery/`, `app/Models/`, migrations, `.docker/` | Code = source of truth for any factual claim about the app. |
| `scripts/benchmark_analysis/`, `storage/benchmarks/` | Real benchmark data for §16. |

Memory (`~/.claude/.../memory/`) holds the verified facts and working rules: `project_thesis`
(structure, verified data model / feature surface / lottery algorithm, known gaps),
`feedback_thesis_rules`, `feedback_working_method`, `feedback_draft_notes`.

## Format & export

Write in Markdown. Export to `.docx` via **pandoc** (installed) for the tutor / Google Docs. Keep
the `{=openxml}` page-break blocks between parts. Math uses LaTeX (`$...$`, `$$...$$`) → Word equations.

## Rules (full detail in memory `feedback_thesis_rules`)

- **Never invent facts.** For anything the code can answer, read the code — don't ask first. Ask
  only for what code can't tell you (rationale, history, intent).
- **Haiku-era docs are unreliable** on code specifics; verify before using. The in-app manuals
  (`resources/js/pages/Documentation/*`) are ~80–90% hallucinated — not a source.
- **`[NOTA: ...]` convention** for draft notes (Spanish, greppable); zero must survive to the final
  (`grep -rn "\[NOTA:" documentation/thesis/*.md`). Working-doc header blocks are also stripped for export.
- Language Diego can defend to the tribunal; describe what *is* (no "I was wrong" residue); no
  meta-commentary about a section; skip trivially-expected implementation minutiae.

## Working method (memory `feedback_working_method`)

Section by section, Diego reviews before moving on. **Flag any edit to already-reviewed text** so it
gets a second pass. Scaffold/write ahead when it prevents duplication.

## What the thesis must demonstrate (degree-level CS engineering)

Make these connections explicit where relevant: software engineering & design patterns (Observer,
Policy, Strategy/plug-and-play solvers, global scopes); databases (schema design, scoping,
migrations); algorithms & optimization (LP formulation, two-phase max-min, the binary-search
contribution); web/distributed systems (Inertia full-stack, real-time WebSockets, queues, Docker);
security (policy authz, project isolation, invitation-only); HCI (accessibility-first, WCAG-AA as
reference, mobile-first); DevOps (containerization, quality gates).

## Current status (2026-07-03)

Reviewed: Parte I §1–4, Parte II §5–9. Awaiting review: Parte II §8–10, **Parte III (whole)**,
Parte IV, appendices. Structure + numbering finalized; `sources/` deleted (useful bits →
`referencias/`); AI docs consolidated into this README. Next: Diego reviews Part III; then export
Parts I–IV + appendices for the tutor. Remaining `[NOTA]`s: appendix placeholders + a few content
flags (plan resizing/multi-level, real-time reliability, §16 visualization).
