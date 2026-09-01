# Cesca Reaction_03001 not firing after Pull (Action_01172)

## Bug

User reported: Cesca (03001) used Pull's Sorcerer City Action (Action_01172) to
pull an opposing character to her location. Reaction_03001 should have offered
"wound an opposing character" but did not.

## Root cause

Event ordering in the queue when Pull resolves:

1. `EventCardMoving` (queued by Action_01172)
2. `EventActionResolved`
3. `EventSorcererAbilityPlayed`

When `EventCardMoving` is *processed*, EventHub queues `EventCardMoved` at the
end of the batch. Actual processing order becomes:

1. CardMoving
2. ActionResolved
3. **SorcererAbilityPlayed** ← Reaction_03001 handleEvent runs here
4. CardMoved ← character actually arrives at Cesca's location

Reaction_03001 had a guard: skip if `getOpposingCharactersAtLocation` is empty.
At step 3 the pulled character is still at their old location, so the guard
returns early and no reaction transition is queued.

By the time the player would have seen the reaction UI, CardMoved would have
run — but we never queued the transition.

Elina's reaction (01118) doesn't have this guard; Cesca's does (added during
implementation to avoid pointless Pass-only prompts).

## Fix

`getOpposingReactionTargets()` — same as location filter, plus: if Cesca was the
performer, ability has an opposing `targetId`, and `hasQueuedCardMoveToLocation`
shows that target is queued to move to Cesca's location, count them.

Added `DB::hasQueuedCardMoveToLocation` / `Theah::hasQueuedCardMoveToLocation`
(deserialize queued EventCardMoved rows) so we don't false-positive on sorcerer
abilities that target remote characters without moving them to Cesca.

`getReactionButtonProperties` unchanged — it runs after CardMoved, when co-
location is already true.

## Alternatives considered

- Reorder Action_01172 to fire SorcererAbilityPlayed at LOWEST_PRIORITY: fixes
  Pull only; every move-then-sorcery action would need the same tweak.
- Remove the empty-target guard entirely: would fix Pull but bring back pointless
  Pass-only prompts for other cases.
