# Plans Within Plans (01016) Audit

## Card Text
**Scheme effect:** Add a Renown to two different locations. Search your deck for a Red Hand Thug. Reveal it and put it into your hand.
**Reaction:** After you claim a location that has an opposing character - En garde target character you control at that location.

## Audit Findings

### Fix Applied
- **Typo in notification**: "reaveal" → "reveal" in the scheme resolution message.

### Verified Correct
- **Scheme flow** (3 states):
  1. State 01016: Player picks 2 city locations → each gets 1 Renown via `actCityLocationsForReknownSelected`
  2. State 01016_2: Player searches deck for Red Hand Thug. Private args show matching cards. Pass blocked if thugs exist in deck (enforced in `actFromCardPass`).
  3. State 01016_3: Multipleactiveplayer state for opponents to acknowledge revealed card. Public args show the chosen card.
- **Reaction trigger**: Fires on `EventLocationClaimed` when: controller claimed it, opposing characters present, and controller has engaged characters there. The engaged check is practical — no valid targets means no point triggering.
- **Reaction effect**: En Garde (disengage) a controlled engaged character at the claimed location. Confirmed by cross-referencing Action_01034 and Action_01081 — `Engaged` check is correct for En Garde targeting in this game system.
- **`isAvailable()` check**: Present in reaction's `handleEvent` (line 74) ✓
- **`setUsed()` call**: Present in reaction's `performReaction` (line 114), only when not passing ✓ — correct behavior since declining preserves availability for future triggers
- **Pre-commit hook compliance**: `CardReaction` requirements met. `Scheme` base class has no `setUsed` requirement (only `SchemeAction` does).
- **Frontend**: All 3 states have entering/leaving/button handlers. Location selection enforced to 2 via `numberOfCityLocationsSelectable`.
- **Zombie handling**: States 01016/01016_2 call `actPass()`, state 01016_3 deactivates player. State 01016 technically only allows `actCityLocationsForReknownSelected` not pass, but zombie edge cases are outside card text audit scope.
