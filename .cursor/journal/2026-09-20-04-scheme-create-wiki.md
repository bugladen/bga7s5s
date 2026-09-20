# Scheme create wiki

## What / why

Created beginner wiki at `misc/wiki/create/scheme/` mirroring Character/Leader guide structure (hub + 12 `Scheme-Guide-*.md` pages).

WHY mirror Leader/Character wiki shape (not dump skill companions verbatim):
- Audience is humans new to the project — progressive disclosure, worked examples, checklists.
- Skill files (`.claude/skills/create-scheme/`) stay the dense agent reference; wiki stays teachable.
- Same page numbering habit so someone who read Character/Leader can navigate by muscle memory.

WHY Scheme-specific page split (not Character's Passives/Techniques pages):
- Schemes' unique teaching load is Planning resolve + Home-until-Dusk lifecycle + three transition maps (resolve / planning-end / HD).
- Replaced Techniques page with Resolve Effects; merged When-Revealed + Forced + equip passives into one page.
- Kept Challenge Actions as its own page — schemes have Pattern E/G and beginners need the engage trichotomy map.

WHY teaching set Crash the Party / No Mercy / Curry Favor:
- `_02004` = trivial resolve + Reaction, no resolve GameState.
- `_03005` = modern GameState resolve pick + Reaction (canonical new pattern).
- `_03053` = two-location resolve + Pattern H immediate City Action (teaches "don't invent an HD state").

WHY folder `scheme` not user's typo `sheme`: matches `character`/`leader` and skill name.

## Files

```
misc/wiki/create/scheme/
  Implementing-A-Scheme-Card.md
  Scheme-Guide-What-Is-A-Scheme.md
  Scheme-Guide-The-Card-Class.md
  Scheme-Guide-Classify-The-Printed-Text.md
  Scheme-Guide-Resolve-Effects.md
  Scheme-Guide-When-Revealed-Forced-And-Passives.md
  Scheme-Guide-Actions.md
  Scheme-Guide-Reactions.md
  Scheme-Guide-Challenge-Actions.md
  Scheme-Guide-Wiring-States-And-JavaScript.md
  Scheme-Guide-Finish-Checklist.md
  Scheme-Guide-Example-Schemes-To-Copy.md
  Scheme-Guide-Glossary-And-Helpers.md
```

Also: updated `PUBLISH.md` copy map; Character/Leader hubs Related guides now link Scheme.

## Intentionally omitted (point at skill)

Deep Pattern I/J/K edge cases, full discard-to-refuse wiring essays, every checklist row from skill checklist.md — beginners drown; examples page is the escape hatch.

## GitHub wiki publish

Pushed to https://github.com/bugladen/bga7s5s/wiki/Implementing-A-Scheme-Card
- Replaced short Armed-and-Marshaled stub with full hub
- Added 12 `Scheme-Guide-*.md` pages with `[[display|Page Name]]` links
- Updated Character/Leader hubs Related guides
- Commit `ae9292d` on `bga7s5s.wiki` master

WHY replace stub: Home.md already links [[Implementing a Scheme Card]].

## Follow-up: How-to-use link rename

User asked to rename How-to-use wiki links to match Character/Leader.

WHY renumber (not just cosmetic):
- Character/Leader use 05=Actions, 06=Reactions, 07=Techniques-slot, 08=Challenges.
- Scheme had Actions=06 / Reactions=07 — broke the shared muscle memory.
- Moved When Revealed/Forced/Passives into the 07 slot (Techniques analogue).
- Also fixed wiki targets: `When-Revealed` → `When Revealed` (Gollum title has spaces).
- Hub blurbs/bare bullets under item 4 now match Character formatting.

Pushed as follow-up commit on bga7s5s.wiki master.
