# Cesca Reaction_03001 not firing after Follow the Thread (Action_03009)

## Bug

User: Cesca (03001) moves via Action_03009 (Follow the Thread Sorcerer Strega
Action). City Reaction should offer "wound an opposing character" and does not.

## Root cause

Same event-ordering class as Pull (`2026-09-01-01-cesca-reaction-pull-timing.md`),
but the *inverse* move direction.

Action_03009 queues: CardMoving → ActionResolved → SorcererAbilityPlayed.
EventHub turns CardMoving into a trailing EventCardMoved. Actual order:

1. CardMoving
2. ActionResolved
3. **SorcererAbilityPlayed** ← Reaction_03001 handleEvent
4. CardMoved ← Cesca arrives at destination with enemies

At step 3 Cesca is still at the old location (often alone). The empty-target
guard (`getOpposingReactionTargets` → count == 0) returns early. No reaction
transition is queued.

The 2026-09-01 fix only covered Case A: opposing *target* queued to move *to*
Cesca (Pull). Follow the Thread is Case B: Cesca herself queued to move *to*
opponents.

Secondary: City Reaction also requires `cardInCity`. From Home → city, Cesca
fails that check at step 3 even though she arrives in city at step 4. Same
queued-destination peek fixes that.

## Fix

1. `DB::getQueuedCardMoveDestination` / `Theah::getQueuedCardMoveDestination` —
   deserialize queued EventCardMoved for a cardId, return `toLocation`.
2. `Reaction_03001::handleEvent` — allow City Reaction if Cesca is in city OR
   has a queued move into a city location.
3. `getOpposingReactionTargets` Case B — if Cesca has a queued destination,
   also count opposing characters already at that destination.

`getReactionButtonProperties` unchanged — runs after CardMoved, co-location true.

## WHY not reorder Action_03009 / pass targetLocation only

- Reordering SorcererAbilityPlayed after CardMoved would need the same fix on
  every move-then-played sorcerer action (03009, 03032, …). Centralizing in the
  reaction matches the Pull precedent.
- Action_03032 already passes destination as `targetLocation` but Reaction_03001
  never read it; queued CardMoved is authoritative for any performer-move action
  regardless of whether they set targetLocation.

## Related

- `2026-09-01-01-cesca-reaction-pull-timing.md` — Case A (target → Cesca)
- Also covers Bloody Entrance (Action_03032) performer-move timing the same way
