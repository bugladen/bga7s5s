# Mid-gamble draw stole revealed cards (04003b ← 04010)

## Report

Eddie: Unravel (`Reaction_04010`) used during gamble → Desideria `Reaction_04003b` wound+draw. Drawn card was the first card shown in the gamble reveal. "Cards revealed for gambling are no longer in the deck" = after the draw, that peeked card was in hand, so the choose set / `GAMBLE_REVEAL_COUNT` no longer matched the deck tops.

## Mechanism

Gamble is a **peek**: `stDuelGambleRevealed` / args only read top N via `getCardsOnTopOfPlayerFactionDeck`. Cards stay in `Faction-<pid>` until `actGambleCardChosen` (chosen → dueling line; unchosen sink).

`Reaction_04010` on Use queues `EventSorcererAbilityPlayed`. `Reaction_04003b` offers wound+draw; draw is `EventCardDrawn` → `playerDrawCard` → `pickCard` = **literal top of deck** = first revealed card. `GAMBLE_REVEAL_COUNT` was not decremented (unlike Ivy `02042`, which intentionally removes a revealed Sorcery and shrinks the count).

Timing window: after Use, still in `DUEL_GAMBLE_REVEALED_*` event loop, before choose. `DUEL_GAMBLED` is still false. `GAMBLE_REVEAL_COUNT` remains set until EOR even after choose — so skip logic must not key off count alone.

## Fix

`DeckTrait::playerDrawCard`: when `IN_DUEL` + `GAMBLE_REVEAL_COUNT > 0` + not `FROM_BOTTOM` + not `DUEL_GAMBLED` + drawer is duel-round actor's controller → ensure `count+1` cards (reuse getCardsOnTop reshuffle-preserve-tops) and move the card **under** the peek into hand. If nothing under after reshuffle, fall back to pickCard and decrement count (Ivy-style) so choose UI stays coherent.

WHY not clear count on choose / not fix only 04003b: other mid-reveal draws would hit the same landmine; count is still needed through choose args; post-choose draws must see normal tops (`DUEL_GAMBLED` gates that).

## Not changed

Ivy path unchanged. Bottom-reveal gambles unchanged (draw edge ≠ peek edge).
