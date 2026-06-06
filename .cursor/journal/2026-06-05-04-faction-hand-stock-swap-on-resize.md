# Faction hand stock-type swap on resize across mobile breakpoint

## Problem
`factionHand` is constructed once in `Setup.js` based on a single `isMobile`
evaluation at setup time — picking `HandStock` (desktop) or `LineStock`
(mobile, no fanning). The mobile CSS at `seventhseacityoffivesails.css:1427+`
flattens the layout with `!important` when the viewport drops past
`max-width: 768px` (or `max-height: 500px` landscape), but the JS object
isn't swapped. So a desktop player who shrinks the window past the breakpoint
ends up with the mobile CSS layout backed by a `HandStock` — selection
delegation, sort behavior, and anything that asks the stock for its type or
internal state is wrong for the layout it's actually in.

Symptom the user described: "the hand is shown locked in its container but
it is still a HandStock vice LineStock."

## Fix
Extract stock construction into a helper that re-reads the breakpoint, then
swap the stock instance on resize when the breakpoint is crossed.

- `Utilities.js#isFactionHandMobile` — canonical breakpoint check. Mirrors
  the CSS media query exactly. `adjustHandCardOverlap` and `setupFloatingHand`
  now call this instead of inlining the test.
- `Utilities.js#createFactionHandStock` — builds the right stock type on
  `#factionHand`, wires `onSelectionChange`. Used by Setup and by the swap path.
- `Utilities.js#swapFactionHandStockIfNeeded` — compares
  `this.factionHandIsMobile` to the live breakpoint state and rebuilds when
  they disagree.
- A resize listener installed at the top of `setupFloatingHand` calls the
  swap on every resize. This listener is registered BEFORE the early-return
  for mobile-at-startup, so a player who starts mobile and resizes wider
  still gets a swap to `HandStock`.

## Why clone-and-replace the #factionHand element
bga-cards `CardStock.bindClick` attaches a delegated click listener on the
container element. The listener is an anonymous closure — no way to
`removeEventListener` it. If we just construct a new stock on the same
container, both old and new stocks have click listeners on it and clicks
fire twice. Cloning shallow (`cloneNode(false)`) and replacing the node
drops the listener cleanly.

The card divs that were inside `#factionHand` get detached when the
container is swapped out. They aren't orphaned — `factionHandManager` still
tracks them in its card map. When we then call `newStock.addCard(card)` for
each, bga-cards sees the manager has a div for the card and the old stock
still "contains" the card, so it calls `moveFromOtherStock` and re-parents
the existing div into the fresh container. Inline styles applied by
`applyFactionHandCardStyle`, tippy tooltips bound by element ID, and any
chip children (cats-embargo target, etc.) all travel with the div.

The old stock instance is left to garbage collection. We don't call its
`remove()` because that would detach the (already-detached) old container
element, which is harmless but pointless, AND because doing so before the
swap would clear the old stock's `cards` array — and bga-cards needs that
populated so `originStock.contains(card)` returns true during the
`moveFromOtherStock` lookup. The simplest correct order is: pull cards out
of old stock via `getCards()`, replace the container DOM, construct new
stock, then `newStock.addCard(card)` for each.

## Why not just rely on the mobile CSS
Considered just leaving the JS object alone (`HandStock` everywhere) and
trusting the CSS to flatten the layout. Rejected because:
- Sorts differ in spirit (LineStock at construction passes `center: false`
  to leave the hand left-aligned in its scroll container).
- The `animationManager` is omitted for mobile to avoid the move animations
  on every state change — keeping `HandStock` keeps the animation manager
  active, which is what we explicitly chose not to do on mobile.
