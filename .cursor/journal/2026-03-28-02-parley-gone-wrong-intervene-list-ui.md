# Parley Gone Wrong — Forum Intervene List UI

## What Was Done

Added a visual overlay on the Forums location image showing which players can intervene (i.e., have contributed Renown to the Forums) while the Parley Gone Wrong scheme is in play. Player names are displayed in their respective player colors.

## Files Changed

- `modules/php/cards/_7s5s/_01150.php` — Added `notifyInterveneList()` (private) and `getInterveneListData()` (public). The `handleEvent` now sends a `parleyInterveneListUpdated` notification whenever the intervene list changes (both when a player is added and when cleared at end-of-day).
- `modules/php/Game.php` — `getAllDatas()` now iterates cards in play looking for `_01150` instances and includes `forumInterveneList` in the result for page refresh support.
- `modules/js/Templates.js` — Added `jstpl_forum_parley_gone_wrong_intervene_list` template (a container div placed inside forum-image).
- `modules/js/Notifications.js` — Registered `parleyInterveneListUpdated` notification and added handler. Also added clear-on-remove logic to `notif_cardDiscardedFromPlay` and `notif_cardRemovedFromPlay` (checks `cardNumber === 150 && expansionName === '_7s5s'`).
- `modules/js/Utilities.js` — Added `displayForumInterveneList()` and `removeForumInterveneList()` helper functions.
- `modules/js/Setup.js` — Renders the intervene list on page load from `gamedatas.forumInterveneList`.
- `seventhseacityoffivesails.css` — Added `position: relative` to `._7sfs-city-image` (needed for absolute positioning of the overlay). Added `._7sfs-forum-intervene-list` styles: absolute positioned at top-right, semi-transparent black background, small bold text in column layout.

## Design Decisions

### WHY overlay inside forum-image rather than a sibling
The overlay is placed as a child of `#forum-image` using `dojo.place(..., 'first')` with absolute positioning. This keeps it scoped to the image bounds. The alternative (placing it as a sibling like the location control chip) would require different positioning math since the parent `_7sfs-city-location-contents` uses inline-flex layout.

Added `position: relative` to `._7sfs-city-image` to establish the positioning context. This affects all city images but since none of them had children before, it's harmless.

### WHY clear-on-remove checks card identity on JS side
Eddie confirmed the scheme only leaves play (never moves), so we only need to hook into `cardDiscardedFromPlay` and `cardRemovedFromPlay`. We check `card.cardNumber === 150 && card.expansionName === '_7s5s'` on the JS side rather than sending a separate notification from PHP. This avoids adding server-side hooks into the generic card removal flow.

### WHY three separate clear paths
1. `EventDuskEndOfDay` → PHP sends `parleyInterveneListUpdated` with empty list
2. `cardDiscardedFromPlay` → JS checks if it's the Parley scheme and removes overlay
3. `cardRemovedFromPlay` → same check

The scheme never moves, so `cardMoved` is not hooked.

### WHY `pointer-events: none` on the overlay
The overlay sits on top of the forum-image which is a click target for city location selection. Without `pointer-events: none`, the overlay would intercept clicks and break the selection flow during scheme resolution states.
