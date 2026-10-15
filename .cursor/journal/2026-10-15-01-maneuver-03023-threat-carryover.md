# Maneuver_03023 Second Wind — leftover threat still wounded

## Symptom
Eddie: used Maneuver_03023 in a duel. Log showed "Threat is not converted to wounds; 2 threat carries over to the next round." Then after End Round: "has received 2 wound(s) due to: 2 Threat was left over in their Pool."

## Root cause
Same trap as Breastplate `_01153` (journal 2026-08-21-01).

`Maneuver_03023` suppressed leftover-threat conversion in **handleEvent** by zeroing `EventCharacterBeingWounded.wounds` and stashing into `PENDING_*_THREAT`.

`EventCharacterBeingWounded` has `runEventHubAfterCards=true`, so a pure handleEvent zero *can* beat the hub's `wounds > 0` gate — but reactions that clone+cancel+requeue (Reaction_01181 and kin) run in the same card loop. If they clone before 03023:

1. Clone captures `wounds = 2`
2. 03023 zeros the (canceled) original, logs carry-over, sets PENDING
3. Hub skips canceled / zeroed original
4. Reaction releases the clone with `wounds = 2` → CharacterWounded applies

Matches the log order exactly.

## Fix
Move suppression into `eventCheck` (queue-time, before serialize). Keep Resolve / Cancel / EndOfRound IsActive bookkeeping in `handleEvent`. handleEvent must NOT also suppress.

WHY eventCheck: `queueEvent` → eventCheck → serialize. Any later clone sees `wounds=0`. Same pattern as Breastplate and Kaspar `_03014`.

## Tests
`tests/regression/Maneuver_03023_Test.php` — eventCheck suppresses + PENDING; handleEvent does not; absent adversary / inactive / wrong source leave wounds alone.

Added `DUEL_GAMBLED` + `PENDING_*_THREAT` to FakeGame (must match Game.php).

## Not changed
RH-capped carry-over amount (PENDING gets `event->wounds`, not pre-RH threat). User's case was 2=2. Full-threat rollover would need stDuelEndOfRound to expose original threat before the SQL wipe — separate if reported.
