# Halloween chase villagers

## Context
Continuation of CITY_HALLOWEEN horseman ride. User wants two villagers in front of `#city-halloween-horseman` so it looks like he's chasing them.

## What / Why
- Added `villager-1.svg` / `villager-2.svg` under `img/holiday/halloween/`. WHY SVG not GenerateImage PNG: same rationale as ghosts — crisp alphas at ~18px, no baked checkerboard, matches holiday sprite pattern.
- Two different dress/palette silhouettes (brown tunic vs blue dress + red hair) so they read as a pair, not clones. Both face **left** like the horseman PNG.
- Wrapped horseman + villagers in `#city-halloween-chase` in Templates.js. WHY group instead of animating three lefts: one `left` transition + one `scaleX` keeps formation locked. Villagers sit to the **left** of the horseman in DOM; `scaleX(-1)` for rightward legs flips the whole pursuit so they stay visually ahead.
- Renamed `setupHalloweenHorseman` → `setupHalloweenChase`; animates the chase group. `SIZE` 31 → 72 (group width) so tower endpoint is still chase-right-edge meets tower-left.
- Chase `pointer-events: none`; horseman `pointer-events: auto` so the Happy Halloween tippy still works.
- Staggered run-bob keyframes on villagers (0.35s, 2px) — flavor of fleeing without competing with the slow ambient crossing.

## Watch
- Wider chase (72px) shortens the travel corridor vs solo horseman — if it feels cramped near the phase chip when facing right (horseman on visual left of box), may need a small phaseX nudge.
- Child `transform` bob + parent `scaleX` compose fine; don't put a transform transition on the chase group or the face snap will morph.

## Follow-up: boundary-only turn + horseman bob
User: don't switch directions until horseman hits the boundary; give horseman his own bounce.

WHY the old group `scaleX` was wrong: flipping the 72px chase box at turnaround kept `left` fixed but swapped which edge the horseman occupied — he visually jumped ~41px into/past the tower before the return leg, reading as a mid/early direction switch.

Fix:
- Chase.left tracks horseman only (width 31). Villagers offset ahead in JS (`-22/-40` leftbound, `36/54` rightbound).
- `scaleX` on each sprite, not the group — horseman world position stays put on turnaround; villagers pop to the new ahead side.
- Next leg starts on `transitionend` for `left` (plus duration+50 safety). Face/formation only changes inside `ride()` / `boot()`, i.e. after a boundary hit.
- Horseman bob via `top` keyframes (0 → -3px, 0.48s); villagers bob via `margin-top`. WHY not transform: JS owns `transform` for facing.

## Follow-up: per-villager independent reverse (reverted)
User: when each villager hits the boundary, reverse direction on their own.

Tried independent `#city` sibling patrols with per-sprite `left`. User rejected: "They should always stay ahead just ahead of the horseman, not on their own tracks. The horseman is chasing them."

Restored formation chase: villagers back inside `#city-halloween-chase`, offsets `34/50` (rightbound) / `-20/-36` (leftbound) so they stay just ahead. One `left` on chase; reverse only on chase `transitionend`. Per-sprite `scaleX` kept (no group flip jump).

Lesson: "reverse on their own" meant per-sprite facing at the shared boundary — not independent tracks.

## Follow-up: staggered individual turns
User: "They should not all turn together as one, they should turn individually" (while still staying ahead of the horseman).

At chase `transitionend`: `turnToward` flips lead villager (v2, furthest ahead) first, then v1 (+170ms), then horseman (+340ms); each slides to the new ahead offset via CSS `left` transition. Return leg starts after the stagger. Formation chase preserved during the crossing; only the boundary pivot is individualized.
