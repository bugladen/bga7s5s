# EventEnteringPayState Added to Recruit and Brute Flows

## Context

Grand Merchant Anghos (02021) has a City Reaction: "When discarding cards to pay costs - One of your attachments discarded this way gains Wealth." Implementing this requires a reaction hook that fires *before* payment validation, since gaining Wealth changes how many cards are needed to pay.

The existing `EventEnteringPayState` event is that hook -- it fires before the player enters the payment UI, and reactions can intercept it. But two payment flows lacked it: recruit mercenary and play brute.

## What Changed

Added the standard events/reactions/pay-for-reaction state triplet to both flows, following the equip-attachment-from-hand pattern exactly.

**Recruit mercenary:**
- `CHOOSE_MERCENARY` now transitions to `CHOOSE_MERCENARY_EVENTS` (was `PAY_FOR_MERCENARY`)
- 3 new states: `_EVENTS` (4230), `_REACTIONS` (4231), `_PAY_FOR_REACTION` (4232)
- `actHighDramaRecruitActionMercenaryChosen` now fires `createEnteringPayStateEvent` with `PAY_STATE_RECRUIT_MERCENARY`

**Play brute:**
- `PLAY_BRUTE` now transitions to `PLAY_BRUTE_EVENTS` (was `PAY_FOR_BRUTE`)
- 3 new states: `_EVENTS` (4800), `_REACTIONS` (4801), `_PAY_FOR_REACTION` (4802)
- `actHighDramaBruteActionBruteChosen` now fires `createEnteringPayStateEvent` with `PAY_STATE_PLAY_BRUTE`

## WHY: Discount Preservation in Theah.php

Critical catch: `calculateInHandPayDiscount` always writes to `Game::DISCOUNT` at the end, defaulting to 0 if no pay state type matches. For recruit and brute, the discount is calculated *earlier* in the flow (parley discount for recruit, brute discount for brute) and stored in globals before the event fires. Without a matching case, the EventHub's chained `EventCalculatePayDiscount` would clobber the discount to 0.

Fixed by adding a case for `PAY_STATE_RECRUIT_MERCENARY` and `PAY_STATE_PLAY_BRUTE` that re-reads the existing discount from globals, preserving the value set earlier. This is different from the other pay state types which calculate their discount fresh inside `calculateInHandPayDiscount`. WHY the asymmetry: for recruit/brute, the discount depends on earlier player choices (parley, etc.) that are resolved in separate states before reaching the payment step.

## Files Modified

- `modules/php/States.php` — 6 new state constants
- `modules/php/Game.php` — 2 new `PAY_STATE_*` constants
- `states.inc.php` — 6 new state entries (3 per flow), 2 transitions rewired
- `modules/php/FrameworkActionsTrait.php` — `createEnteringPayStateEvent` calls in both selection methods
- `modules/php/theah/Theah.php` — discount preservation for new pay state types

## No JS Changes

The new states use generic names (`stRunEvents`, `playerReaction`, `playerPayForReaction`) already handled by the existing JS. No client-side changes needed.
