# Hexenjagd Cancel Timing Bug

## The Bug

Hexenjagd (01053) was failing to cancel the wound from Action_01012 (Sibella Scarpa's sorcerer ability). The player would see the Hexenjagd prompt, choose to cancel, but the target still got wounded.

## Root Cause

The deletion of the target wound event was happening TOO LATE — inside `handleEvent(EventRiskReactionTriggered)`, which only fires inside `actPayForReaction` (the pay state). But `stRunEvents` runs BEFORE the pay state and processes the wound event queue first.

The timeline:
1. Player cancels via Hexenjagd → `performReaction` queues `[EnteringPayState, ReactionPayTransition]` → `nextState("done")`
2. `nextState("done")` → `stRunEvents` → `runEvents()` runs the full queue including **TargetWound** ← wound fires here
3. `ReactionPayTransition` fires → game goes to pay state
4. Player pays → `actPayForReaction` → stacks `EventRiskReactionTriggered`
5. `EventRiskReactionTriggered` fires → `deleteEventsTargetingCard` → **too late, wound already applied**

## Why It Was Wrong

The code was following a pattern from similar reactions (like Reaction_01122) but only partially. Reaction_01122 does the deletion in `performReaction` (correct). Reaction_01053 was doing it in `handleEvent(EventRiskReactionTriggered)` (wrong timing).

The user's intuition was right: "Could it be the reaction cancellation is not fast enough to delete the wound events?" — yes, exactly. The architecture requires deletion before `nextState("done")` because that kicks off `stRunEvents`.

## Fix (01032 clone + cancel)

Do not extract wound rows from the events table. Follow Unyielding Loyalty (`Reaction_01032`):

- Hexenjagd still offers on `EventSorcererAbilityStart` / `EventCharacterTargeted`.
- Choosing cancel sets `holdingEffects`. Effect events stay queued; `stRunEvents` processes them before pay.
- While holding, clone+cancel the same effect set as 01032 (for TargetId): Engaged, Engarded, Moving, BeingWounded, BeingHealed, CharacterTargeted, ChallengeIssued. All have `runEventHubAfterCards`, so EventHub bails on `canceled`.
- Decline after Back re-queues every saved clone (`skipNextEvent` so we do not hold them again).
- Successful pay clears clones (`clearEvents`, including CHALLENGE_CANCELLED like 01032) and deletes remaining targeting events.

Removed `extractQueuedCharacterBeingWoundedEvents`.
