# Mireli's Revision (01135) — transition impossible at DUEL_GAMBLE_SETUP_EVENTS (52740)

## Report
Eddie: `This transition (01135) is impossible at this state (52740)`.

## Root cause
State 52740 = `DUEL_GAMBLE_SETUP_EVENTS`. Reaction_01135 queues `EventTransition("01135")` on cancel → forced free gamble. That edge was registered on `DUEL_COMBAT_CARD_EVENTS` and `DUEL_CHOOSE_GAMBLE_CARD_EVENTS` (→ SETUP), and on cost-events (→ REVEALED stopgap from May 2025), but **not** on SETUP_EVENTS / REVEALED_EVENTS.

Repro shape (same class as `2026-05-07-04-mireli-revision-01135-cost-events-state-gap.md`):
1. CombatCardAnnounced queues a reaction transition **per** Mireli in hand (starter decks have 2).
2. First cancel resolves → `"01135"` → `DUEL_GAMBLE_SETUP` → SETUP_EVENTS.
3. Leftover sibling `"reaction"` drains inside SETUP_EVENTS; paying it queues another `"01135"`.
4. `nextState("01135")` while still in 52740 → BGA throws.

## Fix
1. `states.inc.php`: `"01135" => DUEL_GAMBLE_SETUP` on SETUP_EVENTS and REVEALED_EVENTS.
2. Cost-events: change REVEALED → SETUP so EventGambleSetup / 03cd05 still run (closes the Sept 12 unfinished note).
3. `Reaction_01135`: on successful cancel, `clearSiblingMireliReactionTransitions` — delete other hand copies' reaction transitions / sourceId transitions so a second cancel of the same announce cannot nest.

## WHY SETUP not REVEALED for the new edges
Forced gamble must re-enter `stDuelGambleSetup` (EventGambleSetup). Jumping to REVEALED skips Devil Jonah's Bones top/bottom choice — the May cost-events stopgap was wrong for that reason.

## Not changed
Maneuver_01135 path (resolve-maneuver / technique-events → `DUEL_RESOLVE_MANEUVER_01135`) untouched. Other announce reactions (02039 etc.) still allowed to drain before the queued `"01135"` transition.
