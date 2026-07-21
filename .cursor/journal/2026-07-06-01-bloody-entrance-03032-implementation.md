# Bloody Entrance (_03032) Implementation

## Context

Eddie asked to finish Montaigne Risk **Bloody Entrance** — Sorcerer City Action only. Uses `EXTRA_ACTIONS` for the follow-up turn but needed a **new framework pattern** to lock the performer.

## Card Text

**Sorcerer City Action:** Wound your performer • Move them to any location, then they may perform another action. *(It must be performed and they must be the performer of the action)*

## Action Implementation (`Action_03032`)

- `RiskCityAction` + `ISorcererAbility`
- Sorcerer performers in city with at least one valid destination (any city location + Home if not already there — same destination pool as `Action_03029` MOVE_FROM_PERFORMER)
- Sub-state `HIGH_DRAMA_PLAYER_TURN_03032` location chooser (modeled on `03009`)
- Effect order: sorcery start → wound (with `eventCheck`) → move (with `eventCheck`) → sorcery played → grant extra action → action resolved

## New Pattern: `EXTRA_ACTION_PERFORMER`

**Problem:** `EXTRA_ACTIONS` alone only keeps the same *player* on turn. Bloody Entrance requires the same *character* as performer, and passing must be forbidden.

**Solution:** Pair globals:
- `EXTRA_ACTIONS = 1` — consumed in `stNextPlayer` (existing)
- `EXTRA_ACTION_PERFORMER = characterId` — cleared when turn actually advances to next player (else branch of `stNextPlayer`)

**WHY not reuse `CHOSEN_PERFORMER`:** That global is wiped at the start of every `stNextPlayer` along with other action globals. Need something that survives across the extra-action boundary.

### Enforcement points

1. **`argPlayerTurn`** — when locked: `mustPerformAction=true`, hide brutes, recompute each `can*` via `Theah::characterCan*` for that one character; filter in-hand/in-play availability via `actionAvailableToPerformer`
2. **All performer-chooser args** — `filterPerformerIdsForExtraAction()` → only locked id
3. **All `actHighDrama*PerformerChosen`** — `assertIsExtraActionPerformer($id)`
4. **In-hand/in-play action chosen** — validate `actionAvailableToPerformer`; pre-set `CHOSEN_PERFORMER` when action doesn't require performer selection
5. **`actHighDramaPass`** — throws when `mustPerformExtraAction()`
6. **JS `OnUpdateActionButtons.js`** — hide Pass when `mustPerformAction`

### Theah helpers added

`characterCanMove/Recruit/Equip/BasicChallenge/BasicClaim`, `actionAvailableToPerformer`, `playerHasInPlayActionsForPerformer`, `playerHasInHandActionsForPerformer`.

## Files

- `modules/php/cards/faf/_03032.php` — wired `IHasActions` + `Action_03032`
- `modules/php/cards/faf/actions/Action_03032.php` — new
- `modules/php/States/faf/State_highDramaPhase03032.php` — new
- `modules/php/States.php` — `HIGH_DRAMA_PLAYER_TURN_03032 = 403032`
- `states.inc.php` — `"03032"` transition
- `Game.php` — `EXTRA_ACTION_PERFORMER` + helpers
- `StatesTrait.php` — clear performer lock on real turn end
- `ArgumentsTrait.php`, `FrameworkActionsTrait.php`, `Theah.php` — enforcement
- `OnUpdateActionButtons.js`, `OnUpdateActionButtons.faf.js`, `OnEnteringState.faf.js`, `OnLeavingState.faf.js`

## Feelings

The framework work is the interesting part — the card itself is a straightforward wound+move Sorcerer city action. Splitting `EXTRA_ACTIONS` (whose turn) from `EXTRA_ACTION_PERFORMER` (which character) feels clean and reusable for any future "same performer must act again" effects. Slightly worried about edge cases where an action has no performer concept but is still listed — `actionAvailableToPerformer` returns false for those, which is correct.

Zombie mode not updated for mandatory extra action — would need to auto-pick an action for locked performer instead of passing.

## Skill update (2026-07-06)

Captured in `create-risk` skill:
- **Pattern A.2** — wound + move to any location + mandatory extra action with `EXTRA_ACTION_PERFORMER`
- Ability-shape table row for the italic "must perform / same performer" wording
- Cross-cutting helpers docs for `EXTRA_ACTIONS` vs `EXTRA_ACTION_PERFORMER`
- Reference table row + When You Finish checklist item #22
- Canonical ref bullet for `_03032`
