# In-city scheme sticks ~5px below city location

## Bug
Path to Poluchatel (any scheme in city) — bottom of the element sticks out of the city location by ~5px.

## WHY
Two leftovers from the home-scheme layout when `._7sfs-scheme-in-city` only flipped `position` to `relative`:

1. **`top: 35px` still applied** — home uses absolute + `top: 35px` to sit the crop in the home row. Under relative that offset shifts the art down without shrinking the home gap. Combined with vertical centering in the 110px city row, the crop overflows the location.

2. **`._7sfs-scheme-player-color` is `position: relative; bottom: -15px`** — only used in city (home removes the class). Relative keeps ~16px in flow and shifts the dot further down. Centered in a 110px row: layout height ≈ 75 (art) + 16 (dot) = 91 → start offset ≈ 9.5; visual bottom of the shifted dot ≈ 115.5 → **~5px past the location**. Matches the report.

Initiative/panache tops (63/87) were calibrated assuming art at `top: 35`, so zeroing art `top` required subtracting 35 for in-city boxes (28/52).

## Fix
In `seventhseacityoffivesails.css`:
- `._7sfs-scheme-in-city { top: 0 }`
- Container-scoped initiative/panache tops 28 / 52
- Container-scoped player color → `position: absolute; left: 130px; top: 55px` (same corner as the old relative hack, no flow height)

Home layout untouched (still absolute + top 35, no player-color class).

## Verify
Path / Leshiye alone at a city location — crop and color dot fully inside the colored location strip; stats still on the scheme art.
