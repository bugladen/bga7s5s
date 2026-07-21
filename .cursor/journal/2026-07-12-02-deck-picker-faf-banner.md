# Deck picker banner — FAF overlay

## What

Added `faf.png` to `._7sfs-deck-picker-banner-image` via `::before`, mirroring the existing `tac.jpg` on `::after`.

## Why ::before not ::after

`::after` already owns Tac (Tooth and Claw). Pseudo-elements are one-per-side, so FAF goes on `::before`. Same sizing/positioning pattern as Tac so they read as a pair of expansion badges on the banner.

## Transform

- Tac: `translate(calc(-50% - 220px), -50%) rotate(-20deg)` — left of center
- FAF: `translate(calc(-50% - 200px), -50%) rotate(18deg)` — Eddie corrected from +20 to -200 after seeing it

Sat too close to center at +20; now nearly mirrors Tac (-220) at -200, both on the left with opposite rotations.

## Stacking / rotation tweak

Eddie wanted FAF on top of Tac and rotated -18deg (not +18). Swapped pseudo-elements: Tac on `::before`, FAF on `::after` so paint order puts FAF above without needing z-index. FAF still at -200px / -18deg; Tac still -220px / -20deg.
