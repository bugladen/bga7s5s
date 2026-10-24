# Gold or Steel: destroyed adversary cannot pay the wound

User: their character was already destroyed and still suffered a wound to stop the opponent's Gold or Steel draw. The character has to be alive for that "unless".

## Why live getCharacterById

`getDuelRoundOpponent()` puts back the last in-play snapshot when the opponent is in Discard/Locker (city location, old wounds). Maneuver_02038 used that snapshot for the choice, so the Locker copy still got EventCharacterBeingWounded and the draw never happened.

Stat gate in `isAvailableToPlayer` still uses `getDuelRoundOpponent()`. "More Combat/Finesse than the adversary" should keep using last-known stats so the maneuver can be played against someone who already died. Only the wound payment checks the live row.

Do not "simplify" the wound check back onto `getDuelRoundOpponent()`.

## What happens now

- Resolve: adversary in discard/locker → queue the draw, no 02038 choice.
- Choice state: `canTakeWound` false hides Suffer a Wound. A stale id=1 also draws instead of wounding.
- Voluntary "let them draw" is unchanged.

IsDying but not yet moved to the Locker can still take the wound. User's case was already destroyed (location). Blocking IsDying would force the draw if a heal saved them before the destroy event landed.
