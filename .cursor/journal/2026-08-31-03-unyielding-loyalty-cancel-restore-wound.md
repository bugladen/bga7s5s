# Unyielding Loyalty (01032) — restore effect when Night of Drinking cancels

## Bug
Bleed Out wounds a character → Unyielding Loyalty intercepts and cancels the wound
(player pays Thug) → Night of Drinking cancels Unyielding Loyalty → wound never
happens. Should: cancel removes UL's cancel, original wound resolves.

## Root cause
Same class of bug as Stubborn (01140) / Night of Drinking (journal 2026-05-15-02).
UL clones `EventCharacterBeingWounded`, sets `canceled=true`, stores clone in
`$characterWoundedEvent`. On successful reaction, `EventRiskReactionTriggered` calls
`clearEvents()` and the wound stays cancelled. When 01109 deletes that triggered
event, neither `clearEvents()` nor `releaseEvent()` runs — stored clone is orphaned.

Today's earlier fix (2026-08-31-02) only ensured Thug/Red Hand cost pays before
01109 can cancel; it did not address restoring the cancelled effect.

## Fix
- `Reaction_01032::revertCancellation()` — calls existing `releaseEvent()`, clears
  cost-choice flags, marks owner updated. Mirrors decline/pass re-queue path.
- `Reaction_01109` — when cancelled risk is `_01032`, dispatch `revertCancellation`
  on its `Reaction_01032` instance (same pattern as `_01140`).

## WHY not generic hook
Only Risks that store cancelled events need this on 01109 cancel. Today that's
01140 and 01032. Vittoria (01014) etc. are character reactions, not cancellable
by Night of Drinking.

## Files
- `modules/php/cards/_7s5s/reactions/Reaction_01032.php`
- `modules/php/cards/_7s5s/reactions/Reaction_01109.php`
