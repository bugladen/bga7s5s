# Diplomatic Impunity (02029) Implementation

## Card Text
- **Diplomat Action:** If you control more **Diplomats** at your performer's location than the amount of Renown there • Claim that location.
- **Maneuver** +1[riposte] for each **Diplomat** you control at this location.

## What Was Done

Implemented both abilities on the Montaigne faction Risk card Diplomatic Impunity (`_02029`).

### Diplomat Action (Action_02029)
Follows the direct-claim pattern from Indomitable Will (`Action_01130`) combined with Diplomat-filtering from Battle of Wits (`Action_02028`) and Tea and Cakes (`Action_02025`).

- Performer must be a Diplomat in the city
- Condition checked: count of player's Diplomats at performer's location > `CityLocation->Renown` at that location
- On trigger: queues `createLocationClaimedEvent` directly — no pressure, no custom states
- Resolves inline with `announceAction`, `setUsed`, `resetPlayerPassCount`, `createActionResolvedEvent`

### Maneuver (Maneuver_02029)
Variable riposte bonus based on Diplomat count, pattern from `Maneuver_01136` (riposte) but dynamic.

- `EventDuelCalculateManeuverValues`: Counts Diplomats controlled by card owner at the duel actor's location
- Adds that count as riposte bonus (could be 0 if no Diplomats, though unlikely in practice)

## Key Design Decisions

**WHY no `IAbilityThatTargetsCharacters`:** The action doesn't target an opposing character — it implicitly targets the performer's location. The claim happens at whatever location the chosen performer stands on. No target selection UI needed.

**WHY "Diplomat Action" not "Diplomat City Action":** The card text says "Diplomat Action:" (no "City"). However, the action still restricts performers to characters in the city via `getCharactersInCityByPlayerId` because claiming a location only makes sense at city locations. The distinction from "City Action" is about the base class (`RiskAction` vs `RiskCityAction` / `SchemeCityAction`), not whether it operates in the city.

**WHY the `getDiplomatsAtLocation` helper:** Both `isAvailableToPlayer` and `getPerformersForAction` need the same filtered list (player's Diplomats at a specific location). Extracted to avoid duplication. Uses `getCharactersAtLocationByPlayerId` which already filters by player, then filters for Diplomat trait.

**WHY `checkedLocations` array in `isAvailableToPlayer`:** Multiple Diplomats could be at the same location. Without deduplication, we'd redundantly check the same location's condition for each Diplomat there. The array short-circuits once we've checked a location.

**WHY no custom states or transitions needed:** Unlike Battle of Wits (02028) which needs the challenge flow, this action's effect is immediate and unconditional once the performer is chosen. If the performer is valid (Diplomat at a location where Diplomats > Renown), the claim just happens. Same pattern as Action_01130's EventActionTriggered handler.

**WHY riposte bonus can technically be 0 in the maneuver:** If somehow no Diplomats are at the duel location when the maneuver resolves, the bonus is 0 and no explanation is added. This is a defensive guard — in practice, the participant in the duel is likely a Diplomat themselves and would count toward the total.

## Files Created
- `modules/php/cards/tac/actions/Action_02029.php`
- `modules/php/cards/tac/maneuvers/Maneuver_02029.php`

## Files Modified
- `modules/php/cards/tac/_02029.php` — added interfaces, traits, wired Action + Maneuver
