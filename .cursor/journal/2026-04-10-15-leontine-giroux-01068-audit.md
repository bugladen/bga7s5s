# Léontine Giroux (01068) Audit

## Card Text
"Sorcerer City Action: Wound Léontine • Move target character you control from her location to another."

## Files Reviewed
- `modules/php/cards/_7s5s/_01068.php` - Character class
- `modules/php/cards/_7s5s/actions/Action_01068.php` - Action implementation
- `states.7s5s.php` (lines 975-1004) - Two states: 01068 (character selection) and 01068_2 (location selection)
- `modules/js/OnEnteringState.7s5s.js`, `OnUpdateActionButtons.7s5s.js`, `OnLeavingState.7s5s.js` - JS handlers
- No reaction files exist for this card (none needed)

## Verdict: PASS - No bugs found

All card text requirements are correctly implemented:

1. **Sorcerer City Action**: ISorcererAbility interface + hasTrait("Sorcerer") + cardInCity checks.
2. **Wound cost**: createCharacterBeingWoundedEvent for Léontine, 1 wound.
3. **Target character you control at her location**: Filtered by ControllerId and Location matching Léontine's.
4. **Move to another location**: Filters out Léontine's current location, validates selection.

## Pattern Observations

Compared against Action_01012 (Sibella) and Action_01201 (Ravenna). Action_01068 follows the same pattern as Action_01201:
- SorcererAbilityStart → Wound (cost) → Effect → SorcererAbilityPlayed → ActionResolved
- Uses explicit `eventCheck()` before `queueEvent()` for wound and move events (hard gate pattern: if the event is blocked by a card, the action fails entirely rather than silently dropping the event).

Action_01012 puts the wound cost BEFORE SorcererAbilityStart and uses batch IDs. Action_01201 and 01068 don't use batch IDs and put Start before cost. Both patterns exist in the codebase - not a bug, just variation.

Note: Léontine herself is a valid move target (she's at her own location and controlled by the player). If the wound knocks her out, the move event would naturally fail through the event system. Edge case but handled correctly by design.
