# Corpse Speak (_01154) Audit

## Card Text
> May only equip to your Sorcerer. The equipped character's [Influence] is set to 0.
> Action: Engage the equipped performer • Play target risk from your discard pile as if it was from your hand. Send this card to The Locker.

## Result: All Correct — No Fixes Needed

This is a complex card with a multi-file implementation: `_01154.php` (card definition), `Action_01154.php` (action logic with custom states), and `_01154_RiskClone.php` (clone card that handles post-play cleanup). All five aspects of the card text are correctly implemented.

### Equip Restriction
- "your" enforced by `CanEquipToOpponents = false` (FactionAttachment default), validated in `FrameworkActionsTrait.php`
- "Sorcerer" enforced by `eventCheck` on `EventAttachmentEquipped`, throws if character lacks the Sorcerer trait

### Influence Lock
- `InfluenceLocked = true` + `InfluenceLockedValue = 0` — `Character.php` applies this in its stat calculation loop

### Engage Cost
- `isAvailableToPlayer` gates on `!$character->Engaged`
- `handleEvent` queues `CardEngagedEvent` before transitioning to selection state

### Play Risk from Discard as if from Hand
- Uses `risksAvailableFromDiscardPile` to find affordable risks with available actions
- `getArgsFromAction` further filters actions to those performable by the equipped character
- `stateFromAction` (01154_2): hides original risk, creates a `_01154_RiskClone` in hand with cloned action/traits/cost, routes into normal in-hand action flow

### Send to Locker
- `_01154_RiskClone::handleEvent` on `EventCardDiscardedFromHand`: hides clone, returns original risk to discard pile, unequips Corpse Speak, sends it to The Locker

### Cancel Path
- If player reaches state 01154 with no usable options: character gets en-garded (undoes engage), player gets extra action as compensation
- `setUsed` is NOT called on cancel — action stays available for future turns

## Architecture Notes for Future Reference

WHY the three-file approach: The clone mechanism (shared pattern with _01106 and _01124) is necessary because "play as if from hand" requires the card to actually exist in the player's hand for the normal play-from-hand flow to work. The clone sits in hand temporarily, then on discard the RiskClone's event handler cleans up (hides clone, restores original, and in this case sends Corpse Speak to The Locker). The original risk is hidden in `LOCATION_PERMANENTLY_HIDDEN` during play to prevent duplicate issues, then restored to the discard pile when the clone is discarded.

WHY `getArgsFromAction` filters by performer separately from `risksAvailableFromDiscardPile`: The latter is a shared utility that checks player-level availability. The former adds Corpse Speak's specific constraint that only the equipped character can perform the action. There's a theoretical edge case where a risk passes the utility filter but has no actions performable by the equipped character — the cancel path handles this gracefully.
