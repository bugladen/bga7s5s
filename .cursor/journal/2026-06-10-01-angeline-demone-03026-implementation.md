# Angeline Dèmone (_03026) Implementation

## Card Structure

**Angeline Dèmone — Uneasy Ally (Montaigne, Duelist/Pirate/Sorcerer)**

Text:
1. **Passive**: "While you control another Sorcerer at Angeline's location, she has +1 Influence"
2. **Action**: "Discard a card • Move Angeline to an adjacent City location. Then, if the discarded card was a Sorcery, wound an opposing character"

## Implementation Plan

### Part 1: Passive Influence Modifier

Pattern: "While a condition on other cards holds, apply a stat modifier"
- Hook `EventCharacterMoved` for Angeline (move herself OR move another character)
- Hook `EventCardMoved` for any card (another Sorcerer moving away from her location or to her location)
- Recompute: count Sorcerers at Angeline's location (excluding self)
- Queue `createCharacterInfluenceModifiedEvent(±1)` on transition
- Use a `$SorcererCountAtLocation` field + flag-based delta pattern

Key gates:
- Angeline must be in play (`ControllerId > 0 && ! characterIsInDiscardOrLocker`)
- Angeline must be in a city location (not home, not approach)
- Count OTHER Sorcerers at the SAME location (exclude Angeline herself)

### Part 2: Action (Discard + Move + Conditional Wound)

Pattern: Two-step CharacterAction
- Step 1: Discard picker (cards in hand)
- Step 2: Adjacent city location picker
- Effect resolution: Move to location, then if discarded card was Sorcery, wound an opposing character at the new location

States needed:
- `HIGH_DRAMA_PLAYER_TURN_03026` (step 1: discard picker)
- `HIGH_DRAMA_PLAYER_TURN_03026_2` (step 2: location picker)

WHY two steps: The action text reads "Discard a card • Move … Then, if the discarded card …" — the discard choice must resolve before the move/wound, so separating into two states makes sense (pick card → pick location → effect).

State class: `State_highDramaPlayerTurn_03026` with basic picker structure
- `"discardChosen"` → `HIGH_DRAMA_PLAYER_TURN_EVENTS`
- `"locationChosen"` → `HIGH_DRAMA_PLAYER_TURN_EVENTS` (resolve inline)

Action class: `Action_03026` extends CharacterAction

JS wiring: Standard two-step picker UI
- OnEnteringState: highlight hand cards for step 1, city locations for step 2
- OnUpdateActionButtons: Confirm button for both
- OnLeavingState: cleanup

### Key Decisions

1. **Influence recompute timing**: Hook both `EventCardMoved` (for other cards moving) and `EventCharacterMoved` for Angeline's own moves. Use a running count field `$SorcererCountAtLocation` + delta pattern (like Elena's Finesse bonus).

2. **Discard + Move + Wound order**: 
   - At `EventActionTriggered`, queue the transition event
   - Step 1: prompt for discard, store choice in global
   - Step 2: prompt for location
   - In `actFromActionWithId` (step 2), apply the effect inline:
     - Move Angeline via `createCardMovingEvent`
     - Check if discarded card's trait was Sorcery
     - If yes, queue wound event against an opposing character at the new location

3. **Wound targeting**: "wound an opposing character" — need valid-target precondition. But where? The player doesn't pick a target — we pick one for them. Options:
   - Wound the first opposing character at the new location
   - Wound any opposing character at the new location (let EventHub pick)
   - Error if no opposing character exists

   Reading the card text literally: "wound an opposing character" is singular but doesn't name "choose". The codebase pattern for "effect without UI choice" is to apply the effect if valid targets exist. Since there's no picker UI, wound any one opposing character (first in the list). OR: only wound if exactly one opposing character exists. OR: let EventHub handle it (wound the first in the list by default).

   **Decision**: Use the simplest interpretation — wound the first opposing character at the new location (in some deterministic order). If no opposing characters exist at the destination, no wound. This matches the codebase's "wound an opposing character" pattern in abilities that don't prompt for a specific target.

   Actually, re-reading: the card text says "wound an opposing character" with no "choose" — this is **mandatory**, not optional. So wound MUST happen if ANY opposing character is present. Use `getOpposingCharactersAtLocation` at the new location; if non-empty, wound the first one (or any deterministic pick).

4. **City location eligibility**: "Move Angeline to an adjacent City location" — "adjacent" means next to her current location. "City location" means not Home. Use `$theah->getAdjacentCityLocations($current, $includeHome=false)` to get valid destinations.

5. **Action name**: Something like "Discard and Relocate" or "Discard a card and move to adjacent location".

## Risk Factors

- **Sorcerer count timing**: If another Sorcerer is destroyed at the same time Angeline is at that location, the count recompute might fire before or after. The `parent::handleEvent($event)` discipline (call parent first) ensures we see up-to-date Sorcerer state, but destroyed characters still exist in `theah->getCharactersAtLocation` until they're fully removed. **Need to gate on `! characterIsInDiscardOrLocker($character)`** when counting.

- **Discard validation**: When the player picks a card to discard in step 1, confirm it's from `LOCATION_HAND`. Standard gate.

