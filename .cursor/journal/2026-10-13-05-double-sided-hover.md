# Double-sided card hover (IsDoubleSided / FlippedImage)

## Context
Rena (`_05Gary` / `_05GaryFlipped`) introduced two-sided cards. `FlippedImage` was already set on both faces pointing at the other art. User asked hover to show both faces, and to gate that with an explicit `$IsDoubleSided` flag rather than inferring from a non-empty `FlippedImage`.

## WHY IsDoubleSided (not just flippedImage truthy)
Empty `FlippedImage` is the normal default on every Card. Opting in via a boolean keeps dual-face hover intentional for future two-sided cards and avoids any accidental dual layout if someone sets art without meaning "show both in hover."

## Approach
- `Card.php`: `$IsDoubleSided` + serialize `flippedImage` / `isDoubleSided` in `getPropertyArray`
- Gary A/B: `IsDoubleSided = true` (FlippedImage already swapped per face)
- JS: `buildCardTooltipImagesHtml(card)` — single img, or wrapper with both when flag + flippedImage
- Wired into image-only hover (`buildImageTooltipHtml`, ability overlay path) and text hover (`prependCardImageToTextTooltip`)
- CSS: image-only = side-by-side faces; text hover = stack faces in left column (three columns would overflow); mobile stacks faces
- Dual-face **75% scale is mobile-only** (186px per face = 75% of 248). Desktop stays full 400px / ~808px pair.
- Dual faces are **side-by-side** everywhere: desktop Image+Hover (faces row + text), desktop image-only, and mobile (75% scale).
- **Bug fix:** Image+text was stacking faces because nested `_7sfs-card-tooltip-images` collapsed. Fix: unwrap sibling `<img>`s into outer `_7sfs-text-tooltip-with-image` flex row + `_7sfs-text-tooltip-double-sided` marker. Mobile uses row+wrap so text falls under the face pair.
- Mobile sizes diverge by hover mode: **image-only 186px** (tippy ~378px); **Image+text 120px** + `max-width: calc(50% - 3px)` so faces share one row inside ~85vw tippy. Do not shrink image-only to match Image+text.

## Not done / watch
- Text table still shows **current** face stats/text only — other face is readable from its art. No `FlippedText` / `FlippedTitle` yet; add if players want structured text for both sides.
- Existing in-play games need a reload / re-serialize to pick up new property keys (normal for card property changes).
