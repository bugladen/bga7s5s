# Wilhelm _02013 Sorcerer duration (Q&A)

Eddie asked how long Wilhelm's discard-action Sorcerer grant lasts.

## Answer
Not until end of turn. Card text: "as if they were a Sorcerer" for the issued Combat challenge.

Code: `Action_02013::doEffect` adds Sorcerer; `handleEvent(EventChallengeIssued)` removes it when that defender is challenged. Duration = until challenge is issued (challenge-scope only).

## WHY contrast with Daniella
Same day `_03013` Continuous Reaction uses turn-scope (`EventPlayerTurnEnd`). Wilhelm is challenge-enabling only so his restriction (`Villain/Sorcerer/Monster`) passes for one challenge — no lingering trait needed after `EventChallengeIssued`.
