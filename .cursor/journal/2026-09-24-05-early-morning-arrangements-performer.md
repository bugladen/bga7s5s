# Early Morning Arrangements — Julius not selectable as performer

User: in-play City Action on `_03cd20`, could pick Giacinto but not Julius.

## Correction from user

Card text has **no Engage cost**. Engaged gate was wrong — removed.

## What changed

Deleted `Action_03cd20::getPerformersForAction` override and the `isAvailableToPlayer` that deferred to it. Now uses `EventCityAction` defaults: any of your characters at the event's location (engaged OK).

## WHY the Engaged filter was wrong

Impl journal (2026-05-12) assumed "Pressure engages → already-engaged can't pay." That mirrors basic Claim, but this City Action's printed text does not Engage as a cost. Filter without printed Engage = inventing a restriction.

Also: the action never queued `createCardEngagedEvent` anyway — so the gate was inconsistent with its own resolution.

## Still true

Performer must still be **at Early Morning Arrangements' location** (EventCityAction). If Julius was elsewhere, he still wouldn't show.
