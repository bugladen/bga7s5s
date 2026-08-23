# Breastplate unequip: fly then orphan in city

## Symptom
Eddie: Breastplate (_01153) destroy after wounds — card flies to discard first,
then an *unattached* Breastplate sits in the city row. Wanted: unattach first,
then that element flies to discard.

## Root cause (two layers)

### 1. Event queue tie order
`DB::getNextEvent` was `ORDER BY event_priority LIMIT 1` with no `event_id`
tiebreaker. Unequip + discard are both `MEDIUM_PRIORITY`. Breastplate queues
them in the right order, but MySQL can return discard first. Client then:

1. `cardDiscardedFromPlay` — flies the still-attached card, destroys it
2. `attachmentUnequipped` — recreates a city-row sibling that never gets discarded

Matches the visual exactly. July 1 journal already smelled this (discard nulling
`divId` before unequip) but blamed client-only; the race was real at the DB.

### 2. Aug 3 recreate always
`67c0b842` re-added destroy+recreate so discard had something to fly. That
removed the July skip (`location !== Player Discard`). When discard won the
race, recreate built the orphan on purpose.

## Fix
- **PHP** `DB::getNextEvent`: `ORDER BY event_priority, event_id` — FIFO within
  same priority. WHY event_id not a priority bump on Breastplate: every
  unequip-then-discard pair (and any same-priority queue) needs this; card-local
  priority hacks would rot.
- **JS** `notif_attachmentUnequipped`: reparent the live node (strip
  `_7sfs-attached-card`, `dojo.place` before character) instead of
  destroy/recreate. Skip entirely if `location === LOCATION_PLAYER_DISCARD`
  (discard already ran). Fallback recreate only if the DOM node is missing but
  the card is still in play (02055-style leave-in-play).

## Related
- 2026-07-01-01-breastplate-unequip-notif-crash.md
- 2026-08-03-02-attachment-unequip-ownerdocument.md
- 2026-04-19-01-unequipped-attachment-positioning.md
