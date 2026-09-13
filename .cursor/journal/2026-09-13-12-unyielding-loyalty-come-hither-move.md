# Unyielding Loyalty vs Come Hither — cancel didn't stop the move

## Bug
Player discarded a Thug for Reaction_01032 (Unyielding Loyalty) after Come Hither
(Action_01162) targeted their character. The character still moved.

## Why it looked like 01032 failed
01032 did cancel `EventCharacterTargeted`. Come Hither does not move on that
event. It queues a location-pick transition (`01162_2`) in the same
`actFromActionWithId` call, *before* reactions run.

`runEvents` order:
1. Process `EventCharacterTargeted` — UL sets `canceled=true`, stores a clone,
   queues a reaction transition at the *end* of the queue.
2. Next event is already-queued `EventTransition 01162_2` — location pick fires
   immediately. `runEvents` returns.
3. Player picks a location. `EventCardMoving` + `actionResolved` are queued.
4. *Then* the UL reaction UI appears. Player pays the Thug. `clearEvents()`
   drops the stored targeting clone.
5. `EventCardMoving` is still in the queue. UL is no longer available (card
   left hand / used), so the move resolves.

The 2026-05-22 Come Hither journal already flagged this double-fire
(target event then move event). `skipNextEvent` only helps if UL is still
in hand for the second event. Paying the reaction removes the card first.

## Why not fix 01032
Card text is "when targeted • cancel the effects." Intercepting
`EventCharacterTargeted` is correct. The action that fired targeting must
not continue its effect steps after that event is canceled.

02048-style "remember source and auto-cancel later events" would also work
but is the wrong owner: every multi-step target-then-effect action would
need the reaction to know about its follow-up events. Gate the follow-up
on the targeting event surviving instead.

## Fix
`Action_01162` no longer queues `01162_2` next to `EventCharacterTargeted`.
`handleEvent` queues `01162_2` only when `EventCharacterTargeted` for this
ability is **not** canceled.

- Pay UL / Maryam cancel: event stays canceled, never re-queued → no location
  pick, no move. Events drain → `endOfEvents` → NEXT_PLAYER.
- Decline UL / Vittoria redirect: event re-queued with `canceled=false` →
  `01162_2` then fires (Vittoria also updates `targetId`; existing
  `CHOSEN_TARGET` sync still runs).

## Why not actionResolved on canceled
Queuing `actionResolved` when we first see `canceled=true` is too early —
UL sets that flag *before* the player decides. Decline would then fire
`actionResolved` and then still go to location pick (Daniella-style
listeners would think the action ended). Confirmed-cancel path does not
need it: `endOfEvents` already advances High Drama.

## Not done
Defending Honor (`Action_01078`) still queues its challenge transition
beside `EventCharacterTargeted`. Same class of bug if UL/Maryam cancel
that targeting. Left alone — not this report.
