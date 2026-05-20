# Indomitable Will + Reaction_01184 → Game Stuck

## Symptom
A character has `INDOMITABLE_WILL_CONDITION` (set by `Action_01130`, Yevgeni). Another player initiates a basic Claim Action against that location. `Reaction_01184` (Claude de la Roche) triggers on the `EventPressureOccuring`. No matter which option the reaction-player picks (`specialCount` or `pass`), the game gets stuck with:

> Indomitable Will: Yevgeni is still at the location. Location cannot be claimed.

## Root Cause
`Action_01130::eventCheck()` only checked `EventLocationClaimed`. In the basic-claim flow:

1. `actHighDramaClaimActionPerformerChosen` (FrameworkActionsTrait.php:725) creates `EventPressureOccuring` and calls `eventCheck` on it (line 753). Action_01130 didn't listen for this event, so nothing threw.
2. State transitions into `stHighDramaPressureLocation`. Pressure resolves. `Reaction_01184` is queued and the player picks an option.
3. After the reaction's `nextState("done")`, event processing resumes. The `EventLocationPressureResult` handler in `EventHub.php:1043` creates the `EventLocationClaimed` and calls `$theah->eventCheck($claimEvent)` directly (line 1055) — NOT via `queueEvent` which has a try/catch.
4. Action_01130's eventCheck for `EventLocationClaimed` throws `BgaUserException` — but this is during *server-side event processing*, not a player action. The exception escapes up through the state machine. Client never gets a clean error; game state is half-transitioned.

The user's reaction choice had already mutated state (`setUsed(true)`, globals set for `CLAUDE_PRESSURE_TYPE`), so retrying is not safe either.

## Fix
Added an `EventPressureOccuring` arm to `Action_01130::eventCheck()`. It throws the same `BgaUserException` *only when* `IS_BASIC_CLAIM_ACTION` is true (i.e. this is the basic claim) AND location/player match the indomitable-will conditions.

**Why this works:** The first `eventCheck` for the basic claim happens at line 753 of FrameworkActionsTrait.php — inside the player input handler `actHighDramaClaimActionPerformerChosen`. BGA catches `BgaUserException` from player input handlers and surfaces it as a clean error, leaving the game state untouched. No reactions get queued because we throw before `queueEvent`.

**Why not just remove the EventLocationClaimed check / EventHub line 1055 eventCheck:**
- The existing `EventLocationClaimed` check is also a safety net for non-basic claim paths (Action_01206, Maneuver_01107, Reaction_01080, Action_01143, Action_01141, Action_01030, Action_01103a, Action_01095a, Action_02029, Action_01028, Action_01075 all queue claim events directly via `queueEvent`, which has its own try/catch). So that path silently aborts the claim and emits a notification — acceptable behavior.
- Line 1055 of EventHub is the only "leaky" path. Rather than restructure event-processing exception handling, the targeted fix is to catch the basic-claim case earlier where `BgaUserException` *is* appropriate.

## Files Touched
- `modules/php/cards/_7s5s/actions/Action_01130.php` — added EventPressureOccuring use + arm in eventCheck

## Possible Future Cleanup (NOT done here)
- The direct `eventCheck` on EventHub.php:1055 is risky in general — any eventCheck that throws during server-side event processing will stuck the game. Could be wrapped in try/catch (like `queueEvent` does) so claims blocked by future cards "just don't happen" rather than aborting the whole turn. Leaving alone for now because it might be intentional in some other path I haven't audited.
- A symmetric concern: many other cards' `eventCheck` methods throw `BgaUserException`. Audit which can be triggered during event processing vs. player input only.
