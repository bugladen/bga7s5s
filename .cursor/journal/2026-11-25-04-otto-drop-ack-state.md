# Otto Action_01038 — drop multi-ack reveal state

## Ask
User: "Clean up Otto" right after Gustavo 01192 ack removal — same treatment.

## Prior flow
`01038` MULTI (full ack including initiator) → `01038_2` GAME `stSetCurrentPlayer` → `01038_3` ACTIVE choose Attachment.

Note vs Gustavo: Otto used full acknowledge (`stMultiPlayerInitCardRevealAcknowledge`), not sans-initiator. Bridge restored `CURRENT_PLAYER` (Otto never set `MULTI_STATE_INITIATING_PLAYER`).

## New flow
Trigger → log reveal → transition `01038` straight into ACTIVE choose Attachment. Deleted `_2`/`_3` classes + constants. Hub `"01038"` unchanged.

## Log
- `cards[]` property arrays for hover hydration (was inject-codes-only — Monet note in 2026-11-13-10).
- Message shape aligned with Gustavo: `(${names})` instead of the old HTML `<p>Cards Revealed:` block.
- Pass-only-when-no-Attachments rule kept (server throw + JS Pass button gate).

## Zombie
Same pick-first-Attachment-or-pass logic moved onto `State_highDramaPhase01038` (was on `_3`). WHY not bare pass: `actFromActionPass` throws while Attachments remain.

## Tests
`Action_01038_Test` + `Card_01038_Test` — trigger now asserts found + cards[].

## Follow-up
Kaj `01180` cleaned the same way (2026-11-25-05).
