# Duel Gambled Card Display on Refresh

## Bug

In the duel table, when cards are gambled they are rotated 90deg and should overlap (stack on top of each other). This worked via the live notification path but on page refresh the gambled cards rendered side-by-side with no overlap.

## Root Cause

Two code paths render gambled combat cards:

1. **Live (Notifications.js:2078-2085)** — `notif_updateRoundWithCombatStats`
   Adds `_7sfs-engaged` and `_7sfs-duel-row-combat-card-gambled` classes to **`cardDivId`** (the individual `<div>` for the card).

2. **Refresh restore (Utilities.js:1567-1571)** — duel row rebuild
   Was adding the same classes to **`divId`** (the parent `duel_round_${round}_combat` container `<td>`), not the per-card div.

The rotation/margin needs to be applied per card so each card rotates in place. Applied to the container, the cards inside still flow horizontally as inline blocks and there's no per-card rotation pivot to make them stack.

## Fix

Changed lines 1569-1570 in Utilities.js to target `cardDivId` instead of `divId`, matching the notification path.

## WHY note for future-me

Don't be tempted to "consolidate" by applying engaged/gambled classes to the row container — the CSS for `._7sfs-engaged` (`transform: rotate(90deg); margin-left:15px;`) is intentionally per-card. Each card's rotation pivots around its own center, and the `margin-left:15px` is what creates the overlap with the previous card (the rotated bounding box is narrower than the unrotated one, and the negative-ish visual gap from rotation plus the small left margin makes them stack).
