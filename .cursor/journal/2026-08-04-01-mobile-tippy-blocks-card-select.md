# Mobile tap shows tippy instead of selecting card

## Symptom
On mobile, tapping a selectable in-play character shows Hover Text/image tippy but does not select the card.

## Cause
Tippy is attached to `${card.divId}_image` — the same node that gets `onclick` → `onCardInPlayClicked` when the card is made selectable. Tippy default `touch: true` treats the first tap as "show tooltip". On touch (esp. iOS) that steals/focuses the interaction so selection never sticks.

## Fix
In `_getTippyBaseOptions` (Utilities.js): `touch: ['hold', 500]`.

WHY hold globally (not only when `_7sfs-selectable`):
- Selectable class is added/removed per state; tippy options are set at create time
- Same conflict exists for any tippy'd clickable (action buttons, locations with tippies)
- Short tap = act; long-press ~500ms = read tooltip. Matches card-game mobile norms

Desktop mouseenter + delay unchanged.

## Verify
1. Mobile / DevTools touch: enter a choose-character state
2. Quick-tap a highlighted character → should select (confirm enables)
3. Long-press same card → tooltip should appear
