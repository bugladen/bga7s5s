# Mobile approach deck: schemes mixed with characters

## Ask
Eddie: on mobile during planningPhase, characters and schemes are mixed. Schemes should display first, then characters.

## Root cause
Continuation of `2026-04-07-06-approach-deck-mobile-stacking-bug.md`. Mobile CSS forces `#approachDeck` into flexbox and overrides `.stockitem` to `position: relative` so ebg.stock's absolute layout (and its **weight** sort) no longer controls visual order. Flex uses DOM order / CSS `order`. Cards were added in server location order, so schemes and characters interleaved.

Desktop still looks fine because stock places by weight via left/top.

## Fix
1. `Setup.js` — sort `gamedatas.approachDeck` schemes/attachments first (weight 1) before `addCardToDeck`, matching `addCardToDeck` / factionHand sort.
2. `Utilities.js` `setupNewStockCard` — set `cardDiv.style.order` to 1 for Scheme/Attachment, 2 otherwise. WHY CSS order too: `notif_approachCardsReceived` appends cards later; without order, a late scheme would sit at the end of the flex row. Covers chooseList stocks harmlessly (no flex order consumers).

## Unfinished
None for this bug. If Eddie wants characters strictly after schemes with attachments in a third bucket, tweak weights — currently attachments share scheme weight (same as desktop stock).
