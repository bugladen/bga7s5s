# 11 — Example Leaders to copy

← [10 — Checklist](10-checklist.md) · [Index](README.md) · Next: [12 — Glossary](12-glossary-helpers.md)

**Always mirror.** Find the closest printed text below and open those files before writing yours.

Paths are under `modules/php/cards/` unless noted.

## Start here (teaching set)

| Leader | Id | Why copy it |
|---|---|---|
| **Cesca del Rosso** | `faf/_03001.php` | Passive end-of-Dawn draw + City Reaction + two-step City Action. Best first complete example. |
| Reaction / Action | `faf/reactions/Reaction_03001.php`, `faf/actions/Action_03001.php` | Button Reaction; multi-step CharacterAction with targeting. |
| **Yevgeni** (base) | `_7s5s/_01116.php` | Passive duel combat-card hook + paired Reactions. |
| **Soline el Gato** | `_7s5s/_01089.php` | Passive duel Finesse aura + City Reaction move. |
| **Sanjay** | `faf/_03037.php` | Gambled Riposte passive + Collect Renown Reaction + never-engages challenge Action. |

## Passives

| Need | Mirror |
|---|---|
| End-of-Dawn / phase draw | Cesca `_03001` |
| Gambled combat card +Riposte | Sanjay `_03037` |
| Every combat card +Thrust | Yevgeni `_01116` |
| While wounded +Combat | Ise `_03016` |
| Wound prevention | Kaspar `_03014` |
| Muster + Approach forced effect | Joern `_03015` |
| Location-counting aura | Angeline `_03026`, Axelle `_04022`, Danilo `_04002` |
| Opponent Collects one fewer Renown | Ekaterina `_03049` |
| Adversary −Stat during duel | Tomoe Sango `_04043` (contrast Soline `_01089`) |

## Actions

| Need | Mirror |
|---|---|
| Two-step City Action (pick A then B) | Cesca `Action_03001` |
| Dual City Actions on one card | Damya `Action_03038a` / `03038b` |
| Draw then discard | Damya `Action_03038a` |
| Pressure with win-on-ties | Soline `Action_03040` |
| Move to Leader then En Garde attachment | Yepikhodov `Action_03051` |
| Reveal hand or Pass → move both | Giacinto `Action_04032` |

## Reactions

| Need | Mirror |
|---|---|
| After Sorcerer ability (not Sorcerer Reaction) | Cesca `Reaction_03001` |
| Button per opposing character + Pass | Cesca `Reaction_03001` |
| Continuous (fires every time) | Angeline `Reaction_03025`, Ekaterina `Reaction_03049` |
| Cancel Dusk move-Home opt-out | Ise `Reaction_03016a` |
| After enemy moves here | Ise `Reaction_03016b` |
| After any character moves here | Soline `Reaction_03040` |
| Click-to-pay Wealth in Reaction | Don Constanzo `Reaction_03003` |
| En Garde City Reaction | Desideria `Reaction_04003a` |
| End of High Drama claim uncontrolled | Sango `Reaction_04043` |

## Techniques

| Need | Mirror |
|---|---|
| Simple +1 Riposte | Angeline `Technique_03025a` |
| Gambling Technique | Aja `Technique_03002` |
| City location picker | Angeline `Technique_03025b` |
| Look / sink / reorder own deck | Benci `Technique_04001` |
| Private look at adversary hand | Yevgeni `Technique_03052` |
| +1 Thrust or +1 Parry choice | Iago `Technique_04033` |
| En Garde Technique +1 Thrust | Aimée `Technique_04021b` |

## Challenge Actions

| Need | Mirror |
|---|---|
| Engage + special intervene/refuse | Aja `Action_03002` |
| Never engages | Sanjay `Action_03037` |
| Performer ≠ owner (Thug issues) | Don Constanzo `Action_03003` |
| No intervention allowed | Raven `Action_04012` |
| Intervene follow-up (wound or draw) | Danilo `Action_04002` |
| Character-scoped refuse (no new type) | Daichi `_03050` |

## Base classes worth reading once

| File | Why |
|---|---|
| `modules/php/cards/Leader.php` | CrewCap, Panache, destroyed-Leader / scheme logic |
| `modules/php/cards/Character.php` | Stats, wounds, attachments, default `canChallenge` / `canIntervene` |

## How to mirror safely

1. Copy structure (methods, gates, event types), not unrelated card-specific filters.
2. Rename ids (`03001` → your number) everywhere: PHP class, state constants, JS state keys, `states.inc.php` transition keys.
3. Re-read your printed text after copying — engagement, City gates, and "target" wording often differ.
4. When two mirrors conflict, prefer the newer FAF/BAS Leader that matches your expansion's style.

## Next

Definitions and helper cheatsheet → [12](12-glossary-helpers.md)
