# Yevgeni (Reaction_01116b) discount vs Corpse Speak clones

## Report

Reaction_01116b not applying -1 when paying for risks recovered via Action_01154 (Corpse Speak).

## Root cause (two layers)

### 1. Wrong cardId on EnteringPayState (primary for clones)

Action_01154 (same pattern on 01106 Improvising / 01124 Vedma) did:

```php
createEnteringPayStateEvent(..., $riskCard->Id, PAY_STATE_IN_HAND_ACTION, $newActionId)
```

`$riskCard` is the original, already moved to LOCATION_PERMANENTLY_HIDDEN. The hand clone `$card` owns the cloned action (`setOwnerId($card->Id)`).

Reaction_01116b stores `$event->cardId` as `DiscountedCardId`, then only adds discount when `$action->OwnerId == $this->DiscountedCardId`. Original ≠ clone → activate appears to work but DISCOUNT global never gets the -1.

Normal hand play uses `$action->getOwningCard()->Id` (FrameworkActionsTrait). Performer-required clone path also goes through that framework method after choose-performer — so it was already correct. The broken path is specifically the no-performer branch that Action_01154/01106/01124 fire themselves.

WHY fix at the pay-event call sites (pass `$card->Id`), not in the reaction: the event cardId is the contract for “what you’re paying for.” Matching FrameworkActionsTrait keeps all discount consumers consistent (01116b, 03013, future ones).

### 2. Performer-null gate in getActionFromHandDiscount

```php
if ($owner && $performer && $owner->ControllerId == $performer->ControllerId)
```

`calculateInHandPayDiscount` only loads a performer when `RequiresPerformerSelected`. Risks like Action_01162 leave `$performer` null → discount never applied even with matching card IDs. Same bug on normal hand play, not just clones. Same copy-paste in Reaction_03013 (Daniella).

WHY compare to `$action->getOwningCard()->ControllerId` instead of performer: the card being paid for is what matters; performer is optional for in-hand pay.

## Fix

- Action_01154 / 01106 / 01124: pass `$card->Id` into createEnteringPayStateEvent; WHY comments left in place
- Reaction_01116b + Reaction_03013: controller check via action owner

## Not finished / watch

If someone “fixes” the clone pay events back to `$riskCard->Id` thinking “we’re recovering that card,” they’ll re-break every DiscountedCardId matcher. The clone is what lives in hand and owns the action.