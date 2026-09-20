# CityAttachment create wiki pages

## Task
User wants beginner-facing GitHub-push-ready wiki pages on creating a complete CityAttachment class set. Place in `misc/wiki/create/cityattachment`, push to GitHub wiki. Use create-city-attachment skill + FactionAttachment/CityCharacter wiki as templates. Explain when to use CityAttachment.

## Context inherited
2026-09-20 FactionAttachment wiki (`05`) established hub + guide map, `[[wiki link]]` conventions, PUBLISH.md map, Home links. CityAttachment skill companions encode Patterns A–G; canonical teaching card is Devil Jonah's Bones `_03cd05`.

## Plan / WHY
Mirror FactionAttachment page set so beginners see one familiar map. Deliberately **replace Equip Restrictions + Techniques pages** with **Steady-State And Custom States** — those are the CityAttachment-specific beginner landmines (override `get*` not mutate globals; insert setup states before auto game-states). Equip restrictions still mentioned briefly; Techniques rare on city gear so pointed from Classify/Examples only.

Walking examples:
- Devil Jonah's Bones `_03cd05` — Forced wound + steady-state + mid-duel custom state
- Guild Triskelion `_01198` — passive Duelist + City Action challenge
- Smuggled Item `_01187` — destroy-cost AttachmentAction
- Sorte Deck `_01181` — AttachmentReaction
- Silver Spine `_03cd21` — Pattern G once-per-Day Risk cancel

WHY note Riposte carefully: pre-commit requires Riposte on FactionAttachment only — CityAttachment is excluded. Skill finish blurb says "always set Riposte" but hook does not; wiki follows the hook.

WHY destroy → city discard: FactionAttachment sink chains (faction deck bottom) would be wrong for city gear; Actions page calls this out hard.

## Status
Done. Pushed wiki commit `2f179db` to `bga7s5s.wiki` master.

## What shipped (local + wiki)
12 pages under `misc/wiki/create/cityattachment/`:
1. `Implementing-A-CityAttachment-Card.md` — hub, use-case table, walking examples
2. What Is / Card Class / Classify
3. Passives And Forced (A, B, G)
4. Actions / Reactions
5. Steady-State And Custom States (E + F — CityAttachment specialty)
6. Wiring / Checklist / Examples / Glossary

Also: PUBLISH.md map; Home.md wiki link; FactionAttachment + CityCharacter hubs cross-link CityAttachment guide.

Local source remains in main repo under `misc/wiki/create/` (untracked until user commits game repo). Wiki clone at `wiki-tmp/` after push.

## Feel
Same altitude as FactionAttachment wiki. Biggest beginner landmines called out repeatedly: Forced≠Reaction, steady-state≠global mutation, destroy→city discard, no AttachmentCityAction, no initializeFaction, no Riposte requirement, Pattern G chip/divId gotchas.
