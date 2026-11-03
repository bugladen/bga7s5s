# Pull (_01172) — UL reaction with no target in the log

## Bug
Opponent played Pull. Unyielding Loyalty opened on the EventCardMoving
intercept, but the game log never named who was being pulled. Same UX hole as
the basic-challenge announcePendingChallenge fix (2026-10-21-01).

## Why
Pull announces when the Risk is played (before performer/target pick). Effects
are queued directly on target confirm — SorcererAbilityStart, optional
performer wound, CardMoving, ActionResolved, Played — with no notify of the
chosen character. UL cancels CardMoving before EventHub writes the move line,
so the only public signal was the earlier "played Pull" line.

Porté Travel (01085) already notifies "has chosen to move …" at pick time.
Action_01138 / 04040 use "uses X targeting Y" for the CharacterTargeted window.
Pull never got either.

## Fix
`Action_01172::actFromActionWithId` notifies after validation and before the
effect queue:

`${card_inject_code}: ${player_name} has chosen to move ${character_inject_code} to ${performer_inject_code}'s location.`

WHY Porté wording over generic "targeting": Pull's whole effect is the move,
so naming destination helps when UL pops. Intent language ("has chosen to
move") not past-tense — cancel may still strip the batch.

WHY not refactor onto EventCharacterTargeted: out of scope for this report.
UL already catches CardMoving with shared batchId; Torsten ordering comments
depend on Start-before-wound in that batch. Announce-only is the minimal fix.

## Tests
Action_01172_Test: happy path asserts the message type/copy and inject codes;
refuse path clears notify and asserts no announce.
