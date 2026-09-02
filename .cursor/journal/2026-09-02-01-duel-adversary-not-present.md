# Duel ends when adversary not present

## Rule

At end of a duel round (after EventDuelEndOfRound movers), if the round actor's adversary is not present — in locker/discard or at a different location — the duel does not continue.

## WHY stDuelNextPlayer, not stDuelEndOfRound

`stDuelEndOfRound` location/adversary-threat nullify must run **before** EndOfRound moves (Technique_01036) while participants are still co-located. See `2026-07-28-03-technique-01036-threat-remain.md`.

Presence for **continuation** must run **after** those movers, with live `getCharacterById` locations. Re-using nullify logic here would wipe adversary pool threat after Daniella flees — wrong.

## Implementation

In `stDuelNextPlayer`, after empty-pool check, before switching active player:
- `buildCity()` then live adversary via `getDuelOpponentId` + `getCharacterById`
- Not present if null, `characterIsInDiscardOrLocker`, or `$actor->Location != $adversary->Location`
- `endOfDuel` transition; leftover pool threat is discarded with duel cleanup (not applied as wounds)

## Scenarios

| Scenario | Result |
|---|---|
| Daniella (01036) flees EOR, threat in adversary pool | Duel ends; threat not nullified retroactively, not applied |
| Adversary destroyed mid-round | Duel ends |
| Maneuver moves only one participant apart | Duel ends at EOR |
| Maneuver_01133 moves both together | Co-located → duel continues if threat remains |
| Empty pools | Existing end (checked first) |

## Feel

Clean split: stDuelEndOfRound = threat accounting while co-located; stDuelNextPlayer = should we keep fighting?
