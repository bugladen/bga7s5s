# HIGH_DRAMA_IN_PLAY_ACTION_CONFIRM — New Confirmation State

User requested inserting a confirmation step between `HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION` and `HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER`. Confirm fires `EventActionActivated`, runs the standard events/reactions cycle, then proceeds to performer selection.

## Flow before

```
CHOOSE_ACTION
  ├─ inPlayActionChosen (no performer needed)
  │    → fires EventActionTriggered → PLAYER_TURN_EVENTS
  └─ requiresPerformerSelected
       → CHOOSE_PERFORMER → fires EventActionTriggered → PLAYER_TURN_EVENTS
```

## Flow after

```
CHOOSE_ACTION
  └─ actionChosen (uniform; no event fired here)
       → CONFIRM
            ├─ confirmed (fires EventActionActivated)
            │    → CONFIRM_EVENTS → CONFIRM_REACTIONS / CONFIRM_PAY_FOR_REACTION
            │    → endOfEvents → DISPATCH (game-state, stHighDramaInPlayActionDispatch)
            │         ├─ requiresPerformerSelected → CHOOSE_PERFORMER → fires EventActionTriggered → PLAYER_TURN_EVENTS
            │         └─ inPlayActionChosen → fires EventActionTriggered, → PLAYER_TURN_EVENTS
            └─ back → CHOOSE_ACTION
```

## Why this shape

Two reasons matter for future readers:

1. **Two distinct events.** `EventActionActivated` and `EventActionTriggered` are different — Activated is "the player committed to performing this action," Triggered is "the action is now executing with a chosen performer." The CONFIRM state is the natural moment to fire Activated; CHOOSE_PERFORMER (already firing Triggered) stays untouched. Cards like `Reaction_02030a` already key on Activated/Triggered as separate hooks.
2. **Standard EVENTS/REACTIONS/PAY trio.** I copied the IN_HAND_ACTION pattern verbatim — same names ("playerReaction", "playerPayForReaction"), same `argsForStatePrivate`, same `actReactionForState` / `actPayForReaction` action set. This is intentional: those names are reused across many state groups in the codebase and the framework dispatch knows how to handle them.

## Decisions

- **`requiresPerformerSelected` branching preserved, moved to after CONFIRM_EVENTS.** First pass collapsed both forward transitions out of CHOOSE_ACTION into a single uniform path through CHOOSE_PERFORMER, which would have forced an extra click for actions that didn't require performer selection. User pushed back on that. Fix: added `HIGH_DRAMA_IN_PLAY_ACTION_DISPATCH` (game-type, action `stHighDramaInPlayActionDispatch`) between CONFIRM_EVENTS' `endOfEvents` and the next active state. The dispatch reads `CHOSEN_ACTION` and branches: `requiresPerformerSelected` → CHOOSE_PERFORMER, else fires `EventActionTriggered` and goes straight to PLAYER_TURN_EVENTS — exactly the old non-performer code path, just relocated. CHOOSE_ACTION now has only one forward transition (`actionChosen` → CONFIRM); the branching lives at DISPATCH instead.
- **`actHighDramaInPlayActionChosen` no longer fires `EventActionTriggered` directly.** It used to in the non-performer branch. Now that work is done either at DISPATCH (no-performer case) or at CHOOSE_PERFORMER's `actHighDramaInPlayActionPerformerChosen` (performer-required case). Both code paths queue an `EventActionTriggered` exactly once, with the same `(performerId, sourceId)` semantics as before — for `CardAction` the source is `OwnerId`, otherwise `THEAH_ID`, and for the no-performer case the performer is the same as the source (matches the original `$id, $id` argument pattern).
- **CharacterAction default performer still set in `actHighDramaInPlayActionChosen`.** Keeps the existing convention so CHOOSE_PERFORMER has `CHOSEN_PERFORMER` populated when entered for the perf-required path.
- **CONFIRM description shows the chosen action's name in brackets.** `descriptionmyturn` reads `${you} must confirm the chosen In-Play Action [${actionName}]:` (and the spectator description likewise). Args returns `actionName => $action->Name` plus `i18n => ["actionName"]` so the framework translates the action's `clienttranslate`'d name client-side. Standard BGA state-description substitution — no JS handling needed.

## State numbering

Used 462–466 for CONFIRM / CONFIRM_EVENTS / CONFIRM_REACTIONS / CONFIRM_PAY_FOR_REACTION / DISPATCH, matching the flat-increment style of the IN_HAND_ACTION group (47, 471–475). Verified unused via grep.

## Files touched

- `modules/php/States.php` — 5 new constants (CONFIRM, CONFIRM_EVENTS, CONFIRM_REACTIONS, CONFIRM_PAY_FOR_REACTION, DISPATCH)
- `states.inc.php` — registered 5 new states; collapsed CHOOSE_ACTION's two forward transitions into one (`actionChosen` → CONFIRM); CONFIRM_EVENTS' `endOfEvents` lands at DISPATCH
- `modules/php/FrameworkActionsTrait.php` — simplified `actHighDramaInPlayActionChosen`; added `actHighDramaInPlayActionConfirm`
- `modules/php/StatesTrait.php` — added `stHighDramaInPlayActionDispatch` (the requires/no-requires branch)
- `modules/php/ArgumentsTrait.php` — added `argsHighDramaInPlayActionConfirm`
- `modules/js/OnUpdateActionButtons.js` — confirm/cancel buttons (cancel reuses generic `actBack`)
- `modules/js/OnEnteringState.js` / `OnLeavingState.js` — highlight/unhighlight chosen action card (matching CHOOSE_PERFORMER's pattern)

`states.7s5s.php` was not touched — its references to CHOOSE_ACTION/CHOOSE_PERFORMER are all "back" transitions from card-specific sub-states, which still resolve correctly.

## Untested

- Did not run a full game session. Should smoke-test:
  - In-play action with `RequiresPerformerSelected = true` (e.g., Move/Challenge actions). Confirm → events → DISPATCH → performer pick.
  - Non-required action (basic CharacterAction). Confirm → events → DISPATCH fires EventActionTriggered → PLAYER_TURN_EVENTS — should match pre-refactor behavior exactly except for the new CONFIRM step and the new `EventActionActivated` firing point.
  - Cancel button from CONFIRM lands back at CHOOSE_ACTION cleanly (state-private highlights cleared via OnLeavingState).
  - Reaction_02030a still triggers on the Activated event from CONFIRM (the card listens for the event regardless of state).
