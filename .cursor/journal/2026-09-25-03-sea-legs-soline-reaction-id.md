# Sea Legs (01173) × Soline (01089) — REACTION_ID overwrite

## Report

Opponent Taunts victim's character Bazaar → Forums (Soline performer at Forums). Victim plays Sea Legs to move back to Bazaar. Opponent Passes Soline's City Reaction. Log shows Sea Legs played + paid, character stays at Forums.

## Root cause

Not mutual invalidation. Ordering is correct; a shared global is the bug.

Taunt queues CardMoving then ActionResolved. CardMoved (prio 3) → Sea Legs reaction transition. ActionResolved (prio 5) → Soline reaction transition. Both at REACTION_PRIORITY 6; Sea Legs has older event_id so it prompts first.

Sea Legs accept path:
1. `performReaction` queues EnteringPayState + ReactionPayTransition, `nextState("done")`
2. Framework `actReactionForState` sets `Game::REACTION_ID` = `moveAgain-The Grand Bazaar`
3. Events drain → Soline's already-queued reaction transition opens **before** Sea Legs pay (Soline was queued earlier, pay events are newer)
4. Soline Pass → `actReactionForState` overwrites `REACTION_ID` = `"pass"`
5. Sea Legs pay opens; `actPayForReaction` builds `EventRiskReactionTriggered` with `reactionId="pass"`
6. Sea Legs handler: `str_replace("moveAgain-", "", "pass")` → `"pass"` → `locationInCity` false → **silent no-move**

Matches report exactly: paid/played notify fires in `actPayForReaction` before the triggered event; move notify never fires.

Soline Move (not Pass) would also corrupt: `REACTION_ID` → `moveTo-…`.

## Fix

Stash destination on the reaction in `performReaction` as `ChosenLocation` + `IsUpdated` (mirror Reaction_01137 `FollowCharacterId`, Reaction_04058 `chosenTargetId`). On `EventRiskReactionTriggered`, prefer sticky field over parsing `$event->reactionId`.

WHY not reorder Soline after pay: would change reaction timing / rules feel, and wouldn't fix the global race for other intervening reactions.

## Broader hazard

Any pay-for-reaction that resolves effect from `$event->reactionId` is vulnerable when another reaction intercalates between choice and pay. Known parsers: 01053, 03031, 01080, 01088, 01023, 01027, 01090. Framework fix (key REACTION_ID by internalId, or always stash on reaction in actReactionForState) would be the real harden — not done this pass.

## Files

- `modules/php/cards/_7s5s/reactions/Reaction_01173.php` — ChosenLocation stash + prefer on trigger
