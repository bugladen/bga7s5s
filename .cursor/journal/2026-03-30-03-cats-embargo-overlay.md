# The Cat's Embargo (_01098) — Embargoed Card Name Overlay

## What Was Done

Added a visual overlay on The Cat's Embargo scheme card showing the name of the currently embargoed card. Positioned at the top-right corner of the card image, following the same pattern established by Siren's Scream.

## Files Changed

- `modules/php/cards/_7s5s/_01098.php` — Added `getCatsEmbargoData()` method that returns `cardId` + `embargoedCardName` when `EmbargoedCardId != 0`. Added `catsEmbargoUpdated` notification in `actFromCardWithId` after the embargo target is set.
- `modules/php/Game.php` — `getAllDatas()` now looks for `_01098` instances in the city and includes `catsEmbargoData` in the result. Combined the loop with Siren's Scream to avoid iterating `getAllCards()` twice.
- `modules/js/Templates.js` — Added `jstpl_cats_embargo_card_name` template using the shared `_7sfs-card-player-list` CSS class.
- `modules/js/Utilities.js` — Added `displayCatsEmbargoCardName(cardId, embargoedCardName)` and `removeCatsEmbargoCardName()`. The display function translates the card name via `_()` before rendering.
- `modules/js/Notifications.js` — Registered `catsEmbargoUpdated` notification (1ms delay) and added handler.
- `modules/js/Setup.js` — Renders the overlay on page load from `gamedatas.catsEmbargoData`.

## Design Decisions

### WHY no explicit cleanup on card removal
Same reasoning as Siren's Scream — the overlay is a child of the card image element, so it's automatically destroyed when the card element is destroyed (sent to locker, discarded, etc.).

### WHY single span instead of a list
Unlike Siren's Scream (which shows a list of player names), the embargo only tracks one card name at a time. The overlay contains a single `<span>` with the card name. When a new embargo target is set (each planning phase), the old overlay is replaced via `removeCatsEmbargoCardName()` called at the top of `displayCatsEmbargoCardName()`.

### WHY reusing `_7sfs-card-player-list` CSS class
The shared class already handles position, background, font size, z-index — all identical to what's needed here. The class name is a bit misleading (it's not a "player list") but it was intentionally designed as a generic card overlay class per the Siren's Scream journal entry.

### WHY `getCatsEmbargoData()` returns null when `EmbargoedCardId == 0`
The embargo target isn't set until the first planning phase end. Before that, the scheme is in play but has no target. Returning null prevents the overlay from rendering with no content.
