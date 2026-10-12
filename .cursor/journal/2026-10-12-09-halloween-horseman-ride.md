# Halloween horseman ambient ride

## Context
Continuation of CITY_HALLOWEEN. User wants `#city-halloween-horseman` to move ambiently between `#city-day-phase` and `#city-ur-tower` at random speed, flipping so the horse faces travel direction.

## What / Why
- Moved horseman out of `#city-day-phase-row` to a direct `#city` child in Templates.js. WHY: row is a narrow absolute box at left:160px; can't animate across to UR tower while nested there. Absolute `left` on `#city` (position:relative) matches towers / day chip band (`top: -15px`).
- CSS: `position:absolute; top:-15px; left:160px; will-change:left; z-index:2`. Dropped chip border-radius leftovers (no longer a chip sibling).
- Native art faces **left**. Going right → `scaleX(-1)`; going left → identity. Flip set at leg start with **no** transform transition so it snaps, not morphs.
- `setupHalloweenHorseman()` in Setup.js — same setTimeout/CSS-transition loop as ghosts. Duration 4–12s random per crossing (ambient).
- Endpoints from getBoundingClientRect relative to `#city` (subtract borderLeft). Phase chip often `display:none` early (row hidden until turnPhase>0) → zero rect; fallback `left: 160` matching `#city-day-phase-row`.
- Left endpoint = just right of phase chip (`phaseRect.right + 4`), not on top of the text. WHY: old in-row home was beside the chip; covering "High Drama" at rest would annoy.
- Right endpoint = horseman's right edge meets `#city-ur-tower` left (`towerLeft - SIZE`). WHY: user doesn't want him crossing over the tower — turn around at its left side.
- Gate: called next to ghosts when `CITY_HALLOWEEN === 1`.

## Watch
- If crossings feel too slow/fast, tune the 4000 + random*8000 window.
- Re-measures every leg so phase-chip width changes (Dawn → High Drama) and late row show update the left park spot.
- setTimeout loop for session lifetime — same as ghosts; no teardown.
