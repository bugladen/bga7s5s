# Vittoria × Giacinto 04032 — Thug stayed behind

## Report
Giacinto (BAS `_04032`) targeted Vittoria; opponent passed (no discard); location
chosen. Vittoria mustered a hand Thug and redirected. Giacinto moved; Thug did not.

## WHY
`Action_04032` queues performer `CardMoving` then target `CardMoving` (shared
batchId). FIFO: Giacinto's move resolves first. Vittoria holds her `CardMoving`
and redirects via `Reaction_01014`.

`performReaction` re-ran `isValidTargetForAbility` on the Thug. Action_04032
requires "same location as Giacinto" — he already left for the destination →
reject → `cancelEvents` drops the held move. Challenge redirects already skipped
that check for the same "board changed since commit" reason (Valeri adjacent).

`deleteEventBatch` on hold is fine here: Giacinto's move already drained, so the
sibling is gone. Shared batch would be wrong if target were queued *first*
(would wipe Giacinto's still-queued move on hold) — do not reverse order without
splitting batchIds.

## Fix
Treat `$this->cardMovingEvent` like `$this->challengeIssuedEvent`: skip
`isValidTargetForAbility`, `releaseEvent` onto the Thug. inPlayThug buttons
already limit to Thugs at Vittoria's location; held event carries destination.

## Not doing (yet)
Fire `EventCharacterTargeted` on 04032 target pick so redirect happens at printed
"Target" timing (before reveal/pass and before moves). Would need softlock
ActionResolve on failed redirect (Torvo 02034 shape). CardMoving skip is enough
for this report.

## Tests
`Giacinto 04032 CardMoving redirect moves Thug after Giacinto already left` in
Reaction_01014_Test. Source-gate assert for the skip condition. 20/20 + Action_04032.
