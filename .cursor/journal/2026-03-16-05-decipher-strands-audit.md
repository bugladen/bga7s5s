# Decipher the Strands (_02005) Audit

## Context

Fifth card audit in the series (_02001 through _02005). Eddie asked to audit _02005 (Decipher the Strands) against its card text. A Vodacce scheme with Sorte/Weave traits — adds renown then manipulates an opponent's deck.

## Findings

### 4 bugs found, all fixed

**Bug 1: Missing SorcererAbilityPlayedEvent on allSunk path**

The sorcery lifecycle has Start and Played events. Start fires when opponent is chosen (state _3), Played fires at the end (state _5). But if ALL cards are sunk in state _4, the "allSunk" transition skips state _5 and goes straight to events. Result: sorcery started but never completed. Could affect cards/reactions that track sorcery usage.

WHY this is definitely a bug: The sorcery lifecycle should be symmetric. Every Start needs a Played. The _5 path has both, the allSunk path was missing Played.

**Bug 2: onMultipleChooseListCardsConfirmed vs onCardsSorted**

The reorder state (_5) used the wrong JS confirm handler. `onMultipleChooseListCardsConfirmed` collects the order attribute from each card but never sorts by it — the cards go in `getSelectedItems()` iteration order which is unpredictable. `onCardsSorted` correctly sorts by descending order so that `insertCardOnExtremePosition(true)` in the PHP loop produces the right final deck order (first-clicked = top of deck).

WHY the descending sort matters: PHP inserts each card on TOP of the deck sequentially. So the last id in the array ends up on top. By sorting descending (last-clicked first), the first-clicked card is processed last and ends up on top. This is the same pattern used in highDramaPhase02002_3 (Elisabetta Bonora's deck manipulation).

**Bug 3: Stale addSortTagToCard.order counter**

The sort tag counter is a function property that persists across states. Without cleanup, the next time any state uses `addSortTagToCard`, the counter starts from whatever it was left at. The analogous 02002_3 state correctly deletes it.

**Bug 4: Missing server-side minimum sink validation**

Minor defensive fix. JS prevents 0-card submission but PHP should validate too.

## Pattern observations

This card has very similar deck manipulation to _02002 (Elisabetta Bonora) — both look at top cards, sink some, reorder the rest. The code was clearly modeled after _02002 but the JS handlers weren't copied exactly right. The `onCardsSorted` vs `onMultipleChooseListCardsConfirmed` distinction is subtle and easy to miss. Both functions look almost identical — the only difference is a single `.sort()` call.

Future audit note: any state that uses `addSortTagToCard` in its event handler should use `onCardsSorted` (not `onMultipleChooseListCardsConfirmed`) and should clean up `this.addSortTagToCard.order` in `onLeavingState`.
