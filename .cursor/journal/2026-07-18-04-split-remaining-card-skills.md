# Converted remaining card skills to progressive disclosure

## Why

Eddie said "yes convert all" after create-risk + create-character splits. Remaining monoliths: city-character, scheme, faction-attachment, city-attachment, city-event-card (even the ~500-line ones for consistency).

## Results (Router lines)

| Skill | Before | After | Companions |
|---|---|---|---|
| create-city-character | 946 | 136 | 9 |
| create-scheme | 1333 | 145 | 11 (incl reactions/actions/walkthroughs) |
| create-faction-attachment | 968 | 145 | 10 |
| create-city-attachment | 575 | 117 | 11 (A–G) |
| create-city-event-card | 566 | 93 | 7 (incl sub-patterns) |
| create-risk (earlier) | 1660 | 151 | 9 |
| create-character (earlier) | 2966 | 248 | 9 |

All seven card-create skills now use the same shape: SKILL.md router + pattern/wiring/helpers/references/checklist companions. Content moved verbatim (CRLF). Canonical top blurbs dropped from routers where they duplicated references.md.

## Scheme-specific naming

Scheme has no Pattern D/E letters in the original — Reactions and Actions were top-level sections, so companions are `reactions.md` / `actions.md` / `walkthroughs.md` rather than forcing D/E. Pattern F (Forced Planning End) kept its letter.

## Watch-outs

- Em-dash in PowerShell scripts written via the Write tool got mojibake — use ASCII `-` in generated scripts, or write via PS here-strings.
- PowerShell `"$nlWhen ..."` parses as variable `$nlWhen` — ate the word "When" in all five routers. Fixed post-hoc. Always use `"${nl}When"` or string concat.
- `SKILL.md.bak` removed after verify; no backups left.
- When updating any skill: add to companion + shape-table row + checklist; do not re-monolith SKILL.md.
- city-character Pattern E is tiny (pointer to create-character) — kept as its own file for shape-table routing symmetry.

## Feelings

Done set. Uniform UX across all card skills is worth converting the already-under-500 ones too — agents learn one navigation pattern. Scheme's reactions/actions split is the right call; stuffing them into pattern-d/e would conflict with create-risk letter meanings.
