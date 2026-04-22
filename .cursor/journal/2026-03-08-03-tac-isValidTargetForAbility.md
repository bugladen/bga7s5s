# TAC Cards: isValidTargetForAbility Refactor

## Context
Continuing the `isValidTargetForAbility` pattern from the previous session (2026-03-07-01) into the new TAC card branch. Four new files in `modules/php/cards/tac/` implement `IAbilityThatTargetsCharacters` but were missing the `isValidTargetForAbility` method.

## What Was Done

### Files Modified
1. **Action_02001** (Andriana - Move Target Character): Extracted 3 conditionals from state 02001_2 — same controller, same location, not in city. State 02001 handles card selection (not character), so left that untouched.

2. **Action_02008** (Strega - Place Risk under Opponent's Character): Extracted 1 conditional from state 02008_2 — same controller. State 02008 handles risk card selection, left untouched.

3. **Action_02010** (Strega - Move Wounds Between Characters): Extracted 3 conditionals from state 02010 — different controller, no wounds, different location. State 02010_2 and 02010_3 have their own character/wound validation that stays inline because isValidTargetForAbility is a single method and state 02010_2 has different semantics (target vs source, different error messages).

4. **Reaction_02001** (Andriana - Wound Non-Sorcerer): Simple `return [true, ""]` — uses `performReaction` not `actFromActionWithId`, no character validation to extract.

### Also Applied
- Removed `IAbilityThatTargetsCards` from all 4 files (implements clause + use import), consistent with the previous session's rule that a class shouldn't implement both interfaces.
- Added `UserException` and `Character` imports.
- Changed all `\BgaUserException` to `UserException`.

## WHY Decisions

**WHY only extract state 02010 validation (not 02010_2):** The interface defines a single `isValidTargetForAbility` method. State 02010 selects the "from" character (wounded source), state 02010_2 selects the "to" character (destination). These have different semantics and error messages ("from a character that is not yours" vs "to a character that is not yours"). The method can only represent one set of validation. Chose state 02010 since it's the first/primary character selection step.

**WHY Reaction_02001 gets simple implementation:** The reaction's targeting logic is handled internally via `handleEvent` (stores CharacterId when conditions match) and `performReaction` (uses stored CharacterId directly). There's no `actFromActionWithId` character selection flow to gate. The method is only added to satisfy the interface contract.

## Status
Complete. All 4 files verified with isValidTargetForAbility. No linter errors.
