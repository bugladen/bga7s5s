# Approach Deck Mobile Stacking Bug

## The Report
User reported cards in the Approach Deck are stacked vertically left-aligned on mobile browser. Eddie has only tested on desktop Chrome.

## Root Cause Analysis

The approach deck uses `ebg.stock`, which calculates card layout positions (absolute positioning) based on `dojo.marginBox('approachDeck').w`. The stock calculates:
```
perLines = Math.max(1, Math.floor(control_width / (108 + 5)))
```

If `control_width` < 226px, `perLines = 1` → single column → vertical stacking.

### Why Desktop Works
Desktop CSS (`@media min-width: 769px`) sets `min-width: 1300px` on `#approachDeck-container`. This guarantees the inner `#approachDeck` div always has sufficient width for multiple cards per row, even during initial setup before the DOM is fully laid out.

### Why Mobile Breaks
Mobile CSS only sets `background: #c9a87c` on `#approachDeck-container`. No `min-width`, no `overflow`, no explicit width management. The stock width calculation is entirely dependent on `dojo.marginBox` returning the correct value.

BGA's framework applies CSS `zoom` to the game area on mobile (`autoscale: true` default). The game doesn't override `onScreenWidthChange`. CSS `zoom` interacts differently with `offsetWidth` (used by `dojo.marginBox`) across browsers:
- Chrome 128+: `offsetWidth` returns unzoomed layout width → works
- Older browsers / Safari: `offsetWidth` may return zoomed visual width → potentially too small

The stock only recalculates on `window.onresize`, which CSS zoom doesn't trigger. So a bad initial measurement persists.

## Game Interface Width
`gameinfos.inc.php` has `game_interface_width.min = 740`. The CSS breakpoints are at 768/769px. These are complementary (no gap).

## WHY `min-width: 1300px` Exists on Desktop
The desktop CSS also has `width: fit-content` on the container. Since stock items are absolutely positioned (don't contribute to content size), `fit-content` would shrink the container to just the label text width. `min-width: 1300px` prevents this collapse. This is essentially a width safety net that mobile doesn't have.

## Key Files
- `seventhseacityoffivesails.css` lines 1109-1133 (desktop), 1388-1390 (mobile)
- `modules/js/Setup.js` lines 260-287 (stock creation)
- `modules/js/Utilities.js` lines 1397-1435 (showApproachDeckAtTop/Bottom - no updateDisplay call)
- `gameinfos.inc.php` line 93 (game_interface_width.min = 740)

## Possible Fixes
1. Add mobile CSS for approach deck with min-width or flex layout
2. Override `onScreenWidthChange` to call `this.approachDeck.updateDisplay()`
3. Add `requestAnimationFrame` callback after setup to re-trigger `updateDisplay()`
4. Consider the BGA-recommended viewport approach (override `onScreenWidthChange` to remove zoom and use viewport meta tag instead)

## Fix Implemented

Added mobile CSS in the `@media (max-width: 768px)` block that overrides `ebg.stock`'s absolute positioning with flexbox:

- `#approachDeck-container`: `overflow-x: auto` for horizontal scrolling
- `#approachDeck`: `display: flex; flex-direction: row; flex-wrap: nowrap` — bypasses stock's width calculation entirely. `height: auto !important` overrides the JS-set inline height.
- `#approachDeck .stockitem`: `position: relative !important; top: auto !important; left: auto !important` — overrides both BGA framework CSS (`position: absolute`) and the inline styles that `updateDisplay()` calculates. The `!important` beats inline styles without `!important`.

### WHY this approach over alternatives
- **Why not `min-width` on the container?** It would force the stock to recalculate correctly, but the zoomed `dojo.marginBox` issue would still affect which row/column each card lands in. Also doesn't help if `offsetWidth` returns 0.
- **Why not `onScreenWidthChange` override?** It would fix zoom-triggered recalculation but not the initial render timing. Also adds JS complexity.
- **Why not `requestAnimationFrame` callback?** Would only fix timing issues, not CSS zoom interaction.
- **Why flexbox override?** It completely sidesteps the stock's positioning logic on mobile. No matter what `dojo.marginBox` returns, the cards are positioned by CSS flexbox. This is the same pattern used for `#factionHand` mobile. Robust and simple.

### Side effect: stock animations on mobile
The stock's `dojo.fx.slideTo` animation (used when adding/removing cards) will be a no-op on mobile because `top: auto !important` and `left: auto !important` override whatever the animation tries to set. Cards will appear/disappear without sliding. This is acceptable — the faction hand also skips animations on mobile.
