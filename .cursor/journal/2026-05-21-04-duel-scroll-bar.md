# Duel Table - Top Scroll Bar

## Task

Add a horizontal scroll bar at the **top** of the duel table in addition to the existing bottom one, so players viewing a wide duel table on mobile or desktop can scroll from either edge.

## What Changed

- `modules/js/Templates.js` — `jstpl_duel_table` now wraps the table in `#duel_wrapper` and adds a sibling `#duel_scroll_top` (with `#duel_scroll_top_inner`) immediately above `#duel`.
- `seventhseacityoffivesails.css` — added rules for `#duel_wrapper`, `#duel_scroll_top`, `#duel_scroll_top_inner`. Moved `margin-bottom: 10px` from `#duel` (still there) up onto the wrapper.
- `modules/js/Utilities.js` — added `setupDuelScrollSync()` and `updateDuelScrollWidth()`; called from `displayDuelTable()` and `displayDuelRow()` respectively.
- `modules/js/Notifications.js` — `dojo.destroy('duel')` → `dojo.destroy('duel_wrapper')`; placeholder placement target `'duel'` → `'duel_wrapper'`.
- `modules/js/Setup.js` — placeholder placement target `'duel'` → `'duel_wrapper'`.

## Why this approach

There is no pure-CSS way to put a horizontal scrollbar on **both** top and bottom of the same scroll container. The standard pattern is two sibling scroll containers (top is a thin "dummy" whose inner element matches the table's scrollWidth), with JS keeping their `scrollLeft` synced. That's what I did.

Alternatives considered:
- **`transform: rotateX(180deg)`** to flip the scrollbar to the top — gives a top-only scrollbar (not both). The user accepted top-only as a fallback but both is achievable, so this wasn't needed.
- **Wrap with no JS** — won't work; without JS sync the scrollbars are independent and confusing.

## Implementation notes (the WHY)

- `setupDuelScrollSync()` uses a single shared `syncing` flag to break the feedback loop where one scrollbar's `scroll` event sets the other's `scrollLeft`, which fires its own `scroll` event, ad infinitum. The flag is consumed on the next event and reset.
- `updateDuelScrollWidth()` runs after every `displayDuelRow()` because adding rounds can extend column widths (long maneuver/technique names). The header alone may not need scrolling, but later rounds do.
- `#duel_scroll_top` has `height: 16px` (and `overflow-y: hidden`) to guarantee the scrollbar track has space to render even though its inner content is only 1px tall. Without an explicit height some browsers collapse the container and hide the scrollbar.
- I introduced a `#duel_wrapper` rather than placing `#duel_scroll_top` directly as a sibling of `#duel` because:
  1. `dojo.destroy('duel')` on `notif_duelEnd` only removed `#duel` — would leave an orphan scrollbar.
  2. `dojo.place('factionHand-placeholder', 'duel', 'after')` needed to keep placing the placeholder outside the whole duel block (now `'duel_wrapper'`, 'after').
  
  A wrapper makes the lifecycle obvious: one element to destroy, one anchor for the faction hand placeholder.

## Potential gotchas for future sessions

- If a new code path mutates duel column widths *without* going through `displayDuelRow`, the top scrollbar's inner width will be stale. If that becomes a problem, call `updateDuelScrollWidth()` from wherever the mutation happens — or hook a `ResizeObserver` on `#duel_table` from `setupDuelScrollSync`.
- The `syncing` flag pattern works for typical synchronous scroll handling but can drift if a browser fires multiple scroll events out of order. If users report jitter, switch to comparing `scrollLeft` values directly and only assigning when they differ.
- `#duel_wrapper` is not styled as a flex/grid container — it's a plain block. Don't add `display: flex` without testing; the top bar relies on natural block stacking with `#duel`.
