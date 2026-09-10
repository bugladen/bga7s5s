# Quick Reflexes (02059) Decline Loop

## Bug
Choosing Decline on Quick Reflexes reaction looped back to the same reaction prompt.

## Root cause
On intercept, Reaction_02059 cancels the wound and saves a clone. On Decline it re-queues that clone so the wound resolves — correct intent. But the re-queued `EventCharacterBeingWounded` still matched `handleEvent` because `savedWoundEvent` was cleared and `isAvailable()` stayed true. Same card caught the same wound again → infinite loop.

## Fix
Used the existing `skipNextEvent` pattern from Reaction_01032 / Reaction_03031 (not `cancelDeclinedByCardIds` on the event — that's for movement cancel reactions like Stubborn). On decline, set `skipNextEvent = true` before re-queuing; on the next wound event for this card instance, skip intercept once and let the wound through.

WHY skipNextEvent over event-level declined IDs: instance-scoped flag is what other in-hand wound intercept reactions use; a second copy of Quick Reflexes in hand should still be able to react after the first copy declines.
