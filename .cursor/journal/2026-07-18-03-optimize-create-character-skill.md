# Split create-character skill (progressive disclosure)

## Why

Eddie said yes after create-risk split. create-character was worse: ~2966 lines (was quoted ~2265 earlier — file grew). Same problem: every card session appended blurbs + shape rows + pattern essays + finish items.

## What we did

Same template as create-risk:

| File | ~Lines | Role |
|---|---|---|
| `SKILL.md` | 248 | Router: intro, companions, siblings, distinction, both anatomies, shape table, short finish |
| `pattern-a.md` | 860 | Passive / Forced / handleEvent / eventCheck (largest) |
| `pattern-c.md` | 461 | Action / City Action |
| `pattern-f.md` | 202 | Challenge-issuing City Actions (kept F letter — no Pattern B in original) |
| `pattern-d.md` | 466 | Reactions |
| `pattern-e.md` | 448 | Techniques / Maneuvers |
| `wiring.md` | 129 | JS + pre-commit + style |
| `helpers.md` | 25 | Cross-cutting helpers |
| `references.md` | 91 | Exemplar table |
| `checklist.md` | 68 | Deep finish |

Content moved verbatim (CRLF). Canonical top blurbs dropped from router — they duplicate `references.md`.

## WHY keep Pattern F as its own file

Challenge-from-action is a distinct read path from general Pattern C multi-step. Agents issuing a Combat challenge shouldn't load all of Pattern C pressure/draw-discard prose, and vice versa. Letter gap (no B) preserved — inventing B would confuse vs create-risk's B.

## Watch-outs

- PowerShell `"@` here-strings eat backticks/`$vars` — use `@'...'@` (learned on create-risk).
- When adding new Character learnings: companion + shape-table row + checklist item; do not bloat SKILL.md.
- create-city-character / create-scheme / create-faction-attachment still monoliths if Eddie wants the same treatment next.
- Pattern A at 860 is still fat for a single companion — further split (passives vs bans vs location-counting) possible later if needed; progressive disclosure already wins vs loading 2966.

## Feelings

Router at 248 is under the 500 guideline and still carries the huge shape table (necessary for routing). Pattern A being 860 means a pure-passive card still loads a lot — acceptable for now; splitting A further is diminishing returns until someone complains.
