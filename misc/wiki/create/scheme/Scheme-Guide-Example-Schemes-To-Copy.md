# 11 — Example Schemes to copy

← [[10 — Checklist|Scheme Guide Finish Checklist]] · [[Index|Implementing a Scheme Card]] · Next: [[12 — Glossary|Scheme Guide Glossary And Helpers]]

**Always mirror.** Find the closest printed text below and open those files before writing yours.

Paths are under `modules/php/cards/` unless noted.

> Prefer **Scheme** exemplars over Character exemplars when the text matches. Action/Reaction habits overlap, but scheme resolve, Home lifecycle, and `SchemeCityAction` are scheme-specific.

## Start here (teaching set)

| Scheme | Id | Why copy it |
|---|---|---|
| **Crash the Party** | `tac/_02004.php` | Trivial dual Renown resolve + City Reaction. No resolve GameState. |
| Reaction | `tac/reactions/Reaction_02004.php` | Pressure trigger; capture location; adjacent move. |
| **No Mercy** | `faf/_03005.php` | Renown + trait-filtered discard pick (modern GameState class) + Reaction. |
| Reaction | `faf/reactions/Reaction_03005.php` | Challenge refused → claim; `setUsed` discipline. |
| State | `States/faf/State_planningPhaseResolveSchemes03005.php` | Canonical new resolve-pick state. |
| **Curry Favor** | `faf/_03053.php` | Two-location resolve + immediate City Action (no HD GameState). |
| Action | `faf/actions/Action_03053.php` | Spend score Renown → claim → opponents draw. |

## Resolve

| Need | Mirror |
|---|---|
| Named-location Renown only | Crash the Party `_02004` |
| Pick one city location | Winter's Wind `_02046`, Épée Sanglante `_01071` |
| Two different locations | Premonition `_03006`, Sworn Swords `_03030`, Curry Favor `_03053` |
| Trait-filtered discard → hand | No Mercy `_03005` |
| Attachment from discard | Armed and Marshaled `_01044` (legacy inline state — prefer `_03005` shape for new work) |
| Add **or** move Renown | Kaspar's Occupation `_02014` |
| When-Revealed + each opponent picks | Shifting Tides `_01151` |

## When-Revealed / Forced / Passives

| Need | Mirror |
|---|---|
| When-Revealed cleanup before resolve | Shifting Tides `_01151` |
| Forced at Planning End (opponent pick) | The Cat's Embargo `_01098` + `State_planningPhaseEnd_01098` |
| Forced draw-then-discard at Planning End | Proper Study `_03041` + `State_planningPhaseEnd_03041` |
| Forced at High Drama End | Burn like Mice `_03061` + `State_highDramaEnd_03061` |
| Forced on destroy during duel | Gutter Full of Roses `_02052` |
| Opponent equip costs +1 | Smuggling Run `_03063` (`getEquipDiscount`) |

## Actions

| Need | Mirror |
|---|---|
| Immediate spend → claim → opponents draw | Curry Favor `Action_03053` |
| Branched Sorcerer Porté moves | Hour of Blood `Action_03029` |
| Wound unequipped → Resolve pressure → wound+Home | No Steel, No Surrender `Action_03054` |
| Move Renown or available attachment | Smuggling Run `Action_03063` |
| Muster from Locker; dusk return | Deal with the Devil `Action_03062` |
| Hero trait City Action | Burn like Mice `Action_03061` |
| Leader City Action on a scheme | Kaspar's Occupation `Action_02014` |

## Reactions

| Need | Mirror |
|---|---|
| After challenge refused → claim | No Mercy `Reaction_03005` |
| After opponent pressures → move adjacent | Crash the Party `Reaction_02004` |
| After you claim → move Renown | Proper Study `Reaction_03041` |
| After your character destroyed in city | Noble Sacrifice `Reaction_03017` |
| Multi-stage + cross-player (Strega) | Premonition `Reaction_03006` |

## Challenge Actions

| Need | Mirror |
|---|---|
| Split Diplomat engager / Duelist challenger + intervene gate + accept threat | Sworn Swords `Action_03030` |
| Engage → Finesse challenge + discard-to-refuse | When Least Expected `Action_03042` |

## Wiring references

| Need | Mirror |
|---|---|
| Modern resolve GameState | `States/faf/State_planningPhaseResolveSchemes03005.php`, `...03053.php` |
| Planning-End Forced state | `States/faf/State_planningPhaseEnd_03041.php` |
| HD multi-step Action | `States/faf/State_highDramaPhase03029.php` (+ `_2`, `_3`) |
| Two-location `actionMap` | Search `PlayerActions.js` for `planningPhaseResolveSchemes_03053` |

## Next

Jargon and helpers → [[12 — Glossary|Scheme Guide Glossary And Helpers]]
