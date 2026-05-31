# City view: player's own cards displayed to the LEFT of the city-image

## Task
In the city display, cards with `controllerId === this.player_id` should render on the LEFT of the `_7sfs-city-image`, while everyone else's cards render to the right (as before). Additionally, all `_7sfs-city-image` elements must be vertically aligned across the 5 location rows — if Docks has 3 of my cards and Forum has 0, the Forum's `_7sfs-city-image` must shift right by the same amount so the images line up vertically.

## Approach

### DOM structure (Templates.js)
Each `_7sfs-city-location-contents` already had:
```
[frontcap] [city-image] [other-cards...] [endcap]
```
Cards were placed via `dojo.place(html, '<location>-endcap', 'before')` — i.e. sibling of the endcap on the right side of the image.

I added a new sibling container BEFORE the city-image, with an internal anchor element:
```
[frontcap] [<loc>-my-cards [<loc>-my-cards-endcap]] [city-image] [other-cards...] [endcap]
```

The anchor (0×0) is needed because `dojo.place(..., target, 'before')` places the new element as a previous sibling of `target`. To insert into the my-cards container we route to its inner endcap anchor.

### Routing (Utilities.js)
`getTargetElementForLocation(location, playerId)` already takes a playerId param. I extended it: when `location` is one of the 5 city locations AND `playerId == this.player_id`, it returns the my-cards endcap instead of the regular endcap. All existing callers (Setup.js, Notifications.js) were already passing `card.controllerId` as the second arg — this was a no-op for city locations before, but now matters.

### Setup.js
City-card setup loops (Ole's Inn, Docks, Forum, Bazaar, Garden) used hard-coded `'dock-endcap'` etc. as the placement target. Replaced each with a call to `getTargetElementForLocation(<LOC>, card.controllerId)` so the routing is centralized.

### Vertical alignment (`alignCityImages`)
Added `alignCityImages()` in Utilities.js. It:
1. Resets `min-width: 0` on all 5 `_7sfs-city-my-cards` containers
2. Measures each container's `scrollWidth`
3. Sets `min-width` on all 5 to the max measured

Called at the end of Setup.js, and after every notification that adds/removes a card from a city location: `notif_cardMoved`, `notif_cardMustered`, `notif_characterRecruited`, `notif_cityCardAddedToLocation`, `notif_schemeMovedToCity`, `notif_cardDiscardedFromPlay`, `notif_cardRemovedFromPlay`.

### CSS
Added `_7sfs-city-my-cards` matching the parent contents' inline-flex/row-gap so cards stack horizontally with the same gap. `justify-content: flex-end` pushes cards to the right edge (against the image). `_7sfs-city-my-cards-endcap` is `width: 0; height: 0` — just an invisible insertion anchor.

## WHY this approach over alternatives

**Why JS-based alignment rather than pure CSS Grid/subgrid?** I considered making `#city` a CSS grid with subgrid columns so all rows share a single grid context. But `#city` contains the 4 towers (absolutely positioned), discard/locker piles, day indicator, AND the 5 location rows with separator divs between them. Forcing a single grid context through all those siblings via `display: contents` got messy fast. JS alignment of 5 elements is a few lines, runs after each relevant notif, and isolates the concern.

**Why an inner 0×0 anchor instead of changing placement to `'last'` inside my-cards?** `createCharacterCard`/`createAttachmentCard`/etc all hard-code `dojo.place(..., targetDiv, 'before')`. Changing every card creator to accept a placement param would touch a lot of code. Adding a 0×0 anchor inside the my-cards container is one line and lets us reuse the existing 'before' placement semantics.

**Why `scrollWidth` rather than `getBoundingClientRect().width`?** `scrollWidth` returns the natural content width even when the element is being held wider by `min-width`. Since we reset min-width to 0 before measuring, both work — `scrollWidth` is just slightly clearer-intent.

## Risk / things to watch
- Spectators: `this.player_id` is not a valid playerId for a spectator, so `parseInt(playerId) === parseInt(this.player_id)` is always false → all cards route to the original endcap (right side). My-cards containers stay empty (width 0), so all city-images line up at the left — same as before. ✓
- During `notif_cardMoved`, the slide-and-attach animation runs BEFORE the call to `alignCityImages`. That's intentional — slide first, then realign once card has actually landed.
- `notif_cardMustered` and `notif_cityCardAddedToLocation` call `alignCityImages` BEFORE the grow-from-scale-0 animation, which means the alignment is based on the card's full size from the start. Good — alignment is synchronous and won't visually shift during the grow.
