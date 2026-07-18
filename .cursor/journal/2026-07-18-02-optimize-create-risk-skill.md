# Optimizing create-risk SKILL.md

## Context

Eddie asked how to optimize create-risk. File is ~1660 lines; Cursor create-skill guidance says keep SKILL.md under 500 and use progressive disclosure. create-character is worse (~2265). Sibling skills have zero companion files — everything is one monolith.

## Diagnosis (WHY it's large)

It grew as a session-learning dump: every new Risk (03033–03056…) appended a canonical blurb, a shape-table row, a full pattern subsection, AND a "When You Finish" checklist item. Same fact lives 3–4 times. Pattern C alone is ~616 lines. The top canonical-ref list is ~40 dense one-paragraph exemplars that load on every invoke even when the card is a simple City Action.

## Recommended shape (don't implement unless asked)

**SKILL.md (router, ~200–350 lines):**
- description / triggers
- base anatomy (short skeleton)
- "Pick the Right Ability Shape" table (router only)
- one-line pointer list: which companion file to open
- short finish checklist (hooks + traits + php -l) — not the 38-item essay

**Companions (read on demand):**
- `pattern-a.md` / `pattern-b.md` / `pattern-c.md` / `pattern-d.md` / `pattern-e.md`
- `wiring.md` (states + JS + pre-commit)
- `references.md` (card id → pattern tags + path; replace the essay blurbs)
- optionally `checklist.md` for the deep finish items

WHY progressive disclosure: agent only pays tokens for the pattern matching the printed Text. A City Action shouldn't load C.5 gamble hijack prose.

## What NOT to do

- Don't "summarize until vague" — regression risk is the whole point of this skill; keep WHYs in the pattern files.
- Don't nest references deeper than one level from SKILL.md.
- Don't merge with create-character yet — shared Maneuver/Technique content is real but a shared file needs careful ownership so both skills don't drift.

## Feelings

The skill is doing its job (agents don't invent patterns) but it's past the point where loading the whole thing is free. Split by pattern is the highest-ROI change. Deduplicating the finish checklist against pattern sections is the second.

## Done (Eddie said yes — implement the split)

Executed progressive disclosure. Content preserved (CRLF intact), not summarized.

| File | ~Lines | Role |
|---|---|---|
| `SKILL.md` | 152 | Router: intro, companion table, anatomy, shape table, short finish |
| `pattern-a.md` … `pattern-e.md` | 266 / 59 / 619 / 280 / 73 | Full pattern WHYs |
| `wiring.md` | 100 | states / JS / hooks / style |
| `helpers.md` | 42 | Theah/Game helpers |
| `references.md` | 42 | exemplar table |
| `checklist.md` | 56 | deep finish items 1–38 |

WHY this split: agent loads ~152 always; Pattern C (619) only when Maneuver. City Action runs should not pay for C.5 gamble hijack.

Watch-outs for future agents updating the skill:
- Add new pattern detail to the matching companion, not SKILL.md
- Add a shape-table row + short finish pointer in SKILL.md if it's a new top-level route
- Put deep regression traps in checklist.md
- PowerShell double-quoted here-strings eat backticks and `$vars` — use `@'...'@` when editing these files from scripts
- create-character (~2265) is the same problem; use this as the pilot template

Not done: create-character split, shared Maneuver doc with create-character.