- **Move mechanic**: The "Move Angeline to an adjacent City location" is a `createCardMovingEvent` with her as the mover, current location as source, destination as target. Set `engage=false` (moving isn't engagement), sourceId = action id.

- **Wound after move**: The wound is queued AFTER the move event (same `actFromActionWithId` act handler). The wound target must exist AT THE DESTINATION. This is safe because the move event is queued first but executed after the state transition.

## Implementation Complete

### What Was Built

1. **Character card class** (`_03026.php`):
   - Passive Influence bonus: +1 when another Sorcerer is at her location
   - Uses `EventCardMoved` to hook when Sorcerers move to/from her location
   - Recompute via delta-event pattern (like Elena's Finesse bonus)
   - Tracks `$SorcererCountAtLocation` running count

2. **Action class** (`Action_03026.php`):
   - Two-step CharacterAction
   - Step 1: Discard a card from hand (card picker)
   - Step 2: Move to adjacent city location (location picker)
   - Effect: If discarded card was Sorcery, wound an opposing character at new location
   - Uses `EventFactory::createCardMovingEvent` for move (engage=false)
   - Uses `EventFactory::createCharacterBeingWoundedEvent` for wound
   - Stores discarded card id in `Game::CHOSEN_CARD` global for step 2 access

3. **State classes** (two files):
   - `State_highDramaPhase03026`: step 1 card picker
   - `State_highDramaPhase03026_2`: step 2 location picker with `<Back` button

4. **State configuration**:
   - Added constants to `States.php`: `HIGH_DRAMA_PLAYER_TURN_03026` (403026), `HIGH_DRAMA_PLAYER_TURN_03026_2` (4030262)
   - Added mappings to `states.inc.php` in `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`

5. **JavaScript wiring**:
   - `OnEnteringState.faf.js`: Step 1 highlights hand cards, step 2 highlights adjacent locations
   - `OnUpdateActionButtons.faf.js`: Confirm buttons for both steps, Back button for step 2
   - `OnLeavingState.faf.js`: Cleanup highlighting and clientStateArgs

### Design Decisions & WHY

1. **Event for passive**: Used `EventCardMoved` (not "moving" or location-specific events) because:
   - Fires for any card moving to/from Angeline's location (other Sorcerers arriving/leaving)
   - Fires when Angeline moves herself to/from a location
   - Happens *after* the move is committed, so we see accurate state

2. **Recompute timing**: Recompute on every relevant move, using delta pattern (like Elena):
   - WHY delta, not "set to new value": Avoids interference with other character cards' Influence changes
   - Idempotent: calling `applySorcererBonus(0, ...)` multiple times is safe

3. **Wound targeting**: Wound the first opposing character at new location (deterministic):
   - Card text doesn't say "choose", so no UI prompt
   - Card text doesn't say "all", so wound just one
   - No error if no opposers exist (valid edge case — then no wound)

4. **Step 2 logic**: Location picker uses `actFromCardWithLocations`, not `actFromCardWithId`:
   - State classes must expose `actFromCardWithLocations(string $locations)` as the `#[PossibleAction]`
   - Framework JSON-decodes the payload and passes it through to the action's `actFromCardWithIds(array $ids)` handler
   - This is the pattern for any action that picks city locations

5. **Card discard timing**: Queued BEFORE the move event:
   - Effect reads: "Discard a card • Move Angeline"
   - Cost-before-effect ordering: discard happens, then move
   - Both are queued at once (state 2's act handler), but discard event is queued first

6. **Sorcerer count scope**: Counts OTHER Sorcerers at Angeline's location (excludes self):
   - `character->Id != $this->Id` check ensures we don't count Angeline as a Sorcerer herself
   - Excludes destroyed characters (`characterIsInDiscardOrLocker`) so destroyed Sorcerers don't suppress her bonus

7. **Action availability**: Checks both:
   - Cards in hand to discard (if none, action unavailable)
   - Adjacent city locations to move to (if none, action unavailable)
   - This prevents useless prompts

### Files Created/Modified

- Created: `/c/code/7s5s/modules/php/cards/faf/actions/Action_03026.php`
- Created: `/c/code/7s5s/modules/php/States/faf/State_highDramaPhase03026.php`
- Created: `/c/code/7s5s/modules/php/States/faf/State_highDramaPhase03026_2.php`
- Modified: `/c/code/7s5s/modules/php/cards/faf/_03026.php` (added passive + action wiring)
- Modified: `/c/code/7s5s/modules/php/States.php` (added constants)
- Modified: `/c/code/7s5s/states.inc.php` (added transition mappings)
- Modified: `/c/code/7s5s/modules/js/OnEnteringState.faf.js`
- Modified: `/c/code/7s5s/modules/js/OnUpdateActionButtons.faf.js`
- Modified: `/c/code/7s5s/modules/js/OnLeavingState.faf.js`

### Testing Notes

All PHP files pass syntax check. JavaScript files follow the established codebase patterns for two-step actions.

**Future QA should verify**:
1. Sorcerer count updates correctly when Sorcerers move in/out (inc/dec)
2. Count resets to 0 if Angeline moves to Home (no adjacent city locations available from Home anyway)
3. Discard + move completes in one action turn (two states, one action)
4. Wound targets first opposing character at destination; no error if none exist
5. Back button works in step 2 to re-select discard card
6. Pre-commit hook passes (no missing `createActionResolvedEvent`, no forbidden calls)

