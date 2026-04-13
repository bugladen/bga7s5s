# Vladislav Novikoff (01188) Audit

## Card Text
> Traits: Mercenary, Ussura
> Negotiable (You may parley when paying for this card.)
> Vladislav may intervene even while engaged.
> When Vladislav intervenes, do not play a combat card. Take the threat as wounds. The duel ends. (Restricted Hostilities still applies.)

## Bug Found: "Intervene while engaged" not implemented

The `canIntervene()` override in _01188 is a no-op. It calls `parent::canIntervene()` (which only checks `isControlled()`) and returns true — engagement is never checked there. The real engagement gating happens in two separate locations:

1. `FrameworkActionsTrait::actHighDramaChallengeActionIntervene` — throws exception if `$character->Engaged`
2. `ArgumentsTrait` args builder — skips character with `continue` if `$character->Engaged`

Both locations had exemptions for _01178 (Carmella) and _01040 (Rena with en garde weapon), but _01188 was missing from both lists.

WHY this was missed: The `canIntervene()` override looks like it should handle this — someone reading only _01188.php would think engagement is covered. But `canIntervene()` never checks engagement; that's enforced externally. The override is a red herring.

**Fix**: Added `_01188` to the engagement exemption branches in both `FrameworkActionsTrait.php` and `ArgumentsTrait.php`, following the Carmella pattern. Added import statements for the _01188 class in both files.

## What checks out

- **Negotiable**: `$this->Negotiable = true` ✓
- **VLADISLAV_DUEL_TYPE**: Set on `EventDuelNewRound` when round 1 starts and Vladislav is the actor (defender goes first in round 1). Forces args to return only `endDuelAvailable: true`, all other options false ✓
- **No combat card**: `combatCardAvailable: false` in the Vladislav duel type args ✓
- **First-round validation bypass**: The "must gamble or play combat card on round 1" check explicitly skips `VLADISLAV_DUEL_TYPE` ✓
- **Take threat as wounds**: Standard `stDuelEndOfRound` processes the defender's ending threat as wounds on the actor (Vladislav). Since no combat card was played, ending threat equals starting threat ✓
- **Duel ends**: After Vladislav's round, `stDuelNextPlayer` sees both threat pools at 0 (defender's was applied/zeroed, challenger's was 0 initially) and transitions to duel end ✓
- **Restricted Hostilities**: Standard `stDuelEndOfRound` applies RH cap (non-lethal threat capped at stat value) ✓
- **HasIntervened lifecycle**: Set on `EventCharacterIntervened`, used during `EventDuelNewRound`, cleared on `EventDuelEnd`. Flag only set during handleEvent (not eventCheck), so canceled interventions don't leave stale flags ✓
- **actDuelEndDuel guard**: Only allowed when `DUEL_TYPE == VLADISLAV_DUEL_TYPE` ✓
- **Re-engagement safety**: If Vladislav is already engaged and intervenes, the engage event fires again but setting `Engaged = true` on an already-engaged character is harmless ✓
