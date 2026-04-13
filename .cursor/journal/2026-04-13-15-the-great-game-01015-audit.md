# The Great Game (01015) Audit

## Card Text
- **Scheme resolve**: Add a Renown to The Docks and The Grand Bazaar.
- **Reaction**: After a character is destroyed • Draw a card.
- **City Action**: Destroy your performing character • Wound target character at that location.

## Findings

### Bug 1: Reaction trigger too restrictive (Reaction_01015.php)
The reaction's `handleEvent` had a condition checking `$character->ControllerId == $scheme->ControllerId`, meaning it only triggered when the scheme controller's OWN character was destroyed. Card text says "a character" with no ownership qualifier — should trigger on ANY character destruction.

**Fix**: Removed the controller ownership check. Now any `EventCharacterDestroyed` event triggers the reaction (as long as `isAvailable()` passes).

### Bug 2: Action targeting restricted to opponents only (Action_01015.php)
Card text says "Wound target character at that location" — no ownership restriction. The action was incorrectly limiting targets to opponent characters only in three places:
- `getPerformers`: filtered to locations with opponent characters only
- `getArgsFromAction`: same issue (initially missed, then corrected per user feedback)
- `isValidTargetForAbility`: rejected same-controller characters

**Fix**: Changed all three to only exclude the performer itself (who is being destroyed as part of the cost). Any other character at the location is a valid target, including the player's own.

### Verified correct
- Scheme resolve: Adds renown to Docks and Grand Bazaar — matches card text.
- City Action: Destroys performer, wounds any target at same location — matches card text.
- Pre-commit hook rules all satisfied (setUsed, isAvailable, createActionResolvedEvent).

## WHY
- Bug 1 was likely a copy-paste from a reaction that only triggers on your own characters. The card text is clear: "a character" = any character.
- Bug 2: I initially assumed "target character" meant opponent only (common pattern), but user confirmed the card text has no ownership qualifier. This is an important distinction — the ability to wound your own character could be strategically relevant (e.g., triggering the card's own reaction for a draw).
