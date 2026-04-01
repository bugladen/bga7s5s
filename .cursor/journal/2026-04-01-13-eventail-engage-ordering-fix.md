# Éventail Engage Ordering Fix

## The Bug

The earlier implementation journal (`2026-04-01-12-eventail-02027-implementation.md`) stated:
> "Engage costs are already handled by the core system before Parley/Claim resolve."

**This was wrong.** Traced through the full state machine and event system to verify:

1. **Claim Action** (`stHighDramaPressureLocation`): The engage event was queued to the DB via `queueEvent()`, but `pressureLocation()` was called immediately after — before `runEvents()` ever processed the queue. The influence tally read `ModifiedInfluence` from in-memory card objects that still had Eventail's +1 bonus.

2. **Parley (Recruit Action)**: The discount was computed in `actHighDramaRecruitActionParleyYes` using `getParleyDiscount()` which reads `$this->ModifiedInfluence`. The engage only happened much later in `actHighDramaRecruitActionPayForMercenary`. The character was still en garde (and still had the +1) when the discount was computed.

## WHY the event system makes this subtle

`queueEvent()` writes to the `events` DB table and calls `eventCheck()` (validation only). It does NOT call `handleEvent()` — that only happens when `runEvents()` drains the queue. `runEvents()` is typically called from the `stRunEvents` game state, which runs as a dedicated state machine step. So events queued within a game state or action handler are never processed until the next `stRunEvents` state.

Eventail's influence modification happens in `handleEvent(EventCardEngaged)` → `modifyInfluence($character, -1)`. Since this handler only fires during `runEvents()`, the influence stays inflated until then.

## What Was Fixed

### Claim Action (simple)

In `stHighDramaPressureLocation`, after queuing the engage event, added `$this->theah->runEvents($skipTransitions = true)` followed by `$this->theah->buildCity()` before calling `pressureLocation()`. The queue is guaranteed empty at this point (drained by the prior `CLAIM_ACTION_CHOOSE_PERFORMER_EVENTS` state), so this only processes the engage and its sub-events (like Eventail's influence modification).

### Parley / Recruit Action (structural)

This required restructuring the state flow because the discount is displayed in the PAY_FOR_MERCENARY UI. The engage must be processed before that UI is shown.

**Moved engage event** from `actHighDramaRecruitActionPayForMercenary` to `actHighDramaRecruitActionMercenaryChosen`. The engage event is now queued alongside the `EnteringPayStateEvent`, so both are processed by the existing `CHOOSE_MERCENARY_EVENTS` state (which runs `stRunEvents`).

**Added two new game states:**

- `RECRUIT_COMPUTE_DISCOUNT` (4233): Runs after events are processed, before `PAY_FOR_MERCENARY`. Computes the parley discount using the now-accurate (post-engage) influence values. This replaced discount computation that was previously scattered across `actHighDramaRecruitActionParleyYes`, `actHighDramaRecruitActionParleyNo`, and `stHighDramaRecruitActionParleyable`.

- `RECRUIT_UNDO_ENGAGE` (4234): Runs when the player backs out from `CHOOSE_MERCENARY` to `CHOOSE_PERFORMER`. En-gardes the performer to undo the parley engage, preventing the player from being stuck with an engaged character they never committed to using.

**Cleaned up parley choice handlers**: `actHighDramaRecruitActionParleyYes` and `actHighDramaRecruitActionParleyNo` now only set the `PERFORMER_PARLEYED` flag. All discount computation happens in `stRecruitComputeDiscount`.

### Parameter rename

Renamed `Theah::runEvents(bool $debug)` to `runEvents(bool $skipTransitions)` since the parameter's purpose is to skip `EventTransition` and `EventChangeActivePlayer` handling, not "debug mode." Updated all 12 callers in `DebugTrait.php`.

## Files Changed

- `modules/php/theah/Theah.php` — `$debug` → `$skipTransitions` in `runEvents()`
- `modules/php/DebugTrait.php` — all callers updated
- `modules/php/StatesTrait.php` — `stHighDramaPressureLocation` fix, new `stRecruitComputeDiscount` and `stRecruitUndoEngage`, cleaned up `stHighDramaRecruitActionParleyable`
- `modules/php/FrameworkActionsTrait.php` — moved engage to `actHighDramaRecruitActionMercenaryChosen`, removed from payment handler, cleaned up parley choice handlers
- `modules/php/States.php` — new constants `HIGH_DRAMA_RECRUIT_COMPUTE_DISCOUNT` (4233) and `HIGH_DRAMA_RECRUIT_UNDO_ENGAGE` (4234)
- `states.inc.php` — wired new states, updated transitions
