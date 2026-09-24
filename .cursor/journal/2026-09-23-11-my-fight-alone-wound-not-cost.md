# My Fight, Alone (01136) — wounds are not a play cost

## Bug
`Action_01136::isAvailableToPlayer` / `getPerformersForAction` required
`$character->Wounds == 0` continue — treating healability as a cost to offer the card.

Same Eddie ruling as Matushka's Song (03060 journal -10): being wounded is not a cost.

## Printed parse
`City Action: If your performer is the only character you control at this location • They heal a wound.`
- Alone-at-location = availability If (keep)
- Heal = effect after bullet (not a gate)

## Fix
- Performer filter = alone at location only
- On resolve: heal only if `Wounds > 0`; always ActionResolved
- Maneuver untouched (no wound gate; alone +1 Riposte)

## WHY skip heal event when 0 wounds
Avoid emitting CharacterBeingHealed for a no-op. Action still resolves so play-for-ability side effects work.
