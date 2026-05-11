# LocationAction Used-List Overlay

## What Was Done

Added a player-name overlay to city locations whose LocationAction has been used today. The overlay appears at the top-right of the location's image element (e.g. `oles-inn-image`, `garden-image`) and lists each player who has activated that location's action this Day, colored in the player's color. The overlay is cleared at end-of-day when the action's `playersUsed` list resets.

This was modeled directly on Siren's Scream (`Action_01179`) — Eddie asked for that exact pattern.

## Files Changed

- `modules/php/theah/actions/LocationAction.php`
  - Added public `$LocationName` (subclasses must set this — used by JS to find the right location image element).
  - Added `getUsedListData(Game $game): array` returning the same `{playerId, playerName, playerColor}` shape Siren's Scream uses.
  - Added `notifyUsedList(Game $game)` emitting a `locationActionUsedListUpdated` notification carrying `{actionId, locationName, usedList}`.
  - `setPlayerUsed()` now calls `notifyUsedList()` after appending the player.
  - `handleEvent()` calls `notifyUsedList()` after clearing on `EventDuskEndOfDay`.
- `modules/php/theah/actions/OlesInnAction.php` — set `$this->LocationName = Game::LOCATION_CITY_OLES_INN`.
- `modules/php/theah/actions/GovernorsGardenAction.php` — set `$this->LocationName = Game::LOCATION_CITY_GOVERNORS_GARDEN`.
- `modules/php/theah/Theah.php` — added `getActions(): array` (the `$Actions` property is private, so Game.php needed a getter).
- `modules/php/Game.php` — imported `LocationAction`; `getAllDatas` now emits `locationActionUsedLists` (array of `{actionId, locationName, usedList}`) by iterating `$this->theah->getActions()` and filtering on `LocationAction`.
- `modules/js/Templates.js` — added `jstpl_location_action_used_list` using actionId in the div id.
- `modules/js/Utilities.js` — added `displayLocationActionUsedList(actionId, locationName, usedList)` and `removeLocationActionUsedList(actionId)`. Uses the existing `getCityLocationElement(locationName)` helper which queries by `data-location` attribute.
- `modules/js/Notifications.js` — registered `locationActionUsedListUpdated` (priority 1) and added the handler.
- `modules/js/Setup.js` — iterates `gamedatas.locationActionUsedLists` on page-load so F5 restores the overlay.
- `seventhseacityoffivesails.css` — added `._7sfs-location-action-used-list { pointer-events: none; }` modifier to keep the overlay from intercepting clicks on the location image (locations ARE click targets during scheme/selection states — same concern as the Parley Gone Wrong overlay).

## Design Decisions

### WHY the base class owns this, not each subclass
Siren's Scream put the logic on the card-specific Action class (`Action_01179`) because each card has a unique `cardId` and its own DOM target. LocationActions all share the same shape — `playersUsed` list, location-image overlay target — so the base class is the natural home. Two subclasses today (Ole's Inn, Governor's Garden); the third location action that gets added will get this for free.

### WHY `$LocationName` instead of mapping `Id` → location in JS
The location-image element already has a `data-location` attribute matching the location name string ("Ole's Inn", "Governor's Garden"), and `getCityLocationElement()` is the standard helper that resolves location-name → element. Passing `locationName` in the notification reuses that helper instead of inventing a parallel `actionId` → element mapping in JS. The `actionId` is still in the payload so the JS can build a stable div id (`location-action-used-list-OlesInn`) without slugifying the location name (apostrophe in "Ole's Inn" would be painful as a CSS-safe id).

### WHY `getActions()` getter rather than making `$Actions` public
The `$Actions` and `$Reactions` properties on `Theah` are private and have been for a long time. Internal Theah code uses `$this->Actions` directly; nothing outside the class touches `Actions`. Adding a getter keeps the encapsulation rather than flipping visibility for a single external caller.

### WHY `pointer-events: none` here but not on Siren's Scream
Siren's Scream is a card overlay — the underlying card image isn't a click target for location selection, so the journal note from 2026-03-28-03 deliberately kept pointer-events enabled to preserve the tippy tooltip on hover. Location images ARE click targets during scheme/selection flows, so the Parley Gone Wrong overlay (`_7sfs-forum-intervene-list`, used on the forum image) needs `pointer-events: none` to avoid blocking clicks. This new overlay sits on the same kind of element, so it follows the location-overlay precedent. The tippy tooltip won't fire on hover as a result — acceptable cost since the location context already conveys what the overlay represents.

### WHY notification on `setPlayerUsed` rather than from `handleEvent` on `EventActionTriggered`
`setPlayerUsed` is called from `FrameworkActionsTrait::actHighDramaInPlayActionConfirm` — a single, well-defined entry point that runs after the player commits the action. Emitting the notification there avoids duplicating it across both Ole's Inn and Garden subclasses (which would otherwise have needed to override `handleEvent`).

### WHY emit a notification on end-of-day clear
Without it, the overlay would linger across days for any player still looking at the board. The handler trivially handles empty lists by destroying the div (`removeLocationActionUsedList` runs at the top of `displayLocationActionUsedList`).

## What I'd Verify in a Real Game

- 3-player game: Ole's Inn overlay shows when the controller draws via the location action; clears at Dusk.
- 4+ player game: both overlays appear independently; using one doesn't trigger the other.
- F5 mid-day after using the action: overlay restored from `gamedatas.locationActionUsedLists`.
- City selection state during a scheme: clicking on Ole's Inn / Garden image still works (i.e. the overlay doesn't intercept the click).
