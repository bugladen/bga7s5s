# Early Morning Arrangements — City Action usable from Home

## Bug
Player pressures `_03cd20` successfully → card moves to their Home. Next day
(Used reset at dusk) the City Action shows as available again if they have
characters at Home, and they can re-pressure.

## Cause
`EventCityAction::isAvailableToPlayer` only checked "friendly character at
`$card->Location`". Home is a valid Location string, and
`getCharactersAtLocation(LOCATION_PLAYER_HOME)` returns home characters, so
the gate passed. `getInPlayActionsAvailableToPlayer` already scans non-hand
locations including Home, so the action appeared in the HD list.

`_03cd20` is the first CityEventCard that *lives* in Home (Reaction while
there). The base class never anticipated that Location.

## Fix
Gate `EventCityAction::isAvailableToPlayer` on `$theah->cardInCity($card)`
before the character-at-location check. City Actions require the event in
the city by definition — fixing the base class covers every
`EventCityAction` subclass, not just `_03cd20`.

WHY base class not Action_03cd20 override: same hole would reopen for any
future event that parks in Home. `Action_03cd01` already had its own
`cardInCity` for a CharacterAction; EventCityAction was the missing
parallel.

## Tests
`Action_03cd20_Test`: unavailable in Home with friendly Home character;
available in city with friendly at the event's location.

## Not done
Did not add engaged-performer filtering to Action_03cd20 (skill template
mentions it; journal from May said EventCityAction default was enough).
Separate from this bug.
