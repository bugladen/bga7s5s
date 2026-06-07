# Commanding (_03020) Implementation

Eisen Risk, WealthCost 0, 0/2/2 with dashed Riposte. Traits Virtue + Stalwart.

Text:
- **Leader Action:** Target an opposing character • Move them Home.
- **Leader Reaction:** When Renown would be moved from this location • Cancel the movement.

## "Leader" reading

"Leader Action:" and "Leader Reaction:" — performer-trait gate (like Strega/Duelist), not flavor. NOT a Sorcerer ability. Enforced by checking the player's Leader via `getLeaderByPlayerId(playerId)` (each player has one Leader at most).

"This location" in the Reaction = **the player's Leader's location**. The Risk is in hand, so "this" can't refer to the Risk's location. The Leader is the only "this" the printed text can sensibly bind to. Gate the Reaction's trigger on `$event->fromLocation == $leader->Location`.

## Action shape

`RiskAction implements IAbilityThatTargetsCharacters`. "Action:" (not "City Action:"), but the "Target an opposing character" filter naturally excludes home performers (a home Leader has no opposing characters at their home).

**No `RequiresPerformerSelected`.** Each player has one Leader. Fetching it directly with `getLeaderByPlayerId` is cleaner than running the framework's performer-selection state with a single eligible option. `isValidTargetForAbility` resolves the Leader fresh each time from the Risk's `ControllerId`, so no `CHOSEN_PERFORMER` global is needed.

State: `HIGH_DRAMA_PLAYER_TURN_03020 = 403020` (state-id convention `4<NNNNN>`). GameState class with `"targetChosen" => HIGH_DRAMA_PLAYER_TURN_EVENTS` (also `"zombie"`). `actFromCardWithId` dispatches into the framework. Modeled directly after `State_highDramaPhase03011` (same target-picker shape).

Move event: `createCardMovingEvent(..., Game::LOCATION_PLAYER_HOME, $engage = false, ...)`. The character knows their owner; the framework routes them to the correct player's home.

Risk class marked `IRiskThatTargetsCharacters` (the Action targets a character). Sibling skills require this marker on the Risk class itself, not the Action.

## Reaction shape (Pattern D.2-adjacent)

`RiskReaction`, triggers on `EventRenownMovingBetweenLocations`. Gates:
1. `isAvailable()` (Risk not yet used this turn — RiskReactions reset per turn).
2. Owner in hand (`Location == Game::LOCATION_HAND`).
3. Player controls a Leader.
4. Leader's location == `$event->fromLocation`.

On match: clone `batchId`, `fromLocation`, `toLocation`, `amount` onto the reaction; queue `createReactionTransitionEvent`.

### Why delete in `EventRiskReactionTriggered` (post-pay), not in `performReaction`

User initially said "For performReaction, delete..." — I implemented it literally, but the user corrected: a RiskReaction should delete its side-effect events only **after** the Risk has been paid for. Otherwise a framework cancel-reaction (Hexenjagd-style) firing during the pay step would drop the Renown movement without the Risk actually being spent — strictly better than the printed text says.

Pattern D.2 from the create-risk skill is the right reference: `performReaction('cancel')` `stackEvent`s only the pay events (`createEnteringPayStateEvent` + `createReactionPayTransitionEvent`); after the pay resolves the framework fires `EventRiskReactionTriggered`, and that handler is where `deleteEventBatch` + `setUsed` + notify happen.

The reaction transition is `stackEvent`'d (not `queueEvent`'d) in `handleEvent` so it lands at the front of the queue and pre-empts the Remove/Add events already pending — the user wins the race to offer the Reaction before the Renown move would otherwise resolve.

### ICancelReaction is required for the post-pay race

First pass without `ICancelReaction` left the Remove/Add events firing *before* `deleteEventBatch`. Root cause: `FrameworkActionsTrait::actChooseCardForReactionPaid` `queueEvent`s the `EventRiskReactionTriggered` for non-`ICancelReaction` reactions — and the Remove/Add events have `HIGH_PRIORITY = 2` while `queueEvent` defaults to `MEDIUM_PRIORITY = 3`. Lower-number priority dequeues first, so Remove/Add ran ahead of our deletion handler.

