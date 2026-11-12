# Friends at Court + Not Today → pressure Player Home

## Report
Not Today lethal wound in a duel; Friends at Court (`_01080`) played afterward. Log: reaction announce said City Docks, but pressure targeted Player Home (SUCCESS, then "cannot be claimed").

## Root cause
1. **Not Today (`_01169`)** queued wound + Home move at same MEDIUM priority. FIFO: CardMoving ran before CharacterDestroyed, so the dying participant was moved Home before destroy drained.
2. **Friends at Court** armed `DuelLocation` from `$dyingCharacter->Location` at destroy time → Player Home. Card text is "Pressure your performer's location" (the survivor). Notify used `$performer->Location` (still Docks) while `pressureLocation` used `DuelLocation` (Home) — matches the mismatched log.

## Fix
1. `Reaction_01080`: capture `$performer->Location` (survivor / `DuelOpponentId`), not the dying character. Notify uses `DuelLocation`.
2. `_01169`: Home move at `Event::LOW_PRIORITY` (after MEDIUM wound/destroy). Cancel `EventCardMoving` when source is this card and target is `IsDying` or discard/locker (`runEventHubAfterCards` so hub never queues CardMoved).

## WHY both
01080 alone would paper over Not Today for this card, but other destroy-time readers of Location would still see Home. Not Today alone would leave 01080 wrong vs printed text if anything else moved the corpse before destroy. Do both.

## Tests
`Reaction_01080_Test`: arming with dying char at Home still pressures docks.
`Card_01169_Test`: move priority + cancel when dying/discarded.
