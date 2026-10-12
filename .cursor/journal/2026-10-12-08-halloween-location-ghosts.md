# Halloween location ghosts

## Context
Continuation of CITY_HALLOWEEN seasonal UX. User wants barely-visible 50px ghost sprites drifting in the background of each location side (my-cards / other-cards), max 5 per side, never over cards.

## What / Why
- Asset: `img/holiday/halloween/ghost.svg` — hand-authored sheet-ghost silhouette. WHY SVG not the GenerateImage JPG: JPG has no alpha (and the tool saved checkerboard/white bake risk); SVG stays crisp at 50px and matches other holiday transparent assets pattern.
- **Path move (later same day):** all `img/holiday/*` → `img/holiday/halloween/`. WHY: leave room for other holidays under `img/holiday/<name>/`. Call sites only in `seventhseacityoffivesails.css` (7 urls). Base reference PNGs moved too (not CSS-referenced).
- Gate: `window.CITY_HALLOWEEN === 1` → `setupHalloweenGhosts()` from Setup.js right after horseman tooltip (after Ole's/Garden destroy so destroyed locations aren't seeded).
- DOM: each `._7sfs-city-my-cards` / `._7sfs-city-other-cards` gets a `._7sfs-halloween-ghost-layer` (absolute inset 0, overflow hidden, pointer-events none, z-index 0). Ghosts are absolute children of the layer.
- WHY layer not overflow:hidden on the side itself: card fly animations can briefly leave the flex box; clipping the side would clip cards. Layer-only overflow confines ghosts without touching cards.
- WHY z-index 0: `._7sfs-card` is z-index 10 — ghosts stay under cards/chips.
- Count: 1–5 random per side (`1 + floor(random * 5)`). Cap is the hard rule; at least one so empty sides still feel haunted.
- Motion: CSS `left`/`top` linear transitions, 5–12s per leg, plus a light bob keyframe (staggered delay/duration). Slow on purpose — ambient, not arcade.
- Layout helpers under `._7sfs-city-halloween`: `position:relative`, `align-self:stretch`, `min-height:110px` (match city image), `min-width:60px`. WHY min-width: subgrid `auto` columns collapse empty sides to ~0 so ghosts would have nowhere to wander before cards arrive. Halloween-only so non-seasonal layout unchanged.
- wander retries every 100ms until layer `clientWidth/Height >= 50` — setup() runs before first layout.

## Toggle
Same as other Halloween bits: `window.CITY_HALLOWEEN = 0` disables (no class → no CSS, setupHalloweenGhosts not called).

## Watch
- Opacity is 0.14 — if Eddie says too visible/invisible, tune that one number.
- min-width:60px widens empty sides slightly during Halloween; if board looks padded early-game, drop or reconsider location-level layer.
- setTimeout wander loops for the whole session — fine for BGA page lifetime; no teardown.
