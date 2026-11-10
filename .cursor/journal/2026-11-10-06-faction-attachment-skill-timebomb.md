# create-faction-attachment skill — Porcelain Heart learnings

Updated skill after finishing `_05TimebombFlipped`. Captured WHYs that would otherwise regress:

1. **Permanence** ≠ B'' condition — `canBeMoved` + unequip dying-host + optional locker veto. Softlock if dying exception omitted.
2. **Leader force-equip** ≠ Pattern A trait dual-gate — `getRequiredAttachTargetId` redirect; Object of Wonder skips `canAttachTo`.
3. **Forced adversary −Parry** = Calculate + `adversaryId == AttachedToId` + `removeParry` (negatives OK). Not Announced, not Technique_01204 deferred next-round.

Also cleared stale "still TODO" line in create-character references for Flipped.
