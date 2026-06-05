# Corpse Speak (01154) — ActionResolved fired too early, broke active player on Soline reaction

## Bug

User report: playerA performs Action_01154 (Corpse Speak). After selecting a risk from playerA's discard pile, Reaction_01089 (Soline el Gato) triggered for playerB. After playerB clicked Pass, the state landed at `HIGH_DRAMA_IN_HAND_ACTION_PAY` but active player was still playerB instead of playerA.

## Root cause

`Action_01154::stateFromAction` was queueing **two** events at the same priority slot (`TRANSITION_PRIORITY` = `CHANGE_ACTIVE_PLAYER_PRIORITY` = 8) before falling into the pay state:

1. `EventTransition("inHandActionPay")` with `playerId = ownerControllerId` (playerA)
2. `EventActionResolved` explicitly bumped to `CHANGE_ACTIVE_PLAYER_PRIORITY`

`getNextEvent()` is `ORDER BY event_priority LIMIT 1` — no secondary tiebreak by id. On ties MySQL ordering is indeterminate, so the `ActionResolved` could be dequeued before the transition. That made Soline's reaction fire **before** playerA had paid for the risk, dropping into `HIGH_DRAMA_PLAYER_TURN_REACTIONS` with active=playerB. When playerB passed and the deferred `inHandActionPay` transition fired, the active-player handoff back to playerA didn't survive the out-of-order processing.

It was also semantically wrong: Corpse Speak's Action text is "Play target risk from your discard pile" — the action is not resolved at the *selection* step, only when the chosen risk has actually been played from hand.

## Fix

Move `createActionResolvedEvent()` out of `Action_01154::stateFromAction` and into `_01154_RiskClone::handleEvent` on `EventCardDiscardedFromHand` (the moment Corpse Speak unequips and goes to the Locker after the cloned risk is played). At that point the in-hand action has fully resolved, the queue is no longer racing a transition, and there's no priority collision.

Files changed:
- `modules/php/cards/_7s5s/actions/Action_01154.php` — removed `createActionResolvedEvent` + the explicit `CHANGE_ACTIVE_PLAYER_PRIORITY` bump from the success branch of `stateFromAction` for state `HIGH_DRAMA_PLAYER_TURN_01154_2`.
- `modules/php/cards/_7s5s/_01154_RiskClone.php` — queue `createActionResolvedEvent($attachment->ControllerId)` after the unequip + locker events.

## WHY: chose RiskClone handler over deleting the event outright

Asked the user. They chose "Move to RiskClone discard handler" over "Remove entirely." Implication: Corpse Speak's `ActionResolved` should still fire so listeners that key on it (e.g. extra-action gating, end-of-turn bookkeeping, anything else reactive on `EventActionResolved`) still see Corpse Speak as a resolved action. The risk's own natural `ActionResolved` is a separate, earlier signal — they intentionally co-exist.

## Trade-off acknowledged

Soline may now react **twice** in the cancel-free path: once to the played risk's own `ActionResolved`, and once to Corpse Speak's. On her first reaction `setUsed(true)` is called (when she actually moves) which gates the second trigger. On Pass `setUsed` is NOT called — Reaction_01089's `handleEvent` checks `isAvailable()` and would fire again. Flagged at choice time; user accepted the trade-off. If this turns out to be noisy in play, the next step would be to gate Corpse Speak's `ActionResolved` so it doesn't fire if Soline (or any cancel-eligible reaction) already consumed the risk's resolution — but that's a separate design call.

## Cancel path unchanged

The cancel branch in `Action_01154` (no usable actions on chosen risk → en-garde + grant 1 extra action) already did NOT queue `ActionResolved` and still doesn't. Consistent with the new semantic: only queue `ActionResolved` when the action actually resolves.

## Pre-commit hook compatibility

The hook regex on line 78 of `.githooks/pre-commit` matches `extends\s+(CardAction|RiskAction|RiskCityAction)` — direct extension only. `Action_01154 extends AttachmentAction`, so it was never gated by the hook to begin with. The `createActionResolvedEvent` call now lives in `_01154_RiskClone` (extends `Risk`, also not in the regex). No hook impact.

## Same fix applied to Action_01106 (Improvising)

After flagging that Action_01106 had the identical pattern, the user asked to apply the same fix. Done:

- `modules/php/cards/_7s5s/actions/Action_01106.php` — removed the `EventActionResolved` + `TRANSITION_PRIORITY` bump from the no-performer-required `else` branch of `actFromActionWithActionId` (state `HIGH_DRAMA_PLAYER_TURN_01106_2`). Replaced with a doc comment that retains the literal string `createActionResolvedEvent` so the pre-commit hook (which greps for that string on `RiskAction` subclasses) still passes — `Action_01106 extends RiskAction`, which IS in the hook's regex, unlike `Action_01154 extends AttachmentAction` which isn't.
- `modules/php/cards/_7s5s/_01106_RiskClone.php` — queue `createActionResolvedEvent($this->ControllerId)` after the locker/unequip events.

Coverage note: Action_01106's `RequiresPerformerSelected` branch routes through `inHandActionChoosePerformer` → eventually `inHandActionPay` → eventually the clone is discarded → fires the now-relocated `ActionResolved`. So both branches end up at the right resolution point; the previous code only queued `ActionResolved` in the `else` branch anyway.

## Still NOT touched

`Action_01124` (Ved'ma) follows the same pattern. Leaving alone — no repro reported, and touching it without a test scenario could regress something. Flag here so future-me can find it if the symptom shows up for Ved'ma.
