# debug_AddCardToApproachDeck

Eddie asked for a debug helper to inject a card into a player's Approach Deck by classname.

## Implementation
Added to `DebugTrait` next to `debug_AddCardToHand`:
- `createCardInLocation($className, LOCATION_APPROACH, $playerId, $playerId)`
- notify with `approachCardsReceived` so the approach stock updates without reload

## WHY this shape
- Same signature pattern as hand/faction helpers: `(className, playerId)` — classname alone isn't enough in multiplayer studio.
- Used `approachCardsReceived` (not `#[Debug(reload: true)]`) because EventHub's CharacterPutIntoApproachDeck and DeckTrait setup already use that notif, and `notif_approachCardsReceived` already calls `addCardToDeck(this.approachDeck, card)`. Live UI update matches AddCardToHand's drawCard pattern better than a full reload.