- Anything in the codebase that does `instanceof HandStock` (none right now,
  but we shouldn't bake in the assumption) would behave wrong.

## Knock-on changes to setupFloatingHand
The original `setupFloatingHand` early-returned on mobile, so the
`MutationObserver` on `#factionHand`, the scroll listener, and the
`onResize` (overlap + check) handler were never installed for
mobile-at-startup users. After a mobile→desktop swap, the floating UI
wouldn't work for them.

Fix:
- Don't early-return on mobile. Install everything.
- `doCheck` runtime-checks `isMobile()` and bails out (also resets
  `isCurrentlyFloating` so the next desktop-side check starts fresh —
  otherwise stale state could leave a class behind).
- The MutationObserver is reinstalled after each stock swap, since the
  container node it was watching has been replaced. I wrapped
  `this.swapFactionHandStockIfNeeded` from inside `setupFloatingHand` to
  add the disconnect/reattach. The earlier-registered resize listener
  resolves `this.swapFactionHandStockIfNeeded` at call time, so it picks
  up the wrapped version.

## Third iteration — sibling approach (the right one)
The promise-chain fix still left exactly one ghost card in the desktop→mobile
direction. Reading the actual bga-cards source explained why:
`CardManager.getCardElement` is literally `document.getElementById(getId(card))`.
When we cloned-and-replaced #factionHand, every card div was detached from the
document — getElementById returns null for elements in detached subtrees. So
in `addCard`:

```ts
if (originStock?.contains(card)) {
    let element = this.getCardElement(card);   // null — detached
    if (element) { ... moveFromOtherStock ... }  // SKIPPED
}
// needsCreation = true
const newElement = this.manager.createCardElement(card, ...);  // BLANK div
promise = this.moveFromElement(card, newElement, ...);
```

So clone-and-replace never moved the original divs at all — it spawned blank
new ones, and we relied on `applyFactionHandCardStyle` to repaint each. The
one-ghost issue was a race in that repaint (the animationManager queued
something on one card differently than the others — I don't fully understand
why exactly one, but the fix doesn't depend on that diagnosis).

**The sibling approach.** Keep the old container in the document; create a
fresh sibling and reassign the `factionHand` id to it. Now when addCard runs:

- `getCardElement(card)` is `document.getElementById(cardId)` → returns the
  existing div (still in document under the renamed-but-attached old container)
- `moveFromOtherStock` runs: `addCardElementToParent` appendChild's the existing
  div into freshEl — DOM move, no recreation
- `origin.removeCard` checks `this.element.contains(this.getCardElement(card))`
  — old container no longer contains the div (it just got moved) → false →
  `manager.removeCard` is NOT called → the div is safe

The cards' inline background-image, tippy tooltips, child chips (cats-embargo
target), and the entire inner `bga-cards_card-sides` DOM all ride along with
the existing div untouched. No ghost frame is possible because no card is
ever recreated.

After all moves, the old container is drained (empty) and renamed; remove it
from the DOM. Also `manager.removeStock(oldStock)` so its container-level
click listener can be GC'd.

## Second ghost-frame gotcha — asymmetry across the breakpoint
After the first applyFactionHandCardStyle fix, the user reported the
desktop→mobile direction *still* showed one ghost frame, while mobile→desktop
was clean. That asymmetry was the clue.

The CardManager is constructed ONCE at setup time and its `animationManager`
is set from `isFactionHandMobile()` at that moment — `undefined` on mobile
start, our real one on desktop start. On swap we keep the same manager.

So when a desktop session shrinks to mobile, `addCard` goes through the
animated `moveFromOtherStock` path. bga-cards rebuilds the card's inner side
DOM *during* the animation (asynchronously, after addCard returns). Our
synchronous `applyFactionHandCardStyle` painted the background onto the OLD
sides moments before the lib wiped them — ghost frame. Mobile-start sessions
never animated, so the painting ran on the final sides and looked fine.

Fix: chain `.then(...)` on addCard's returned Promise so the style application
runs after the rebuild settles. Why only ONE ghost card and not all? Probably
because subsequent addCards interleaved into already-rebuilt elements by the
time the synchronous loop hit them — first card hits mid-animation, the rest
settle. Either way, .then() makes the timing deterministic.

I considered alternatives: rebuilding the CardManager on swap to match the
new mode's animation policy (loses the cardId→div map), pre-attaching divs
to the fresh container before constructing the new stock (might bypass
moveFromOtherStock but unclear and brittle to lib internals), or passing
some `{ forceAnimation: false }` setting to addCard (not documented for our
lib version). The promise chain is the lowest-risk fix.

## Ghost-frame gotcha after first cut
First swap pass called `addCard` without re-applying `applyFactionHandCardStyle`,
assuming bga-cards' `moveFromOtherStock` would preserve our inline styles on
the existing card div. It doesn't — `addCard` rebuilds the card's inner DOM
(the `bga-cards_card-sides` / front / back structure). The library's setupDiv
callback is supposed to repopulate that on rebuild, but Setup.js has a comment
right there: "Apply styling directly since setupDiv callback doesn't work."
So the lib leaves the inner sides blank and we apply the background image
inline ourselves.

That means EVERY `addCard` call has to be paired with `applyFactionHandCardStyle`.
The original setup loop does that. The swap loop now does too. Without it,
the swap produces visibly empty card frames for a moment until something
else triggers a re-style.

Same logic applies to the cats-embargo chip — it's a child of the card
element (sibling of `bga-cards_card-sides`), and `addCard`'s inner rebuild
can drop it. The swap re-places the chip if the card's conditions still
include the target marker. Guarded with `getElementById(chipId)` so we don't
double-place if a future bga-cards version stops nuking siblings.

## Order of resize listeners
Two resize listeners now exist on `window`:
1. The swap listener (registered first, at the top of `setupFloatingHand`).
2. The original `onResize` (registered later, for `adjust + checkFloating`).

Browsers fire listeners in registration order, so the swap runs before
the adjust/check on every resize — meaning by the time `adjustHandCardOverlap`
runs, `this.factionHand` is already the correct type and
`document.getElementById('factionHand')` resolves to the fresh container.
