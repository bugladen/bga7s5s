# Cross of the Martyrs (_02016) Audit

## Context
Eddie asked me to audit Cross of the Martyrs against its card text. This is a Reaction attachment card from the TAC expansion (Eisen faction).

## What I found

The main card class (`_02016.php`) was fine — equip restriction on Sorcerers and Zealot trait grant both correct.

The reaction class (`Reaction_02016.php`) had four significant bugs, all related to the reaction not matching the card text:

1. **No opponent check** — The reaction fired on anyone's abilities, including the owning player's. Card says "when an opponent targets."
2. **No ownership check on target** — Didn't verify the target was "your character." Would react to opponents targeting each other.
3. **No wound** — Card says "wound your performer" but no wound was applied. Added via `EventFactory::createCharacterBeingWoundedEvent`.

## Design decisions

**WHY "your performer" means the chosen character, not the equipped character:**
I initially misread "your performer" as meaning the character equipped with Cross of the Martyrs. Eddie corrected me: the "performer" is the character the player CHOOSES to perform the reaction. The player picks from their characters at the location (excluding the original target). That chosen character gets wounded AND becomes the new target. So the character selection buttons were correct all along — the only missing piece was the wound.

**WHY the wound is queued before the redirect check:**
The wound is part of the card effect. I queue it before the validity check because the card text presents it as "wound your performer • That opponent targets your performer instead" — the wound happens, then the redirect. If the performer turns out to be an invalid target (edge case), the wound still happened but the ability is canceled.

**WHY I didn't touch the decline path:**
When player declines, the original event stays canceled and is never re-released. This seems like it would cause the original ability to fizzle, which feels wrong. But other reactions in the codebase use the same pattern, so either the framework handles it elsewhere or it's by design. Didn't want to change framework-level behavior for a card audit.

## Observations
This reaction is one of the most complex in the codebase — it intercepts 6 different event types (Engaged, Engarded, CardMoving, CharacterBeingWounded, CharacterBeingHealed, ChallengeIssued) and handles each one. The pattern of cloning events, canceling originals, and re-queueing with modified targets is intricate. The `skipNextEvent` flag to prevent infinite recursion when the re-queued event triggers the same handler is clever but fragile.
