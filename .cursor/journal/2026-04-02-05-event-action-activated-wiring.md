# EventActionActivated Wiring

## What

Wired up `EventActionActivated` as a framework-level event that fires when any action (in-hand, in-play, or location) has been committed. This gives other cards a hook to react to "an action has been announced."

## WHY this approach

Eddie needs an upcoming card to react when actions are committed. The challenge was finding the right "point of no return" for each action flow:

- **In-hand actions**: Clear commit point at payment (`actPayForInHandAction`), but the action's `handleEvent` also calls `announceAction` when `EventActionTriggered` fires after payment.
- **In-play actions**: No single framework-level commit point. The player can back out even after `EventActionTriggered` is queued. Each action commits itself inside its own `handleEvent`, but they all call `announceAction()` when they do.
- **Location actions**: Commit immediately when `EventActionTriggered` fires in `handleEvent`. They don't call `announceAction()` since they extend `LocationAction` -> `Action`, not `CardAction`.

## WHY `announceAction` for card actions

Putting the event inside `CardAction::announceAction()` covers both in-play and in-hand card actions with zero duplication. Every card action calls `announceAction` when it commits. We considered adding it to `actPayForInHandAction` for in-hand actions, but that would have been a duplicate since `announceAction` already fires for those too.

## WHY separate hook for location actions

Location actions (`OlesInnAction`, `GovernorsGardenAction`) extend `LocationAction` -> `Action`, not `CardAction`. They don't have `announceAction()`. We considered refactoring `announceAction` up to the base `Action` class, but that would require adding `Id` to the base class (currently defined separately on `CardAbilityTrait` and `LocationAction`). Eddie chose the simpler two-hook approach over the refactor.

For location actions, `sourceId` is `0` since they don't have an owning card.

## Files changed

- `modules/php/theah/events/EventActionActivated.php` — renamed from `EventActionAactivated.php` (typo fix)
- `modules/php/theah/Events.php` — added `ActionActivated` constant
- `modules/php/EventFactory.php` — added `createActionActivatedEvent` factory method + import
- `modules/php/cards/actions/CardAction.php` — queues `EventActionActivated` inside `announceAction()`
- `modules/php/theah/actions/LocationAction.php` — queues `EventActionActivated` inside `handleEvent()` for `EventActionTriggered`
