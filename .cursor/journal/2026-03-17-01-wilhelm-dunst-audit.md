# Wilhelm Dünst (_02013) Audit

## Context

Continuing the card audit series. Wilhelm is an Eisen Hero with a static restriction (combat-only challenges against Villains/Sorcerers/Monsters) and a City Action that discards a Relic/Faith card to issue a combat challenge treating the target as a Sorcerer.

## Bugs Found

### 1. JS filter included 'Sorcery' — wrong trait

The `OnEnteringState.tac.js` handler for `highDramaPhase02013` was filtering hand cards by `'Sorcery' || 'Relic' || 'Faith'`. The card text only says Relic or Faith. The Sorcery inclusion was a copy/paste error or confusion. Server validation was correct, so this was a UX-only bug (player selects Sorcery card, server rejects it).

WHY I'm confident the fix is right: The server-side `isAvailableToPlayer` and `actFromActionWithId` both only check Relic/Faith. The JS was the outlier.

### 2. eventCheck missing combat stat check

The card says "may only issue [com] challenges" but eventCheck only validated target traits. Added a `CHALLENGE_STAT != STAT_COMBAT` check. Normal challenges default to combat so this is primarily a defensive guard against edge cases where another card effect (like an attachment action) sets a non-combat stat and uses Wilhelm as the challenger.

WHY this matters: Without this check, if someone attached a Cavalier Hat to Wilhelm and used its Finesse challenge action, the EventChallengeIssued would fire with Wilhelm as challenger, the eventCheck would pass (if target had Villain/Sorcerer/Monster), and a Finesse challenge would go through despite the card text restriction.

## "As if Sorcerer" mechanic — clever and correct

The doEffect adds "Sorcerer" trait to the target, then the handleEvent removes it on EventChallengeIssued. I verified this is safe because:
- `addTrait` appends to the array (can create duplicates)
- `removeTrait` uses `array_search` which only removes the FIRST occurrence
- So if a character natively has Sorcerer, they end up with two entries, one gets removed, they keep their original

The timing is also correct: eventCheck runs before event handling, so the trait is present during validation, then cleaned up during handling.

## Everything else looked clean

Action states, validation, cost/effect ordering, JS leaving/entering state, and action buttons all checked out.
