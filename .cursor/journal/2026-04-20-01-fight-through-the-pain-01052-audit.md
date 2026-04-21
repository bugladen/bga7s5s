# Fight Through the Pain (01052) Audit

## Card Text
"**Action:** Target equipped character heals a wound."
"**Maneuver:** When your round ends, your participant heals a wound."

## Architecture
Eisen Risk card (Flourish/Relentless/Eisenfaust). Has both an Action and a Maneuver.

### Action Flow
- RiskAction with `RequiresPerformerSelected = true` and `IAbilityThatTargetsCharacters`
- `getPerformersForAction` filters to player's characters with `Attachments > 0 AND Wounds > 0`
- `handleEvent` on `EventActionTriggered`: heals the chosen performer via `createCharacterBeingHealedEvent`
- The performer selection serves as the targeting mechanism (no separate target selection step in the engine for in-hand risk actions)

### Maneuver Flow
- Sets `HealAtEndOfRound` flag on `EventResolveManeuver`
- Clears flag on `EventManeuverCanceled`
- On `EventDuelEndOfRound`: if flag set, heals `getDuelRoundActor()` 1 wound (if alive and wounded), resets flag

## Bug Found: `isValidTargetForAbility` validated the wrong entity

The method ignored its `$character` parameter and re-fetched/validated the `CHOSEN_PERFORMER` global instead. Compared with other RiskActions implementing `IAbilityThatTargetsCharacters` (01034, 01078), they correctly validate the `$character` parameter relative to the performer.

Additionally, the card says "Target **equipped** character" but the validation didn't check for equipment (attachments).

### Fix
- Removed performer re-fetch, now validates `$character` directly
- Added equipment check: `count($character->Attachments) == 0`
- Kept ownership and wound checks against `$character`

### WHY this matters even though the method is rarely called
During normal action flow for in-hand risk actions, `isValidTargetForAbility` is NOT called by the engine (the framework fires `EventActionTriggered` directly after performer selection). However:
1. `handleEvent` itself calls `isValidTargetForAbility($game, $performer)` as validation, which works because performer IS the target
2. Reaction_01014 (and possibly others) can call this method when checking whether a character is a valid target for the ability
3. The `IAbilityThatTargetsCharacters` interface is checked by Reaction_01008 via `instanceof` to determine if abilities target characters

## Card Stats Verified
- 0 cost, dashed Riposte, 0 Parry, 5 Thrust
- Traits: Flourish, Relentless, Eisenfaust
- All match the card image

## Maneuver
No issues. The recent commit (22109d5a) already fixed the wound-gating bug in `isAvailableToPlayer`.
