# Move Home crashes on getCityLocation

## Symptom
`actHighDramaMoveActionDestinationChosen` fatal when choosing Player Home:
`City location Player Home not found` from `Theah::getCityLocation`.

## Cause
Commit `53782b3e` (2026-06-11, "Basic move action backend now has guards") added:
```php
$locationCheck = $this->theah->getCityLocation($location);
```
Player Home is a legal Move destination — `getAdjacentCityLocations` appends it when `$includeHome` is true, and the JS makes the home endcap selectable. But `getCityLocation` only knows the five city plazas; it throws `\Exception` (not UserException) so the intended null→UserException path never ran.

## Fix
Accept `LOCATION_PLAYER_HOME` OR `locationInCity($location)`. Adjacency check still gates the actual move.

WHY not keep getCityLocation for city-only: it throws hard Exception → BGA "Server syntax error" instead of a clean UserException. `locationInCity` returns bool so we can UserException properly.

## Unfinished
None for this bug. Worth a glance if other June-ish guards call `getCityLocation` on destinations that can be home — `actCityLocationsForReknownSelected` is city-only so fine.
