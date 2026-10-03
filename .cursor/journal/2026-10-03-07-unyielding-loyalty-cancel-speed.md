# Unyielding Loyalty (01032) — ICancelReaction + Night of Drinking speed

## Ask
User: Reaction_01032 should be a cancel reaction and trigger at the same speed as
Reaction_01109 (Night of Drinking).

## What was wrong
UL already cancelled effects in-place (`canceled=true` + clone + deleteEventBatch)
like Stubborn, but it was **not** `ICancelReaction` and used `queueEvent` for the
offer transition and pay path. Framework post-pay then `queueEvent`s Triggered at
MEDIUM — behind HIGH_PRIORITY siblings. 01109 / Stubborn / Objection all stack.

## Change
- `implements ICancelReaction` — `actChooseCardForReactionPaid` stacks
  `EventRiskReactionTriggered` + `EventRiskPlayed` so `beginCostChoice` is not
  stuck behind MEDIUM noise after wealth pay.
- Offer transition in `interceptEvent`: `stackEvent` (was `queueEvent`).
- Play path in `performReaction`: ReactionPay then EnteringPay, both
  `stackEvent` + `HIGHEST_PRIORITY` — same order/shape as 01109.
- Cost-choice transition in `beginCostChoice`: `stackEvent` so Red Hand / Thug
  picker runs immediately after stacked Triggered.

## Kept as-is (deliberate)
- Pay wealth first, then additional cost (2026-09-13-13). Do not move Thug/Red
  Hand back before EnteringPay.
- Do not `setUsed` until after cost choice — Theah skips reaction transitions
  when `!isAvailable()`.
- Immediate intercept + `releaseEvent` on Pass / failed cost / 01109
  `revertCancellation` — still the right cancel model for multi-event targeting;
  ICancelReaction only fixes the *speed* of offer/pay/Triggered, not the
  store-and-release shape.

## Files
- `modules/php/cards/_7s5s/reactions/Reaction_01032.php`
