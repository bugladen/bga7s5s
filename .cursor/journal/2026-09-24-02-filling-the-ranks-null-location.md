# Filling The Ranks (_01144) — Null Location Fatal

## Crash

Production TypeError:
`createRenownAddedToLocationEvent(): Argument #2 ($location) must be of type string, null given`
called from `_01144.php` line 87 (first renown-place state).

`$ids[0]` was null — empty array (`[]` → undefined key → null) or `[null]` from client.

## WHY this shape of fix

Same failure mode as Boar's Guile `_01125` (journal `2026-06-07-01-boars-guile-01125-hardening.md`). UI normally requires a selection, but:
- `onCityLocationsSelected` confirmation dialog can still call `submitLocations()` with fewer selections than required
- crafted / replayed requests can send junk
- `array $ids` type hint does NOT protect against `[null]`

Without a guard, EventFactory's string typehint fatals the table. Validation throws `UserException` instead.

## What changed

In `_01144.php` both resolve states:
1. `$ids[0] ?? null` + `is_string` + `in_array(..., city location names, true)` before EventFactory
2. Step 2 also rejects `$location === CHOSEN_LOCATION` (card text: different location) — was UI-only

Also normalized `GAME::CHOSEN_LOCATION` → `Game::CHOSEN_LOCATION` (same constant; consistency only).

## Did NOT change

- Fewest-renown scoring logic (still raw `player_score` SQL; works for this card)
- Leader Reaction recruit path
- Shared JS `submitLocations` empty-submit path — server hardening is enough to stop the fatal; broader JS tighten would help many schemes but is out of scope for this bug

## Regression risk

Don't remove the `is_string` check thinking `locationInCity(string)` is enough — calling `locationInCity(null)` is itself a TypeError.
