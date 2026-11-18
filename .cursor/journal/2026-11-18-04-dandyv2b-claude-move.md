# Action_05Dandyv2b text change — Claude move

Printed Action on `_05Dandyv2` (PTv0.4.2) changed from Covert collect Renown to:

> **Action:** Pay [2 Wealth] • Move Claude to any **City** location.

Original `_05Dandy` / `Action_05Dandyb` (Covert collect) left alone.

## What changed

- `Action_05Dandyv2b` now pays 2 then moves Claude to any City other than his current spot.
- Destinations = all `getCityLocations()` except current. No ally-at-destination gate (unlike original `Action_05Dandya`). No Covert / Renown / control gate.
- Printed as Action not City Action — legal from Home (all cities eligible) or from a City.
- Shackles/Harpoon gate availability + pay-step refuse + location-step soft resolve (same WHY as Dandya soft-lock).
- Two-step pay → location (`05Dandyv2b` / `05Dandyv2b_2`). State id `405014212`.
- JS enter/leave/buttons for `_2` mirror v2a location pick. Pay step still multi-select + `updateDandyWealthPay(2)`.
- Engage not printed — `createCardMovingEvent(..., engage=false)`.
- Tests rewritten for Claude move; Engaged import typo fixed (`EventCardEngaged` use vs live `EventCardEngarded` class).

## WHY keep two-step pay → location

Same as v2a / original Dandya: hand discard must resolve before the effect step so paid cards are gone when the player picks. Not a same-state pay+resolve.

## WHY any City, including from Home

Printed "Action" (not City Action) + "any City location". Ally gate was the old a-action nerf; this is the replacement for Covert collect and should be the flexible reposition.

## Pitfall

`EventCardEngaged` and `EventCardEngarded` are both real classes. Live en garde path is **Engarded**. A `use ...EventCardEngaged` alias does not cover `new EventCardEngarded()` — PHP then looks in the test namespace and fatals. Fonts make the two names easy to misread.
