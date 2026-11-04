# Used sticky after redraw? (user: turn 4, possible prior use)

User hypothesis: 01109 used earlier, redrawn by turn 4, Used never reset.

## How Used works for NoD

- Cancel path `setUsed(true)` then discard (pay for reaction).
- Clear: `CardReaction` on `EventDuskEndOfDay` (discard is in buildCity; faction deck gets `deliverDuskEndOfDayToFactionDecks`).
- Mid-day recycle: `shufflePlayerDiscardIntoPlayerFactionDeck` calls `clearAbilityUsedFlags()` before move (fix from 2026-10-07-08). Covered by `DeckTrait_Draw_Test`.

## Answer

Yes sticky Used is possible **same day before dusk** if the card is redrawn — but only if the return-to-hand path skips the recycle clear. Normal empty-deck reshuffle should soft-clear now.

Same day, two copies in starter: using copy A leaves copy B available. Sticky only bites the same physical card instance.

Cross-day (dusk happened while card in discard/deck/hand): dusk should have cleared; redraw next day should be fine.

Not a new bug in the 02029 path — situational Used gate. De-dupe/01090 session changes still unrelated to this.
