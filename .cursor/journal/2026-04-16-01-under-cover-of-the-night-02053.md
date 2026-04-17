# Under Cover of the Night (_02053)

## Card Summary
Neutral Scheme (Initiative 3, Panache +1). Two parts:
1. **Resolve**: Add a Renown to City Docks
2. **City Reaction**: At beginning of Dusk - performer doesn't move Home, then send up to one card from each discard pile to The Locker

## Design Decisions

### City Reaction vs Regular Reaction
This is a "City Reaction" on a Scheme, which means it uses the `IHasReactions` + `ReactionTrait` pattern with a separate `Reaction_02053` class. This matches the _02004 (Crash the Party) pattern - another Scheme with a City Reaction that uses the same architecture.

Regular "Reaction:" entries on Schemes (like _01125's Dusk reaction) can be handled directly in `handleEvent`, but City Reactions consistently use separate Reaction classes throughout the codebase.

### Finding the Performer
The game doesn't persist a direct scheme-to-performer link on the card object. The performer is tracked in the `player` table as `selected_character_id`. At the beginning of Dusk, this is still set (it gets cleared in `stDuskPhaseCleanup`). So the reaction queries the DB directly:
```php
$sql = "SELECT selected_character_id FROM player WHERE player_id = {$owner->ControllerId}";
```

WHY: Considered storing performer ID on the scheme during EventResolveScheme, but that would add a custom property that needs serialization. The DB query is simpler and guaranteed correct since the data persists until Dusk cleanup.

### "Does Not Move Home" Pattern
Follows the _01177 (Penya Shows The Way) pattern exactly:
- Add a condition (`UNDER_COVER_OF_THE_NIGHT`) to the performer character when reaction is accepted
- In `eventCheck`, catch `EventCardMoved` (not `EventCardMoving`) going to `LOCATION_PLAYER_HOME`
- Throw `BgaUserException` to cancel the movement
- Remove the condition after blocking (one-time use per Dusk)

WHY EventCardMoved not EventCardMoving: The EventHub processes `EventCardMoving` by creating an `EventCardMoved` event. The actual card movement happens when `EventCardMoved` is processed. _01177 blocks at the `EventCardMoved` level, and I follow that precedent.

Added a `toLocation == LOCATION_PLAYER_HOME` check that _01177 doesn't have - extra safety in case the condition somehow persists through other movement scenarios.

### Discard Pile Card Selection
"Send up to one card from each discard pile to The Locker" covers ALL discard piles: each player's + the city discard pile.

Uses the _02024 (Oath of Vengeance) iteration pattern:
- One transition event is queued per non-empty discard pile (empty piles are skipped entirely)
- Each transition enters the same state (`DUSK_PHASE_BEGIN_02053`)
- `$ProcessedPiles` array on the scheme tracks which piles have been handled (like _02024's `$ReknownLocations`)
- `getNextDiscardPile()` finds the next unprocessed non-empty pile
- Player picks 1 card (`actFromCardWithIds`) or passes (`actPassWithPass`) for each pile
- Cleanup: `$ProcessedPiles` resets on `EventDuskPhaseEnd`

WHY per-pile iteration instead of all-at-once: matches the _02024 pattern, gives clearer UX (one pile at a time), and the transition count matches the number of non-empty piles so the event queue is correct.

Cards go to the card's `OwnerId`'s locker via `createCardSentToLockerEvent($card->OwnerId, $card->Id)`, not the _02053 controller's locker.

### State Architecture
Uses the class-based GameState pattern (like _02024's `State_duskPhaseBegin02024`), not the old array-based pattern in `states.7s5s.php`. The transition `"02053" => States::DUSK_PHASE_BEGIN_02053` is added to `DUSK_PHASE_BEGIN_EVENTS` in `states.inc.php`.

## Files Changed
- `modules/php/Game.php` - Added `UNDER_COVER_OF_THE_NIGHT` condition constant
- `modules/php/States.php` - Added `DUSK_PHASE_BEGIN_02053 = 8002053`
- `states.inc.php` - Added transition entry
- `modules/php/States/tac/State_duskPhaseBegin02053.php` - New state class
- `modules/php/cards/tac/reactions/Reaction_02053.php` - New reaction class
- `modules/php/cards/tac/_02053.php` - Updated scheme class

## Still Needed
Frontend implementation for the card selection state (showing discard piles, multi-select UI). The backend provides cards with a `discardPilePlayerId` property for grouping.
