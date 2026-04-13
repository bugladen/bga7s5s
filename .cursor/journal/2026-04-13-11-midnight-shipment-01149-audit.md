# Midnight Shipment (01149) Audit

## Card Text
**Scheme:** Add a Renown to [The Docks] and [The Grand Bazaar]. Then, add a City Card to [The Docks].
**City Action:** If [The Docks] has no City Cards or only events - Move your performer from [The Docks] to any City location.

## Scheme Resolution - OK
The `_01149.php` handleEvent correctly:
- Adds Renown to Docks and Grand Bazaar via two `createReknownAddedToLocationEvent` calls
- Adds a City Card to Docks via `createCityCardAddedToLocationEvent`

## City Action Availability - OK
The `isAvailableToPlayer` logic counts ICityDeckCard vs CityEventCard instances. Since CityEventCard implements ICityDeckCard, if all city cards are events the counts are equal and the check passes. If any non-event city card exists, cityCards > eventCards and it blocks. Correct.

The getPerformersForAction correctly narrows to player's characters at the Docks only.

## Bug Found: Missing actionResolvedEvent and resetPlayerPassCount

**What:** `Action_01149::actFromActionWithIds` was missing `createActionResolvedEvent()` and `resetPlayerPassCount()`.

**Why this matters:** Every SchemeCityAction that completes an action needs to fire `createActionResolvedEvent` to signal the game that the action resolved (which triggers reactions and progresses the flow). The pass count reset is needed so the game doesn't think players are still passing. Compared against Action_01152a, Action_01015, Action_01044 which all include both calls.

**Note:** Action_01147 also lacks `createActionResolvedEvent()`, but that one transitions into the framework's equip attachment flow, which calls it at the end of `FrameworkActionsTrait::actEquipAttachment` (line ~690). So 01147 is fine - the resolved event is deferred to when the equip actually completes.

## Frontend - OK
- OnEnteringState correctly excludes Docks from selectable locations (can't move back to where you started)
- Action buttons have back and confirm, standard pattern
- State definition looks correct

## Fix Applied
Added `createActionResolvedEvent()` and `resetPlayerPassCount()` in `actFromActionWithIds` after the move event, matching the pattern in Action_01152a.
