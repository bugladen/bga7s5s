# Image Tooltip Width Lock

User asked to lock image tooltip width to the card width.

## Problem
Image tooltips built by `buildImageTooltipHtml` and the inline html in `addCardImageTooltip` (Utilities.js ~491, 519) include the card image plus optional conditions / abilities overlay. Previously the tippy-box used `maxWidth: 'none'` (Utilities.js:73,100) and the image had `max-width: 400px`. When conditions text was long, the tippy-box would stretch wider than the card image, breaking the visual.

## Fix
Locked `.tippy-box[data-theme~='7sfs']:has(._7sfs-card-tooltip-img)` to a fixed 400px (desktop) / 248px (mobile) width using `:has()`. Also changed the image rule to `width: 100%` so it fills the locked container.

WHY use `:has()`: the same `7sfs` theme is used for both basic text tooltips (which can be up to 500px wide) and card-image tooltips. We only want to lock width when the tooltip actually contains a card image. `:has()` is the cleanest selector — no JS, no extra theme variant.

WHY `!important` on max-width: had to override the existing tippy default-stylesheet max-width set elsewhere; the previous `maxWidth: 'none'` option in JS would compete with the CSS otherwise.

Mobile breakpoint kept at 248px to match the existing `_7sfs-card-tooltip-img` mobile rule (50% of natural 495px width).

Did not touch the JS — kept `maxWidth: 'none'` on the tippy options so basic text tooltips remain unaffected.
