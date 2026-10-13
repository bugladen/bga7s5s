# Halloween chase stops short on mobile

## Context
User: on mobile, horseman + villagers don't travel all the way to the UR tower.

## Cause / Why
`endpoints()` used `getBoundingClientRect()` deltas to compute CSS `left` for `#city-halloween-chase`. BGA applies CSS `zoom` on the game area for mobile. Native GBR returns scaled viewport coords; CSS `left` is layout px — so the tower target was undershot by ~zoom factor. Desktop (zoom 1) looked fine.

BGA docs call this out: use `getBoundingClientRectIgnoreZoom` for positioning inside the zoomed area. We used `offsetLeft` / `offsetWidth` instead — same unscaled layout space as absolute children of `#city`, no framework helper dependency.

## Fix
Holiday.js `endpoints()`:
- phaseX = `phaseRow.offsetLeft + phase.offsetLeft + phase.offsetWidth + 4` (fallback 160 when row hidden / width 0)
- towerLeft = `tower.offsetLeft`
- ready gate on `city.clientWidth`

Removed `relLeft` + borderLeft GBR math.

## Watch
If a future browser treats `offsetLeft` under `right:-15px` oddly for the jackolantern tower, fall back to `city.clientWidth - tower.offsetWidth + 15` (matches `right: -15px`). Current offsetLeft path matches desktop GBR math when zoom=1.
