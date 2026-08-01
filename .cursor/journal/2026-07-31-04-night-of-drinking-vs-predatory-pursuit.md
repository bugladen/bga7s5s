# Night of Drinking (01109) vs Predatory Pursuit (01137)

## Report
"Reaction_01109 does not activate against Reaction_01137"

## Root cause
June 5 change (`2026-06-05-05-night-of-drinking-react-on-announce.md`) moved
01109's Risk-cancel trigger from `EventRiskPlayed` → `EventActionActivated`
for announce-on-action semantics.

Risk *Reactions* never fire `EventActionActivated`. Payment for an in-hand
reaction queues `EventRiskPlayed` + `EventRiskReactionTriggered` only
(`FrameworkActionsTrait` pay-for-reaction path). So Predatory Pursuit (and
every other Risk Reaction play, including Stubborn) stopped offering Night of
Drinking after that swap.

Stubborn cancel was previously fixed on the EventRiskPlayed path
(`2026-05-15-02`); the June 5 swap silently re-broke that activation too.
The Stubborn revertCancellation special case is fine — it just never got
reached because 01109 never activated.

## Fix
Restore an `EventRiskPlayed` handler, gated on
`areRiskReactionTriggeredEventsQueuedForSource($riskId)`.

WHY the gate: Action plays still queue EventRiskPlayed after
EventActionActivated. Without the gate, declining cancel on announce would
offer cancel again when EventRiskPlayed fires. The pending
RiskReactionTriggered is the reliable "this was a reaction play" signal —
Action plays queue ActionTriggered instead.

WHY not EventReactionActivated: that fires from CardReaction::performReaction
*before* payment (HIGHEST_PRIORITY stack). Card text requires costs paid
("All costs are still paid."). EventRiskPlayed is post-payment.

01137 needs no Stubborn-style revert — its move/wound only run inside
EventRiskReactionTriggered, which deleteRiskReactionTriggeredEvents removes.

## Files
- `modules/php/theah/DB.php` — areRiskReactionTriggeredEventsQueuedForSource
- `modules/php/theah/Theah.php` — passthrough
- `modules/php/cards/_7s5s/reactions/Reaction_01109.php` — EventRiskPlayed branch
