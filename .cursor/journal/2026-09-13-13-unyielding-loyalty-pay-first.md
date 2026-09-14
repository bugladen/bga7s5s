# Unyielding Loyalty (01032) — pay wealth before additional cost

## Bug
Reaction_01032 showed Red Hand destroy / Thug discard buttons as the *first* choice,
before the player paid the Risk. Card must be paid first; additional cost comes after.

## Why the old order existed
2026-08-31-02 put `payCost()` in `performReaction` *before* EnteringPayState so Night
of Drinking (01109) could not cancel UL without the Thug/Red Hand already being paid.
01109 deletes `EventRiskReactionTriggered` on cancel; the cost lived there, so it
never ran.

That matched 01109's "(All costs are still paid.)" but inverted the printed play
order: wealth first, then the bullet additional cost.

## New order (Pattern D.1)
1. Offer Play / Pass only (no cost buttons).
2. Play → EnteringPayState + pay transition (wealth).
3. `EventRiskReactionTriggered` → `beginCostChoice()` → second playerReaction with
   Red Hand destroy and/or Thug discard. No Pass — already committed.
4. After that pick: `payCost()`, `clearEvents()` (confirm the cancel), `setUsed`.

Do **not** `setUsed` before the cost-choice transition. `Theah::runEvents` skips
`transition == "reaction"` when `! isAvailable()`.

## 01109 still collects the additional cost
01109 still deletes `EventRiskReactionTriggered`, so the normal post-pay cost UI
would never appear. `revertCancellation()` now calls `beginCostChoice()` after
`releaseEvent()` — effect is restored, then the player must still pick Red Hand /
Thug. That's how "all costs are still paid" survives pay-first.

If they spent their last Thug as wealth and have no Red Hand, `beginCostChoice`
releases the held event (cancel fails) and notifies. Rare; cost is 1.

## Also
Both options show at once after pay (card text is OR). Old sequential
Red-Hand-then-Pass-to-see-Thugs is gone — that Pass was mixed with declining the
whole reaction.

## Files
- `modules/php/cards/_7s5s/reactions/Reaction_01032.php`
