# Penya _03cd01 Implementation

First faf (Fur and Fangs) expansion card implemented. City Character with four abilities.

## Card Text
- **Negotiable** (parley when paying)
- **Penya cannot intervene.**
- **City Forced:** When Penya participates in a duel or would be wounded • Put the top card of the City Deck at his location. Then, shuffle him into the City Deck.
- **City Action:** Engage Penya • Move Penya and another of your characters at this location to the same adjacent City location.

## Implementation Decisions

### canIntervene() override
Simple — return `false`. Matches the `Character::canIntervene()` pattern at Character.php:78-81. The base checks `isControlled()`, Penya overrides to always false. Theah's `interventionCheck()` calls this method at Theah.php:1656/1663.

### City Forced: Wound trigger
Uses `EventCharacterBeingWounded` with `$event->canceled = true` — same pattern as Maryam (_01186). Key difference: Maryam only cancels wounds from Risk cards (`$source instanceof Risk && $source instanceof IRiskThatTargetsCharacters`). Penya cancels ALL wounds while in city — no source filter needed.

### City Forced: Duel trigger
Uses `EventDuelStarted`. This fires AFTER the duel DB record is created and "duel started" notification is sent (StatesTrait.php ~1010-1029). The forced removes Penya from play and puts him in the city deck, but the duel has already been set up. **This means the duel system needs to handle a missing combatant gracefully.** This is a known edge case that may need additional work — flagging it rather than trying to solve it in this implementation.

WHY not use `EventChallengeIssued` instead? Because the card says "participates in a duel", not "is challenged." A challenge can be rejected without a duel happening. The trigger should only fire when the duel actually starts.

### City Forced: Deck shuffle timing
The tricky part: after `createCardAddedToCityDeckEvent` or `createCardRemovedFromPlayEvent` is queued, we need to shuffle AFTER the card is actually in the deck.

Solution: Event processing order. For events with `runEventHubAfterCards = false` (default), EventHub processes first, THEN cards' handleEvent fires (Theah.php:226-243). So:
1. Queue `createCardRemovedFromPlayEvent` (Penya → city deck)
2. EventHub processes it — moves Penya to city deck location
3. Cards' handleEvent fires — Penya listens for `EventCardRemovedFromPlay` targeting himself, triggers deck shuffle

WHY `createCardRemovedFromPlayEvent` instead of `createCardAddedToCityDeckEvent`? The "removed from play" event sends the proper frontend notification ("cardRemovedFromPlay") to animate Penya leaving the board. The card added to city deck event is designed for cards already revealed/in limbo, not for cards visually on the board.

### City Action: Two-step state flow
- State 1 (`03cd01`): Choose companion character — another of your characters at Penya's location
- State 2 (`03cd01_2`): Choose adjacent city location — both Penya and companion move there

Follows the Action_01138 / Action_01055 two-step pattern. State 1 uses `actFromCardWithId`, state 2 uses `actFromCardWithIds` (location selection).

Move events use `$engage = false` — the card says "Engage Penya" as a cost (handled via createCardEngagedEvent in handleEvent), but doesn't say the characters engage when they arrive. Penya is already engaged from the cost.

### State IDs
Expansion 3 uses `403XXXX` prefix to avoid conflicts with exp 1 (`401XXX`) and exp 2 (`402XXX`):
- `HIGH_DRAMA_PLAYER_TURN_03CD01 = 4030001`
- `HIGH_DRAMA_PLAYER_TURN_03CD01_2 = 40300012`

State files go in `modules/php/States/faf/` (new directory, matching the _7s5s/tac pattern).

### Pre-commit hook compliance
Action_03cd01 extends CharacterAction, which requires:
- `createActionResolvedEvent` ✓ (in actFromActionWithIds)
- `$this->setUsed()` ✓ (in handleEvent)
- `$this->resetPlayerPassCount()` ✓ (in handleEvent)
- `$this->announceAction()` ✓ (in handleEvent)

## IMPORTANT: Don't forget JavaScript state wiring

Every new state needs JS wiring in BOTH:
- `modules/js/OnEnteringState.faf.js` — UI setup (highlight selectables, mark chosen characters)
- `modules/js/OnUpdateActionButtons.faf.js` — action buttons (Confirm, Confirm Location, Back)

Without this, the state will load but the player will have no UI to interact with. This is easy to forget since the PHP side compiles/runs without it.

Patterns to follow:
- Character selection: `highlightCardsAsSelectable(ids)` + button `onChooseInPlayCardConfirmed()`
- Location selection: `makeCityLocationSelectable(element)` + button `onCityLocationsSelected()`
- Already-chosen characters: `dojo.addClass(image, '_7sfs-chosen')`

## Files Created/Modified
- `modules/php/cards/faf/_03cd01.php` — Updated with all abilities
- `modules/php/cards/faf/actions/Action_03cd01.php` — New city action
- `modules/php/States/faf/State_highDramaPhase03cd01.php` — State 1
- `modules/php/States/faf/State_highDramaPhase03cd01_2.php` — State 2
- `modules/php/States.php` — Added state constants
- `states.inc.php` — Added transition mappings
- `modules/js/OnEnteringState.faf.js` — JS state handlers for both steps
- `modules/js/OnUpdateActionButtons.faf.js` — JS action buttons for both steps

## Open Questions
- **Duel edge case:** If Penya's forced triggers on EventDuelStarted, the duel record is already created. The duel engine may or may not handle a missing combatant. Needs testing.
- **Top city card reveal:** `getCardsOnTopOfCityDeck(1)` returns raw card info, not a card object. Cast `$topCard['id']` to int for `createCityCardAddedToLocationEvent`. The EventHub handler for this event (`EventCityCardAddedToLocation`) loads the card object and moves it.
