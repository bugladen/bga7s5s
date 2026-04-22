# Until Morale Improves (01152) — Pass Button Fix

## Problem
The scheme resolve has a 3-state flow: (1) add renown to any location, (2) pick a source location with renown to move FROM, (3) pick adjacent destination. State 1 had a "Move a Renown Instead" button that passed to state 2, and state 2 had a "Pass" button that skipped entirely. If no locations had renown, a player could pass through both states and do nothing — the scheme would resolve with no effect.

Card text: "Add a Renown to any location or move a Renown to an adjacent location." This is mandatory — the player must do one or the other. If there's no renown to move, the player must add.

## Fix
Four files changed:

1. `states.7s5s.php` — Changed state 1's args from `argsEmpty` to `argsForState` and possibleactions from `actPassWithPass` to `actFromCardPass`. The switch to `actFromCardPass` routes the pass through the card class instead of the generic framework action, allowing card-specific validation.
2. `_01152.php` — Two additions:
   - `argsFromCard` for state 1: returns `canMoveRenown` boolean by iterating `getCityLocations()` checking `Renown > 0`.
   - `actFromCardPass` override: for state 1, checks if any location has renown. If yes, calls parent (notification) + transitions to state 2. If no, throws `UserException` blocking the pass.
3. `OnUpdateActionButtons.7s5s.js` — "Move a Renown Instead" button only renders when `canMoveRenown` is true.
4. `PlayerActions.js` — Changed state 1 entry in `onPass` action array from `actPassWithPass` to `actFromCardPass` to match the new possibleaction.

## WHY this approach
Switched from `actPassWithPass` (generic framework pass) to `actFromCardPass` (card-routed pass) following the pattern established by `_02052` (Gutter Full of Roses), which does the same kind of "throw if pass isn't valid" check. This gives both client-side prevention (hidden button) and server-side validation (exception if somehow called).

The base `Card.actFromCardPass` does NOT call `nextState`, so the override must handle the transition itself. The parent call is kept for the pass notification message.

State 2's pass button remains — it's for when renown DOES exist but the player changes their mind about moving.
