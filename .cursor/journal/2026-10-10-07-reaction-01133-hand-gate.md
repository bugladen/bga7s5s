# Reaction_01133 — offer hand-gated with WillEngage reset

## Bug
On `EventEnteringPayState`, clearing `WillEngage` required `Location == HAND`, but stacking the Engage/Pass reaction only checked `cardId == owner` + unengaged performer. Suite had pinned this as SUSPECTED BUG.

## Fix
Nested the offer inside the same hand-gated block as the WillEngage reset. WHY: Risk pay/engage chooser only makes sense from hand; HD flow already pays from hand so this was latent, but the asymmetric gates were wrong and the pin called it out.

## Test
Flipped `EnteringPay while not in hand still offers…` → asserts neither clear nor offer when off-hand.
