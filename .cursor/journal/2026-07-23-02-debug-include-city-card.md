# DEBUG_INCLUDE_CITY_CARD null crash

## Symptom
`stDawnCityCards` fatals: "Trying to access array offset on null" at `$cityCard['id']` when `DEBUG_INCLUDE_CITY_CARD` is set.

## Attempt 1 (wrong)
Create card in `debug_IncludeCityCardInSetup` during pickDecks, store card id. Failed — Eddie: city deck / early card is gone by dawn. Flow is pickDecks → buildTable (`buildDecks`) → setup → dawn. Creating during pickDecks races that.

## Fix (correct)
- Debug helper: validate via `instantiateCard`, store **class name** only.
- `stDawnCityCards`: `createCardInLocation($class, $location)` then fire the usual city-card-added event. No getCardsOfType / no early id.

WHY create at dawn: after `buildDecks`, location is real, and it works even when the class isn't in the chosen city deck (bas on Core/CU2024).

## Call site
Pass bare id like `04cd01` (not `_04cd01`).
