# Servo Scarpa (01011) Audit

## Card Text
> Servo may issue challenges to characters opposing your Red Hands at locations adjacent to him. When he does, move him there.
> **Technique:** +1 [Thrust] for each of your other Red Hands at this location.

## Bugs Found and Fixed

### 1. Typo: "Deulist" trait (critical)
`_01011.php` line 36 had `"Deulist"` instead of `"Duelist"`. This meant Servo would never match `hasTrait("Duelist")` checks used by Duelist Maneuvers (01077, 01084), Cavalier Hat (01073), Mastercrafted Rapier (01074), etc. Every other Duelist in the codebase spells it correctly.

### 2. `getTargetCharacters()` missing adjacency filter
`isAvailableToPlayer()` correctly filtered Red Hands to those at locations adjacent to Servo, but `getTargetCharacters()` (used for the actual target selection UI and validation) did not apply this filter. This meant the player could see and select targets opposing ANY Red Hand, not just those at adjacent locations.

WHY the fix works: Added the same adjacency logic from `isAvailableToPlayer()` — get Servo via `getOwningCharacter()`, compute adjacent locations, filter thugs by both Red Hand trait AND adjacency. Now `getTargetCharacters` and `isAvailableToPlayer` are consistent.

### 3. Technique counted ALL Red Hands, not just "your" Red Hands
Card says "your other Red Hands" but the technique filter was `$character->Id != $actor->Id && $character->hasTrait("Red Hand")` — missing the controller check. An opponent's Red Hand at the same location would incorrectly boost Servo's thrust. Added `$character->ControllerId == $actor->ControllerId` filter, matching the pattern used in Technique_01039.

## What Looked Correct
- Action moves Servo to target's location before the challenge transition
- Challenge type set to `SERVO_SCARPA_CHALLENGE_TYPE` with Combat stat
- `setUsed()` called correctly
- Technique handles both EventGenerateChallengeThreat and EventDuelCalculateTechniqueValues
- State wiring and frontend handlers exist for both 01011 and 01011_2 transitions
