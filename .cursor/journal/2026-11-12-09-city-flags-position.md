# City flags at bottom corners

## Ask
Position `img/flags.png` at the bottom of the city; horizontal flag pole aligned with the center of both city corners (ll/lr towers).

## What
- `Templates.js`: `#city-flags` div inside `#city` (before ll/lr towers)
- CSS: absolute, `left:0; width:100%` so ends sit on tower horizontal centers; `bottom:1px` + `translateY(calc(100% * (1 - 15/270)))` so pole y≈15 in the 820×270 asset lands on tower vertical centers; pennants hang below. Towers `z-index:2`, flags `z-index:1` so finials tuck under the corner blocks.

## WHY these numbers
- Towers: `left/right:-15px`, 30×30 SVG + 1px border → border-box center at padding-edge horizontally and `bottom:1px` vertically (`-15 + 16`).
- Pole wood in `flags.png` is roughly y=10–20 (brown span); used 15 as center. Finials slightly inset so width:100% puts gold tips just inside the tower centers — reads as mounted in the corners.

## Not done
No JS; pure CSS. Didn’t touch Halloween lantern towers beyond inheriting the same z-index on ll/lr.

## Follow-up: 75% scale
User: scale down 25%, keep centered. `width:75%; left:12.5%` — same vertical pole alignment. No longer spans tower centers horizontally (deliberate).

## Follow-up: 50% scale
User: 50% of original. `width:50%; left:25%`.

## Follow-up: homes clearance
User: move homes down so they don't obscure flags. Absolute flags take no layout space. Added `--_7sfs-city-flags-scale: 0.5` + `--_7sfs-city-flags-clearance: 110px`; `#city` bottom margin uses the clearance (was 20px). Flags width/left derived from the scale var. WHY 110px: hang ≈ scale×cityWidth×255/820 ≈ 0.155×cityWidth; 110 clears ~700px city + gap. If city is much wider and tips still clip, bump the clearance var.
