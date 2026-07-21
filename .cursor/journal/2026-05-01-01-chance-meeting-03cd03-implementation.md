# Chance Meeting (_03cd03) Implementation

Second faf expansion card. A CityEventCard with a single City Action that targets a player and lets eligible opponents Muster from their approach decks.

## Card Text
> **City Action:** Target a player • In order of Initiative, each player that controls fewer characters than the targeted player may Muster a character from their approach deck at this location. Discard this card.

## Implementation Decisions

### No engagement cost
Unlike Risky Undertaking (01185) or Point of Opportunity (01189), this card has no explicit cost (no "Engage your performer", no "Discard X cards"). The standard requirement of EventCityAction (a friendly character at the location) is the only gate. So no `createCardEngagedEvent`, no character "performer" tracked.

### Two-state flow with multi-player loop
- **State `03cd03`** (active): the action triggerer picks a target player. `actFromCardWithId(playerId)`.
- **State `03cd03_2`** (active): each eligible opponent (in turn order) is rotated through this state via queued EventTransitions to either Muster or Decline.

WHY two states with a transition loop instead of multipleactiveplayer: the card text says "In order of Initiative" — strict sequential ordering matters. multipleactiveplayer would allow simultaneous responses. Following the Shifting Tides (_01151) precedent of queueing one EventTransition per opponent in turn_order.

### Ordering of discard relative to musters
TRANSITION_PRIORITY = 8, MEDIUM_PRIORITY = 3 (lower number = higher priority = runs first). If we queued [transitions..., discardEvent] up front, the discard (priority 3) would beat all the transitions (priority 8) and run before any muster — the card would be gone before opponents got their turn.

Solution: track a `chanceMeetingRemaining` global (JSON list of remaining player IDs). After each opponent acts in 03cd03_2, pop them from the list. When the list becomes empty, that final invocation queues the discard + actionResolved events — at that point there are no more transitions ahead of them in the queue, so they fire next.

If the eligible list is empty up-front (no player has fewer chars than the target), the same `queueDiscardAndResolve()` runs immediately from the target-chosen handler.

### actionResolved playerId
Used `Game::CURRENT_PLAYER` global, not `getActivePlayerId()`. By the time the last muster resolves, the active player is whoever the last musterer was — not the action triggerer. `CURRENT_PLAYER` reliably stores the player whose high drama turn this is, which is the correct player for the action-resolved signal.

### State IDs
Continuing the Penya pattern from `2026-04-26-01-penya-03cd01-implementation.md`:
- `HIGH_DRAMA_PLAYER_TURN_03CD03 = 4030003`
- `HIGH_DRAMA_PLAYER_TURN_03CD03_2 = 40300032`

State files in `modules/php/States/faf/`. Transitions registered in `states.inc.php` under HIGH_DRAMA_PLAYER_TURN_EVENTS.

### Pre-commit hook
Action_03cd03 extends EventCityAction → CardAction. The hook only requires `createActionResolvedEvent` for CardAction descendants. setUsed/resetPlayerPassCount/announceAction are *only* required for CharacterAction subclasses. setUsed is also unnecessary because the card is discarded after one use.

resetPlayerPassCount IS called (once, in handleTargetChosen) following the convention from 01185, which also uses it for an EventCityAction.

### Eligibility computation
"controls fewer characters than the targeted player" — uses `Theah::getCharactersInPlayByPlayerId()` which counts characters in the city OR at player home (excludes purgatory, hand, dueling line). The target player themselves is always excluded (they don't have fewer than themselves). Iterates `ORDER BY turn_order` from the player table — turn_order is the day's initiative order.

### Muster mechanics
Reused the pattern from 01072 (Réputation Méritée): get cards in `Game::LOCATION_APPROACH` for the player, filter for `instanceof Character`, validate the chosen card is in that set, then `EventFactory::createCharacterMusteredEvent($playerId, $cardId, $location)`. The event handler in EventHub takes care of moving the character into play and any reactions (e.g., crew cap).

Decline (id=0) is allowed — the text says "may Muster", not "must". UI shows a "Decline" button in addition to the muster card selection.

### Globals cleanup
`chanceMeetingRemaining` and `CHOSEN_TARGET` are deleted in `queueDiscardAndResolve` so they don't leak across the day or to subsequent triggers of similar cards. CHOSEN_TARGET is also reset by `stNextPlayer` defensively.

## JS Wiring
- `OnEnteringState.faf.js`: highlight Chance Meeting card during both states; set up approach deck selection mode in 03cd03_2 with non-Character cards greyed out.
- `OnUpdateActionButtons.faf.js`: per-player buttons in 03cd03 (mirrors 02002 Elisabetta pattern); Confirm Muster + Decline buttons in 03cd03_2.
- `OnLeavingState.faf.js`: reset approach deck selection mode and unselectable styling.
- `PlayerActions.js`: extended `onMusterCardSelected` action map to include `highDramaPhase03cd03_2`.

## Files Created/Modified
- `modules/php/cards/faf/_03cd03.php` — added IHasActions/ActionTrait wiring
- `modules/php/cards/faf/actions/Action_03cd03.php` — new
- `modules/php/States/faf/State_highDramaPhase03cd03.php` — new
- `modules/php/States/faf/State_highDramaPhase03cd03_2.php` — new
- `modules/php/States.php` — added two state constants
- `states.inc.php` — added two transition mappings under HIGH_DRAMA_PLAYER_TURN_EVENTS
- `modules/js/OnEnteringState.faf.js`, `OnUpdateActionButtons.faf.js`, `OnLeavingState.faf.js` — UI wiring
- `modules/js/PlayerActions.js` — added 03cd03_2 to onMusterCardSelected routing

## Open Questions / Followups
- **Zombie behavior in 03cd03_2**: I have it call `actFromCardWithId(0)` so the player is treated as Declining the muster, which pops them from REMAINING and lets the loop continue. Untested in zombie scenarios.
- **CURRENT_PLAYER global lifecycle**: I'm relying on this being set during the high-drama-turn lifecycle. Verified by reading Theah::runEvents() lines 296-317 which falls back to it when resetting active player. Should be reliable but worth double-checking if the discard/actionResolved fires from an unexpected context.
- **Crew cap reaction during muster**: If a Mustering player is at crew cap, `Reaction_CrewCapLimit` may fire. That's expected core behavior — they'd discard a character for the new one. No 03cd03-specific handling needed.
