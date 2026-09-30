# Action_03013 Sorcerer duration vs challenge

Eddie asked when the grant ends. Case: Action used before a challenge; target still has Sorcerer during the duel.

Eddie then said it must also drop at end of turn and as soon as a challenge starts. Co-location removal stays.

## What changed
Action_03013 and Reaction_03013a both clear their tag lists on `EventChallengeIssued` and `EventPlayerTurnEnd`, in addition to move/destroy.

WHY both: the reaction applies the same temporary Sorcerer. Leaving it location-only would recreate the "still a Sorcerer in the duel" case when the grant came from the reaction.

WHY ChallengeIssued specifically: cards handle that event before the hub snapshot (`runEventHubAfterCards`). The duel stat snapshot therefore does not include the pre-challenge trait. The duel-hub button grants after the challenge is already issued, so that opt-in still lasts through the duel and falls off at turn end (or if someone leaves the location).

WHY any player's turn end, not only Daniella's controller: `EventPlayerTurnEnd` is the High Drama turn that is actually ending. Filtering to her controller would let a hub grant made during an opponent's turn survive until her next turn.

Still also clears on leave-location and destroy. Challenge does not move characters, which is why ChallengeIssued had to be explicit.
