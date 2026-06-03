# CityLocation Field Rename: Reknown -> Renown

## What
Renamed the `Reknown` field on `CityLocation` class to `Renown` (fixing the typo). Updated all 45 references across 20 files.

## WHY only CityLocation
The `Card` class also has a `Reknown` field with the same typo. Eddie explicitly asked to only rename the CityLocation field, not Card's. This is important because:
- Card's `Reknown` is serialized to JSON as `'reknown'` key in `Card.php` line 447, and the JS side reads it as `event.reknown` / `card.reknown`
- Renaming Card's field would require coordinated JS changes and database migration
- CityLocation's field is only accessed server-side via `->Reknown` property access, so it's a simpler rename

## Files changed
- `CityLocation.php` — field declaration + constructor
- `Theah.php` — 6 location initializations
- `EventHub.php` — 2 lines (cityLocations array access only; Card lines untouched)
- `StatesTrait.php` — 3 plunder phase references
- 13 card files accessing `$location->Renown` patterns

## Deliberately untouched
- `Card.php` `$this->Reknown`
- `EventHub.php` `$card->Reknown` lines (1013-1045)
- `_01179.php` `$this->Reknown` (Card subclass)
- `DebugTrait.php` `$card->Reknown`
- `Action_01179.php` `$card->Reknown`
- Event class names (EventRenownAddedToLocation, etc.)
- Method names (getRenownForLocation - done, etc.)
- JS notification keys, variable names
