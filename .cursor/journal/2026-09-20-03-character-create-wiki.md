# Character create wiki

## What / why

Created beginner wiki at `misc/wiki/create/character/` mirroring the Leader guide structure (`README` + `01`–`12`).

WHY mirror Leader wiki shape (not dump skill companions verbatim):
- Audience is humans new to the project — progressive disclosure, worked examples, checklists.
- Skill files (`.claude/skills/create-character/`) stay the dense agent reference; wiki stays teachable.
- Same page numbering as Leader so someone who read one guide can navigate the other by habit.

WHY Character-specific teaching set (not Cesca):
- Cesca is a Leader — wrong skeleton for this guide (`CrewCap`/`Panache`/`Leader` trait traps).
- Aldo `_01007` = canonical regular Character (skill references.md says so) — passive + Action.
- Ise `_03016` = multi-ability without Action/JS — shows "complete set" can mean card + Reactions only.
- Damya `_03038` = dual Actions + wiring when they need states.

WHY emphasize muster/Approach dual-hook and `initializeFaction` early: those are the two Character traps that Leader beginners don't hit, and vice versa.

Also updated Leader README Related guides to link here (was a vague sentence).

## Files

```
misc/wiki/create/character/
  README.md
  01-what-is-a-character.md
  02-card-class.md
  03-classify-text.md
  04-passives.md
  05-actions.md
  06-reactions.md
  07-techniques-maneuvers.md
  08-challenges.md
  09-wiring.md
  10-checklist.md
  11-examples.md
  12-glossary-helpers.md
```

## Unfinished / follow-ups

- No city-character / attachment / scheme / risk wiki siblings yet (only Leader + Character).
- Wiki deliberately omits most edge-case rows from skill shape table — points at `.claude/skills/create-character/` for those.
- Did not rewrite skill companions; wiki is derived, not a replacement.

## GitHub wiki publish

Pushed to https://github.com/bugladen/bga7s5s/wiki/Implementing-A-Character-Card
- Replaced short Maya stub `Implementing-A-Character-Card.md` with full hub
- Added 12 `Character-Guide-*.md` pages with `[[wiki links]]`
- Updated Leader hub Related guides to link Character guide
- Commit `33940bb` on `bga7s5s.wiki` master

WHY replace stub instead of new top-level page: Home.md already links [[Implementing a Character Card]] — keep that entry point so existing wiki nav still works.

## Link fix (088fcfe)

User reported Character Guide Passives links broken. Root cause: GitHub/gollum wiki syntax is `[[display text|Page Name]]` (same as existing Scheme stub `[[location | Folder-Structure]]`). We had MediaWiki-ish `[[Page Name|display]]`, so targets were literal "04 — Passives" (no such page) while visible text looked like the real title.

Also: pipes inside `[[...|...]]` break markdown tables — converted those to `[text](Page-Slug)`.

Fixed Character **and** Leader guides (same bug on Leader since original publish).

## Local source now GitHub-push-ready (this session)

Replaced numbered local files (`01-*.md`, `README.md`) under `misc/wiki/create/{character,leader}/` with the exact wiki filenames and contents from `088fcfe`:

- `Implementing-A-*-Card.md` hubs
- `Character-Guide-*.md` / `Leader-Guide-*.md`
- Links already `[[display|Page Name]]`; tables use `[text](Slug)` 
- Added `misc/wiki/create/PUBLISH.md` with copy/push instructions

WHY overwrite local numbered layout: conversion step (relative .md → wiki links) caused the broken-order bug. Source of truth must be copy-as-is.

Sanity script confirmed: no leftover `](*.md)` links; no `[[Page|display]]` wrong-order links.
