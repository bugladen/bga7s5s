# Giacinto (01205) vs Unyielding Loyalty — effect engage survived cancel

## Bug
Opponent used Giacinto to Engage+move Elena (03004). Player played Unyielding
Loyalty. Move was canceled; Elena stayed Engaged. Giacinto correctly stayed
Engaged (printed cost). Engaging Elena is an effect and should have canceled too.

## Root cause
`Action_01205` queued cost + all effects in one batch in `actFromActionWithIds`:

1. Engage Giacinto (cost) — same batchId
2. Engage victim (effect)
3. Move both (effect; victim move had engage=true)

UL reacts to the first matching event on *your* card. City cards run before HAND
in `buildCity`, so depending on timing UL could cancel a later Moving after the
victim Engage had already applied. deleteEventBatch then removed remaining moves
— partial cancel: engaged, not moved.

Same class as Come Hither (2026-09-13-12): multi-step effect must not proceed
once targeting/cancel has fired.

## Fix
Come Hither pattern + cost outside the cancelable batch:

1. On location commit: queue Engage Giacinto with **no** batchId (cost always pays).
2. Queue `EventCharacterTargeted` with batchId (cancel hook).
3. `handleEvent`: only when that targeting event is **not** canceled, queue victim
   engage + both moves (batchId) + actionResolved.
4. Victim move uses engage=false — engage is its own clause (01104 / Lodestone WHY).

Giacinto is processed before HAND, so he may queue effects before UL cancels;
UL's deleteEventBatch still strips the batched effects. Decline re-queues
targeting (clone taken before canceled=true) → effects queue for real.

## Announce?
User asked if cost engage belongs in announceAction. **No for this card.**
Announce runs at in-play confirm, before victim/location pick (Back still
possible). Cost should pay on final location commit. Risk Actions put engage in
announce for Night of Drinking (01109); Giacinto is a City Character Action —
01109 does not cancel it.

## Files
- `modules/php/cards/_7s5s/actions/Action_01205.php`
