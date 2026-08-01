# Auto-Acknowledge Card Reveals (pref 110)

## Goal

Players with pref 110=Yes should not be nudged to the table to click Ok on card-reveal acknowledges.

## Final approach / WHY

**Server-side** during state init: deactivate those players before they stay multiactive.

WHY server, not client:
- BGA turn notifications fire for multiactive players. Client auto-click still means "you have a turn" / go-to-table nudge.
- Clearing in `st*` / `onEnteringState` means they never remain active → no nudge.
- Reveal is still in the game log; chooseList Ok UI is only for players who want to see it (pref No).

Dedicated inits (not the generic `stMultiPlayerInit*`) so Patricia discard `01095` and other non-reveal multiplayer states are untouched:
- `stMultiPlayerInitCardRevealAcknowledge`
- `stMultiPlayerInitCardRevealAcknowledgeSansInitiatingPlayer`
- `clearCardRevealAcknowledgeForPlayersWithPreference()` shared helper

## Client auto-click removed

Eddie: frontend auto-ack not needed once server clears pref-110 players at state init. Removed `addCardRevealAcknowledgeButton`; Ok buttons restored to plain `actOk` → `onMultipleOk`.

## Files

- `modules/php/StatesTrait.php` — server helpers
- `states.7s5s.php` — action wiring
- State_* files (01098_2, 01090, 01077, 03043)
- `seventhseacityoffivesails.js` / `gamepreferences.json` — pref 110
- JS Ok buttons: `OnUpdateActionButtons.7s5s.js` / `.faf.js` (no auto-click)
