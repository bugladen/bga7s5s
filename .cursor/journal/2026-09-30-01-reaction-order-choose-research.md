# First Player reaction order — codebase research

## Context
Parent asked for exploration toward First Player choosing reaction order when multiple reaction transitions are queued. Today’s WIP: `Event::$runImmediately` added (uncommitted) on Event.php; DB has no `getNextEventByRunImmediately` yet. Disk Theah::runEvents still only calls `getNextEvent()` — editor buffer once showed a sketched call site that is NOT on disk.

## Key findings (WHY this matters for the feature)

1. **No CHOOSE_NEXT stubs exist.** Closest complete pattern is When Revealed choose-order (states 243–247): First Player picks from button list, globals track remaining, each choice queues one effect then drains its own `*_EVENTS` loop. Journal `2026-04-13-16-when-revealed-ordering.md` has the WHY for separate event loops.

2. **Reaction transitions are FIFO at priority 6.** `createReactionTransitionEvent` sets `transition='reaction'`, `REACTION_PRIORITY=6`. Queue is PHP `serialize()` into `events.event_serialized`; dequeue is `ORDER BY event_priority, event_id` then DELETE row. Multiple simultaneous reaction offers run in queue order (old event_id first) — documented in sea-legs/soline journal as intentional ordering that still races on REACTION_ID global.

3. **`runImmediately` is brand-new.** Property exists on Event (default false). Nothing reads it yet. No `getNextEventByRunImmediately` in DB.php. Theah does not peek/filter reaction transitions before taking the next event. Implementing choose-order likely needs: list queued `EventTransition` with `transition=reaction`, pause drain, First Player pick → set that event `runImmediately` or restack it to front / delete+requeue.

4. **Existing DB helpers for transitions:** `areTransitionEventsOfTypeForPlayerQueued`, `deleteTransitionEvents`, `deleteTransitionEventsBySourceId` — all LIKE-match on serialized blob. Mutation pattern exists (`decrementFirstQueuedPlayerGainsReknown` unserialize→mutate→UPDATE). Reuse that for promoting a chosen reaction event.

5. **UI pattern:** `playerReaction` uses `args._private.args.buttons` + `actReactionForState`. When Revealed uses public `whenRevealedCards` + `actChooseWhenRevealedCard`. In-play actions use `_private.actions`. Generic button list = forEach addActionButton.

## Hierarchy reminder
`Reaction` (theah) ← `CardReaction` ← `RiskReaction` / `AttachmentReaction`. Risk only changes hand-oriented description/announcement.

## Unfinished for implementer
- Decide: interrupt inside runEvents when ≥2 reaction transitions queued vs new CHOOSE_NEXT state reachable from every `*_EVENTS` “reaction” transition (hard — reactions fire from many EVENTS states).
- When Revealed avoided per-EVENTS branching by using a dedicated choose state before events. Reaction choose-order may need a global CHOOSE_NEXT state that every EVENTS state’s `"reaction"` can go to first — or peek+choose inside runEvents before nextState("reaction").
