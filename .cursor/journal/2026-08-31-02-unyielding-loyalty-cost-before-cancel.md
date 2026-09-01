# Unyielding Loyalty (01032) — cost before Night of Drinking cancel

## Bug
When opponent used Night of Drinking (01109) to cancel Unyielding Loyalty, the Thug
discard / Red Hand destroy never happened. Card text is "discard a Thug • Cancel the
effects"; 01109 says "All costs are still paid" — the additional cost should resolve
before cancel is even offered.

## Root cause
Thug discard and Red Hand destroy lived in `EventRiskReactionTriggered`, which fires
*after* `actChooseCardForReactionPaid` queues `EventRiskPlayed`. 01109 listens on
`EventRiskPlayed` (when a RiskReactionTriggered is queued for that risk) and deletes
the pending `EventRiskReactionTriggered` on cancel — so the cost handler never ran.

## Fix
Extracted `payCost()` and call it from `performReaction` when the player commits to a
Thug or Red Hand, *before* queuing `EnteringPayStateEvent` / pay transition. That
queues the discard/destroy immediately, ahead of wealth payment and `EventRiskPlayed`.

`EventRiskReactionTriggered` now only calls `clearEvents()` (drop the stored targeted
event clones) — the cancel effect confirmation step after the card is paid for.

## WHY this ordering
- Matches bullet cost • effect on the card: additional cost first, then play/pay the Risk.
- Aligns with 01109's "(All costs are still paid.)" — if 01109 cancels after announce,
  wealth + Thug/Red Hand should already be gone.
- `deleteRiskReactionTriggeredEvents` on 01109 cancel does not touch
  `EventCardDiscardedFromHand` / destroy events, so a cost queued in `performReaction`
  survives cancellation.

## Files
- `modules/php/cards/_7s5s/reactions/Reaction_01032.php`
