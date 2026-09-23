# Reaction_04042 — empty-hand fire + Pass + shuffle + Yevgeni pay hook

## Context inherited
Kaj `_04042` journal (2026-09-16) deliberately **kept** a pre-prompt wealth gate. User reversed that, then: **no affordability gates at all** (Yevgeni pay-time discount). Then: emit EnteringPayState before click-to-pay so Leader Yevgeni gets his Reaction window.

## User ask
1. Fire even if hand empty (0-cost Artifacts in City Deck)
2. Pass button when nothing affordable
3. Shuffle City Deck after action complete
4. No affordability gates (Yevgeni can discount during pay)
5. Search labels `(Cost: X)`; host labels without cost
6. Right before pay card choices, Yevgeni Leader needs opportunity for discount Reaction
7. Yevgeni discount must persist through Back + another Artifact pick
8. (this) Generalized to IPayTimeCostDiscount — Yevgeni 01116b + Daniella 03013

## Why EnteringPayState
`Reaction_01116b` listens on `EventEnteringPayState` (not on click-to-pay itself). Standard equip pay states emit it; Tomas/Monet/Kaj in-reaction click-to-pay did not — so Yevgeni never prompted. Fix: before stage=pay, set `CHOSEN_PERFORMER` + queue `createEnteringPayStateEvent(..., PAY_STATE_EQUIP_ATTACHMENT)`. Yevgeni stacks his reaction ahead of Kaj's requeue. `refreshPaymentCost()` re-runs `getEquipDiscount` (sees `IsActive`) when pay UI loads / on pay clicks. If cost drops to 0, show Equip/Pass instead of hand cards.

## Why retarget DiscountedCardId / IPayTimeCostDiscount
Pay-time discounts scope -1 to `DiscountedCardId` (Stubborn mid-chain bleed fix). After Activate, Back to search and pick Artifact B left DiscountedCardId on A → getEquipDiscount missed. Interface `IPayTimeCostDiscount` + Kaj walks player-controlled `IHasReactions` cards for any active implementor (01116b, 03013). Clear on finalize/finishWithoutEquip so the -1 cannot leak to a later unrelated equip this turn (Kaj does not emit ActionResolved).

## Change summary
- Trigger: attachable hosts only (no wealth)
- Buttons: all attachable; Pass always; search `(Cost: X)`; hosts `Equip to %s`
- Pay: EnteringPayState + refreshPaymentCost + confirmEquip when free after discount
- Shuffle on finalize + finishWithoutEquip when `$didSearch`

## Crystal Eye 01200 crash on Approach
Equipping Crystal Eye via Kaj during Approach (state 220) queued Forced transition `"01200"`, only registered on HD/Duel EVENTS → GS1 impossible transition. Wired `PLANNING_PHASE_APPROACH_CARDS_PLAYED_01200(+_2)` returning to Approach EVENTS + JS handlers. Yevgeni free-equip (Back from 2-cost → Crystal Eye cost 0 on host pick) just made it fire earlier; any Crystal Eye equip via Kaj Approach would have hit the same gap.
