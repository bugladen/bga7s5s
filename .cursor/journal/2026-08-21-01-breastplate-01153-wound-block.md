# Breastplate (_01153) leftover-threat wound still applied

## Symptom
End of duel round, 1 leftover threat on Breastplate's equipped character. Log showed "Breastplate blocked a wound. Wounds went from 1 to 0", then later the character "has received 1 wound(s) due to: 1 Threat was left over in their Pool."

## Root cause
Commit `dbbf159e` (Jul 1) moved the wound reduction from `eventCheck` into `handleEvent` while removing the actor/adversary duel-participant gate.

`EventCharacterBeingWounded` has `runEventHubAfterCards = true`, so a pure handleEvent reduction *can* beat the hub's `wounds > 0` gate — but reactions that clone+cancel+requeue (Reaction_01181 and kin) run in the same card loop. If they clone before Breastplate:

1. Clone captures `wounds = 1`
2. Original is canceled; Breastplate still reduces the canceled original and logs 1→0 / sets `hasBlockedWound`
3. Hub skips the canceled event
4. Reaction releases the clone with `wounds = 1`; Breastplate is already spent; hub queues `EventCharacterWounded` with 1

That matches the log order exactly.

Commit `91c1f82f` had already moved reduction into `eventCheck` specifically so other cards see the reduced count ("before other cards know about it"). The Jul 1 move undid that.

## Fix
- Put wound reduction back in `eventCheck` (keep the Jul 1 "any duel wound while IN_DUEL" scope — no actor/adversary gate).
- Keep `EventDuelEnd` flag reset + destroy-on-wounded in `handleEvent`.
- Persist via `updateCardObjectInDb` when the flag flips in `eventCheck`. WHY: leftover-threat events are queued from `stDuelEndOfRound` outside `runEvents`, so `IsUpdated` alone is lost across the state transition and Breastplate could block twice in one duel.

## Not changed
Still missing `initializeFaction('Castille')` (noted in 2026-07-01-01) — unrelated.
