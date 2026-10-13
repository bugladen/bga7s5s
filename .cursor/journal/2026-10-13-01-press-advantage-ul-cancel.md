# Press the Advantage (01058) vs Unyielding Loyalty — effects survived cancel

## Bug
Opponent played Press the Advantage targeting Sibella (engage + Home). Defender
played Unyielding Loyalty (Shield Rite wealth, Alcee Thug cost). Sibella still
went Home engaged.

## Root cause
Same class as Come Hither (2026-09-13-12) / Amour (01104) / Giacinto (01205).

`Action_01058` never fired `EventCharacterTargeted`. On target pick it queued:
1. `EventCardEngaged` (if ready) — no batchId
2. `EventCardMoving` Home with `engage=true` — no batchId
3. `ActionResolved`

UL can intercept engage or move. It cancels that one event and
`deleteEventBatch` only if `batchId` is set. With no batch, the sibling still
ran. Typical path: UL holds the engage → player pays → `clearEvents()` → move
still in queue → Home engaged.

## Fix
Amour pattern:

1. `actFromActionWithId`: queue only `EventCharacterTargeted` with batchId.
2. `handleEvent`: when that targeting survives (`!canceled`), queue engage then
   Home move (`engage=false`) with same batchId + actionResolved.
3. Separate engage clause + move without engage flag (Lodestone WHY from 01104).

Pass / Night of Drinking re-queue targeting → effects emit again. Successful UL
leaves targeting canceled → no effects.

## Files
- `modules/php/cards/_7s5s/actions/Action_01058.php`
- `tests/regression/Action_01058_Test.php`
