# Approach Deck FLIP Animation

## What Was Done

Added a slide animation to the approachDeck container when it moves between its "top" position (above the city, during planning phase) and its "bottom" position (below city, dimmed, during other phases).

## How It Works

Uses the FLIP (First, Last, Invert, Play) animation technique:
1. Record the element's `getBoundingClientRect()` before the DOM move
2. Perform the `dojo.place()` move (instant DOM relocation)
3. Record the new `getBoundingClientRect()`
4. Animate from the old position to the new using `translateY` with the delta

The opacity is also animated to match the dimming class (`_7sfs-dimmed` = opacity 0.5):
- Moving to top: fades from 0.5 → 1.0
- Moving to bottom: fades from 1.0 → 0.5

## WHY This Approach

- FLIP is the standard technique used elsewhere in this codebase (see `notif_factionHandCardSentToBoard` in Notifications.js)
- Web Animations API (`element.animate()`) is used throughout the project, never dojo animation utilities
- Animation respects `animationManager.animationsActive()` so it's skipped when animations are disabled
- The DOM move still happens synchronously, so callers don't need to await — the animation is purely visual

## Key Change: Arrow Functions → Regular Functions

The original `showApproachDeckAtTop` and `showApproachDeckAtBottom` were arrow functions (`() => {}`), which meant `this` was not bound to the game object. Converted to `function()` to access `this.animationManager`. This was necessary but is also a correctness fix — any future code in these functions that needs `this` would have failed with arrow syntax.

## Files Modified

- `modules/js/Utilities.js`: Rewrote `showApproachDeckAtTop` and `showApproachDeckAtBottom`
