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
| `documentation/thesis/resumen.md` | YAML metadata (title/author) + Resumen (abstract). First file in the merge. |
| Export | `documentation/thesis/export-tesis.sh` (draft with marks and `cambios.md`; `--final` without; `--upload` updates the Google Doc in place). Each chapter file begins with an openxml page break. |
| `documentation/thesis/capitulo-1-introduccion.md` | Capítulo 1 — Introducción y contexto. |
| `documentation/thesis/capitulo-2-descripcion-funcional.md` | Capítulo 2 — Descripción funcional. |
| `documentation/thesis/capitulo-3-el-sorteo.md` | Capítulo 3 — El sorteo. |
| `documentation/thesis/capitulo-4-ingenieria.md` | Capítulo 4 — Ingeniería del sistema. |
| `documentation/thesis/capitulo-5-conclusiones.md` | Capítulo 5 — Conclusiones y trabajo futuro. |
| `documentation/thesis/bibliografia.md` | Bibliografía (top-level section, real entries; a few `[NOTA]`s for access dates / citation style). |
| `documentation/thesis/apendices.md` | Apéndices A–H (A holds the data-model derivation; rest are placeholders). |
| `documentation/thesis/referencias/antecedentes-y-bibliografia.md` | Distilled Fierro facts + citation list → feeds §2 and Appendix L. |
| `app/Services/Lottery/`, `app/Models/`, migrations, `docker/` | Code = source of truth for any factual claim about the app. |
| `documentation/thesis/cambios.md` | The current review round, as the tutor sees it: one line per comment (numbered after round 1's #1–14), linked to the change. Draft only. |
| `scripts/benchmark_analysis/`, `storage/benchmarks/` | Real benchmark data for §3.6 and Appendix C.3. |

Memory (`~/.claude/.../memory/`) holds the verified facts and working rules: `project_thesis`
(structure, verified data model / feature surface / lottery algorithm, known gaps),
`feedback_thesis_rules`, `feedback_working_method`, `feedback_draft_notes`.

## Format & export

Write in Markdown. Export to `.docx` via **pandoc** (installed) for the tutor / Google Docs. Keep
the `{=openxml}` page-break blocks before each chapter. Math uses LaTeX (`$...$`, `$$...$$`) → Word equations.

**Hierarchy and numbering.** Capítulo (`#`), sección (`##`), subsección (`###`). Headings carry no
number: `assets/numeracion.lua` numbers them ("Capítulo 3 — …", "3.5", "3.5.2"). Every heading
that is referenced has an id (`{#cap-sorteo}`, `{#sec-busqueda-binaria}`), and the text refers to it
by id: `la Sección @sec-resultados`, `(§@sec-bb-monotonia)`; the filter writes the number and fails
on an unknown id. Resumen, Bibliografía, Apéndices and `cambios.md` are `{.unnumbered}`; appendix
letters (A, A.1) are written by hand.

## Rules (full detail in memory `feedback_thesis_rules`)

- **Never invent facts.** For anything the code can answer, read the code — don't ask first. Ask
  only for what code can't tell you (rationale, history, intent).
- **Haiku-era docs are unreliable** on code specifics; verify before using. The in-app manuals
  (`resources/js/pages/Documentation/*`) are ~80–90% hallucinated — not a source.
- **`[NOTA: ...]` convention** for draft notes (Spanish, greppable); zero must survive to the final
  (`grep -rn "\[NOTA:" documentation/thesis/*.md`). Working-doc header blocks are also stripped for export.
- **Revision marks for the tutor.** Every change made in answer to a review is marked, so the tutor
  reads only what changed: `[texto]{.nuevo}`, `[texto]{.cambio}`, `[texto]{.quitado}` (or
  `::: nuevo` … `:::` blocks for whole paragraphs and sections), plus a note giving the reason,
  `[#22: motivo]{.rev #rev-22}`, numbered after the comment that prompted it. `cambios.md` opens the
  draft with one short line per comment, its number linking to that note (`[#22](#rev-22)`); no
  verbatim quotes, no entries for comments that needed no change. Structural changes (renumbering)
  are stated there in a sentence, not marked in the text. `assets/revision.lua` colors them; `export-tesis.sh --final` drops the marks and
  `cambios.md`. Before the next round's changes, the previous round's marks are accepted (unwrapped)
  once the tutor has seen them.
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

## Current status (2026-11-20)

The thesis was updated to the rewritten app (React/Mantine, open sign-up, per-project roles,
forum, chat, Assistant, AWS delivery): chapters 1–5 and appendices A–I follow the code as of
2026-11-20; the three Mermaid diagrams were regenerated (`mmdc` from a local npm install, using
the system Chrome). What was not touched: chapter 3's formulation and benchmarks, appendix C.3.
The solver's real tie-break and unbalanced-type limits are stated as facts (§3.2, §3.7) with the
fix in §5.2.6. Open `[NOTA]`s for Diego: how to present the autonomous rewrite (F.7), the
conclusions' self-critique, the DX appendix's framing, the list of real projects (§1.2.2);
plus §1.2.3's "la sección anterior". Rule: the body describes the current app only — no
history, no transient state, AI only in Appendix F (memory `thesis-describes-current-state`). Each new or rewritten section carries a `[Sección nueva/reescrita …]{.rev}`
note at its top, linked from `cambios.md`; whole sections are not coloured sentence by sentence.
