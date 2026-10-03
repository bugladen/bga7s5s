# Rosa (02033) × Soline (01089) — chooseNext wrongly bundles windows

## Bug
Soline Move Action into Rosa's location → First Player sees chooseNext with BOTH Rosa's CardMoved reaction and Soline's ActionResolved reaction as simultaneous choices.

Correct timing (rules + card text):
- Reaction_02033 after character moves to Rosa's location (`EventCardMoved`)
- Reaction_01089 after the Action resolves (`EventActionResolved`)

## Root cause (not the reaction cards)
Neither Reaction_02033 nor Reaction_01089 listens to the wrong event.
Aggregation bug: ActionResolved (formerly LOWEST=5) drained *before* REACTION_PRIORITY=6 UIs, so Soline's transition joined Rosa's in the same priority-6 tier; chooseNext treats ≥2 at that tier as one simultaneous set.

## Queue for basic Move (`actHighDramaMoveActionDestinationChosen`)
1. CardMoving (MEDIUM=3) + ActionResolved queued together
2. Hub CardMoving → queues CardMoved (MEDIUM=3)
3. CardMoved → Rosa queues createReactionTransitionEvent (6)
4. **Old:** ActionResolved (5) still ahead of reactions → Soline queues createReactionTransitionEvent (6) → chooseNext sees both
5. **New:** Rosa drains at 6 first; then ActionResolved (8) → Soline alone at 6

Same latent ordering documented in `2026-09-25-03-sea-legs-soline-reaction-id.md` (FIFO was "correct" then). chooseNext (`2026-09-30-02`) made the cross-window bundle user-visible.

## Fix shipped
Priority ladder:
- 6 REACTION
- 7 DEFERRED_REACTION (unchanged — Ambush pay)
- **8 ACTION_RESOLVED** (new; was LOWEST=5)
- **9 TRANSITION / CHANGE_ACTIVE_PLAYER** (was 8)

WHY not ActionResolved=7: mid-chooseNext demoted siblings sit at 7; ActionResolved would intercalate between chosen reaction pay and remaining CardMoved reactions, then Soline@6 would cut in — worse.

WHY not tie ActionResolved with TRANSITION at same number: Corpse Speak (`2026-06-05-01`) — MySQL `ORDER BY event_priority` has no event_id tiebreak; indeterminate dequeue.

Preserves ActionResolved-before-Transition for trailing multi-discard (04005 / 04018 / 01095b).

## Files
- `modules/php/theah/events/Event.php` — ACTION_RESOLVED_PRIORITY=8; TRANSITION/CHANGE_ACTIVE_PLAYER=9
- `modules/php/theah/events/EventActionResolved.php` — use ACTION_RESOLVED_PRIORITY
- Comment nits: Action_04005, Action_04018, Reaction_04010, State_duelGambleRevealed_04010

## Regression watch
- Move into Rosa + Soline available → Rosa alone, then Soline (no chooseNext unless multiple CardMoved-window reactions)
- Sea Legs + Soline: Sea Legs chooses first; siblings deferred; pay before Soline (already chooseNext deferral)
- 04005/04018 multi-discard: ActionResolved still before discard Transition
- Pattern C.5/D.5 gamble transitions: still after reaction 6 (now at 9)
