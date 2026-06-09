# The Boar's Guile (_01125) — Server-Side Input Hardening

## Why

The user asked whether a hostile/modified client could submit invalid strings to the `PLANNING_PHASE_RESOLVE_SCHEMES_01125` states. Audit found four unvalidated server-side inputs:

1. **`_01125` (place renown)** — `$ids[0]` flowed straight into `createRenownAddedToLocationEvent`. EventHub does `$this->cityLocations[$event->location]->Renown += $event->amount;` which fatals on an unknown key (`Attempt to read property 'Renown' on null`). It would also write a junk `Reknown_<bogus>` global.
2. **`_01125_2` (choose source to move from)** — Same issue. Existing renown>0 check only triggers if the global happens to exist.
3. **`_01125_3` (choose destination)** — No check that the destination is (a) a real city location, (b) adjacent to the source from `Game::CHOSEN_LOCATION`, or (c) not the source itself. UI enforces adjacency client-side only.
4. **`_01125_4` (choose enemy character)** — `$id` went to `getCardObjectFromDb($id)` with no check that the result is a `Character` with a non-zero controller different from the active player. A crafted client could mark its own character (or a non-Character card) as Adversary of Yevgeni.

## What

Added validation at the top of each branch in `_01125.php`:

- Steps 1, 2, 3 validate `$location` is a string AND `in_array($location, array_keys($game->theah->getCityLocations()), true)`.
- Step 3 also requires `in_array($location, $game->theah->getAdjacentCityLocations($source, false), true)` and `$location !== $source`. Passed `includeHome: false` because renown can't sit on home — destination must be a city location.
- Step 4 requires `$character instanceof Character && controllerId !== 0 && controllerId !== activePlayerId`.

All validations throw `Bga\GameFramework\UserException` (already imported as `UserException` at the top of the file).

## Notes / Why this shape

- Used `$ids[0] ?? null` and `is_string()` guard rather than relying on the `array $ids` type hint, because `json_decode("[null]")` returns `[null]` — a valid array with a non-string first element. Type hint alone doesn't protect.
- Strict comparison (`true` third arg to `in_array`, `===` for controller IDs) avoids PHP's loose-comparison foot-guns (e.g. `0 == "any-non-numeric-string"` is false but `"0" == 0` is true).
- Kept the existing renown>0 check in step 2 — it's a separate concern (game rule, not input validation).
- Did NOT add a check that step-1 location is *different from* step-3 destination across the flow — the card text says you can either place OR move, not both, and the state machine enforces that (you only reach step 2 via the "pass" exit from step 1).
- The pass logic in step 2 already validates "no locations have renown" before allowing pass, so no change needed there.

## Future regression risk

If someone "simplifies" these by removing the `is_string` check or relaxing the `in_array` strict flag, the surface re-opens. The validations look defensive/redundant against the UI but exist specifically because BGA clients can be modified — never trust the client.
