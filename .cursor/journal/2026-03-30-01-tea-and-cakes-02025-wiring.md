# Tea and Cakes (02025) Ability Wiring

## What Was Done

Wired up both abilities on the Montaigne scheme "Tea and Cakes" (`_02025`):

### Ability 1: Scheme Resolve Effect
> "Add a Renown to any location. Target opponent adds a Renown to any location."

Three-state flow:
1. `PLANNING_PHASE_RESOLVE_SCHEMES_02025` (2602025) — Owner picks any city location for 1 Renown
2. `PLANNING_PHASE_RESOLVE_SCHEMES_02025_2` (26020252) — Owner picks a target opponent
3. `PLANNING_PHASE_RESOLVE_SCHEMES_02025_3` (26020253) — That opponent picks any city location for 1 Renown

The transition to state 3 uses the **opponent's player ID** as the initiating player in `createTransitionEvent`, which causes BGA to make the opponent the active player for that state. This is the same pattern as Shifting Tides (`_01151`) where opponents get their own turn to place renown.

### Ability 2: Diplomat City Action
> "Target an opposing character with equal or lower [inf] • Move them, your performer and a Renown from this location to the same adjacent location."

`Action_02025` extends `SchemeCityAction` (not `CharacterAction` like 02023's action) because it's a scheme-based city action. Two-state flow:
1. `HIGH_DRAMA_PLAYER_TURN_02025` (402025) — Choose opposing character with influence <= performer's
2. `HIGH_DRAMA_PLAYER_TURN_02025_2` (4020252) — Choose adjacent city location

On resolution: moves target, moves performer, removes 1 Renown from source location and adds 1 to destination (as a move, with `$isMove = true`).

## Key Design Decisions

**WHY SchemeCityAction over CharacterAction:** The card is a Scheme, not a Character. `SchemeCityAction` checks that the scheme is in `LOCATION_PLAYER_HOME` and that there's at least one friendly character in the city. Then we further filter performers to those with the `Diplomat` trait. Action_01071 (Épée Sanglante) uses the exact same pattern for Musketeer-gated scheme city actions.

**WHY no renown constraint on adjacent location:** Unlike Sir Jack Harding (02023) which requires the destination to have "less Renown", Tea and Cakes has no such restriction on the adjacent location. The card text just says "adjacent location" — no renown comparison.

**WHY `$includeHome = false` for adjacency:** The card says "adjacent location" generically, but moving characters + renown Home doesn't make game sense (Home isn't a city location with a renown track in the normal sense). Following the 02023 precedent where Home is excluded. If this turns out wrong, change the flag in `getAdjacentCityLocations` call in `Action_02025`.

**WHY the renown move checks for > 0:** The card says "a Renown from this location" — if there's no Renown at the location, the move portion silently does nothing. This is a graceful degradation. The character moves still happen regardless.

## Files Created
- `modules/php/cards/tac/actions/Action_02025.php`
- `modules/php/States/tac/State_planningPhaseResolveSchemes02025.php`
- `modules/php/States/tac/State_planningPhaseResolveSchemes02025_2.php`
- `modules/php/States/tac/State_planningPhaseResolveSchemes02025_3.php`
- `modules/php/States/tac/State_highDramaPhase02025.php`
- `modules/php/States/tac/State_highDramaPhase02025_2.php`

## Files Modified
- `modules/php/cards/tac/_02025.php` — added IHasActions, ActionTrait, handleEvent, argsFromCard, actFromCardWithId, actFromCardWithIds
- `modules/php/States.php` — 5 new state constants
- `states.inc.php` — transition entries for both planning phase and high drama event hubs
- `modules/js/OnEnteringState.tac.js` — 5 new state handlers
- `modules/js/OnLeavingState.tac.js` — 4 new state handlers (state 2 for opponent pick has no UI to clean up)
- `modules/js/OnUpdateActionButtons.tac.js` — 5 new state handlers

## Corrections Made by Eddie
- `_02025.php` line 106: Changed `$game->getActivePlayerName()` to `$game->getPlayerNameById($this->ControllerId)` in the opponent-chosen notification. WHY: The active player at that point IS the scheme controller, but `getPlayerNameById` is more explicit and safer — it won't break if something changes the active player context.
- `_02025.php` line 161: Changed `$game->getActivePlayerName()` to `$game->getPlayerNameById($opponentId)` in the opponent-places-renown notification. Same reasoning — the active player should be the opponent at this point (since the transition used their ID), but being explicit is clearer.

## JS Wiring Patterns Used
- **Scheme resolve location picks** (`02025`, `02025_3`): Same pattern as `planningPhaseResolveSchemes_02005` — make all city locations selectable, 1 location max, confirm button.
- **Scheme resolve opponent pick** (`02025_2`): Same pattern as `planningPhaseResolveSchemes_02005_3` — render a button per opponent from `args.args.opponents`.
- **City action character pick** (`highDramaPhase02025`): Same pattern as `highDramaPhase02023` — highlight performer, highlight selectable cards, confirm button + back button.
- **City action location pick** (`highDramaPhase02025_2`): Same pattern as `highDramaPhase02023_2` — show selectable adjacent locations, highlight performer + chosen target, confirm + back buttons.

## Still Needed
- Testing via BGA studio
- Audit against card text after testing
