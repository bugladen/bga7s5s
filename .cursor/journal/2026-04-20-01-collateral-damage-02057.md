# Collateral Damage (_02057) - Maneuver Implementation

## Card Text
**Maneuver:** Wound a character at this location that is not a participant in the duel. If they have Brute, wound them again.

## Implementation
Risk card with a single generic maneuver (no trait-specific variants like Thug/Duelist/Spy). Player must select a target character from non-participants at the duel location.

### Pattern Used
Followed `Maneuver_01051` (character targeting) + `State_duelResolveManeuver_02038` (tac GameState class) patterns.

Key decisions:
- `IAbilityThatTargetsCharacters` interface since player selects a character
- `getValidTargets()` helper shared between `isAvailableToPlayer`, `getArgsFromManeuver`, and validation
- Targets are ANY non-participant character at the location (not restricted to opponent's or own characters)
- Brute check happens immediately after first wound, queues a second wound event
- Used `argsForStatePrivate()` for the state args (matches 01051 pattern for character selection)

### Files Created
- `modules/php/cards/tac/maneuvers/Maneuver_02057.php` — maneuver class
- `modules/php/States/tac/State_duelResolveManeuver_02057.php` — GameState class

### Files Modified
- `modules/php/cards/tac/_02057.php` — added IHasManeuvers, ManeuverTrait, maneuver instantiation
- `modules/php/States.php` — `DUEL_RESOLVE_MANEUVER_02057 = 52502057`
- `states.inc.php` — transition `"02057"` under DUEL_RESOLVE_MANEUVER_EVENTS
- `modules/js/OnEnteringState.tac.js` — character selection (makeCardSelectable)
- `modules/js/OnLeavingState.tac.js` — clear selection
- `modules/js/OnUpdateActionButtons.tac.js` — confirm button (onChooseInPlayCardConfirmed)

## WHY: No setId() needed for single-class maneuvers
`initializeAbility()` (called in the Maneuver constructor) already sets `$this->Id` to the unqualified class name via `get_class($this)`. So `new Maneuver_02057()` automatically gets `Id = "Maneuver_02057"`. Calling `setId("Maneuver_02057")` after is redundant.

The existing cards that DO call `setId()` (e.g., `_02009`, `_02038`) need it because they create multiple instances of the same class with different suffixes (`Maneuver_02009_Thug`, `Maneuver_02009_Duelist`). For single-instance maneuvers, skip it.
