# 11 — Example CityCharacters to copy

← [[10 — Checklist|CityCharacter Guide Finish Checklist]] · [[Index|Implementing a CityCharacter Card]] · Next: [[12 — Glossary|CityCharacter Guide Glossary And Helpers]]

**Always mirror.** Find the closest printed text below and open those files before writing yours.

Paths are under `modules/php/cards/` unless noted.

> Prefer these **CityCharacter** exemplars over faction Character exemplars when the stub `extends CityCharacter`. Ability patterns overlap, but city-deck fields and muster lifecycle differ.

## Start here (teaching set)

| CityCharacter | Id | Why copy it |
|---|---|---|
| **Penya** | `faf/_03cd01.php` | Canonical CityCharacter. Dashed stats + intervene ban + City Forced + multi-step City Action + state/JS. |
| Action | `faf/actions/Action_03cd01.php` | Engage-as-cost, companion → adjacent location, `CHOSEN_TARGET`. |
| States | `modules/php/States/faf/State_highDramaPhase03cd01.php` (+ `_2`) | Character picker then location picker with Back. |
| JS | `modules/js/OnEnteringState.faf.js`, `OnUpdateActionButtons.faf.js` | Both Penya steps. |
| **Julius Caligari** | `faf/_03cd10.php` | Canonical CityCharacter Reaction wiring (`IHasReactions`). |
| Reaction | `faf/reactions/Reaction_03cd10.php` | Multi-step buttons; recruit + move triggers; `toLocation` gotcha; trait picker; hand reveal. |
| **Kalla and Adelheide** | `faf/_03cd18.php` | Branching "Choose one" post-recruit Reaction. |
| Reaction | `faf/reactions/Reaction_03cd18.php` | Per-option validity, deck search + shuffle, unequip+discard destroy, no Back after commit. |

## Hard bans / Forced / passives

| Need | Mirror |
|---|---|
| Cannot intervene + `eventCheck` | Penya `_03cd01` |
| City Forced on duel start | Penya `_03cd01` (`EventDuelStarted`) |
| Would-be-wounded cancel + Forced | Penya `_03cd01` (also Maryam `_01186` for BeingWounded cancel on a non-city Character) |
| Remove to city deck + shuffle follow-up | Penya `_03cd01` (`EventCardRemovedFromPlay`) |

## Actions

| Need | Mirror |
|---|---|
| Two-step City Action (character → location) | Penya `Action_03cd01` |
| Engage cost then move with `engage=false` | Penya `Action_03cd01` |
| Simple one-step City Action (faction Character, same Action base) | Aldo `Action_01007` — use for Action shape only; keep CityCharacter card skeleton |

## Reactions

| Need | Mirror |
|---|---|
| Multi-step trait / target buttons | Julius `Reaction_03cd10` |
| Recruit + move-to-city triggers | Julius `Reaction_03cd10` |
| Branching Choose one | Kalla `Reaction_03cd18` |
| Search deck for attachment + shuffle | Kalla `Reaction_03cd18` (also Path to Poluchatel `Action_02045` for search recipe) |
| Destroy attachment (unequip + discard) | Kalla `Reaction_03cd18`, `Action_01174` |
| Reveal random hand card | Julius `Reaction_03cd10`, Cat's Embargo `_01098` |

## Techniques / Challenges

CityCharacter Technique and Challenge Action shapes match faction Characters. Use [[Character examples|Character Guide Example Characters To Copy]] for those tables, but keep your card class as `CityCharacter`.

## Base classes worth reading once

| File | Why |
|---|---|
| `modules/php/cards/CityCharacter.php` | `Negotiable` field and inheritance chain |
| `modules/php/cards/Character.php` | Stats, wounds, default `canIntervene` / `canChallenge`, `TechniqueTrait` |
| `modules/php/Traits.php` | `TraitNames::$TraitsJson` for "Name a Trait" pickers |

## How to mirror safely

1. Copy structure (methods, gates, event types), not unrelated card-specific filters.
2. Rename ids (`03cd01` → your number) everywhere: PHP class, state constants, JS state keys, `states.inc.php` transition keys.
3. Re-read your printed text after copying — Negotiable, WealthCost, City Forced scope, and Engage wording often differ.
4. If the closest mirror is a faction Character Action/Reaction, still use a CityCharacter card-class skeleton (`CardNumber = 0`, `CityCardNumber`, `WealthCost`, no `initializeFaction`).

## Next

Definitions and helper cheatsheet → [[12|CityCharacter Guide Glossary And Helpers]]
