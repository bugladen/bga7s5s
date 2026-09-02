# Technique_03025b — unavailable if adversary in locker

## Change

In `isAvailableToPlayer`, after actor-identity check, reject when adversary is null or `characterIsInDiscardOrLocker`.

## WHY

Gambling Technique moves both duel participants to a city location. If the adversary is already in the Locker (or discard), there is no valid "both participants" move — offering the technique would queue a move from a non-city location.

Used the shared helper (same as Technique_01039 / Technique_01063) rather than a locker-only string check. Discard is equally invalid for this move; keeping one helper avoids a one-off Location string compare.

Related: `2026-09-02-01-duel-adversary-not-present.md` — duel ends when adversary not co-located / in locker at next-player; this gate is the complementary "don't offer the relocate technique when they're already gone."
