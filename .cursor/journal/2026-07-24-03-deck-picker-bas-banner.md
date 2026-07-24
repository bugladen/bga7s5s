# Deck picker banner — BAS overlay + badge children

## Context
Prior session (2026-07-12): Tac on `::before`, FAF on `::after` so FAF paints on top. Both pseudo-elements full.

## What Eddie asked
1. Add `img/deckpicker/bas.jpg`, 20px right of the `::after` image (FAF), rotated -18deg.
2. Then: convert tac/faf to children too (more expansions coming).

## Approach / WHY
Pseudo-elements max out at two. Converted all badges to real children with shared `._7sfs-deck-picker-banner-badge` + per-expansion class for image/transform only.

- Template order: tac → faf → bas (DOM order = paint order; newest on top)
- No z-index needed
- Shared layout on `.badge`; each expansion only sets `background-image` + `transform`

Transforms preserved:
- Tac: `-215px` / `+60px` / `-20deg`
- FAF: `-200px` / `+15px` / `-15deg`
- BAS: `-180px` / `+15px` / `-18deg` (−200+20)

## Adding the next expansion
Add another `<div class="_7sfs-deck-picker-banner-badge _7sfs-deck-picker-banner-XXX">` after bas in Templates.js, plus a CSS rule with image + transform. Done.

## Status
Converted. Eddie nudged transforms (FAF Y/rotation, BAS Y/rotation; faf.png→faf.jpg).

## Darken tac/faf 25%
`filter: brightness(0.75)` on `-tac` and `-faf` only — BAS left full brightness so newest badge reads brighter. Same pattern as faction images (`brightness(0.8)`).
