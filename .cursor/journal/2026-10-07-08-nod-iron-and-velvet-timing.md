# Night of Drinking vs Iron and Velvet — "no react at targeting?"

## Question
User asked if NoD owner (2 cards in hand, 1 = NoD) can miss a cancel choice when Iron and Velvet targets their character.

## Answer
Yes, and it's by design / timing, not hand size.

WHY NoD does not show at targeting:
- Printed: cancel when Risk is **announced**.
- `Reaction_01109` only offers on `EventActionActivated` (Risk Action path), plus RiskReaction / Maneuver / Not Today paths — **not** `EventCharacterTargeted` or challenge-target choose.
- I&V `Action_01131` never fires CharacterTargeted; target pick is later in the challenge UI after announce + ActionTriggered → transition `01131`.

I&V sequence: performer → pay → `announceAction` (NoD window) → then target choose.

Hand size 2 (NoD + other): not a gate on offering. Wealth paid only after Cancel chosen.

Real skip reasons at announce: same controller, Used, not in hand, duplicate transition already queued, Sorcery / effectsCannotBeCancelled (I&V is neither).

## Feelings
Classic "reacted to the wrong moment" report. If they swear they never saw announce cancel either, dig logs / Used / who played I&V — not the hand count.

---

## Follow-up: Risk Used + discard recycle

User asked: abilities on played Risks set Used then discarded; recycle never clears Used?

Nuance:
- Only the **played** Action/Reaction gets `Used=true` (not every ability on the Risk). Action: `actPayForInHandAction` → `setUsed(true)` then discard-as-played. Reaction: individual `setUsed` on resolve.
- Recycle (`shufflePlayerDiscardIntoPlayerFactionDeck`) does **not** clear Used — just moveCard + shuffle.
- Used is cleared later by `EventDuskEndOfDay` on CardAction/CardReaction (discard is in buildCity; faction deck gets special `deliverDuskEndOfDayToFactionDecks` pass for sunk cards). Maneuver/Technique default clear on duel end.

So recycle itself is not the clear — dusk (or duel end) is. Sticky Used after redraw would mean dusk never reached that ability instance.

## Fix (user: "This needs to be fixed")

Mid-day empty-deck reshuffle draws a played Risk before dusk → Action/Reaction still Used=true → NoD etc. look unavailable. Dusk later would clear, but same-day redraw is the hole.

Approach:
- `Card::clearAbilityUsedFlags()` soft-clears `$ability->Used` on Actions/Reactions/Maneuvers/Techniques (no `setUsed` event queue — cards are in a deck; matches Lodestone/Unravel dusk-miss heal).
- Call from `shufflePlayerDiscardIntoPlayerFactionDeck` and `shuffleCityDiscardIntoCityDeck` before persisting the move.

WHY soft-clear not setUsed: shuffle runs inside `playerDrawCard` without a guaranteed immediate `runEvents`; queueing ActionUsed mid-draw is noisy/risky. Deck cards don't need client Used notifs.

Dusk path stays as the normal end-of-day clear for cards still in discard/deck that weren't recycled.
