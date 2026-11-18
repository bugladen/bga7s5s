# Action_05Dandyv2a text change — Renown move

Printed Action on `_05Dandyv2` changed from the original Claude City Action (move Claude to a City where you control a character) to:

> **Action:** Pay [1 Wealth] • Move a Renown from this location to an adjacent **City** location.

Original `_05Dandy` / `Action_05Dandya` left alone.

## What changed

- `Action_05Dandyv2a` now pays 1 then moves 1 Renown from Claude's location to an adjacent City (Forsythe-style batch: MovingBetween + Removed + Added with `isMove`).
- Destinations = `getAdjacentCityLocations(..., includeHome=false)`. No ally-at-destination gate. No Shackles/Harpoon gate (Claude is not moving).
- Availability: Renown > 0 here + can pay 1 + at least one adjacent City. Printed as Action not City Action, but Renown only exists in city — `locationInCity` before `getCityLocation` (Home throws).
- State `05Dandyv2a_2` copy + zombie updated to adjacent destinations.
- Tests rewritten for Renown move; Shackles now asserted as still available.
- JS pay/location wiring unchanged (same two-step UX).

## WHY keep two-step pay → location

Same as before / Covert collect: hand discard must resolve before the effect step so the paid card is gone when the player picks. Not a same-state pay+resolve.

## Pitfall

`Theah::getCityLocation` throws for Home — never use `=== null` as the Home guard; use `locationInCity` first.
