# Urraca de Murrieta (_01070) Audit

## Card Text
> Reaction: After you add a Renown to a location, discard a card • Add another Renown to that location. (Moving Renown is not adding Renown.)

## Reaction — Bug Fixed (missing playerId check)

`handleEvent` was not checking that `$event->playerId == $owner->ControllerId`. The card says "After **you** add a Renown" — only the controlling player's renown additions should trigger the reaction. Without this check, any player adding renown anywhere would prompt Urraca's controller for the reaction, which is incorrect.

This is the exact same class of bug as the missing `isAvailable()` check from the 01066 audit — a guard condition that every other reaction in the codebase includes was simply omitted here. Pattern: `Reaction_01016`, `Reaction_01124`, `Reaction_01116b` all check `$event->playerId == $owner->ControllerId` for "you" triggers.

No infinite loop risk: `setUsed(true)` is called in `performReaction` before the queued `EventRenownAddedToLocation` fires, so `isAvailable()` blocks the re-trigger.

## Typo Fix

"Dimplomat" → "Diplomat" in the Traits array.

## Everything Else — Correct

- `!$event->isMove` correctly implements "(Moving Renown is not adding Renown.)"
- Hand-card check prevents triggering with empty hand
- `performReaction` correctly: discards chosen card, adds 1 renown to stored location, marks used
- Pass option correctly does NOT mark used (reaction stays available)
- `$this->location` is properly stored from trigger and cleared after use

## Files Changed
- `modules/php/cards/_7s5s/reactions/Reaction_01070.php` — added `$event->playerId == $owner->ControllerId` check
- `modules/php/cards/_7s5s/_01070.php` — fixed "Dimplomat" typo → "Diplomat"
