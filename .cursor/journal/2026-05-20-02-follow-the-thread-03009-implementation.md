# Follow the Thread (_03009) — Implementation

Risk card with two distinct abilities:

- **Sorcerer Strega Action:** Move your performer to an adjacent location where there is an enemy character or an available **Mercenary**.
- **Strega Maneuver:** -1[Thrust] • Wound the adversary.

Stats: 0 Riposte, 2 Parry, 2 Thrust. Wealth 0. Traits: Flourish, Sorcery, Sorte. Faction: Vodacce.

## Pattern choices

- Text says "Action:" (not "City Action:") so I used `RiskAction` (Risk must be in hand). Performer must be in city though — filtered via `getCharactersInCityByPlayerId` plus `hasTrait("Sorcerer") && hasTrait("Strega")` plus at least one valid adjacent destination. This mirrors `_01025` (Fate's Burden) — also a "Sorcerer Strega Action" using `RiskAction`.
- The keyword chain `Sorcerer Strega Action` decomposes into:
  - "Sorcerer" → `implements ISorcererAbility` + `createSorcererAbilityStartEvent()` / `createSorcererAbilityPlayedEvent()`.
  - "Strega" → performer-trait gate (`hasTrait("Strega")`). NOT a Sorcerer-only ability — both stack.
- Move flow modeled after `Action_01059` (Regroup) which is the canonical "move performer to adjacent City location" pattern. Used `getAdjacentCityLocations(..., $includeHome = false)` because no enemy character or available Mercenary can ever be at the performer's own home.
- "Available Mercenary" = uncontrolled (`! $character->isControlled()`) and `hasTrait("Mercenary")`. This matches the convention in `Theah::getCharactersInCityWithOpposingMercenaries` and `ArgumentsTrait`. Pulled with `getCharactersAtLocation($location, $includeUncontrolled = true)` so uncontrolled mercenaries are visible.

## Strega Maneuver

Mirrors `Technique_01050` for the -1 thrust + wound combination, but adapted to maneuver event hooks:

- `EventDuelCalculateManeuverValues`: `$event->thrust -= 1;` plus explanation. Direct field mutation since the maneuver calc event uses plain int fields (unlike combat-card calc which uses dashed-aware methods).
- `EventResolveManeuver`: `createCharacterBeingWoundedEvent` on `getDuelRoundOpponent()` — wound is a one-shot side effect, so it goes in resolve, not calc.
- No `EventManeuverCanceled` handler — added the literal comment to satisfy the pre-commit hook. There's no per-maneuver state to undo; calc and the queued wound are rolled back by the framework on cancel.

The gate is `actor->hasTrait("Strega")` — pulled from `getDuelRoundActor()`.

## State wiring

- Added `States::HIGH_DRAMA_PLAYER_TURN_03009 = 403009` (state ID convention: `4` + CardNumber).
- Created `State_highDramaPhase03009` GameState class with `actFromCardWithLocations` as the only possible action and `"locationChosen" => HIGH_DRAMA_PLAYER_TURN_EVENTS` as the transition home. Modeled after `State_highDramaPhase03cd01_2` (Penya's adjacent-location chooser), minus the `actBack` since this is a single-step flow without a previous sub-state to return to.
- Action calls `nextState("locationChosen")` to match the named transition.
- `states.inc.php`: `"03009" => States::HIGH_DRAMA_PLAYER_TURN_03009` under `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`.

## Front-end

- `OnEnteringState.faf.js`: `highDramaPhase03009` highlights performer and makes the valid adjacent city locations selectable.
- `OnUpdateActionButtons.faf.js`: adds disabled "Confirm Location" button wired to `onCityLocationsSelected`.
- `OnLeavingState.faf.js`: clears highlights via `resetCityLocations()` + unhighlights performer image.

## Things I considered and ruled out

- **Engaged filter on performer:** considered filtering performers by `! $character->Engaged`, like `_01030` does. Ruled out because `_01030` engages the performer as part of its cost, so the precondition is mechanical. `_01059` (Regroup, just a move) doesn't filter Engaged. Follow the Thread is just a move — no engage cost — so no filter.
- **`IRiskThatTargetsCharacters` on the Risk class:** ruled out. The Action targets a *location*, not a character. The Maneuver wounds the adversary but it's not a chosen target — it's the duel-round opponent. No `EventCharacterTargeted` is fired.
- **`includeHome = true` on `getAdjacentCityLocations`:** ruled out. The adjacent home from any city location is the performer's own home, which can't hold enemy characters and won't have available Mercenaries either.
- **`actBack` in the state:** ruled out. The previous step is the framework's in-hand action performer chooser; there's no card-specific sub-state to return to. Mirrors `State_highDramaPhase03002` and other single-step GameState classes that omit `actBack`.

## Pre-commit hook compliance

- `Action_03009` (extends `RiskAction`): calls `createActionResolvedEvent()` ✓.
- `Action_03009` (implements `ISorcererAbility`): calls both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` ✓.
- `Maneuver_03009` (extends `Maneuver`): includes `// EventManeuverCanceled handler not needed` ✓.

All four PHP files lint clean.
