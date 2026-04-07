# Yevgeni (01116b) Discount Scope Bug + Negative Cost Display

## The Scenario
Player plays 01133's action. Yevgeni's Reaction_01116b triggers → player activates -1 discount → pays for 01133. Later in the same action chain, 01133's movement event fires → Stubborn's reaction triggers → player accepts → enters pay state for Stubborn's reaction. Yevgeni's -1 discount was still active and incorrectly applied to Stubborn's payment, producing a -1 displayed cost.

## Bug 1: Discount persists beyond the intended payment

### Root Cause
The four discount methods (`getActionFromHandDiscount`, `getReactionFromHandDiscount`, `getEquipDiscount`, `getManeuverFromCombatCardDiscount`) only checked `IsActive` + controller match. They didn't verify the card being discounted was the same card that triggered the reaction. The `IsActive` flag only reset on `EventPlayerTurnEnd`, `EventActionResolved`, or `EventDuelEndOfRound` — but mid-action-chain reactions (like Stubborn triggering on a movement event) happen BEFORE `EventActionResolved`, so `IsActive` was still true.

### Fix
Added a card ID check to each discount method:
- `getActionFromHandDiscount`: `$action->OwnerId == $this->CardId`
- `getReactionFromHandDiscount`: `$requestedReaction->OwnerId == $this->CardId`
- `getEquipDiscount`: `$attachment->Id == $this->CardId`
- `getManeuverFromCombatCardDiscount`: `$combatCard->Id == $this->CardId`

`$this->CardId` is set from the `EventEnteringPayState` that originally triggered the reaction, so the discount now only applies to the exact card being paid for.

### WHY not just reset IsActive earlier?
Considered resetting on the next `EventEnteringPayState`, but the ABNORMAL_FLOW re-entry path fires another `EventEnteringPayState` for the same card when the discount activates. Resetting on any new `EventEnteringPayState` would immediately undo the activation. The card ID check is targeted — it scopes the discount to the correct card without interfering with the re-entry flow.

## Bug 2: Wealth cost chip displays -1

### Root Cause
Status bar wealth cost chips (`jstpl_status_bar_wealth_cost_chip`) calculated `cost - discount` inline without flooring at 0. The card element's cost divs all had `discountedCost < 0 ? 0 : discountedCost` but the status bar chip never did.

### Fix
Added `Math.max(0, ...)` to all 12 status bar cost calculations across `OnEnteringState.js` (7 places) and `OnEnteringState.7s5s.js` (5 places). This is a defensive fix — even with Bug 1 resolved, a discount should never produce a negative display cost.

## Files Changed
- `modules/php/cards/_7s5s/reactions/Reaction_01116b.php` — scoped discount to matching CardId
- `modules/js/OnEnteringState.js` — 7 status bar cost floors
- `modules/js/OnEnteringState.7s5s.js` — 5 status bar cost floors
