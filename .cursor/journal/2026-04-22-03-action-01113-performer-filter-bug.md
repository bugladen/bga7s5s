# Robbery (01113) / Liberating Goods (01167) / Improvising (01106) — Stuck State from Self-Wealth Counting

## The Bug

When `isAvailableToPlayer` runs, the Risk card (Robbery/Liberating Goods) is still in the player's hand. `attachmentsAvailableFromOpponentDiscardPile` calls `handWealthCount`, which includes the Risk card itself. If the Risk card is the player's only source of wealth, the check passes — "yes, you can afford the 1-cost attachment."

But when the action triggers, the Risk card moves from hand to PURGATORY (FrameworkActionsTrait.php ~line 933) **before** the action's states begin. Hand wealth drops. The player enters state `highDramaPhase01113` with no affordable attachments and no way to back out — stuck.

## WHY This Approach

First instinct was to filter performers more strictly in `getPerformersForAction`. Wrong — the user corrected me: it's the same performer, same opponent. The issue is temporal: the hand changes between availability check and action execution.

Considered handling the stuck state defensively (auto-resolve when no targets). Rejected because:
- It's a symptom fix, not root cause
- Would require adding pass actions to active player states + frontend changes
- The action shouldn't be offered in the first place if the player can't complete it

Instead, fixed at the source: `isAvailableToPlayer` now accounts for the wealth that will leave the hand when the action is played:
- The Risk card itself (1 wealth, or 2 if it has Wealth trait)
- Cards used to pay the Risk's WealthCost

Added optional `$wealthAdjustment` parameter to `attachmentsAvailableFromOpponentDiscardPile` in Theah.php. Default 0 keeps all other callers unchanged. The `isAvailableToPlayer` methods compute `-(selfWealth + WealthCost)` and pass it.

This is slightly conservative (doesn't account for possible WealthCost discounts), but conservative is correct here — better to not show an action than to show one that causes a stuck state.

## Files Changed

- `modules/php/theah/Theah.php` — `attachmentsAvailableFromOpponentDiscardPile` gets `$wealthAdjustment` parameter
- `modules/php/cards/_7s5s/actions/Action_01113.php` — `isAvailableToPlayer` passes wealth adjustment
- `modules/php/cards/_7s5s/actions/Action_01167.php` — same fix, same pattern (Liberating Goods has same bug)
- `modules/php/cards/_7s5s/actions/Action_01106.php` — `isAvailableToPlayer` now checks adjusted wealth against opponent Risk WealthCost (was missing affordability check entirely). Also adds `getActionFromHandDiscount` to mirror what `getArgsFromAction` state 01106_2 already does.

### Defense-in-depth: Pass button on state 01113

Even with the `isAvailableToPlayer` fix, added a pass button to `highDramaPhase01113` as a safety net — if the player somehow ends up in the state with no valid opponents, they can exit gracefully instead of being stuck.

- `modules/php/States/_7s5s/State_highDramaPhase01113.php` — added `actFromCardPass` PossibleAction, "pass" transition to `HIGH_DRAMA_PLAYER_TURN_EVENTS`, zombie uses "pass"
- `modules/php/cards/_7s5s/actions/Action_01113.php` — `actFromActionPass` creates `actionResolvedEvent` and transitions "pass"
- `modules/js/OnUpdateActionButtons.7s5s.js` — shows red Pass button only when `opponents.length === 0`

## CalculatePayDiscount Event Ordering Bug

### The Bug

When `EnteringPayStateEvent` fires in the event loop, its EventHub handler queues a `CalculatePayDiscountEvent`. But by default it was **queued** (appended to the end of the event queue). If a `TransitionEvent` was also in the queue (which it always is — it's the event that transitions the player into the pay state), the TransitionEvent would fire BEFORE the CalculatePayDiscountEvent was processed.

Result: the player enters the pay state (`01113_3`) with the `DISCOUNT` global unset. The leftover `CalculatePayDiscountEvent` sits in the DB and is only processed after the player confirms payment, when `HIGH_DRAMA_PLAYER_TURN_EVENTS` runs again. At that point the discount calculation is retroactive and useless.

### WHY This Approach

The fix: always **stack** the `CalculatePayDiscountEvent` (highest priority) instead of queuing it. This ensures it processes immediately — before the TransitionEvent that moves the player into the pay state.

This is the right semantic: "calculate the discount NOW, then transition." The `wasStacked` conditional was unnecessary — whether the parent `EnteringPayStateEvent` was stacked or queued, the child discount calculation should always run before the transition.

### File Changed

- `modules/php/theah/EventHub.php` — `EnteringPayStateEvent` handler now always calls `stackEvent` instead of conditionally choosing between `stackEvent` and `queueEvent`.

### Scope

This affects ALL pay states, not just `01113_3`. Any state that uses `EnteringPayStateEvent` + `TransitionEvent` had the same latent ordering bug. The fix is correct for all of them because the discount should always be calculated before entering the pay state.

## Pattern Note

Any RiskAction whose `isAvailableToPlayer` checks `handWealthCount` (directly or via utility methods) is vulnerable to this. The card is still in hand during the check but won't be when the action executes. When auditing similar cards, look for this pattern: an action that requires spending hand wealth where the action's own card contributes to that wealth.
