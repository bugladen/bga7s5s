# Mobile Hover Text + image tooltip width

## Ask
Eddie: on mobile, Hover Text (image + text) doesn't fit the screen. Shrink the container for image+text by 50% width.

## Context
Combined tooltip uses `._7sfs-text-tooltip-with-image` (see `2026-07-28-02-text-tooltip-with-image.md`). Desktop is side-by-side (~400+400). Mobile already stacks column and was set to nearly full viewport (`calc(100vw - 16px)`) so text wouldn't clip at a fixed 248px.

## Change
In the mobile tippy media query (`max-width: 768px` / short landscape), the tippy that `:has(._7sfs-text-tooltip-with-image)` uses:

`width/max-width: calc((100vw - 16px) * 0.75)`

(Was briefly 0.5 — Eddie said too small, bumped to 75%.)

Inner container stays 100% of that tippy. Image `max-width: 100%` follows the box. Image-only tippy still 248px.

## Side clipping
Mobile tippies near edge cards were hanging off the left/right of the screen.

Cause: tippy already used `appendTo: document.body`, but default Popper `preventOverflow` keeps `tether: true`, so a wide (75vw) Hover Text box stays glued to an edge reference and can't fully slide in.

Fix in `Utilities.js` `_getTippyBaseOptions` (shared by both create paths):
- `strategy: 'fixed'`
- `preventOverflow`: padding 8, `rootBoundary: 'viewport'`, `tether: false`, `altAxis: true`
- `flip` with same padding / rootBoundary

CSS assists: `box-sizing: border-box` on `.tippy-box[data-theme~='7sfs']` so the 2px border counts inside the 75% width; mobile root `max-width: calc(100vw - 16px)`.

## Font size
Mobile Hover Text body (`._7sfs-text-tooltip-with-image ._7sfs-basic-tooltip` in the mobile tippy media query) set to **9pt** (file may show Eddie's later tweak). Desktop stays 10pt. Scoped to the combined image+text tooltip only.

## Image vs text vertical fit
Eddie set container to **95%** (later tuned to **85%** in CSS). Full-width stacked image still ate vertical space → text clipped at bottom.

Image width in combined mobile tooltip: **60%** of container (`width/max-width: 60%; height: auto`), centered. Was 50%, then bumped to 60% per Eddie.

## Unfinished
If text still clips on very short landscape phones, next options: max-height + scroll on the text column, or shrink image further.

## Related (2026-08-04)
Mobile selection vs tippy: see `2026-08-04-01-mobile-tippy-blocks-card-select.md` — `touch: ['hold', 500]` so taps select and long-press shows tooltip.

