# Solomonia Saboruvya (_02044) — Two Passive Abilities

## Card Text
- **Ability 1:** "While [The Forums] is uncontrolled, Solomonia cannot be challenged."
- **Ability 2:** "While Solomonia is at [The Forums], add +1 to your total during [Influence] pressures at adjacent locations."

## Implementation

### Ability 1: Challenge Protection via `eventCheck`

Followed the Sigurd Ulfsen (`_01190`) pattern exactly. Override `eventCheck`, check for `EventChallengeIssued` where Solomonia is the defender, then verify The Forums (`Game::LOCATION_CITY_FORUM`) is uncontrolled via `CityLocation::isControlled()`. Throws `BgaUserException` to block the challenge.

**WHY `eventCheck` instead of filtering from `argsHighDramaChallengeActionChooseTarget`:** The args method in `ArgumentsTrait.php` is a framework method that returns all opposing characters at the location. Adding a per-card filter there would require inventing a new `canBeChallenged(Theah)` method on Character and modifying framework code. `eventCheck` catches ALL challenge sources (basic challenge action, ability-issued challenges, etc.) without touching framework code. Sigurd already established this pattern.

**WHY no `canBeChallenged()` method:** Unlike `canChallenge()` (which is parameter-free), a `canBeChallenged` check needs Theah context to look up the city location. Adding a Theah parameter to a new Character method felt like over-engineering for a single card. The `eventCheck` approach already has event context via `$event->theah`.

### Ability 2: Influence Pressure Bonus (Constanzo Pattern)

Followed Don Constanzo (`_01006`) pattern: `handleEvent` on `EventPressureOccuring` sets a global flag, then `pressureLocation()` in `UtilitiesTrait.php` reads it.

**Guard conditions in `handleEvent`:**
1. Solomonia is controlled
2. Solomonia is at `Game::LOCATION_CITY_FORUM`
3. Influence is in the pressure types
4. Pressure location is adjacent to The Forums (City Docks or Grand Bazaar)

**WHY `getAdjacentCityLocations(LOCATION_CITY_FORUM, false)`:** The `false` excludes Player Home from adjacency. Player Home can't be pressured, so including it would be a no-op, but excluding it makes intent clearer and avoids future edge cases.

**WHY the bonus is inside the `foreach ($pressureStats)` loop with `STAT_INFLUENCE` guard:** The card says "during [Influence] pressures" — the +1 only applies when Influence is the active stat. Since Influence appears at most once in `$pressureStats`, this fires exactly once. Contrast with Constanzo who gets +1 per pressure stat type (no stat filter).

**WHY `$playerInfluences` always has Solomonia's controller:** The array is initialized from the `player` DB table (all players), not from characters at the location. So even if Solomonia's controller has zero characters at the pressured location, their row exists and the +1 is applied.

## Files Changed
- `modules/php/cards/tac/_02044.php` — added `eventCheck` + `handleEvent`, new imports
- `modules/php/Game.php` — added `SOLOMONIA_PRESSURE_TYPE = 2048` + `SOLOMONIA_ID` constants
- `modules/php/UtilitiesTrait.php` — added Solomonia +1 influence check in `pressureLocation()`

## No JS/State Changes Needed
Both abilities are purely passive — no custom states, no player choices, no UI handlers. The `eventCheck` throw and the pressure bonus flag are entirely server-side.
