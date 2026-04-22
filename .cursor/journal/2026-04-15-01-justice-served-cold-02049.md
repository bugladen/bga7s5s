# Justice Served Cold (_02049) Implementation

## Card Abilities
1. **City Action**: Performer issues Combat challenge to opposing Mercenary or Thug
2. **Maneuver**: If adversary is Mercenary or Thug, +1 Riposte

## Implementation

Straightforward card — both abilities are well-established patterns.

**Action (Action_02049)**: Extends `RiskCityAction` with `IAbilityThatTargetsCharacters`. Modeled after Action_01083 (Legendary Reputation) for the combat challenge flow, but with trait restriction on targets like Action_02028's approach. Key differences from 01083:
- No "unequipped" restriction on performer (card text doesn't require it)
- Target must be Mercenary or Thug (validated in both `isAvailableToPlayer` and `isValidTargetForAbility`)
- Uses `getOpposingCharactersAtLocation` instead of manual filtering for opposing characters

**Maneuver (Maneuver_02049)**: Conditional +1 Riposte. Modeled after Technique_02023 (checks adversary for Thug/Mercenary) but adapted for the Maneuver event system (`EventDuelCalculateManeuverValues` instead of technique values). Added `isAvailableToPlayer` with IN_DUEL check + adversary trait check, so the maneuver only appears when relevant.

**Challenge type**: Added `JUSTICE_SERVED_COLD_CHALLENGE_TYPE = 16` to Game.php. Transition mapped to shared `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` state in states.inc.php — same pattern as 01083, 01131, 02028.

No new states needed. The card uses `IRiskThatTargetsCharacters` on the main class since the city action targets characters.
