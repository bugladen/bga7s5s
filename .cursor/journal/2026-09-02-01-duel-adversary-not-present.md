# Duel ends when adversary not present

## Rule

At end of a duel round (after EventDuelEndOfRound movers):

- **Adversary gone** (locker/discard/null) → duel ends (even if pool threat remains — leftover discarded with cleanup).
- **Living actor no longer co-located** with adversary (flee / split, e.g. Daniella 01036) → duel ends.
- **Actor dead, adversary still present, threat going to adversary** → duel **continues** (adversary becomes next-round actor and takes the leftover pool as wounds).

## WHY stDuelNextPlayer, not stDuelEndOfRound

`stDuelEndOfRound` location/adversary-threat nullify must run **before** EndOfRound moves (Technique_01036) while participants are still co-located. See `2026-07-28-03-technique-01036-threat-remain.md`.

Presence for **continuation** must run **after** those movers, with live `getCharacterById` locations. Re-using nullify logic here would wipe adversary pool threat after Daniella flees — wrong.

## Regression (2026-09-02 afternoon)

Commit `9a48cb7` ended the duel whenever `$actor->Location != $adversary->Location`. Actor death into locker always differs from the adversary's city location, so leftover adversary-pool threat was discarded and the duel ended incorrectly.

**Fix:** treat location mismatch as ending only when the **actor is still alive**. Actor in discard/locker + adversary still on the board → continue (matches `stDuelEndOfRound` locker exception that keeps adversary threat).

## Implementation

In `stDuelNextPlayer`, after empty-pool check, before switching active player:
- `buildCity()` then live actor + adversary
- End if adversary gone OR (`!actorIsDead` AND locations differ)
- `endOfDuel` transition; leftover pool threat is discarded with duel cleanup (not applied as wounds)

## Scenarios

| Scenario | Result |
|---|---|
| Daniella (01036) flees EOR, threat in adversary pool | Duel ends; threat not nullified retroactively, not applied |
| Adversary destroyed mid-round | Duel ends |
| Actor destroyed by leftover wounds, threat in adversary pool | Duel continues; adversary is next actor |
| Maneuver moves only one (living) participant apart | Duel ends at EOR |
| Maneuver_01133 moves both together | Co-located → duel continues if threat remains |
| Empty pools | Existing end (checked first) |

## Feel

The first cut was too blunt — "not co-located" without asking *why*. Actor death is a location change that must not abort a duel that still has threat for the survivor.
