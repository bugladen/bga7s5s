# Crash the Party draw ignores Objection fail

## Bug
Crash the Party (Reaction_02004) still drew after Objection (Reaction_01027) failed the pressure.

## Why
Pressure pipeline:
1. `EventLocationPressured` — announce totals / provisional success
2. HIGH_PRIORITY cancel reactions (Objection, Shield Rite-ish 04026) can `deletePressureResultEvents()` and queue a **failed** `EventLocationPressureResult`
3. `EventLocationPressureResult` — final SUCCESS/FAILED + claim

02004 was drawing on step 1 (`EventLocationPressured` + `$event->success`). Objection never re-fires Pressured; it only replaces Result. So the draw was already queued before the fail.

02019 (Trial of Faith heal) already listens on Result for this reason.

## Fix
Listen on `EventLocationPressureResult` for the draw; still gate on `$this->location` (set on initiate + move, cleared on pass). Match `$event->location == $location` so a stray Result from another pressure doesn't pay out.

## Not changed
Move-on-initiate still uses `EventPressureOccuring`. Pass still clears `$location` (audit 2026-03-16 bug 2 already fixed).
