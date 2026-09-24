# Mobile tooltip hold delay doubled

User: for mobile only, double the time needed for the hover to appear.

## Change

`_getTippyBaseOptions` in `Utilities.js`: `touch: ['hold', 250]` → `touch: ['hold', 500]`.

## WHY

Desktop hover still uses `CARD_TOOLTIP_DELAY` / `STOCK_CARD_TOOLTIP_DELAY` via tippy `delay: [delay, 0]`. Mobile uses tippy's touch-hold mode so short taps can select in-play cards without stealing the click (see `2026-08-04-01-mobile-tippy-blocks-card-select.md`). Only the hold duration needed bumping — not the desktop delays.

Was briefly at 500 before, then cut to 250 for snappier text checks. User wants the longer press again (accidental hover opens while selecting).

## Not changed

`CARD_TOOLTIP_DELAY` (1000) and `STOCK_CARD_TOOLTIP_DELAY` (500) in `seventhseacityoffivesails.js` — those are mouse-hover delays, desktop-facing.
