# Redirect Reactions: Target Validation

## Context
Integrating `isValidTargetForAbility` checks into reactions that redirect an ability's target to a different character. Two cards affected: Reaction_01014 (Vittoria Anselmo — redirects to Thugs) and Reaction_02016 (redirects to any character at location).

## What Was Done

### Added ability ID capture to `shouldReactToEvent()`
Rather than adding `$this->savedAbilityId = ...` in all 6 event handler blocks in `handleEvent()`, saved the IDs inside `shouldReactToEvent()` when it returns true. This is cleaner — `shouldReactToEvent` already has both `$sourceId` and `$abilityId`, and it only returns true when the reaction will actually fire.

### Added `loadAbility()` helper
Mirrors the lookup logic from `shouldReactToEvent` — tries card-based ability first (`getCardById` → `getAbilityById`), falls back to in-play action (`getInPlayActionById`). Returns `?IAbilityThatTargetsCharacters`.

### Gated `releaseEvent` on validity check in `performReaction`
In the `inPlayThug` not-decline branch, before calling `releaseEvent($game, $characterId)`, loads the ability and calls `isValidTargetForAbility($game, $character)`. Only releases if valid.

Only applied to the thug-targeting `releaseEvent` call. The other two `releaseEvent` calls (decline paths) release back to the owner (Vittoria), not to a thug — those are "undo" paths and weren't gated.

### Added `cancelEvents(Game $game)` method (originally `clearEvents`, renamed)
Pattern mirrors `releaseEvent` but just nulls out all stored event pointers without re-queuing them. Nulls: `engagedEvent`, `engardedEvent`, `cardMovingEvent`, `characterWoundedEvent`, `characterHealedEvent`, `challengeIssuedEvent`, and resets `isChallenger`. Also sets the `Game::CHALLENGE_CANCELLED` global to `true` so downstream state logic knows the challenge was canceled by a reaction's target validation failure.

Called in the `else` branch when `isValidTargetForAbility` returns false — the stored event is discarded (not re-queued), effectively canceling the ability entirely.

### Notification ordering and invalid-target messaging
- Moved the "used Reaction to use [thug] as the new target" notify ABOVE the validity gate so players always see which thug was chosen.
- When the thug is NOT a valid target, a second notify fires: "[thug] is not a valid target for the ability. The ability has been canceled."

## The Pattern for Other Cards

This pattern should be reused for any reaction that redirects an ability's target to a different character:

1. **Capture IDs:** Add `$savedAbilityId` and `$savedSourceId` properties. Save them in the method that decides whether to react (e.g., `shouldReactToEvent`), at the point where the reaction is confirmed.

2. **`loadAbility()` helper:** Look up the ability from saved IDs. Try card-based first (`getCardById($savedSourceId)` → `getAbilityById($savedAbilityId)`), fall back to in-play action (`getInPlayActionById($savedAbilityId)`). Return `?IAbilityThatTargetsCharacters`.

3. **`cancelEvents(Game $game)` method:** Null out all stored event pointers without re-queuing. Mirror the structure of `releaseEvent` but skip the `queueEvent` calls and target-reassignment logic. Set `$game->globals->set(Game::CHALLENGE_CANCELLED, true)` so downstream state logic can detect the cancellation.

4. **Gate in `performReaction`:** Before `releaseEvent`, call `loadAbility` → `isValidTargetForAbility($game, $newTarget)`. If valid, `releaseEvent`. If not, notify that the ability is canceled and call `cancelEvents($game)`.

5. **Notification order:** Announce the chosen new target BEFORE the validity check, so players see the choice. Then either release (valid) or notify cancellation + clear (invalid).

## WHY Decisions

**WHY save IDs in `shouldReactToEvent` instead of in each `handleEvent` block:** All 6 event type handlers call `shouldReactToEvent` with the same sourceId/abilityId pattern. Saving inside the method avoids 12 insertion points (6 blocks × 2 branches each) and keeps the capture co-located with the validation logic.

**WHY only gate the thug-targeting `releaseEvent`:** The decline paths release the event back to Vittoria (the original target). The event was already targeting Vittoria before the reaction fired, so validity was already established. The thug-targeting path is the only one that changes the target to a new character.

**WHY `cancelEvents` instead of just not calling `releaseEvent`:** Without clearing, the stored event pointers would linger as stale state. If the reaction object persists (serialized between states), those orphaned events could cause issues on subsequent interactions. Explicit cleanup is defensive. Setting `CHALLENGE_CANCELLED` signals the state machine that the challenge flow should not continue — without this, downstream states might still expect a challenge to resolve.

**WHY notify before the validity gate:** Players need to see which thug was chosen regardless of whether it's valid. If the thug is invalid, the second message explains why the ability was canceled. Without the first message, the cancellation notification would lack context about what was attempted.

## Reaction_02016 — Same Pattern Applied

Applied the identical pattern to Reaction_02016 (`cards/tac/reactions/Reaction_02016.php`). This reaction redirects any targeted ability to a different character at the same location (not just Thugs like Vittoria).

Differences from Reaction_01014:
- **Simpler `performReaction`:** No hand-thug/in-play-thug two-phase flow. Single `if ($reactionId != 'decline')` block with one `releaseEvent` call.
- **`shouldReactToEvent` has extra checks:** Validates owning attachment is attached, target character is at same location, and there's at least one other character to redirect to. Only one `return true` point, so only one place to save IDs.
- **No decline-path `releaseEvent` calls:** The decline case just falls through to `nextState('done')` — no event release needed (but also means no cleanup of stored events on decline... worth watching).

All the same methods added: `$savedAbilityId`/`$savedSourceId` properties, ID capture in `shouldReactToEvent`, `loadAbility()`, `cancelEvents(Game $game)`. Validity gate in `performReaction` with notify-before-gate and cancel-on-invalid patterns.

## Status
Both Reaction_01014 and Reaction_02016 complete. No linter errors.
