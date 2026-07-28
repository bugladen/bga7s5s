# Technique_01036 + end-of-round threat order

## Context

Earlier today Eddie described EOR order as: EndOfRound moves → location check → actor wounds. That would make Daniella's flee nullify adversary threat. A reorder implementing that was started then discarded (working tree was clean again).

Eddie then clarified: **Technique_01036 should move Daniella at end of round; adversary pool threat should remain.**

## Correction / WHY

Daniella's move is still an EndOfRound effect, but location/adversary-threat commit must happen **while she is still co-located**. Flee after that commit.

Final order in `stDuelEndOfRound`:
1. Location check (mid-round leaves like Maneuver_01033 already applied; 01036 not yet)
2. Nullify adversary threat if different location (alive leave) / both dead; else keep
3. Apply actor leftover threat as wounds
4. Queue `EventDuelEndOfRound` (Daniella moves here)

`stDuelNextPlayer` only continues/ends — no second location nullify (would wipe threat after she flees).

## Also satisfied

Same-location actor death from leftover threat: adversary threat already committed in step 2 before wounds in step 3 → duel can continue. Locker exception still needed for mid-round deaths sitting in Locker at EOR.

## Do not regress

Do NOT move location nullify back after EventDuelEndOfRound / into stDuelNextPlayer — that is exactly what cleared adversary threat when Daniella fled.

Do NOT put EventDuelEndOfRound before the location check if the requirement is "adversary threat remains" for 01036.
