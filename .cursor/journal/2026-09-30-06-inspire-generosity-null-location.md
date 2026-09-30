# Inspire Generosity (_01145) — null $toLocation TypeError

## Bug

Production: `createRenownMovingBetweenLocationsEvent(): Argument #3 ($toLocation) must be of type string, null given` at `_01145.php:117` (state `01145_2`).

## Cause

`actFromCardWithIds` for destination step used `$ids[0]` raw. Client can submit `[]` / `[null]` (empty confirm dialog after "are you sure", or crafted request). `actFromCardWithLocations` JSON-decodes that into `$ids` and passes through. PHP 8 returns null for missing index → TypeError on string-typed EventFactory params.

Same class of bug already fixed on Filling the Ranks `_01144` (WHY comment: "Client can submit [] / [null]"). April 01145 audit marked Clean — didn't catch missing server validation.

## Fix

Mirror `_01144` / `_01125` guards:
- Step 1: require `$ids[0]` be a real city location name before renown check.
- Step 2: require `$fromLocation` (globals) and `$toLocation` both valid city strings and `$toLocation !== $fromLocation` before queuing the three-event renown move batch.

UserException instead of TypeError — player stays in state to retry.

## Not changed

Zombie for `01145_2` still `actPass("pass")` → EVENTS (skips move). Fine. No frontend change — server must stay defensive regardless of confirm UX.
