# Matushka's Efficiency (01133) — Engage does not waive Wealth

## Bug
Eddie: engaging the performer for Efficiency's Action does not remove the play cost.

## Root cause (known footgun from Song)
`Reaction_01133` set `WillEngage = true` on Engage, and `Action_01133::getActionFromHandDiscount` adds `WealthCost` when that flag is set — but **never recalculated** `Game::DISCOUNT` after the choice.

WHY the early calc is wrong: `EventEnteringPayState` has `runEventHubAfterCards=true`. Cards stack the engage Reaction Transition first; EventHub then `stackEvent`s `CalculatePayDiscount`, which gets a *lower* priority and runs **before** the Transition — with `WillEngage` still false. Stacking the Transition does not pre-empt the discount. Pay UI then locked in 0 discount.

Same bug Song (`_03060`) hit; Pattern A.7 already warned: "01133 does not currently recalc discount after Engage — fix Song-style if Efficiency ever regresses."

## Fix
- `Reaction_01133::performReaction` Engage path: after setting `WillEngage` + queueing engage, call `calculateInHandPayDiscount(..., PAY_STATE_IN_HAND_ACTION, ...)` (mirror `Action_03060` / `Reaction_01116b` / `Reaction_03013`).
- Pass also sets `ABNORMAL_FLOW` (was Engage-only) so Back on pay does not re-enter `EnteringPayState` / re-prompt.
- `Action_01133::getActionFromHandDiscount`: gate on `$action->Id == $this->Id` + explanation string (Song hygiene).

## Not migrated
Left as legacy RiskReaction (not GameState like Song). Recalc is enough; Cesca copies Efficiency so keep reaction shape unless Eddie asks for full migrate.

## Skill
Updated Pattern A.7 note — 01133 now does recalc; footgun still documented for WHY.
