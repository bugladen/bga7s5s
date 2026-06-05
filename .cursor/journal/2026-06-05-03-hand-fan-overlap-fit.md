# Hand fan overlap dynamic fit

## Problem
When the faction hand is floating and the player has many cards, the row exceeds the viewport and edges of cards on both sides get clipped. The `_7sfs-floating-hand-container` has `max-width: 90vw` but the inner `#factionHand` cards are positioned via the bga-cards HandStock — they have `width: 108px !important` and use a negative `margin-left` driven by `--card-overlap` (set to `40px` at construction). With nowrap flex + fixed card widths the total row width can blow past the container, so the centered fan extends outside the viewport.

## bga-cards mechanism (and the gotcha)
Per `hand-stock.scss` (thoun/bga-cards): each card except the first gets `margin-left: calc(-1 * var(--card-overlap))`. The constructor sets `--card-overlap` as a CSS custom property on the container element.

**First attempt failed.** I set `--card-overlap` from JS and nothing happened visually. Reason: **this project does not load the bga-cards stylesheet.** Only the JS lib is loaded via `getLibUrl('bga-cards', '1.x')` — there's no companion CSS include. So the lib sets the CSS variable but no rule consumes it. The actual current spacing comes from our own `gap: 5px` rule on `#factionHand` and the cards have no margin at all.

The TypeDoc summary at `/data/game-libs/bga-cards/1.0.13/docs/` also misleadingly says `cardOverlap` is a percent — the source confirms it's a CSS length string with default `60px`. Don't trust that TypeDoc summary; look at the SCSS.

## Fix
Two parts:

1. **CSS (`seventhseacityoffivesails.css`)** — added a rule that owns the negative-margin behavior locally, since the lib's CSS isn't loaded:
   ```css
   #factionHand .bga-cards_card + .bga-cards_card,
   ._7sfs-floating-hand-cards .bga-cards_card + .bga-cards_card {
       margin-left: calc(-1 * var(--card-overlap, 0px));
   }
   ```
   Default is 0 (no overlap), so the small-hand visual stays exactly as before.

2. **JS (`Utilities.js#adjustHandCardOverlap`)** — only kicks in when the row would overflow. The math accounts for the `gap: 5px` on the container: with flex gap + negative margin-left on subsequent cards, total = `cardWidth + (N-1)*(cardWidth + gap - overlap)`. Solve for `overlap ≥ cardWidth + gap - (available - cardWidth)/(N-1)`, clamp to `[0, cardWidth + gap - 18]`. The `available` is `0.9 * innerWidth - 60` to account for the container's `max-width: 90vw` plus padding/edge-card rotation buffer. When the required overlap is 0, I `removeProperty` to leave the cascade clean.

Wired up:
- Initial call inside `setupFloatingHand` (after the isMobile early-return).
- On window resize (was already a listener for `checkFloating` — split into a new `onResize` that runs adjust + check).
- Inside the externally-exposed `this.checkFloatingHand`, which Notifications.js calls after card add/remove. This is what makes the overlap shrink as cards come into the hand.

Skipped on mobile because mobile uses LineStock with a horizontally scrollable container — the `--card-overlap` variable doesn't apply there and overflow is already handled by scroll.

## Why not change CSS only?
A pure CSS solution (e.g. clamp-based on viewport with `--card-overlap: clamp(...)`) would need to know `cardCount` to be correct. CSS can't read DOM child count directly without container queries on `:has()` counts (not portable enough). JS is the right place.

## Why not call adjust on every scroll?
Scroll doesn't change card count or viewport width — recomputing every scroll frame would be wasted work. So adjust only runs on resize and on the hand-state-change path (`checkFloatingHand`).

## Initial-load timing gotcha
After the first fix, the user reported "on page refresh, the spacing is not being adjusted." The function WAS being called from `setupFloatingHand` after `gamedatas.factionHand.forEach(card => factionHand.addCard(card))`, but at that moment bga-cards (with the animationManager attached) hasn't necessarily landed the card divs inside `#factionHand` yet — initial cards animate in from elsewhere. So `querySelectorAll('.bga-cards_card').length` was 0 (or low) at the time of the call, and `cardCount <= 1` returned early.

Fix: install a MutationObserver on `#factionHand` that re-runs `adjustHandCardOverlap` on any child change. That catches every card insertion (initial fan, draws, reactions, etc.) regardless of when it actually lands in the DOM, and means we no longer have to manually hook every notification path. Kept the explicit initial call + a `requestAnimationFrame`-deferred follow-up as belt-and-suspenders.

Also originally tried the adjacent-sibling selector `.bga-cards_card + .bga-cards_card`. Reverted to `:not(:first-child)` (what the bga-cards SCSS itself uses) to be tolerant of any non-card siblings the lib might splice in.

## Available-width gotcha
First version of the math used `0.9 * innerWidth - 60` as the target, mirroring the container's `max-width: 90vw`. Once overlap kicked in, the fan packed into that smaller box and the resulting ~5%-each-side empty band looked like wasted margin (user feedback).

Changed to `innerWidth - 60` (full viewport minus ~30px each side for hover scale / scrollbar). The container's `max-width: 90vw` stays in place but doesn't constrain visual output: `#factionHand` has `overflow: visible`, the cards have explicit `width: 108px !important` (don't shrink to fit), and `justify-content: center` on both the wrapper and the hand keeps the fan centered when its line width exceeds the container. So the fan visibly extends past the 90vw box and uses near-full screen width — which is exactly what we want when overlap is in effect.
