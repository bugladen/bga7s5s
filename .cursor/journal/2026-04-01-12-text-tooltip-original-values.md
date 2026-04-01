# Text Tooltip: Show Original Character Values

## What Changed

`createTextTooltipForCharacter` in `modules/js/Utilities.js` was displaying `modifiedResolve`, `modifiedCombat`, `modifiedFinesse`, `modifiedInfluence`, `modifiedCrewCap`, and `modifiedPanache` — the in-game modified stats. Changed to display the original constructor values: `resolve`, `combat`, `finesse`, `influence`, `crewCap`, `panache`.

## WHY

The text tooltip is meant to show what the card *says* — the printed values — not the current in-game state. Modified values change during play (e.g. buffs/debuffs from attachments, schemes, events), but the hover tooltip should reflect the card's base stats as printed. The image tooltip already shows the card image (which has the original printed values), so the text tooltip should match.

The `dashed*` booleans are still used — those indicate whether a stat is printed as "-" on the card itself, which is a card property, not a runtime modification.

## Files Modified

- `modules/js/Utilities.js`: `createTextTooltipForCharacter` — changed 8 property references from modified to original values.
