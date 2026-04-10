# Terrell Brandt (01042) Audit

## Card Text
**Passive:** When Terrell's duel ends, attachments in his dueling line go into your hand instead of the discard pile. (They do not if Terrell was destroyed.)
**Technique:** +1 [Thrust].

## Technique — Correct
`Technique_PlusOneThrust` handles `EventDuelCalculateTechniqueValues` (+1 thrust) and `EventGenerateChallengeThreat` (+1 adversary threat). Standard pattern, no issues.

## Duel-End Passive — Two Bugs Fixed

Implementation lives inline in `stDuelEnd` (`StatesTrait.php`) rather than as a Reaction class. Terrell is detected via `instanceof _01042` on challenger/defender, then his controller's dueling line cards are redirected from discard to hand via `EventCardAddedToHand`.

### Bug 1: Missing destroyed check
Card text explicitly says "They do not if Terrell was destroyed." The code never checked this. Terrell could be destroyed during the duel (sent to locker via `EventCharacterDestroyed`), but the code would still redirect his attachments to hand.

**Fix:** Added `!$this->characterIsInDiscardOrLocker($terrell)` to the `$terrellInDuel` condition. Uses the existing utility method which checks for "Locker-" or "Discard-" in the location string, consistent with how `stDuelNextPlayer` checks for dead duelists.

### Bug 2: Missing Attachment type filter
Card text says "**attachments** in his dueling line" but the code redirected ALL cards. Non-attachment cards (if any end up in the dueling line) should go to discard normally.

**Fix:** Added `$card instanceof Attachment` check to the redirect condition. Required adding `use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment` import.

### WHY inline in stDuelEnd instead of a Reaction class?
The duel-end cleanup code needs to decide *where* to send each card — hand or discard — at the moment it queues the move events. A reaction would fire *after* the events are already queued. The inline approach is correct for this card's mechanics. Don't refactor this into a Reaction without rethinking the event ordering.

## Items verified
- Technique +1 Thrust: correct in both EventDuelCalculateTechniqueValues and EventGenerateChallengeThreat ✓
- Destroyed check added via characterIsInDiscardOrLocker ✓
- Attachment-only filter added via instanceof Attachment ✓
- Notification message updated to say "Attachments" instead of "Cards" ✓
- No linter errors ✓
