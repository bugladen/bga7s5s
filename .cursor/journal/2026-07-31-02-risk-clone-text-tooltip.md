# Risk clone text tooltips empty

## Report

Risks brought out of discard via Corpse Speak (Action_01154) — and the same clone pattern on Improvising (01106) / Vedma (01124) — show blank/wrong details in text hover tooltips.

## Root cause

`_01154_RiskClone` (and siblings) are blank `Risk` shells. Creation only copies `Name`, `Image`, wealth cost, traits (sometimes), and the action. `getPropertyArray` therefore sent empty `text`, empty `expansionName`, `cardNumber` 0, and R/P/T all 0. Image tooltips still worked because `Image` was copied.

Text tooltip UI (`createTextTooltipForRisk`) needs those printed fields — same class of gap as the earlier Risk CardNumber = 0 work.

## Fix

`RiskClonePropertyTrait` overrides `getPropertyArray` to overlay display fields from `ClonedCardId` via `getCardObjectFromDb`. Keeps clone identity (id, location, actions). Wired into all three hand RiskClones.

WHY overlay in getPropertyArray instead of expanding the copy blocks in Action_01106/01124/01154:
- One place, all three clones, survives page reload (ClonedCardId is persisted)
- Avoids forgetting a field the next time the tooltip gains a row
- Original stays in LOCATION_PERMANENTLY_HIDDEN but is still loadable by id

WHY not copy onto object properties at creation: Name/Image already copied for inject codes / visuals; tooltip path only needs the property array. Don't want three nearly-identical action blocks to drift.

## Not touched

`_02008_RiskClone` is a FactionAttachment under a character, not a hand Risk — different tooltip path. Out of scope unless reported.

## Files

- `modules/php/cards/RiskClonePropertyTrait.php` (new)
- `_01106_RiskClone.php`, `_01124_RiskClone.php`, `_01154_RiskClone.php`
