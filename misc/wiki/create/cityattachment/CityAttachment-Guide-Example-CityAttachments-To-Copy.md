# 10 — Example CityAttachments to copy

← [[09 — Checklist|CityAttachment Guide Finish Checklist]] · [[Index|Implementing a CityAttachment Card]] · Next: [[11 — Glossary|CityAttachment Guide Glossary And Helpers]]

**Always mirror.** Find the closest printed text below and open those files before writing yours.

Paths are under `modules/php/cards/` unless noted.

## Start here (teaching set)

| Attachment | Id | Why copy it |
|---|---|---|
| **Devil Jonah's Bones** | `faf/_03cd05.php` | **Canonical CityAttachment.** Forced wound on equip + steady-state +1 gamble reveal + mid-duel top/bottom choice. |
| State | `modules/php/States/faf/State_duelGambleSetup_03cd05.php` | Custom state inserted into duel gamble flow. |
| **Guild Triskelion** | `_7s5s/_01198.php` | Passive **Duelist** grant + **City Action** that issues a challenge. |
| Action | `_7s5s/actions/Action_01198.php` | AttachmentAction challenge hand-off. |
| **Smuggled Item** | `_7s5s/_01187.php` | Simple **destroy this card** AttachmentAction. |
| Action | `_7s5s/actions/Action_01187.php` | Unequip + city discard cost pattern. |
| **Sorte Deck** | `_7s5s/_01181.php` | AttachmentReaction with engage cost. |
| Reaction | `_7s5s/reactions/Reaction_01181.php` | Cancel/release/skipNextEvent pattern when needed. |
| **Silver Spine** | `faf/_03cd21.php` | **Pattern G** Forced once-per-Day cancel of opponent Risks + chip plumbing. |

## Forced / passives

| Need | Mirror |
|---|---|
| Forced wound on equip | `_03cd05` |
| Passive trait on equip/unequip | `_01198` (Faction twin: `tac/_02047`) |
| Forced once-per-Day Risk cancel | `_03cd21` (character twin: `_01186` Maryam — do not copy chip-removal bug) |
| Forced abilities precedent on attachments | `_01075` Tabard (FactionAttachment, same Forced-in-handleEvent idea) |

## Actions

| Need | Mirror |
|---|---|
| Destroy this card + effect | `Action_01187`, `Action_01191` (`isAttached()` guard) |
| City Action issues a challenge | `Action_01198` |
| Immediate Action shape (Faction twin) | `faf/actions/Action_03065` Lodestone — adapt destination to **city** discard if destroying |

## Reactions

| Need | Mirror |
|---|---|
| Engage this card + heal / effect | `Reaction_01181` |
| Cancel + release + skipNextEvent | `Reaction_01181` |
| Multi-stage / cancel-unless (Faction twins) | `Reaction_03044`, `Reaction_03007` |

## Steady-state / custom states

| Need | Mirror |
|---|---|
| +N gamble reveal via `get*` | `_03cd05` (`getNumberOfGambleCardsToReveal`) |
| Mid-duel player choice in auto flow | `_03cd05` + `State_duelGambleSetup_03cd05` |
| Setup event + globals | Bones journals / `EventGambleSetup` |

## Base classes worth reading once

| File | Why |
|---|---|
| `modules/php/cards/CityAttachment.php` | Thin city-deck attachment base |
| `modules/php/cards/Attachment.php` | `AttachedToId`, `isAttached`, `canAttachTo`, engage |
| `modules/php/cards/FactionAttachment.php` | Confirm you are **not** implementing faction-deck gear |

## How to mirror safely

1. Copy structure (methods, gates, event types), not unrelated card-specific filters.
2. Rename ids (`03cd05` → your number) everywhere: PHP class, state constants, JS state keys, `states.inc.php` transition keys.
3. Re-read your printed text after copying — destroy vs sink, Engage this card vs Engage character, city vs Home often differ.
4. When two mirrors conflict, prefer a newer FAF city attachment that matches your expansion's style.
5. If the closest mirror is a **FactionAttachment**, still use a CityAttachment skeleton (`CityCardNumber`, no `initializeFaction`, city discard on destroy).

## Next

Definitions and helper cheatsheet → [[11|CityAttachment Guide Glossary And Helpers]]
