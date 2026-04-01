# Text Tooltip: Show Original Character Values + Risk CardNumber Fix

## What Changed

### Character tooltip: original values instead of modified
`createTextTooltipForCharacter` in `modules/js/Utilities.js` was displaying `modifiedResolve`, `modifiedCombat`, `modifiedFinesse`, `modifiedInfluence`, `modifiedCrewCap`, and `modifiedPanache` — the in-game modified stats. Changed to display the original constructor values: `resolve`, `combat`, `finesse`, `influence`, `crewCap`, `panache`.

### WHY
The text tooltip is meant to show what the card *says* — the printed values — not the current in-game state. Modified values change during play (e.g. buffs/debuffs from attachments, schemes, events), but the hover tooltip should reflect the card's base stats as printed. The image tooltip already shows the card image (which has the original printed values), so the text tooltip should match.

The `dashed*` booleans are still used — those indicate whether a stat is printed as "-" on the card itself, which is a card property, not a runtime modification.

### Risk CardNumber = 0 bug
66 Risk card constructors never set `$this->CardNumber`, so it defaulted to 0 from `Card::__construct()`. The card number is derived from the filename: `_01076.php` → CardNumber = 76 (first 2 digits = expansion, last 3 = card number). Added `$this->CardNumber = N;` after `ExpansionNumber` in all 66 files. 16 Risk files already had it set correctly.

### WHY the number wasn't set before
The original card implementations for Characters/Schemes/Attachments all set CardNumber, but Risk cards were apparently written without it. It only became visible when the text tooltip was added (preference 100 = Text), since the image tooltip just shows the card image which has the number printed on it.

## Files Modified

- `modules/js/Utilities.js`: `createTextTooltipForCharacter` — changed 8 property references from modified to original values.
- 66 Risk card files in `modules/php/cards/_7s5s/`: Added `$this->CardNumber = N;` to constructors.
