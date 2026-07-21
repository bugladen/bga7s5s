# Loyal (_03035) Implementation

## Context

Eddie asked to finish `_03035` Loyal via create-risk. Prior today: `_03034` La Voix.

## Card Text

**Reaction:** When a pressure occurs, if you control more non-Mercenaries at that location than each opponent • Add +1 to your total for the pressure.

**Maneuver:** Wound your other character at this location • +1[Riposte] or +2[Thrust].

## Approach / WHY

### Reaction = Pattern D.2 (RiskReaction + global mutation)

Mirror `Reaction_02019` (Trial of Faith) trigger on `EventPressureOccuring` + pay → `EventRiskReactionTriggered`, and Solomonia/`pressureLocation` for the +1 total.

WHY new `LOYAL_PRESSURE_TYPE = 8192` + `LOYAL_PLAYER_ID` instead of abusing `PRESSURE_BONUS`:
- `PRESSURE_BONUS` only applies under `PACK_TACTICS_PRESSURE_TYPE` and only for Influence.
- Loyal is any pressure type ("for the pressure"), flat +1 to the reacting player's total.
- Solomonia pattern (flag + id global + branch in `pressureLocation`) is the established channel.

Gate: count non-Mercenary controlled characters per player at `$event->location`; owner's count must be strictly greater than each other player's count at that location ("more … than each opponent"). Players with 0 non-Mercs there count as 0; requiring `myCount > 0` covers beating them.

Effect applies in `EventRiskReactionTriggered` (after pay) so cancel-during-pay cannot leave a dangling bonus — same D.2 discipline as Subtle.

### Maneuver = Pattern C.3 + friendly wound cost

Choice drives calc → `EventManeuverActivated` + `stackEvent` (C.3). Wound is cost → character chooser first (01051 shape), then Riposte/Thrust buttons (03024 shape).

Two states:
1. `duelResolveManeuver_03035` — pick other controlled character at actor location
2. `duelResolveManeuver_03035_2` — pick +1 Riposte or +2 Thrust; queue wound + store choice

WHY wound from `actFromManeuverWithId` on state 2 (not `EventResolveManeuver`): C.3 side-effect variant — side effects queue from the choice act method so they land in the activation sequence before calc.

`IRiskThatTargetsCharacters` on Risk + `IAbilityThatTargetsCharacters` on Maneuver (friendly target chooser).

## Files touched

- `modules/php/cards/faf/_03035.php`
- `modules/php/cards/faf/reactions/Reaction_03035.php` (new)
- `modules/php/cards/faf/maneuvers/Maneuver_03035.php` (new)
- `modules/php/States/faf/State_duelResolveManeuver_03035.php` + `_2` (new)
- `modules/php/Game.php` — LOYAL_PRESSURE_TYPE / LOYAL_PLAYER_ID
- `modules/php/UtilitiesTrait.php` — pressureLocation +1
- `modules/php/States.php`, `StatesTrait.php` (cleanup LOYAL_PLAYER_ID), `states.inc.php`
- `modules/js/On{Entering,UpdateActionButtons,Leaving}State.faf.js`

## Bug: calc raced ahead of Riposte/Thrust choice

Eddie reported combat stats not updating after the choice. Root cause: first state correctly `stackEvent`s from `EventManeuverActivated`, but state 1→2 used `queueEvent("03035_2")`. After character pick, pending `EventResolveManeuver` + `EventDuelCalculateManeuverValues` (queued in `stResolveManeuverFromCombatCard`) ran *before* the Riposte/Thrust prompt, so calc always saw `$ChooseRiposte = false` default.

Fix: `stackEvent` the `03035_2` transition too. No need to re-emit `EventDuelCalculateManeuverValues` — C.3 discipline is "choice before calc," not "recalc after choice." Single-state C.3 (03024) only needs one stackEvent; multi-step must stack every intermediate transition until the calc-driving choice is recorded.

## Skill update (create-risk)

Documented from Loyal session:

1. **Pattern D.2.1** — pressure +1 RiskReaction: mint next binary `PRESSURE_TYPE` + player-id global; apply in `pressureLocation()` outside per-stat loop; never reuse `PRESSURE_BONUS`.
2. **Multi-step C.3** — `stackEvent` *every* intermediate transition until calc-driving choice is stored. `queueEvent` on step 2 races behind pending `EventDuelCalculateManeuverValues`. Do not re-emit calc.
3. **Wound-other + Riposte/Thrust** — two-state chooser then buttons; wound from final `actFromManeuverWithId`.
4. Canonical ref + pick-shape rows + When You Finish #26/#27 + reference table row for `_03035`.


## Feelings

Clean split. The pressure +1 needed a new binary flag because PRESSURE_BONUS is Pack-Tactics-specific — tempting to "reuse" but would be wrong. Two-state maneuver is a bit more ceremony than Superstitious but the wound target chooser demands it.
