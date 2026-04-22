## Castillian Caper (_02035) — implemented

Implemented full plan (without editing the plan file): Planning partial Renown (0 / 1 / 2 placements), two planning states with conditional second transition based on **pre-pick** empty-location list (avoids relying on Renown before queued events apply). Pass throws when placements remain.

Part B: `Action_02035` as `SchemeCityAction`, no `Engaged` filter, Finesse + `CASTILLIAN_CAPER_PRESSURE_TYPE` bonus in `UtilitiesTrait` after the pressure-stat loop (once per resolution). Collect = remove from location + `createPlayerGainsReknownEvent` when Renown > 0 before remove.

WHY pre-pick list for branching: after `createReknownAddedToLocationEvent` the in-memory `CityLocation->Renown` may still be 0 until the event hub runs; counting “other empty” from the snapshot at click time matches “apply as much as possible” without double-entering state 2 incorrectly.

PHP CLI not on PATH in agent shell — syntax not linted via `php -l` here; IDE may show intelephense lag on new `Game::CASTILLIAN_CAPER_PRESSURE_TYPE` until reindex.

## Refactor: one state, multi-location (Eddie)

Planning was refactored from two sequential states to **one** state like **Teeth of the Drachen** (`_02015`): `actFromCardWithIds` receives 1 or 2 location names in one submission; `requiredLocationCount = min(2, empty count)`. Removed `02035_2` state class, `actFromCardPass`, and duplicate JS handlers.
