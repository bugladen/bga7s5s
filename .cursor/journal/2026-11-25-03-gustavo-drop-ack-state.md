# Gustavo Action_01192 — drop multi-ack reveal state

## Ask
User: Action_01192 has a state where all players must acknowledge the revealed cards. Remove it and rely on putting the revealed cards in the log.

## Prior flow
`01192` MULTI (ack sans initiator) → `01192_2` GAME `stSetCurrentPlayer` → `01192_3` ACTIVE choose Risk.

WHY the bridge existed: after multi-ack, BGA active player is whoever clicked Ok last; `_2` restored `MULTI_STATE_INITIATING_PLAYER` / CURRENT_PLAYER before the picker.

## New flow
Trigger → log reveal → transition `01192` straight into ACTIVE choose Risk. Deleted `_2` and `_3` state classes + constants. Hub key `"01192"` unchanged.

## Log as the reveal surface
- List **all** revealed cards in `${names}` (was Risks-only — opponents couldn't see non-Risks without the chooseList UI).
- Pass `cards[]` property arrays so `format_string_recursive_with_injection` seeds logCardCache (Monet 04023 / journal 2026-11-13-10). Inject codes alone → stub tooltips for faction-deck cards not in opponents' cardProperties.
- Dropped `MULTI_STATE_INITIATING_PLAYER` set — nothing reads it for Gustavo anymore.

## JS
Merged former `_3` enter/leave/buttons/EventHandlers onto `highDramaPhase01192`; removed Ok / multi-ack handlers.

## Tests
`Action_01192_Test` + `Card_01192_Test` green (13). Trigger test now asserts no MULTI global + cards[] ids.

## Follow-up
Otto `01038` cleaned the same way (2026-11-25-04). Kaj `01180` still has multi-ack if wanted.
