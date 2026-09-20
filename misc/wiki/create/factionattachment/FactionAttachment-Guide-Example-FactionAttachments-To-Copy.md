# 11 — Example FactionAttachments to copy

← [[10 — Checklist|FactionAttachment Guide Finish Checklist]] · [[Index|Implementing a FactionAttachment Card]] · Next: [[12 — Glossary|FactionAttachment Guide Glossary And Helpers]]

**Always mirror.** Find the closest printed text below and open those files before writing yours.

Paths are under `modules/php/cards/` unless noted.

## Start here (teaching set)

| Attachment | Id | Why copy it |
|---|---|---|
| **Cavalier Hat** | `_7s5s/_01073.php` | Cleanest **equip restriction (Duelist) + City Action**. Best dual-gate template. |
| Action | `_7s5s/actions/Action_01073.php` | AttachmentAction + city gate. |
| **Tabard of the Fallen Musketeer** | `_7s5s/_01075.php` | Equip restriction (non-Diplomat) + **passive Musketeer grant** + City Action. |
| Action | `_7s5s/actions/Action_01075.php` | City Action that enters shared pressure flow. |
| **Lodestone** | `faf/_03065.php` | Neutral + **while-equipped condition** + **immediate sink City Action** (no GameState). |
| Action | `faf/actions/Action_03065.php` | Sink chain + move Home. |
| **Torres Cloak** | `faf/_03044.php` | Offhand + Duelist equip + **cancel Maneuver/Technique unless discard**. |
| Reaction | `faf/reactions/Reaction_03044.php` | Cancel-first multi-stage Reaction. |

## Equip restrictions

| Need | Mirror |
|---|---|
| Trait gate (Duelist / Strega) | `_01073`, `_03007` |
| Inverted trait (non-Diplomat) | `_01075` |
| Must have Weapon equipped | `_01050` |
| Opponent-equip (simple) | `_01021` Legion's Caress |
| Opponent-equip + Finesse vs ally performer | `_03066` Shackles |
| Equip-discount "opponent equips" footgun | `_03063` Smuggling Run (read with Shackles) |

## Passives / Forced / conditions

| Need | Mirror |
|---|---|
| Passive trait on equip/unequip | `_01075`, `_01198`, `_03043` |
| Auto-destroy when prerequisite lost | `_01050` |
| While-equipped: opponents cannot move Home | `_03065` Lodestone |
| While-equipped: cannot move at all | `_03066` Shackles |
| Forced destroy end of High Drama | `_03066` (trigger style also `_01025_Burden`) |
| Forced wound on en garde | `_01021` |

## Actions

| Need | Mirror |
|---|---|
| Simple City Action | `Action_01073` |
| Immediate sink + move Home | `Action_03065` |
| Engage this card + choose location | `Action_03055` Syrneth Compass |
| City Action issues a challenge | `Action_01198` |
| Sink chain reference (Technique twin) | `tac/techniques/Technique_02055` Dame of Swords |

## Reactions

| Need | Mirror |
|---|---|
| Simple engage + three buttons | `Reaction_01022` |
| Hard cancel Technique | Prefer `_03044` gates; shape also `Reaction_01047` / `01146b` |
| Cancel unless discard | `Reaction_03044` |
| Multi-stage Sorcerer City Reaction | `Reaction_03007` Matushka's Shears |
| After opposing moves adjacent | `Reaction_03019` Kaiser Schnurrbart (`Reaction_01066` character twin) |

## Techniques / Maneuvers

| Need | Mirror |
|---|---|
| Simple Technique (−Thrust + wound) | `Technique_01050` |
| Gambling + reveal + discard | `Technique_03043` El Gato's Mask |
| Gambling + remainder-of-duel condition | `Technique_03064` Harpoon |
| Maneuver + Sorcerer | `Maneuver_01133` Matushka's Efficiency |
| Attachment-hosted transition sourceId | `Technique_03043`, `Technique_02006` |

## Base classes worth reading once

| File | Why |
|---|---|
| `modules/php/cards/FactionAttachment.php` | Faction + WealthCost mixins; `CanEquipToOpponents` default |
| `modules/php/cards/Attachment.php` | `AttachedToId`, `isAttached`, `canAttachTo`, engage |
| `modules/php/cards/CityAttachment.php` | Confirm you are **not** implementing a city-deck attachment |

## How to mirror safely

1. Copy structure (methods, gates, event types), not unrelated card-specific filters.
2. Rename ids (`01073` → your number) everywhere: PHP class, state constants, JS state keys, `states.inc.php` transition keys.
3. Re-read your printed text after copying — equip gates, City vs Home, Engage this card vs Engage character, sink vs discard often differ.
4. When two mirrors conflict, prefer a newer FAF attachment that matches your expansion's style.
5. If the closest mirror is a **CityAttachment**, still use a FactionAttachment skeleton (`initializeFaction`, `CardNumber`, faction discard / sink).

## Next

Definitions and helper cheatsheet → [[12|FactionAttachment Guide Glossary And Helpers]]
