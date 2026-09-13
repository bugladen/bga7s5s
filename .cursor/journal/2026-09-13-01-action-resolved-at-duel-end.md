# createActionResolvedEvent at duel end

User asked whether `createActionResolvedEvent` fires at end of a duel.

**Yes.** `StatesTrait::stDuelEnd()` queues it for `$challenger->ControllerId` after `EventDuelEnd` and dueling-line discard cleanup (~1785).

Also fires on challenge cancel (before duel) ~877.

WHY challenge actions often omit the call / leave a comment: the challenge→duel resolution path owns action-resolved so the High Drama turn can advance. Don't double-fire from the challenge action itself when it hands off to duel.
