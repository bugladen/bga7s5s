# Unyielding Loyalty — no Red Hand/Thug prompt after wealth

## Report
Opponent played Andriana Dondolo Action_02001 (move target then challenge).
Defender played Unyielding Loyalty, discarded a hand card for Wealth, then never
got the second prompt to destroy a Red Hand or discard a Thug.

## Root cause
Pay-first flow (2026-09-13-13): Play → wealth pay (Risk to Discard) →
`EventRiskReactionTriggered` → `beginCostChoice()` → second `reaction` transition.

Great Game dusk fix (2026-09-19-11) made `Theah::runEvents` skip card reaction
transitions when Location is `Locker-*` **or** `Discard-*`. Post-pay UL is already
in Discard when that second transition drains → silently skipped. Cancel stays
half-applied (held event never cleared via cost choice / `clearEvents`).

Same class of break for any RiskReaction post-pay stage that re-queues
`createReactionTransitionEvent` (e.g. Vantage Point 04020).

## Fix
Keep Locker skip only. Discard is where played Risks live during post-pay UI;
`isAvailable()` / missing-card fail-closed still covers orphaned transitions.

## WHY not revert pay-first
Printed order is wealth then additional cost. Night of Drinking still gets costs
via `revertCancellation()` → `beginCostChoice()`.

## Not Action_02001
UL correctly intercepts `EventCardMoving`. Challenge skip via
`shouldIssueChallenge` is separate. Bug was framework discard gate.

## Files
- `modules/php/theah/Theah.php`