`ICancelReaction` is just a marker interface (no methods required for our case — `revertCancellation` is only invoked by `Reaction_01109` Not Today and only against `_01140` specifically). The framework checks `instanceof ICancelReaction` and flips both the `EventRiskReactionTriggered` and `EventRiskPlayed` post-pay events from `queueEvent` to `stackEvent`. With those `stackEvent`'d, they get priority lower than every pending event including the high-priority Remove/Add — so `EventRiskReactionTriggered` fires first, `deleteEventBatch` removes the still-queued Remove/Add by their `batchId`, and the Renown movement is fully canceled.

WHY this is the correct marker semantically: our Reaction does cancel an in-flight game effect (the Renown movement). That's the contract `ICancelReaction` exists to signal.

### Why `deleteEventBatch` is safe here

`EventRenownMovingBetweenLocations` has no `EventHub` handler — it's purely a card-listener trigger. The actual Renown changes happen in the `Added`/`Removed` events queued alongside the Moving event (see `_01117`, `_01062`, `_01150` for the canonical "queue all three with shared `batchId`" idiom).

By the time `performReaction` runs, the Moving event has been dequeued and processed (its `handleEvent` ran and queued our reaction transition). The remaining batch members are exactly the `Added` + `Removed` pair the user wants gone. `deleteEventBatch($batchId)` is type-agnostic but in practice deletes only those two.

A specialized "delete renown add/remove by batch" helper would be more explicit but adds infrastructure for a single card. The WHY comment in `performReaction` captures the assumption — anyone tempted to add a fourth event-type to the same batch needs to revisit this.

### Decline path

If the user declines: no Renown events deleted, no pay, no `setUsed`. The Add/Removed events proceed normally as if the Reaction never existed. The Moving event itself didn't need to be canceled — it's informational only.

## Files touched

- `modules/php/cards/faf/_03020.php` — Risk class with `IHasActions`, `IHasReactions`, `IRiskThatTargetsCharacters`.
- `modules/php/cards/faf/actions/Action_03020.php` — new.
- `modules/php/cards/faf/reactions/Reaction_03020.php` — new.
- `modules/php/States.php` — `HIGH_DRAMA_PLAYER_TURN_03020 = 403020`.
- `modules/php/States/faf/State_highDramaPhase03020.php` — new GameState class.
- `states.inc.php` — `"03020" => States::HIGH_DRAMA_PLAYER_TURN_03020` under `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`.
- `modules/js/OnEnteringState.faf.js`, `OnUpdateActionButtons.faf.js`, `OnLeavingState.faf.js` — `highDramaPhase03020` handlers mirrored from `03011`.

## Pre-commit hook compliance

- `Action_03020 extends RiskAction` → `createActionResolvedEvent` ✓
- `Reaction_03020 extends RiskReaction` → `Location == Game::LOCATION_HAND` ✓, `$this->setUsed(` ✓, `$this->isAvailable(` ✓
- Not implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` on the same class ✓
- All function/method signatures have typed parameters ✓

## Open questions

- "Move them Home" — the printed text doesn't specify engaged or unengaged. I went with `$engage = false` (no auto-engage). This matches `Action_01092` (Makepeace's Move Home), differs from `Action_01058` ("Move Opposing Character Home Engaged" — which engages explicitly per its name). For Commanding, the absence of an "engaged" keyword suggests no auto-engage.
- Should the Reaction fire on Renown movements caused by *the Reaction's own player*? Current code doesn't filter on who's moving the Renown — the gate is just "Leader at fromLocation". If the Leader's own controller moves Renown away from their location (e.g., via Odette de Saint-Argent _01062 Reaction), Commanding would offer to cancel their own movement. Probably harmless (player just declines), but worth flagging if the design intent is "only opponent-triggered movements."
