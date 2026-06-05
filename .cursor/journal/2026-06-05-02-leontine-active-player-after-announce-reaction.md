# Léontine (01068) — wrong active player after announce-triggered reaction passes

## Bug

User report from an old deploy (does NOT include the recent 01154/01106/01124 fixes):
- playerA performs Action_01068 (Léontine — Move Character You Control)
- playerB has Reaction_02030a (Gain Musketeer) trigger from the announce event
- playerB clicks Pass on Reaction_02030a
- State lands on `HIGH_DRAMA_PLAYER_TURN_01068` with **playerB** still active, showing playerA's character-pick options

User explicitly asked whether this was the same Soline/ActionResolved priority-race that we just fixed in Corpse Speak / Improvising / Ved'ma. It is NOT.

## How this differs from the Soline pattern

The Soline pattern (01154/01106/01124): the **action** explicitly bumped `EventActionResolved` to `CHANGE_ACTIVE_PLAYER_PRIORITY` (8) alongside an `EventTransition` (also 8). MySQL's `ORDER BY event_priority LIMIT 1` with no tiebreak dequeued them out of order, letting Soline (Reaction_01089) fire on `EventActionResolved` before the in-hand pay transition ran. Fix was to move `ActionResolved` to the RiskClone's discard handler.

Here:
- Reaction is **Reaction_02030a**, not Soline.
- Trigger event is `EventActionActivated` (announce), not `EventActionResolved`.
- Action_01068 only queues a single `EventTransition` (priority 8) inside `handleEvent(EventActionTriggered)`. No `ActionResolved` racing it. `createActionResolvedEvent()` in 01068 lives in `actFromActionWithIds` after the player picks character + location — far from this window.

So no priority-8/priority-8 collision. Different mechanism, same-looking symptom.

## Actual root cause

In-play action dispatch path:

1. `actHighDramaInPlayActionConfirm` queues `EventActionActivated` (priority MEDIUM=3) and transitions to `HIGH_DRAMA_IN_PLAY_ACTION_CONFIRM_EVENTS` (game state → `stRunEvents`).
2. `stRunEvents` dequeues `EventActionActivated`. Reaction_02030a queues a reaction transition (priority 8) for playerB. Transition fires: `changeActivePlayer(playerB)`, `nextState("reaction")` → `playerReaction`.
3. playerB passes → `Reaction_02030a::performReaction(... 'pass')` → `nextState("done")` → back to `HIGH_DRAMA_IN_PLAY_ACTION_CONFIRM_EVENTS` → `stRunEvents`.
4. Queue empty. `runEvents` fallback (Theah.php:346–367) only resets active player to `CURRENT_PLAYER` when **current** state.type is `ACTIVE_PLAYER`. Current state is `HIGH_DRAMA_IN_PLAY_ACTION_CONFIRM_EVENTS` (type=`game`) → reset SKIPPED. Active stays at playerB.
5. `nextState('endOfEvents')` → `HIGH_DRAMA_IN_PLAY_ACTION_DISPATCH` → `stHighDramaInPlayActionDispatch`.
6. `stHighDramaInPlayActionDispatch` reads `getActivePlayerId()` (= playerB, stale) and queues `EventActionTriggered(playerId=playerB, …)`.
7. `Action_01068::handleEvent` reads `$event->playerId` (playerB), queues `createTransitionEvent(playerB, leontine.Id, "01068", …)`. Transition fires → `changeActivePlayer(playerB)` → `HIGH_DRAMA_PLAYER_TURN_01068` with playerB active. **Bug reproduces.**

## Fix

`modules/php/StatesTrait.php:86` — read `CURRENT_PLAYER` from globals and explicitly `changeActivePlayer` at the top of `stHighDramaInPlayActionDispatch`. Matches the pattern already used by `stSetCurrentPlayer` (line 73). Inline WHY comment kept self-contained for non-journal readers.

## Why this fix over the architectural alternative

Considered an alternative: change `Theah::runEvents` fallback (line 358-367) to always reset to `CURRENT_PLAYER` regardless of next state's type. That's a cleaner invariant — "after running events the active player is always the canonical turn player unless we explicitly transitioned mid-flight" — but the blast radius is large. Anything that intentionally hands off across players via a game state could regress. The dispatch-site fix is targeted to the one known broken hop without touching other state machinery.

## Scope of the underlying issue (not Léontine-specific)

The vulnerable pattern is: any in-play `CardAction` whose `handleEvent(EventActionTriggered)` queues an `EventTransition` (which is the *standard* template for `HIGH_DRAMA_PLAYER_TURN_*` states). Because the transition reads `$event->playerId` directly from the `EventActionTriggered` that `stHighDramaInPlayActionDispatch` constructed, every such action shares the same staleness exposure. Fixing it at the dispatch site fixes all of them at once. If only the dispatch site had been doing this once and other places used `getActivePlayerId()` similarly, we'd need to hunt them — but this is the single funnel for in-play actions.

In-hand action path (`actPayForInHandAction`, FrameworkActionsTrait.php:909) is a different funnel and is safe: it's a player-initiated action endpoint, so `getActivePlayerId()` is by definition the paying player.

## Not breaking the prior fixes

The 01154/01106/01124 fixes moved `ActionResolved` to the RiskClone discard handler — they don't depend on dispatch-site active player. Pre-commit hook regex for `createActionResolvedEvent` still matches the relocated calls. No interaction.
