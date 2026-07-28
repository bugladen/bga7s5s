# Text Tooltips Include Card Image

## Ask
Eddie wants Card Hover Style = Text to show the card image (same art as Image preference) *above* the existing full-text stats table — not replace it.

## Approach
Central helper `prependCardImageToTextTooltip(card, textHtml)` in `Utilities.js`. All five `createTextTooltipFor*` builders (Character / Scheme / Attachment / Event / Risk) wrap their table HTML through it.

WHY one helper instead of inlining in each builder: every call site already funnels through those five functions (board, hand, approach deck, log, reaction buttons via `_applyTooltipToNode` / `createTooltipForCard`). One change covers all.

WHY not `buildImageTooltipHtml`: that helper adds a conditions overlay under the image. Text tooltips already include conditions via `conditionsRow()` in the table — duplicating them under the image would be noisy.

## CSS side effect (intentional)
Existing rule `.tippy-box[data-theme~='7sfs']:has(._7sfs-card-tooltip-img)` locks tippy width to 400px / 248px mobile. Text-only tooltips used to allow up to 500px. Now that text tooltips contain an img, they lock to image width. WHY accept that: image on top looks wrong if the tippy is wider than the card; matching widths is the point of the earlier width-lock (see `2026-04-30-06-tooltip-width-lock.md`). Added `margin-top: 8px` + `max-width: none` on the nested `._7sfs-basic-tooltip` so the table doesn't fight the outer lock.

## Unchanged
Image preference path (`buildImageTooltipHtml`, in-play controller overlay with traits/abilities) untouched. Minimal name-only log fallback (no card object) still text-only — no image available.

## Test
Set preference to Text, hover a character / attachment / risk on board, in hand, and in the log. Expect: card art on top, full table below, ~400px wide.

## Font size
Eddie asked to drop text-tooltip font from 18px/18pt → **12pt** → **10pt**. Both `._7sfs-basic-tooltip` and the tippy-themed override stay in sync.

## Side-by-side layout
Stacked image-above-text ran off the bottom of the screen. Switched `._7sfs-text-tooltip-with-image` to `display: flex; flex-direction: row` — image left, text right. Text column kept at **400px** (248px mobile), same as when it was under the image. Image-only width lock now uses `:not(:has(._7sfs-text-tooltip-with-image))` so combined tooltips can grow to ~image+text width.

Mobile (≤768px / short landscape) reverts to **column** stack (image above text). Tippy width is `calc(100vw - 16px)` so the text box fits the screen instead of a fixed 248px that could overflow or leave unused space.

## Viewport fit
Combined tippy capped at `calc(100vw - 16px)`. Text column uses `flex: 1 1 400px; min-width: 0` so it shrinks below 400px when the viewport is too narrow for image+text side-by-side. Image can also flex-shrink. Plain text tooltips: `max-width: min(500px, calc(100vw - 24px))`.
