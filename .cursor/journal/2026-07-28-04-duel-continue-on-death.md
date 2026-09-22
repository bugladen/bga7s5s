# Duel continues after participant death?

Eddie asked how the code decides a duel continues another round when a participant dies.

## Answer (for next agent)

**Death does not end the duel.** Continue vs end is decided in `stDuelNextPlayer()` (`StatesTrait.php`) after end-of-round events.

Flow: `DUEL_END_OF_ROUND` → events/reactions → `DUEL_NEXT_PLAYER` (`stDuelNextPlayer`) → either `endOfDuel` or `newRound`.

Death detection: `Location` contains `Locker-` (or `Discard-` for Brutes).

Death only affects **threat nullification**:
- If both dead, OR actor left the location while still alive → zero the threat the actor was about to leave for the adversary.
- Comment in code: if actor is in the locker, threat **remains**.

Actual gate to continue:
```
if ending_challenger_threat == 0 && ending_defender_threat == 0 && !pending threat globals
  → endOfDuel
else
  → newRound
```

WHY this shape: leftover threat must still apply / be answered even if one side is already destroyed (and swaps can put a new participant in). Pending threat globals also keep the duel alive across the round boundary.

## Context from prior session

Desideria `_04003` just landed; En Garde recovery on thug destroy mid-duel is related (immediate hand return, not DuelEnd defer). Not playtested.
