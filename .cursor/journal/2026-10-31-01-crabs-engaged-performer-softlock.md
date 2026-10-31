# Crabs in a Bucket (_03cd13) — empty choose-performer soft-lock

## Report
Castille Soline player engaged Soline, then initiated Crabs in a Bucket City Action. Stuck in `highDramaInPlayActionChoosePerformer` with no performer. Last log: announced [Claim Location if You Have Fewer Renown].

## Root cause
Same class as Siren's Scream (`2026-05-11-01-sirens-scream-engaged-performer-bug.md`):

- `Action_03cd13::getPerformersForAction` filters out engaged characters (Engage cost).
- `isAvailableToPlayer` checked parent (any character at location), `playersUsed`, claimability, richer opponents — but **not** unengaged performers.
- Confirm announced + set Used → dispatch queued EventActionTriggered → choose-performer with empty `ids` → no Confirm/Pass buttons → hard soft-lock.
- That state had no `actBack` either (zombie was wrongly calling `actBack`).

Regression test had encoded the bug as expected (`engaged still opens the action`). Fixed that assertion.

## Fix
1. `Action_03cd13::isAvailableToPlayer` — require `count(getPerformersForAction) > 0` (Siren's pattern; single source of truth with Engage filter).
2. Framework escape: `actHighDramaInPlayActionCancelNoPerformer` when filtered performer ids empty after Confirm. Undoes CardAction Used / LocationAction playerUsed, `deleteActionTriggeredEvents`, clears `IN_PLAY_ACTION_TRIGGER_PENDING`, queues ActionResolved, `cancelled` → PLAYER_TURN_EVENTS.
3. JS Pass button only when `ids.length === 0`. Zombie picks first performer or cancel escape.
4. `LocationAction::unsetPlayerUsed` for the location-Action undo path.

WHY framework escape (not only availability): live tables already past Confirm need a deploy+refresh recovery. Availability alone only prevents new soft-locks.

WHY refuse cancel when performers remain: not a voluntary take-back after Confirm; Forced-style mandatory when fulfillable (Fine Addition empty-chooser pattern).

## Live table
After deploy: refresh → Pass on choose-performer → Action cancels, Used undone, turn continues via ActionResolved. Action was announced but not completed — no claim, not in playersUsed.

## Tests
- Action_03cd13: engaged-only → unavailable
- FrameworkActions_InPlayHandBrute: cancel undoes Used / clears trigger; refuses when options remain

## Follow-up: TypeError on Pass guard
First JS used `args.args.ids` (copied from onEnteringState / Fine Addition). `argsHighDramaInPlayActionChoosePerformer` returns flat `{ids, actionCardId}` — onUpdateActionButtons sees that flat, so `args.args` was undefined. Fixed to `args.ids`. Card states via `argsForState` still nest under `.args` because PHP wraps `["args" => ...]`.
