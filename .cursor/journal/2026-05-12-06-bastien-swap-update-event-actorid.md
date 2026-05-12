# Bastien Swap (01063) — Update `EventGenerateChallengeThreat.actorId`

## Change

In `Technique_01063Swap::handleEvent`, the `EventGenerateChallengeThreat`
branch now also updates `$event->actorId` to the swapped character, matching
the existing update of `Game::CHOSEN_PERFORMER`.

## Why

Two downstream consumers read `$event->actorId`:

1. `Character::handleEvent` (Character.php:199) — adds the actor's stat
   (Combat/Finesse/Influence based on `$event->statUsed`) to
   `$event->adversaryThreat` when `$event->actorId == $this->Id`. Without
   the swap, the original challenger's stat would be the one feeding the
   threat number.
2. `EventHub::handleEvent` for `EventGenerateChallengeThreat`
   (EventHub.php:1400) — reads `$theah->cards[$event->actorId]` to name
   the actor in the "X has Y total Threat for the Challenge" notification.

After the swap, the new challenger is the one issuing the threat, so it
should be the actor on the event. Previously only `CHOSEN_PERFORMER` was
updated, leaving the event's actor stale.

## Ordering caveat (not addressed)

`EventGenerateChallengeThreat` has `runEventHubAfterCards = true`, so card
handlers iterate first via `foreach ($this->cards as $card)` in
`Theah::runEvents` (Theah.php:235). The swap technique fires inside the
**old** challenger's `Character::handleEvent` (via `parent::handleEvent`
running techniques before the stat-add at line 199):

- Old challenger's body runs after techniques. `actorId != self.Id` (now
  the new challenger), so old's stat is NOT added. ✓
- New challenger's iteration depends on insertion order in `$this->cards`:
  - If new comes AFTER old: new sees updated `actorId`, adds its stat. ✓
  - If new comes BEFORE old: new already ran when `actorId` was the old
    challenger, didn't add. Old then runs, swap fires, but new doesn't
    re-iterate. Result: **neither** stat is added. ✗

The user only asked for the actor field to be updated, so I've stopped
there. If the threat math turns out to be wrong in some game states (a
challenger swap producing 0-stat threat), the proper fix is for the
technique to manually adjust `$event->adversaryThreat` by
`(newStat - oldStat)` — but that has its own double-count risk if the
new challenger's Character::handleEvent later also adds. A robust fix
likely needs either a "swapped" flag on the event consumed by Character
or a re-ordering of techniques vs. Character body. Flagging for later if
the bug shows up.

## Considered alternatives

- **Compute the threat delta in the technique itself.** Correct in
  isolation, but the new challenger's `Character::handleEvent` may also
  add the stat depending on iteration order, leading to double-counting.
  Not safe without additional plumbing.
- **Leave `actorId` untouched and only update `CHOSEN_PERFORMER`.** The
  status quo. Means the EventHub notification names the wrong character,
  and `Character::handleEvent` uses the wrong actor's stat. The user
  explicitly flagged this as wrong.
