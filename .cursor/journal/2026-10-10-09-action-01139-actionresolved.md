# Action_01139 — missing ActionResolved

## Bug
Strength of Ten Action: spend Renown, EXTRA_ACTIONS=2, stamp goToLocker — never queued `EventActionResolved`. Suite pinned as SUSPECTED BUG vs Action_01168 (same Unique spend→Locker).

## Why it mattered
Post-action windows listen for ActionResolved (Soline-style reactions, turn cleanup). Locker redirect on `_01139` is deferred via `goToLocker` + `EventCardDiscardedFromHand` — that is not a substitute. Also fails the RiskAction pre-commit `createActionResolvedEvent()` check pattern.

## Fix
Queue `createActionResolvedEvent($owner->ControllerId)` after the notify, matching Action_01168 / Action_01124. Flipped the pin test to assert count 1.

## Not changing
Deferred locker path on `_01139` itself — that still works via goToLocker; only the missing resolved event was wrong.
