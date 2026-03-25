# Text Tooltip Preference (Preference 100)

## Summary

User preference 100 ("Card Hover Style") option 2 ("Text") enables text-based tooltips instead of card images. Implemented across all card types (Character, Scheme, Attachment, Risk) in board area, faction hand, approach deck, city cards, and log entries.

## Key Architecture Decisions

### Tooltip Method: addTippyTooltip
Initially used BGA native `addTooltipHtml`. Eddie switched to `addTippyTooltip` for consistency across all tooltip types.

### HTML Table Format
All text tooltips (except Scheme, which is still pending conversion) render as borderless HTML tables with:
- `vertical-align:top` on label cells for multi-line rows (Text, Actions, Reactions, Maneuvers, Techniques)
- `padding-right:10px` on label cells for spacing

### PHP Constant
`USER_PREFERENCES_CARD_HOVER_TYPE = 100` defined in `modules/php/Game.php`, mirrored as `this.USER_PREFERENCES_CARD_HOVER_TYPE` in the JS constructor.

## Files Modified

- **`modules/js/Utilities.js`**: `createTooltipForCard` dispatches to type-specific methods. Created `createTextTooltipForCharacter`, `createTextTooltipForScheme`, `createTextTooltipForAttachment`, `createTextTooltipForRisk`. Also updated `applyFactionHandCardStyle` and `setupNewStockCard` with preference gates.
- **`seventhseacityoffivesails.js`**: `logInject` regex, `getHTMLForLog`, `addTooltipsToLog` all updated for log entry text tooltips.
- **`modules/php/cards/Card.php`**: `getCardType()` and modified `getInjectCode()` to embed type in `[id:type:name(image)]` format.
- **`modules/php/Game.php`**: Added `USER_PREFERENCES_CARD_HOVER_TYPE` constant.

## Critical Bug: Log Tooltips for Other Players' Cards (2026-03-25)

### Root Cause
Risk cards (and any card played from another player's hand) were still showing image tooltips in the log. The root cause:

1. `notif_cardAddedToHand` only adds cards to `cardProperties` for the **current player** (`notif.args.player_id == this.player_id`).
2. When Player A plays a Risk, Player B never had that card in `cardProperties`.
3. The `logCardCache` snapshot in `getHTMLForLog` also couldn't help — it copies from `cardProperties`, which was never populated for the other player.
4. So `addTooltipsToLog` found `card = null`, and despite having `cardType` from the `data-card-type` attribute, the old code required `card && type` to show text — falling through to image.

### Fix
Changed `addTooltipsToLog` to handle the case where `type` is known but `card` data is unavailable. When the text preference is active and we have a type from the `data-card-type` attribute but no full card object, we now show a minimal text tooltip with the card name and type (e.g., "Burden of Atlas (Risk)") instead of falling through to the image tooltip.

The condition changed from `if (card && type)` to `if (type)` with an inner branch: if `card` exists, use the full type-specific tooltip; else, build a simple `Name (Type)` tooltip from the span's text content.

### Discard pile search fallback (2026-03-25)

Added `findCardInDiscards(cardId)` method that searches `gamedatas.players[*].discard` and `gamedatas.cityDiscard` for a card by ID. This is used as a third-tier fallback in the `addTooltipsToLog` lookup chain:

1. `this.cardProperties[cardId]` — live session, card still in play or current player's hand
2. `this.logCardCache[cardId]` — snapshot taken when the log span was created
3. `this.findCardInDiscards(cardId)` — card is in a discard pile (especially useful after page refresh, when `getAllDatas` populates discards but not `cardProperties`)
4. Basic `"Name (Type)"` from `data-card-type` — ultimate fallback when no card data exists at all

WHY: After a page refresh, `getAllDatas` sends full card data for all discard piles, but Setup.js never puts those cards into `cardProperties`. Searching the discard arrays recovers full card data for rich text tooltips on refresh, instead of falling back to the minimal name-only tooltip.

### WHY this approach over alternatives
- **Embedding full card data in inject code**: Would bloat every log message with dozens of properties. Impractical.
- **Never deleting from cardProperties**: Doesn't help — the card was never ADDED to other players' `cardProperties` in the first place. The hand is private.
- **Sending card data to all players on play**: Would require framework changes and might leak hidden information.
- **Simple name+type fallback**: Lightweight, respects the text preference, and degrades gracefully when full data isn't available.

### Notification handler timing issue (2026-03-25)

City cards deployed via `notif_cityCardAddedToLocation` were showing "Name (Type)" fallback tooltips in the log. Root cause: BGA processes notifications in this order: (1) format log entry via `format_string_recursive`, (2) call `addToLog`, (3) call notification handler. So when the log entry was formatted and `addTooltipsToLog` ran, the city card's handler hadn't fired yet — meaning `cardProperties` didn't have the card yet.

Fix: Pre-cache card data from notification args in `format_string_recursive_with_injection`, BEFORE the log is formatted. Most notifications include `"card" => $card->getPropertyArray(...)` in their args. By scanning `args` for objects with `id` and `type` properties and storing them in `logCardCache`, the card data is available when `addTooltipsToLog` runs.

WHY this approach: The notification args are the EARLIEST point where card data is available on the JS side. Pre-caching here ensures the log tooltip lookup succeeds regardless of whether the notification handler has run yet. This is a general fix that works for ANY card type, not just city cards.

## Pending
- `createTextTooltipForScheme` still uses `lines.join('<br>')` instead of HTML table format. Needs conversion to match the other three card types.
