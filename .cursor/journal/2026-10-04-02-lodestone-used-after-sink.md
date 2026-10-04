# Lodestone City Action stuck "already used" after equip

## Symptom
Character at City Docks with Lodestone freshly equipped; City Action unavailable / looks used.

## Root cause
`actHighDramaInPlayActionConfirm` sets `Used=true`, then Action_03065 sinks to the **faction deck**. `buildCity()` does not load faction decks (same deliberate omission as Locker — keep out-of-play piles out of the general `handleEvent` loop). `CardAction` only clears `Used` on `EventDuskEndOfDay`, so a sunk card never got that reset while in the deck. Redraw + re-equip kept `Used=true`.

Same class of bug for any sink-self CardAction (e.g. Action_04010).

## Fix
1. **Theah::runEvents** — on `EventDuskEndOfDay`, `deliverDuskEndOfDayToFactionDecks()`: load each player's faction deck, pin into world (`addCardToWorld` so `setUsed` persists the same instance — `_04010` lesson), `handleEvent`, then `unset` so later events don't treat deck cards as in play.
2. **Action_03065::isAvailableToPlayer** — if equipped and `Used`, clear the stale flag (no `setUsed`/queue). Valid because a completed Lodestone resolve always sinks the card; equipped+Used can only be the dusk-miss leftover. Unsticks copies already on the board this turn.

## WHY not only clear on equip / on sink
- Clear on sink → same-day redraw could reuse; dusk-in-deck preserves once-per-day if the card returns before dusk.
- Equip clear alone wouldn't fix other sink-self actions or cards still sitting in the deck at dusk.
- Availability heal is Lodestone-specific (sink-self invariant); systemic dusk pass is the general fix.

## Not done
- Generic equipped+Used heal for other sink-self actions (04010 etc.) — only dusk pass covers those unless we add similar heals.
- Client tooltip may still show `available:false` until action list rebuild / refresh if heal ran server-side only.
