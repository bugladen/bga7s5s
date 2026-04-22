# Teeth of the Drachen (_02015) Audit

## Card Text
> Add a Renown to two locations with no Renown.
> Characters at uncontrolled locations cannot intervene.

## What was correct

- **Resolution effect**: `handleEvent` correctly transitions to state 02015 on `EventResolveScheme`. The state lets the player pick exactly 2 locations. Server validates count==2, validates each has Reknown==0, then queues `EventReknownAddedToLocation` for each.
- **Intervention prevention**: `eventCheck` correctly catches `EventCharacterIntervened` when the card is in `LOCATION_PLAYER_HOME`. Gets the intervening character via `$event->newTargetId`, checks their city location, blocks if `!$location->isControlled()`. This applies to ALL characters regardless of owner, which matches the card text (no owner restriction).
- **JS entering state**: Correctly sets `numberOfCityLocationsSelectable = 2` and filters to only locations with `reknown == 0`.
- **JS leaving state**: Properly resets city locations.
- **City Forum**: Not filtered out, which is correct — the card text says "locations" without excluding any.

## Bugs fixed (3)

### 1. Dead variable removed
`$locations = $game->theah->getCityLocations();` was assigned but never used in `actFromCardWithIds`. Removed it.

### 2. Missing duplicate location validation
Server didn't check that the two selected locations were different. If the same location ID appeared twice, both iterations would pass the `Reknown > 0` check (events are queued, not executed yet) and 2 Renown would land on one location. JS UI prevents this via toggle behavior, but server should be robust. Added `$ids[0] == $ids[1]` check.

### 3. Button text singular vs plural
`OnUpdateActionButtons.tac.js` had "Confirm Location" (singular) for a card that requires selecting two locations. Changed to "Confirm Locations" (plural), consistent with other 2-location cards (01016, 01098).

## What I didn't change

- The intervention prevention applies whenever the card is in `LOCATION_PLAYER_HOME`, even before the scheme is revealed. This matches the pattern used by other scheme cards with continuous effects (e.g., 01150 Parley Gone Wrong uses the same `LOCATION_PLAYER_HOME` check). No face-up/face-down tracking exists in the codebase, so this is the standard pattern.
