# Midnight Shipment back then new performer ends the turn

User: Back from `highDramaPhase01149`, pick a new performer, Confirm, and the turn ends with no move.

## Why the turn ended

In-play dispatch queues `EventActionTriggered` and, when the action needs a performer, goes to choose-performer *without running events*. The first Confirm goes to `HIGH_DRAMA_PLAYER_TURN_EVENTS`, which runs that trigger. Midnight Shipment's handler only queues transition `01149`, and that opens the location UI.

Back is `nextState("back")` to choose-performer. The trigger is already gone. The second Confirm goes to the same events state with an empty queue, so `endOfEvents` is `NEXT_PLAYER`. The action was already announced and spent at confirm time, so the turn just advances.

Same hole on every in-play action whose UI backs to `HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER`: 01011, 01015, 01044, 01147, 01148, 01152a/b, and Épée Sanglante's `backEpeeSanglante` (entry transition `01071` lands on choose-target, which still reads `CHOSEN_PERFORMER` live).

## Why re-queue the entry transition, not the trigger

Re-firing `EventActionTriggered` would reopen the UI for 01149 (its handler only queues the transition) but other actions wound, engage, or stamp challenge type in that handler. That work already happened on the first pass. Replaying it on a performer change would double-pay.

So when the transition is applied from `highDramaPlayerTurnEvents`, remember the name and player id (`IN_PLAY_ACTION_ENTRY_*`), skipping framework hops (`reaction`, `pay`, `chooseNext`, `endOfEvents`, `endOfGame`, `pressureLocation`, the in-hand keys). Dispatch sets `IN_PLAY_ACTION_TRIGGER_PENDING` before the first performer prompt. First Confirm clears the flag and leaves the queued trigger alone — replaying a stale entry from the previous action would skip the new trigger. A later Confirm (the Back case) re-queues the remembered transition, then `inPlayActionPerformerChosen` as before. Events apply `01149` and the location UI reads the new `CHOSEN_PERFORMER`.

`stNextPlayer` deletes the three globals so they do not leak into the next turn.

Did not re-fire from `actBack` itself. Back is shared with states that are not "return to this action UI", and the performer is not chosen yet at Back time. The reopen has to happen when the new performer is stored.

## Tests

Framework suite covers: first Confirm with the pending flag does not replay a stale entry; second Confirm re-queues `01149` with the saved player/source/internal id. Dispatch test asserts the pending flag. `stNextPlayer` clears the new globals. `Action_01149` still green.

Full `php tests/run.php`: 5568 passed, 1 failed. The failure is `_01041v2` construct expecting Influence 2 and getting 1. Pre-existing from the Rosine clone, not this change. I left it.

## abnormalFlow back buttons do not use this

User asked if any abnormalFlow Back tags can take advantage of the resume. No. Only three button sites read the flag, and none of them confirm an in-play performer.

- In-hand choose performer and in-hand pay hide `<` when the flag is set. Back from those states goes to choose-action, or from pay to in-hand choose performer (`backPerformer`). That is the clone/Cesca/engage-chooser injection (01106, 01124, 01154, 04cd01b, Cesca 01008, 01133, 03060). Showing it would reopen the normal action list or re-enter `EnteringPayState`. The resume only runs in `actHighDramaInPlayActionPerformerChosen`.
- Duel maneuver pay uses `backAbnormalFlow` when the flag or `gambled` is set. That transition and normal `back` both go to `DUEL_USE_MANEUVER_FROM_COMBAT_CARD`. Maneuver_01077 sets the flag because the card is auto-played. Unrelated to the empty in-play event queue.

Card action args include `abnormalFlow` from `CardAction::getArgsFromAction`, but no card-state button handler reads it. The in-play Back buttons (01149 and the siblings) are unconditional, so they already hit the new resume. Do not un-hide the abnormalFlow backs thinking this fix covers them.
