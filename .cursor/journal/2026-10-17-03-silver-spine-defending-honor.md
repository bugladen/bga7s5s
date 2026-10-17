# Silver Spine Forced skipped Defending Honor

## Report
Opponent's character with Silver Spine (`_03cd21`) was the target of Defending Honor (`_01078`). That was the first opponent Risk to target them that Day. The challenge still happened. Later Pull (`_01172`) was cancelled by the Forced. User: Forced is not optional — they must not get to save it.

## Why it looked like a choice
There is no Decline button. The Forced never fired on Defending Honor, so the once-per-Day condition stayed off. Pull's `EventCardMoving` is one of the events Spine already handled, so the second Risk spent it. From the table that looks like "declined, then chose to use it."

## Root cause
Defending Honor's printed target is the enemy character, who then issues the challenge. They are the **challenger**, not the defender.

- Spine never handled `EventCharacterTargeted` (import was dead; May 2026 journal claimed the branch was added, it was not in the file).
- `EventChallengeIssued` only matched `defenderId == AttachedToId`.
- Maryam has a CHECK_CANCELLED special case for being the Defending Honor performer, but her `EventCharacterTargeted` handler marks Imperviousness used first, and the `01078` transition is **not** in a batch. `deleteEventBatch` does nothing. The `!hasCondition` special case then no-ops. Challenge still starts after the ability is spent.

Action_01078 queues Targeted, then transition `"01078"`, with no `batchId`.

## Fix
- Spine: cancel `EventCharacterTargeted` when `targetId == AttachedToId` and the source is an opponent Risk that targets characters. Mark used. If the event has a batch, delete it. If not (Defending Honor), `deleteTransitionEventsBySourceId` and queue `ActionResolved` for the Risk's controller so the choose-target state never opens and the action still ends.
- Spine: `EventChallengeIssued` also matches `challengerId` and sets `CHALLENGE_CANCELLED`, in case the transition already ran.
- Maryam: same unbatched-transition strip on `EventCharacterTargeted` only. Did not add a blanket challengerId match — she cancels any Risk, and she is often the performer of her own challenge Risks.

Do not turn this into a Reaction with Decline. Forced means no player choice.

## Tests
`Card_03cd21_Test` and the new Maryam unbatched-transition case. 34 passed with Action_01078.
