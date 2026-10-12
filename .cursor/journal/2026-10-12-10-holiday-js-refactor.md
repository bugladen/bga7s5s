# Holiday.js refactor

## Context
User: move all holiday javascript into a separate file (`holdiay.js` typo → `Holiday.js` to match Setup.js / Utilities.js PascalCase).

## What / Why
- New mixin `modules/js/Holiday.js` → `seventhseacityoffivesails.holiday`.
- Moved here: `window.CITY_HALLOWEEN` flag, board classes, horseman tippy, ghosts, horseman ride, deck-picker banner decorate.
- Setup.js only calls `setupHolidayClasses()` (early, post-HTML) and `setupHolidayFeatures()` (after Ole's/Garden destroy).
- OnUpdateActionButtons uses `decorateHolidayDeckPickerBanner(base)`.
- Wired in seventhseacityoffivesails.js define deps + declare mixins list next to setup.

WHY mixin not a free function module: needs `this.addTippyTooltip`, same pattern as every other feature slice. WHY flag lives in Holiday.js (not main): one place to flip for the season; main entry stays clean.

## Filename
Used `Holiday.js` not `holdiay.js` — typo in the ask; matches existing module naming.
