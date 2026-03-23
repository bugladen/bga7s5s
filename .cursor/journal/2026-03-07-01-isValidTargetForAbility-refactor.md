# isValidTargetForAbility Refactor

## Context
Continuing a multi-session refactoring effort to extract character validation logic from `act*()` methods into dedicated `isValidTargetForAbility()` methods on all classes implementing `IAbilityThatTargetsCharacters`.

The interface (`IAbilityThatTargetsCharacters`) defines:
```php
function isValidTargetForAbility(Game $game, Character $character): array;
```
Returns `[bool $isValid, string $errorMessage]`.

## What Was Done
Updated 19 remaining files that implement `IAbilityThatTargetsCharacters` but hadn't yet gotten the `isValidTargetForAbility` method.

### Group A - 12 files with character validation conditionals extracted:
- Action_01138, 01148, 01152a, 01152b, 01156, 01160, 01161, 01171, 01172, 01194, 01205
- Maneuver_01051

Pattern for each:
1. Added `use Bga\GameFramework\UserException;` and `use ...Character;` imports
2. Created `isValidTargetForAbility()` containing the validation conditionals
3. Replaced conditionals in act method with `[$isValid, $errorMessage] = $this->isValidTargetForAbility(...)` call
4. Kept the null check (`$character == null`) in the original act method
5. Changed `\BgaUserException` to `UserException` in the affected state blocks

### Group B - 7 files with no character validation conditionals:
- Action_01162, 01175, 01176, 01198
- Maneuver_01079
- Reaction_01016, 01037

These implement the interface but don't have character target validation in their act/perform methods. Added simple `return [true, ""];` implementations to satisfy the interface contract.

## WHY Decisions

**WHY keep performer lookup in act method even though isValidTargetForAbility does its own lookup:** The performer variable is often used AFTER validation for subsequent game logic (creating events, notifications, etc.). Duplicating the lookup is acceptable - the method needs to be self-contained for reuse elsewhere.

**WHY some files use `$this->getOwningCharacter()` instead of `$game->globals->get(Game::CHOSEN_PERFORMER)`:** AttachmentAction and CharacterAction subclasses (01156, 01194, 01205) get the "performer" from the owning character rather than globals. Followed the existing convention for each file.

**WHY Action_01161 moved CHOSEN_TARGET set after validation:** Original code set the global before validation. Moved it after to match the pattern from other committed files (e.g., Action_01133).

**WHY Group B files got `return [true, ""]`:** These files don't target characters through their act methods (some use reactions, some target locations/attachments instead). But they implement the interface, so PHP requires the method. A pass-through implementation is correct - any character is "valid" from their perspective since they don't do character-specific targeting.

## Status
All classes implementing `IAbilityThatTargetsCharacters` now have `isValidTargetForAbility()`. This should be the final batch.
