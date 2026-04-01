# Horatio Lockwood (_01066) Audit

## Card Text
> City Reaction: After an opposing character moved to an adjacent location • Move Horatio to their new location.
> Technique: If the adversary is the only enemy character at this location • +2 Thrust.

## Reaction — Bug Fixed (missing `isAvailable()` guard)

The `handleEvent` in `Reaction_01066` was missing the `$this->isAvailable()` check that all other card reactions use. Without it, even after the reaction was used and `setUsed(true)` was called in `performReaction`, the `EventCardMoved` handler would still queue a new reaction transition event. The player would be prompted to follow again even though the reaction was spent for the day.

Other reactions (`Reaction_01065`, `Reaction_01137`, `Reaction_01045`) all gate on `isAvailable()` or `!$this->Used` — this one was just overlooked.

### Trigger logic — Correct as-is

I spent time deliberating whether `$event->fromLocation == $owner->Location` was correct or overly restrictive. The card says "moved to an adjacent location" without explicitly saying "from this location." However, in context:
- All character moves in the game are to adjacent locations, so "to an adjacent location" is descriptive of normal movement
- The "chase" reading (character leaves Horatio's location, Horatio follows) fits the Smirking Rake flavor
- Without the fromLocation check, ANY enemy move that ends up near Horatio would trigger, which feels too broad for a once-per-day reaction
- Reaction_01137 (a similar follow-and-wound card) also cares about the fromLocation

Keeping the fromLocation check as correct.

## Technique — Bug Fixed (multiplayer enemy count)

The original code checked `getCharactersAtLocationByPlayerId($adversary->Location, $adversary->ControllerId)` and compared count to 1. This only counts characters belonging to the adversary's player. In a 3-4 player game, a third player's characters at the same location would be invisible to this check.

"Enemy character" from Horatio's perspective means ANY character not controlled by Horatio's player. Fixed to use `getCharactersAtLocation` and filter out `$owner->ControllerId`. Now all non-friendly characters are counted.

In a 2-player game the behavior is identical. The fix only matters for 3-4 player games.

## Files Changed
- `modules/php/cards/_7s5s/reactions/Reaction_01066.php` — added `$this->isAvailable()` check
- `modules/php/cards/_7s5s/techniques/Technique_01066.php` — broadened enemy character count to all opponents
