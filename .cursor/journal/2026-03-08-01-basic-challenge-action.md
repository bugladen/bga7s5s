# BasicChallengeAction Implementation

## Context
Continuing the `IAbilityThatTargetsCharacters` refactoring work from 2026-03-07. Eddie wants a new `BasicChallengeAction` that represents the basic challenge game action (not card-attached), which can be used for `isValidTargetForAbility` validation and tracked via globals.

## What Was Done

### Created `BasicChallengeAction` (modules/php/theah/actions/BasicChallengeAction.php)
- Extends `Action` directly (not `LocationAction`) since it has no per-player usage tracking or location ownership mechanics
- Implements `IAbilityThatTargetsCharacters`
- Has `public string $Id = 'BasicChallenge'` -- needed because base `Action` doesn't have `$Id`, but `getInPlayActionById()` accesses `$action->Id` when searching the `$this->Actions` array
- `isValidTargetForAbility()` checks performer and character are at the same location, using `CHOSEN_PERFORMER` from globals (same pattern as Action_01029)

### Wired into Theah::buildCity()
- Added unconditionally (no player-count gate unlike OlesInn/GovernorsGarden) since basic challenge is always available
- Added after the GovernorsGarden block, before Reactions

### Modified FrameworkActionsTrait
- `actHighDramaChallengeActionStart()`: After validation, looks up BasicChallengeAction via `getInPlayActionById('BasicChallenge')` and sets `CHOSEN_ACTION` + `TRANSITION_INTERNAL_ID` globals. This mirrors how `actHighDramaInHandActionChosen()` does it for card actions.
- `actHighDramaChallengeActionTargetChosen()`: Replaced inline `$target->Location != $performer->Location` check with `$action->isValidTargetForAbility()` call. Added `buildCity()` call and null-check for the character. Uses `getCharacterById()` instead of `getCardObjectFromDb()` to get the proper `Character` type for the interface.

### Fixed Reaction_01014 (Vittoria Anselmo) for sourceless actions
Two problems when BasicChallengeAction fires events with `sourceId = 0`:

1. **`shouldReactToEvent()`** -- `getCardById(0)` returns null, so it returned false. Added fallback: when card lookup fails, try `$theah->getInPlayActionById($abilityId)` and check if it's `instanceof IAbilityThatTargetsCharacters`.

2. **`EventChallengeIssued` handler** -- The condition `$source && ...` short-circuited the whole block when source is null. Also `$source->ControllerId` was needed for opponent check. Replaced with `$initiatingControllerId = $source ? $source->ControllerId : $event->playerId`. This works because `$event->playerId` is always the player who initiated the challenge.

## WHY Decisions

**WHY extend `Action` directly, not `LocationAction`:** BasicChallengeAction doesn't need per-player usage tracking (`$playersUsed`, `$globalVariableName`), location controller checks, or end-of-day resets. It's a pure game mechanic, not a location action. LocationAction machinery would be dead code.

**WHY the `$Id` property is on the class directly:** Base `Action` doesn't define `$Id`, but `getInPlayActionById()` accesses it. PHP's dynamic nature allows this to work, but it's a bit loose. The alternative would be adding `$Id` to the base `Action` class, but that's a broader change affecting all actions. Keeping it local to this class is less invasive.

**WHY `$event->playerId` works as fallback for controller check in Reaction_01014:** For card-based actions, `$source->ControllerId` identifies who controls the card triggering the action. For a basic challenge, there IS no source card -- the player themselves initiated it, so `$event->playerId` is the correct equivalent. Both represent "who is doing this to Vittoria."

**WHY `buildCity()` was added to `actHighDramaChallengeActionTargetChosen`:** Other act methods in the challenge flow call `buildCity()` (e.g., `actHighDramaChallengeActionPerformerChosen`), and we now need the city built to look up `CHOSEN_ACTION` via `getInPlayActionById`. The original method didn't need it because it only compared raw location strings from DB objects.

## Status
Complete. All five files created/modified, no linter errors.
