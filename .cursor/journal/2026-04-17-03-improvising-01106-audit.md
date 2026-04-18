# Improvising (01106) Audit

## Card Text
"**Action:** Play target risk from an opponent's discard pile, paying all costs. After it resolves, sink it. Send this card to The Locker. (Cards return to their owner's deck when sunk.)"

## Architecture
This card has a clever clone mechanism. It's one of the more complex implementations in the codebase.

### Flow
1. Player plays Improvising from hand (WealthCost 0)
2. `actPayForInHandAction` queues: EventRiskPlayed, EventActionTriggered, EventCardDiscardedFromHand (Improvising)
3. Events process in priority order: Improvising gets discarded to player's discard pile (priority 3) BEFORE the transition to state 01106 (priority 8)
4. Player chooses opponent → chooses risk/action
5. `actFromActionWithActionId`:
   - Hides original card in LOCATION_PERMANENTLY_HIDDEN
   - Creates `_01106_RiskClone` in player's hand (copies name, image, wealth cost, chosen action)
   - Sets ABNORMAL_FLOW to disable back buttons
   - Routes to performer selection or direct payment via normal in-hand action flow
6. Clone's action is paid for and triggered through normal flow
7. Clone gets discarded from hand → `EventCardDiscardedFromHand` fires
8. RiskClone handler:
   - Hides clone
   - Sinks original to owner's faction deck (`$clonedCard->ControllerId`)
   - Moves Improvising from player's discard pile → The Locker

### WHY the clone mechanism
The game's action system expects actions to be on cards in your hand. Rather than special-casing the entire action pipeline, they create a temporary clone with the desired action, inject it into the player's hand, and let the normal in-hand action flow handle payment and triggering. The clone then handles cleanup on discard.

## Audit Result
No bugs found. All five aspects of the card text are correctly implemented. Event ordering verified — Improvising's discard event (priority 3) processes before the transition event (priority 8), ensuring it's in the discard pile for later cleanup.
