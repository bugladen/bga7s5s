# The Great Game (01015) — dusk draw prompt with no destroy

## Report
Eddie: End of day 2, after collecting renown and moving home, offered Great Game's "draw a card" reaction despite no characters destroyed, having already used it earlier that day, and wrong timing.

## Root cause (priority race + fail-open)

Event priorities (lower number drains first): MEDIUM=3 for move-home / scheme→locker; REACTION_PRIORITY=6 for reaction transitions.

Dusk cleanup queues move-home then scheme→locker at MEDIUM. A `EventCharacterDestroyed` in that same batch (or any destroy whose reaction transition was still queued) enqueues Great Game at priority 6. Those prompts drain **after** schemes are already in `Locker-*`.

Compounding bugs:
1. `Reaction_01015` did not gate on scheme still at `LOCATION_PLAYER_HOME`. Same-request leakage: after `CardSentToLocker`, the scheme stays in `$theah->cards` until next `buildCity`, so handleEvent could still queue.
2. `Theah::runEvents` reaction availability check **failed open** — if card/reaction missing, transition still offered. No locker/discard Location skip.
3. No `deleteTransitionEvents` on use (unlike Eko 01182) — leftover transitions relied only on `isAvailable`.
4. Scheme→locker did not clear pending transitions for that scheme sourceId.

"Already triggered" fits once-per-day `setUsed` + a leftover transition that skipped the availability gate, or a second destroy buried in the dusk move spam that still shouldn't prompt after the scheme left play.

## Fix
1. `Reaction_01015` — require `$scheme->Location == LOCATION_PLAYER_HOME` before queueing; `deleteTransitionEvents($this->Id)` on draw.
2. `EventHub` `EventCardSentToLocker` — if card is `Scheme`, `deleteTransitionEventsBySourceId`.
3. `Theah::runEvents` — for card reactions (not `THEAH_ID`): fail closed if card/reaction missing or `!isAvailable()`; also skip if Location is `Locker-*` / `Discard-*`.

## WHY not only fix 01015
The priority race affects any scheme reaction queued in the same dusk cleanup batch. Clearing transitions on scheme→locker + fail-closed runEvents is the durable framework fix; Home gate is the card-local belt.

## Not changed
Once-per-day via `setUsed` / `EventDuskEndOfDay` reset (scheme is already in locker by then so reset is moot for that instance — next reveal is a fresh card). Pass still does not set Used.
