# Matushka's Song (03060) — wounds are not a play cost

## Bug
`Action_03060::getPerformersForAction` required a wounded "another character" at the
performer's location. `Maneuver_03060::isAvailableToPlayer` required the duel actor
to have wounds. Both treated healing precondition as a cost to offer the card.

Eddie: being wounded is not a cost. Players may play for the Sorcerer ability
(Elina renown / Path reactions, etc.) with nobody wounded.

## Fix
- **Action:** performer filter = Sorcerer only. Heal chooser still filters
  `Wounds > 0` (effect filter, not availability). On `EventActionTriggered`, if no
  healable characters, fire Sorcerer start/played + ActionResolved and skip the
  chooser (no soft-lock) — same optional-tail discipline as 01134 / 02045.
- **Maneuver:** availability = Gambling + Sorcerer at location. Resolve still
  no-ops heal when actor has 0 wounds.

## WHY not make heal optional when wounded exist
Printed effect is "Heal two wounds…" (mandatory when able). Empty pool = no-op +
still count as performing the Sorcerer ability. Not Pass-on-chooser when targets
exist.

## Not changed
- `isValidHealCharacter` / `getValidHealCharacters` still require wounds (can't
  meaningfully heal 0).
- Engage / WillEngage / discount path untouched.
