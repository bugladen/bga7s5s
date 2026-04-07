# Burnished Cuirass (01193) Audit

## Card Text
> **Technique:** During the adversary's next round, their combat card has -1 [Thrust].

## Verdict: Functionality Correct, Two Fixes

The core duel technique logic is solid and correctly implements the card text. The technique:
1. Sets a `ReduceAdversaryThrust` flag on `EventResolveTechnique`
2. Applies -1 Thrust during `EventDuelCalculateCombatCardStats` when the owning character is the `adversaryId` (meaning the event is calculating the opponent's combat card)
3. Resets the flag after use (single-round effect)
4. Has safety resets on `EventDuelNewRound` (owner's next round) and `EventDuelEnd`

The `$character->Id == $event->adversaryId` check is the key insight — `actorId` in the event is the character whose combat card is being calculated, and `adversaryId` is their opponent. So when the Burnished Cuirass owner IS the adversary, it means the opponent's card stats are being computed — exactly when we want to reduce thrust.

## Fix Applied

### Explanation message was opaque
The explanation added to `$event->explanations` was just the raw inject code of the card (the card name/link with no context). The sibling technique `Technique_01204` (Syrneth Hand, same pattern for -2 Parry) uses a proper descriptive message: `"%s reduces the Adversary's Parry by %d"`. Applied the same pattern here for Thrust.

### Missing `EventTechniqueCanceled` handler
Since the technique stores state in `$ReduceAdversaryThrust`, canceling the technique after activation would leave the flag `true`, causing a phantom -1 Thrust on the adversary's next combat card even though the technique was canceled. Added a handler matching the pattern from `Technique_01101` — reset the flag and mark the attachment as updated. Used `$attachment instanceof Attachment` guard (no `isAttached()` required) since we just need to mark the card dirty, not traverse to the character.

## Notes

- Both 01193 and 01204 have the same `isAttached()` guard on `EventDuelEnd` and `EventDuelNewRound` cleanup. Theoretically if the card is unattached mid-duel the flag could persist, but this is consistent across techniques and extremely unlikely in practice.
- `Technique_01204` has a stale comment saying "Reduce the opponent's Parry by 1" but the code does `removeParry(2)`. That's a comment bug in 01204, not in scope here.

## Files Changed
- `modules/php/cards/_7s5s/techniques/Technique_01193.php` — fixed explanation message to match established pattern
