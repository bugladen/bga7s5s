# Siren's Scream — Used-List Overlay UI

## What Was Done

Added a visual overlay on the Siren's Scream card element showing which players have taken a Renown from it during the current Day. Player names are displayed in their respective player colors, positioned at the top-right corner of the card. This mirrors the Parley Gone Wrong intervene list pattern.

## Files Changed

- `modules/php/cards/_7s5s/actions/Action_01179.php` — Added `notifyUsedList()` (private) and `getUsedListData()` (public). The `handleEvent` now sends a `sirensScreamUsedListUpdated` notification when a player is added to `playersUsed` and when the list is cleared at end-of-day.
- `modules/php/cards/_7s5s/_01179.php` — Added `getSirensScreamUsedListData()` (public) that delegates to `Actions[0]->getUsedListData()`. Added `Game` import.
- `modules/php/Game.php` — `getAllDatas()` now also looks for `_01179` instances and includes `sirensScreamUsedList` (with `cardId` and `usedList`) in the result for page refresh support.
- `modules/js/Templates.js` — Added `jstpl_sirens_scream_used_list` template using shared CSS class `_7sfs-card-player-list`.
- `modules/js/Utilities.js` — Added `displaySirensScreamUsedList(cardId, usedList)` and `removeSirensScreamUsedList()` helper functions.
- `modules/js/Notifications.js` — Registered `sirensScreamUsedListUpdated` notification and added handler.
- `modules/js/Setup.js` — Renders the used list on page load from `gamedatas.sirensScreamUsedList`.
- `seventhseacityoffivesails.css` — Added `._7sfs-card-player-list` and `._7sfs-card-player-list span` styles.

## Design Decisions

### WHY overlay on card element rather than a location element

Unlike Parley Gone Wrong (which overlays the forum location image, a persistent DOM element), Siren's Scream's overlay goes directly inside the card's `${divId}_image` element. The card already has `position: relative` from `._7sfs-card`, so absolute positioning just works.

### WHY no explicit cleanup on card removal

The overlay is a child of the card element. When the card is destroyed (via `notif_cardDiscardedFromPlay`, `notif_cardRemovedFromPlay`, or `notif_cardSentToLocker`), `dojo.destroy(card.divId)` automatically removes all children including the overlay. This is different from Parley Gone Wrong where `removeForumInterveneList()` must be explicitly called because the overlay sits on the persistent forum-image element.

### WHY methods split between Action and Card

`playersUsed` lives on `Action_01179` (private). The notify and data methods are on the action since it owns the data and already has `$event->theah->game` in its `handleEvent`. The card class `_01179` has a thin `getSirensScreamUsedListData()` delegation method so `Game.php` can access it by finding the card in `getCardsInPlay()` — same pattern as Parley Gone Wrong's `getInterveneListData()`.

### WHY notification includes cardId

Unlike the Parley overlay (which targets a fixed DOM element `#forum-image`), the Siren's Scream overlay targets a dynamic card element whose divId depends on the card's database ID. The notification includes `cardId` so the JS handler can look up the correct `divId` from `cardProperties`.

### WHY shared CSS class `_7sfs-card-player-list` instead of card-specific

Created a generic `_7sfs-card-player-list` class rather than a card-specific one. The styling is identical to `_7sfs-forum-intervene-list` and could be reused by any future card that needs a player name overlay. Used `z-index: 15` (above the card's `z-index: 10`) to ensure visibility.

### WHY no pointer-events: none

Left pointer-events enabled so the tippy tooltip ("Players who have taken a Renown this Day") works on hover. The overlay is small enough and in the corner that it shouldn't significantly interfere with card interactions.
