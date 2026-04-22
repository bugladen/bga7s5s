# Elisabetta Bonora (_02002) Audit

## Context

Continuation of the card audit pattern from the _02001 session. Eddie asked to audit _02002 against its card text. Same methodology: read all relevant files, compare against card text, check for validation gaps.

## Findings

Found 3 bugs, all fixed:

### 1. Typo: "Elissabetta" (double s) in State_highDramaPhase02002_3.php
Cosmetic only, but visible to players in the UI description.

### 2. Missing server-side validation in state 02002_2 discard step
Same pattern as the _02001 audit — the server didn't validate that submitted card IDs were among the original top 3. It checked the cards existed and were in the right deck, but ANY card in the deck would pass. A modified client could discard cards from deeper in the deck. State 02002_3 (the reorder step) already validated correctly against the remaining cards list, so this was an inconsistency.

WHY this matters: The `Game::CHOSEN_CARD` global stores the revealed top 3 cards. The validation in state _2 needs to check against this list, not just against "is the card in the deck." Otherwise the "look at top 3" constraint is only enforced client-side.

### 3. Missing addSortTagToCard.order reset in OnLeavingState
The JS sort-order counter used for card reordering wasn't being reset when leaving state 02002_3. Other states using the same mechanism (01134_3, 01177_2) DO reset it. Without the reset, subsequent sort operations could start with a non-zero counter, causing incorrect ordering.

## Pattern notes

The validation gap pattern from the 02001 audit continues. For actions that store revealed/chosen cards in globals and then act on player submissions, need to always validate submissions against the stored set. The existing checks (card exists, card in right location, card owned by right player) are necessary but not sufficient — they don't restrict to the specific subset the player was shown.

The sort order counter reset is a new pattern to watch for. Any state that uses `addSortTagToCard` for ordering needs `delete this.addSortTagToCard.order` in its leaving handler.

## What's NOT a bug but noted

- `actFromActionWithId` line 89: `if (! isset($players[$id]) && $id != 0)` — the `$id != 0` escape is suspicious (BGA player IDs are never 0), but didn't flag as a bug since the UI only sends valid player IDs. Could revisit if other actions show the same pattern; might be a deliberate convention I'm not aware of.
- The action targets "any player" including self. Card text says "target player" with no restriction, so this is correct.
