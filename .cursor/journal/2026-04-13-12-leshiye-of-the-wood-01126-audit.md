# Leshiye of the Wood (01126) Audit

## Card Text
**Scheme:** Choose one of the outermost locations. Add a Renown to two other locations.

Place this card on the chosen location. Discard all City Cards and Renown there. All characters there go Home and Renown cannot be added or moved to or from the location. It cannot be controlled. At the end of the Day, send this card to The Locker.

## Scheme Resolution - OK
Two-phase flow:
- Phase 1 (state 01126): Player picks an outermost location. Validated against `getOuterCityLocations()`.
- Phase 2 (state 01126_2): Player picks 2 other locations for Renown. eventCheck validates renown can be added. Then fires SchemeMovedToCity event.

## EventSchemeMovedToCity Handler - OK
- Discards all ICityDeckCard instances at ChosenLocation
- Sends all Characters at ChosenLocation home
- Removes all Renown (bypasses own eventCheck via `$reknown->source = $this->Name`)

## eventCheck Restrictions - OK
- Blocks EventReknownAddedToLocation at ChosenLocation
- Blocks EventRenownRemovedFromLocation at ChosenLocation (except by itself)
- Blocks EventLocationClaimed at ChosenLocation
- No separate "renown moved" event type exists; add/remove coverage is sufficient

## End of Day / Locker - OK (generic)
All schemes go to Locker via `stDuskPhaseCleanup()`. The card text just describes standard behavior.

## Bug Found: eventCheck restrictions persist after Locker

**What:** `Theah::eventCheck()` iterates `$this->cards`, which includes cards in the Locker. The `ChosenLocation` property was never cleared when the card went to the Locker, so the renown/claim restrictions would persist permanently across future days.

**Why this matters:** After the scheme goes to the Locker at end of day, players should be able to add/remove renown and claim the location again. Without the fix, the location is permanently locked out for the rest of the game.

**Fix:** Added `EventCardSentToLocker` handler that clears `ChosenLocation` to empty string and persists to DB. Since `eventCheck` compares `$event->location == $this->ChosenLocation`, an empty string matches no location, so all restrictions lift.

**Pattern:** Same approach as `_01143` (Contempt and Hatred) which handles `EventCardSentToLocker` to undo its ongoing effects.

## Frontend - OK
- State 1: Shows outermost locations as selectable, requires exactly 1
- State 2: Shows all locations except ChosenLocation (marked as "chosen"), requires exactly 2
- Standard confirm/back buttons
