# 11 — Example Characters to copy

← [[10 — Checklist|Character Guide Finish Checklist]] · [[Index|Implementing a Character Card]] · Next: [[12 — Glossary|Character Guide Glossary And Helpers]]

**Always mirror.** Find the closest printed text below and open those files before writing yours.

Paths are under `modules/php/cards/` unless noted.

> Prefer these **Character** exemplars over Leader exemplars when the text matches. Ability patterns are shared, but card-class skeletons differ (`initializeFaction`, no `CrewCap` / `Panache`).

## Start here (teaching set)

| Character | Id | Why copy it |
|---|---|---|
| **Aldo Bussotti** | `_7s5s/_01007.php` | Canonical regular Character. Location-Renown Influence passive + one-step City Action. |
| Action | `_7s5s/actions/Action_01007.php` | Simple CharacterAction with picker wiring. |
| **Schwester Ise** | `faf/_03016.php` | Self-condition Combat passive + cancel-and-reissue Reaction + enemy-moves-here Reaction. No Action, no JS. |
| Reactions | `faf/reactions/Reaction_03016a.php`, `Reaction_03016b.php` | Dusk opt-out; pull-friendly-to-here buttons. |
| **Damya Kahina** | `faf/_03038.php` | Dual City Actions (`a`/`b`) with hand discard and attachment destroy. Best multi-Action + state/JS example. |
| **Joern Kietelsson** | `faf/_03015.php` | Pure passives only — muster/Approach, Dusk Resolve penalty, challenge-refused heal. No ability files. |

## Passives

| Need | Mirror |
|---|---|
| Location Renown → Influence | Aldo `_01007` |
| While wounded +Combat | Ise `_03016` |
| Muster + Approach forced effect | Joern `_03015` |
| Phase-conditional Resolve | Joern `_03015` (also Danilo `_04002` for Resolve client notif) |
| Wound prevention | Kaspar `_03014` |
| Weapon equip → +Stat | Rena `_01040`, Íñigo `_03039` |
| Location-counting aura | Edeline `_01037`, Angeline `_03026`, Axelle `_04022`, Benci `_04001` |
| Combat-card trait → Lethal | Jak-Sen `_04041`, Rosa `_02033` |
| Adversary cannot Maneuver (dueling-line trait) | Raven `_04012` |
| Opposing trait −Influence at location | Giacinto `_04032` |

## Actions

| Need | Mirror |
|---|---|
| Simple City Action (move Renown) | Aldo `Action_01007` |
| Dual City Actions on one card | Damya `Action_03038a` / `03038b` |
| Draw then discard | Damya `Action_03038a` |
| Move equipped + destroy attachment + draw | Damya `Action_03038b` |
| Pressure with win-on-ties | Soline `Action_03040` |
| Move target Mercenary Home | Hans `Action_04011` |
| Move opposing engaged Home | Makepeace `Action_01092` |
| Move to Leader then En Garde attachment | Yepikhodov `Action_03051` |
| Reveal hand or Pass → move both | Giacinto `Action_04032` |

## Reactions

| Need | Mirror |
|---|---|
| Cancel Dusk move-Home opt-out | Ise `Reaction_03016a` |
| After enemy moves here | Ise `Reaction_03016b` |
| After any character moves here | Soline `Reaction_03040` |
| Continuous (fires every time) | Angeline `Reaction_03025`, Aimée `Reaction_04021` |
| Click-to-pay Wealth in Reaction | Don Constanzo `Reaction_03003` |
| Would-be-discarded attachment → equip | Tomas `Reaction_04013` |
| Muster → search City Deck Artifact | Kaj `Reaction_04042` |
| En Garde first-round remove threat | Andare `Reaction_04031` |
| Cancel opponent move or engage | Jak-Sen `Reaction_04041` |

## Techniques

| Need | Mirror |
|---|---|
| Simple +1 Riposte | Angeline `Technique_03025a` |
| Gambling Technique | Aja `Technique_03002`, Íñigo `Technique_03039` |
| Trait-gated combat card | Elena `Technique_03004`, Daichi `Technique_03050` |
| Adversary discards a card | Maya `Technique_01093`, Íñigo `Technique_03039` |
| Destroy Owner attachment + Thrust | Tomas `Technique_04013` |
| Look / sink / reorder own deck | Benci `Technique_04001` |
| En Garde Technique +1 Thrust | Aimée `Technique_04021b` |
| +1 Thrust or +1 Parry + deferred threat | Iago `Technique_04033` |
| Copy Technique on other Trait | Aimée `Technique_04021a` |

## Challenge Actions

| Need | Mirror |
|---|---|
| Engage + special intervene/refuse | Aja `Action_03002` |
| Performer ≠ owner (Thug issues) | Don Constanzo `Action_03003` |
| No intervention allowed | Raven `Action_04012`, Valeri `Action_01123` |
| Never engages | Sanjay `Action_03037` (Leader Action — Pattern F identical) |
| Intervene follow-up (wound or draw) | Danilo `Action_04002` |
| Character-scoped refuse (no new type) | Daichi `_03050` |

## Base classes worth reading once

| File | Why |
|---|---|
| `modules/php/cards/Character.php` | Stats, wounds, attachments, default `canChallenge` / `canIntervene`, built-in `TechniqueTrait` |
| `modules/php/cards/Leader.php` | Only if you accidentally opened a Leader stub — shows what Characters lack |
| `modules/php/cards/CityCharacter.php` | Confirm you are **not** implementing a city-deck character |

## How to mirror safely

1. Copy structure (methods, gates, event types), not unrelated card-specific filters.
2. Rename ids (`01007` → your number) everywhere: PHP class, state constants, JS state keys, `states.inc.php` transition keys.
3. Re-read your printed text after copying — engagement, City gates, and "target" wording often differ.
4. When two mirrors conflict, prefer a newer FAF/BAS Character that matches your expansion's style.
5. If the closest mirror is a **Leader**, still use a Character card-class skeleton (`initializeFaction`, no CrewCap/Panache).

## Next

Definitions and helper cheatsheet → [[12|Character Guide Glossary And Helpers]]
