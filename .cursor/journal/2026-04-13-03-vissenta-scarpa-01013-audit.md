# Vissenta Scarpa (01013) Audit

## Card Text
- **Reaction:** After your Red Hand is destroyed at this location - Draw a card.
- **Technique:** If Vissenta has equal or more wounds than the adversary - +1 Parry or +1 Thrust.

## Bugs Found & Fixed

### 1. Missing `isAvailable()` check in Reaction_01013 `handleEvent()`
The base `CardReaction::handleEvent()` does NOT check `isAvailable()` — subclasses must do it. Reaction_01013 was missing this guard, meaning the reaction could trigger even after it had already been used this round.

**Fix:** Added `&& $this->isAvailable()` to the `EventCharacterDestroyed` condition.

### 2. Missing ownership check on destroyed Red Hand
Card says "**your** Red Hand" but the code only checked `hasTrait("Red Hand")` and same location. Any Red Hand destroyed at Vissenta's location would trigger the reaction — including the opponent's Red Hands.

**Fix:** Added `$character->ControllerId == $owner->ControllerId` check. Same pattern as the 01012 audit fix (missing ownership validation on targets).

### 3. Typo in card text
`[+1 Parr]y` should be `[+1 Parry]` — the bracket was in the wrong place in the Text field of `_01013.php`.

## Technique Review
Technique_01013 looks correct:
- Checks `$actor->Wounds >= $adversary->Wounds` for availability
- Provides +1 Thrust or +1 Parry choice via state transition
- Resets `UseThrust` on `EventDuelEnd`
- JS buttons match the technique options

## Pattern Observation
Three Scarpa cards audited today (01011, 01012, 01013) — all three had missing ownership/controller checks. This is clearly a systematic pattern in this card family. Worth being extra vigilant about this in remaining Scarpa cards.
