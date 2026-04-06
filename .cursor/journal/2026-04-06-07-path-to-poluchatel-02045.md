# Path to Poluchatel (_02045) — Scheme with City Placement + Sorcerer City Action

## Card Text
- **Scheme Resolution:** "Choose one of the outermost locations. Add a Renown to two other locations. Place this card on the chosen location."
- **Sorcerer City Action:** "If your performer is at the chosen location • Pressure it with [Influence]. If successful, search your deck for a Dar Matushki or Poluchatel card, reveal it, and put it into your hand. (Shuffle your deck.)"

## Implementation

### Scheme Resolution — Leshiye Pattern (Simplified)

Followed `_01126` (Leshiye of the Wood) exactly for the two-state scheme resolution flow:
- State 1: Choose outermost location → store `ChosenLocation`, persist, `addCardToWorld`, set global
- State 2: Choose two *other* locations → eventCheck + queue renown events, queue `EventSchemeMovedToCity`

**WHY simplified vs Leshiye:** Leshiye does destructive things on placement (discard city cards, send characters home, remove renown, block renown/control). Path to Poluchatel just sits there passively — no `eventCheck` overrides, no cleanup in `handleEvent(EventSchemeMovedToCity)`. The scheme only announces its placement. Its value comes from enabling the Sorcerer City Action.

### Sorcerer City Action — First Scheme-in-City with a City Action

This is the first card that is both a scheme placed in the city AND has a city action. That created a problem:

**WHY full `isAvailableToPlayer` override:** `SchemeAction::isAvailableToPlayer` checks `$owner->Location != Game::LOCATION_PLAYER_HOME` and returns false if the scheme isn't at home. But this scheme lives in the city after placement. `SchemeCityAction` extends `SchemeAction`, so calling `parent::isAvailableToPlayer` would always fail.

Can't skip a class in PHP's `parent::` chain. So `Action_02045::isAvailableToPlayer` fully overrides without calling parent, replicating the essential `CardAction` checks manually:
1. Owner controlled by player
2. Action not used
3. Scheme has a ChosenLocation (is in the city)
4. Player has a Sorcerer at the ChosenLocation

**WHY still extend `SchemeCityAction`:** Inheritance gives us `CardAbilityTrait` and the action framework plumbing. Only `isAvailableToPlayer` needed the override. `getPerformersForAction` was also overridden to return only Sorcerers at the scheme's location.

### Pressure + Deck Search Flow

Followed `Action_02035` (Castillian Caper) for the Influence pressure setup:
- Set `PRESSURING_PLAYER`, `PRESSURE_TYPE` (normal — no custom type needed)
- Queue `EventPressureOccuring` + transition to `pressureLocation`
- Listen on `EventLocationPressureResult` for success/failure

**WHY no custom PRESSURE_TYPE flag:** Unlike Castillian Caper (which adds per-Scoundrel bonuses via a custom pressure type checked in `pressureLocation()`), Path to Poluchatel is a straight Influence pressure with no modifiers.

On success, transitions to `HIGH_DRAMA_PLAYER_TURN_02045` for deck search. Followed `_01016` (Plans Within Plans) for the search pattern:
- Args: enumerate faction deck, filter by `hasTrait("Dar Matushki") || hasTrait("Poluchatel")`
- Choose: validate card is in deck + has trait, queue remove + add-to-hand events, shuffle deck
- Pass: only allowed if no matching cards exist in deck

**WHY shuffle after search:** Card text explicitly says "(Shuffle your deck.)" — unlike Plans Within Plans which doesn't shuffle. Called `$game->getGameDeckObject()->shuffle($deckName)` directly after the search.

### Sorcerer Events

Wrapped the action in `SorcererAbilityStart` / `SorcererAbilityPlayed` events. Start fires before the pressure; Played fires after the pressure result (regardless of success/failure). This follows the pattern from `Action_02010` (Strega wound transfer).

### JS Wiring

**WHY `argsForStatePrivate` for deck search:** The high drama deck search state shows cards from the player's faction deck. These must be private (only visible to the active player). Changed `State_highDramaPhase02045::getArgs()` to call `argsForStatePrivate()` instead of `argsForState()`. This wraps the args in `_private.active.args` so only the active player receives them. Follows the `_01016_2` (Plans Within Plans) pattern exactly.

**JS access pattern difference:**
- Planning phase location args: `args.args.args.locationIds` (public, all players see)
- Deck search cards: `args.args._private.args.cards` (private, active player only)

**State 1 (`planningPhaseResolveSchemes_02045`):** Uses `locationIds` from args to highlight only outermost locations. Follows `_02035` pattern with `getCityLocationElement()`.

**State 2 (`planningPhaseResolveSchemes_02045_2`):** Mirrors `_01126_2` exactly — queries for the `chosenLocation` element via `data-location` attribute, marks it as chosen (non-selectable), makes all other locations selectable. Uses `numberOfCityLocationsSelectable = 2`.

**Deck search (`highDramaPhase02045`):** Opens `choose_container` + `chooseList`, populates with matching cards via `addCardToDeck`, sets single selection mode. Pass button calls `actPass`. Confirm button calls `onChooseListCardConfirmed()`.

## Files Changed
- `modules/php/cards/tac/_02045.php` — full scheme class with `IHasActions`, `ChosenLocation`, scheme resolution handlers
- `modules/php/cards/tac/actions/Action_02045.php` — Sorcerer city action with pressure + deck search
- `modules/php/States/tac/State_planningPhaseResolveSchemes02045.php` — choose outermost location
- `modules/php/States/tac/State_planningPhaseResolveSchemes02045_2.php` — choose two renown locations
- `modules/php/States/tac/State_highDramaPhase02045.php` — deck search after pressure success (uses `argsForStatePrivate`)
- `modules/php/States.php` — 3 new state constants
- `states.inc.php` — 3 new transition entries (02045, 02045_2 in planning; 02045 in high drama)
- `modules/js/OnEnteringState.tac.js` — 3 new state handlers (location pick x2, deck search)
- `modules/js/OnLeavingState.tac.js` — 3 new cleanup handlers (resetCityLocations x2, hide chooseList)
- `modules/js/OnUpdateActionButtons.tac.js` — 3 new button handlers (confirm location, back+confirm, confirm+pass)
