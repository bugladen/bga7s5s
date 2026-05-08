# Mireli's Revision (01135) — missing transition in cost-events state

## The Bug

Reported error: `Unexpected exception: This transition (01135) is impossible at this state (5230)`.

State 5230 is `DUEL_GET_MANEUVER_FROM_COMBAT_CARD_COST_EVENTS`. The `01135` transition was defined in `DUEL_COMBAT_CARD_EVENTS` (5226), `DUEL_RESOLVE_MANEUVER_EVENTS`, and `DUEL_CHOOSE_GAMBLE_CARD_EVENTS`, but not in 5230.

## Repro Path

1. Active player gambles a combat card. `actGambleCardChosen` (`FrameworkActionsTrait.php:1602`) queues `EventCombatCardAnnounced` then `nextState("useManeuver")` — the announcement event sits in the queue, no events state has run yet.
2. Player picks a maneuver in `DUEL_USE_MANEUVER_FROM_COMBAT_CARD` → `DUEL_GET_MANEUVER_FROM_COMBAT_CARD_COST` → state **5230** is the first `stRunEvents` state since the gamble.
3. The deferred `EventCombatCardAnnounced` is processed here. Opponent has Mireli's Revision in hand → `Reaction_01135` queues a `ReactionTransitionEvent`.
4. Opponent picks Gamble, pays for the reaction. `actPayForReaction` stacks `EventRiskReactionTriggered` (`FrameworkActionsTrait.php:1990`).
5. Back in 5230, `EventRiskReactionTriggered` fires → `Reaction_01135::handleEvent` line 69 queues `TransitionEvent("01135", …)`.
6. State 5230 has no `01135` transition → crash.

## The Fix

Added `"01135" => States::DUEL_GAMBLE_REVEALED` to state 5230 in `states.inc.php`.

WHY: The `01135` transition is the cancel-and-force-gamble effect of Mireli's Revision's reaction. It needs to land in `DUEL_GAMBLE_REVEALED` regardless of which event state was active when the reaction resolved. The author registered it in the announcement-time and gamble-time event states but missed the post-gamble useManeuver cost path.

WHY this exact target: Matches what `DUEL_COMBAT_CARD_EVENTS` and `DUEL_CHOOSE_GAMBLE_CARD_EVENTS` already use for the `01135` transition. The reaction's intent is "cancel the announced combat card and have them gamble instead" — `DUEL_GAMBLE_REVEALED` is the entry point for that.

## Audit — Other Paths

Confirmed this was the only reachable event state on the gamble→useManeuver chain that lacked the transition:

- `DUEL_COMBAT_CARD_EVENTS` (5226) — has it ✓
- `DUEL_CHOOSE_GAMBLE_CARD_EVENTS` — has it ✓
- `DUEL_RESOLVE_MANEUVER_EVENTS` — has it (different target, for the maneuver flow) ✓
- `DUEL_GET_MANEUVER_FROM_COMBAT_CARD_COST_EVENTS` (5230) — was missing, now fixed

After 5230 we go to `DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD` (activeplayer, no events) → `DUEL_APPLY_COMBAT_CARD_STATS`. By then `EventCombatCardAnnounced` has already been consumed in 5230, so no later events state can trigger `Reaction_01135`.

## Open Concern (Not Fixed Here)

This patch keeps the state machine alive but the *semantics* are still odd: by the time 5230 runs, the combat card has already moved to `LOCATION_DUELING_LINE` and the player has chosen a maneuver and is being asked to pay its cost. `Reaction_01135::handleEvent` line 47 only checks that the *reaction's owner* (the canceller) is in `LOCATION_HAND`, not that the combat card itself is still cancellable. So the cancel can fire mid-resolution and the discard event on line 66 will try to discard a card that's no longer in hand.

Did not investigate whether `createCardDiscardedFromHandEvent` quietly no-ops in that case or causes other subtle wrongness (wrong notifications, double-discard, etc.). User asked for the minimum fix; flagged this for a future pass.
