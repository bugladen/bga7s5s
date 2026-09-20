# 09 — Wiring states and JavaScript

← [[08 — Passives, Forced, and discounts|Risk Guide Passives Forced And Discounts]] · [[Index|Implementing a Risk Card]] · Next: [[10 — Finish checklist|Risk Guide Finish Checklist]]

Only needed when your ability adds a **card-specific** interactive state (chooser, buttons beyond the shared challenge picker, etc.). Shared challenge target selection needs a transition map entry but **no** new GameState class.

## Where transitions live

| Ability kind | Transition map | Typical state id |
|---|---|---|
| High Drama Action / City Action chooser | `HIGH_DRAMA_PLAYER_TURN_EVENTS` | `4` + card number (`_03009` → `403009`) |
| Shared challenge target | same map → `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` | (shared) |
| Maneuver choice / resolve chooser | `DUEL_RESOLVE_MANEUVER_EVENTS` | `5250` + card number style |
| Final Strike after death | `DUEL_END_OF_ROUND_EVENTS` | `529…` style |
| "You choose their gambled combat card" | `DUEL_GAMBLE_REVEALED_EVENTS` | near choose-gamble family |
| Optional engage before pay (Pattern A.7) | `HIGH_DRAMA_IN_HAND_ACTION_EVENTS` | card `_2` state |

Also register the constant in `modules/php/States.php` and define the state (prefer a GameState class under `modules/php/States/<expansion>/`).

## GameState class vs legacy array

| Format | Where | Transition style |
|---|---|---|
| **GameState class** (prefer for new work) | `States/<expansion>/State_….php` | **Named** keys (`"locationChosen"`); call `nextState("locationChosen")` |
| Legacy array | `states.7s5s.php` | May use `""` default; `nextState()` with no arg |

Do not use `""` as a transition key on GameState classes.

## Miyato / Ota mirror (Neutral / Ussura Maneuvers)

If a Maneuver queues `createTransitionEvent(..., "NNNNN", …)` and the Risk is **Neutral or Ussura**, also add `"NNNNN" => …` under `DUEL_CHOOSE_TECHNIQUE_EVENTS.transitions`. Without it, Miyato/Ota's clone path throws an impossible transition.

Normal combat-card play only needs `DUEL_RESOLVE_MANEUVER_EVENTS`.

## JavaScript trio

For each new interactive High Drama / duel chooser state, add matching handlers in the expansion's JS files (e.g. `modules/js/On*.faf.js`):

1. **`OnEnteringState`** — highlight performer / make targets or locations selectable; stash ids in `clientStateArgs`
2. **`OnUpdateActionButtons`** — Confirm button (often starts disabled)
3. **`OnLeavingState`** — clear highlights / `resetCityLocations()` / clear `clientStateArgs`

Location-chooser pattern: copy `highDramaPhase03009` (or `03032` / `03045`).  
Character-chooser pattern: copy `highDramaPhase03011` / `03060` / `03071`.

### Skip the JS trio when…

The chooser is entirely **reaction buttons** inside `playerReaction` (multi-stage RiskReactions like Confusion `_03068`). No GameState, no On*.js.

## Zombie transitions

Every activeplayer state needs a `"zombie"` transition back to a safe events state so disconnected players do not soft-lock the table.

## Pre-commit reminders while wiring

| File shape | Required literal |
|---|---|
| `RiskAction` / `RiskCityAction` | `createActionResolvedEvent` |
| `Maneuver` | cancel handler **or** `// EventManeuverCanceled handler not needed` |
| `RiskReaction` | hand `==` guard + `setUsed` + `isAvailable` |
| `ISorcererAbility` | start **and** played sorcerer events |

## Style notes

- Prefer `$game->getPlayerNameById($id)` over deprecated `getActivePlayerName()`
- Throw `\Bga\GameFramework\UserException` (not deprecated `BgaUserException`)
- Typed parameters on every method (`Game $game`, `int $playerId`, …)
- PSR-12, 4-space indent, braces on their own line

## Next

Run the checklist → [[10 — Finish checklist|Risk Guide Finish Checklist]]
