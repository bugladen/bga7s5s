# City corner towers → circles

## Ask arc
Circles via CSS? → swap rect for circle → remove inner-quadrant border → wall join broken + inner turret became square → revert and retry.

## What failed (do not repeat)
1. CSS `border` + `border-radius` + partial side colors (transparent or city-color). Transparent showed map gaps; city-color still didn’t join the wall. Asymmetric borders + overflow clipping made the core read as a **square**.
2. Nudging -15→-17 while keeping that border model.

## Current approach (after revert)
- **No CSS border** on normal towers.
- SVG: tan disc `r=15` + black core `r=10` + black rim `r=14.5` with `stroke-dasharray` 270° (gap = city-facing quadrant). Per-corner `transform="rotate(... 15 15)"` on the rim (UL 0, UR 90, LL -90, LR 180).
- Position `-16px` so 30×30 center sits on `#city` outer corner (-1) — rim endpoints should meet the wall lines.
- Halloween: hide SVG; `border-radius:50%` + lantern paint on the host (unchanged ids for FLIP).

## WHY two fills not one stroked circle
Original `._7sfs-city-tower` was fill+stroke on one shape. Fine for rect (square core). One stroked circle plus host `border-radius`/`overflow` produced a square-looking core in practice — split disc/core keeps the turret round regardless of host chrome.

## Verify
Hard-refresh (Templates.js + CSS). Check UL join and that core is round. If rim gap faces wrong way on a corner, fix that corner’s rotate only.

## Follow-up: inset grey core
User: black core → darker grey with 3D inset.

Per-tower `radialGradient` (unique ids — shared #id would bind all fills to the first SVG).

**Bug:** CSS `._7sfs-city-tower-core { fill: #3f3f3f }` overrode `fill="url(#…)"` (CSS beats presentation attrs) → flat grey, no inset. Screenshot confirmed flat.

**Fix:** drop CSS fill; lighter high-contrast gradient (`#c4c4c4` → `#8e8e8e` → `#2a2a2a`, light at 68%/70%); add shade ring + white lit ellipse. Stroke-only on core in CSS.

## Follow-up: stone not metal
User: inset looked metallic → want stone.

Cool greys + white specular ellipse = chrome. Switched to warm stops near city Stone grey (`#c4b6a4` / `#8f8272` / `#3f3830`), removed lit ellipse, softened brown shade, added per-tower `feTurbulence` soft-light grain (unique filter ids + seeds so corners don’t clone the same noise).

## Follow-up: pull turrets in
User: sticking out too far from corner walls. `-16` → `-13`, then another nudge to `-10`. Rim/wall join looser than outer-corner centering — tradeoff for bastion proportions. Flags still use their own bottom math; check if pennants look off.

## Follow-up: Halloween turret border
User: Halloween isn’t showing turret borders.

Cause: Halloween used `3px solid var(--_7sfs-city-color)` only — same hue as `#city` wall, so the frame disappeared where the bastion overlaps chrome (and after normal towers dropped their CSS black border, there was no black rim left).

Fix: `1px solid black` + `box-shadow: inset 0 0 0 3px var(--_7sfs-city-color)` so lanterns keep a visible outline and a city-color ring over the JPG.

## Follow-up: +10% diameter
User: increase turret diameter 10%. SVG `width/height` 30→33 with `viewBox="0 0 30 30"` so geometry/dasharray stay in 30-space and scale. Halloween host 33×33. Position `-10`→`-11` to hold roughly the same inset center.

