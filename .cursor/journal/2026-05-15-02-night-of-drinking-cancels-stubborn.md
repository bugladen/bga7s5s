# Night of Drinking (01109) cancelling Stubborn (01140)

## Problem reported
User: "Reaction_01109 does not seem to be canceling Reaction_01140".

## Trace
Stubborn is a Risk whose Reaction cancels a character movement. When played
reactively:
1. `EventCardMoving` fires.
2. `Reaction_01140` clones the event, sets `$event->canceled = true`, stacks a
   `ReactionTransitionEvent`, and stores the clone in `$this->eventCardMoving`.
3. Owner picks Cancel → pay → `RiskReactionTriggered` + `RiskPlayed` are
   stacked for Stubborn.
4. Opponent's `Reaction_01109` catches the `EventRiskPlayed` (Stubborn is not
   Sorcery) and stacks its own reaction transition.
5. After 01109 is paid for, its `EventRiskReactionTriggered` handler runs and
   deletes the pending `RiskReactionTriggered`/`ActionTriggered` events for the
   Stubborn card id.

That deletion stops Stubborn's "I cancelled the movement" notification from
firing, but the `EventCardMoving` itself was already cancelled at step 2 and
nothing re-queues it. End state: the character does not move, even though
Stubborn was cancelled. That is what the user saw.

## Fix
Mirror the `_01169` (Not Today) special case that already lived in
`Reaction_01109::handleEvent`:

- Added `Reaction_01140::revertCancellation(Theah $theah)` which re-queues
  the stored `eventCardMoving`, clears it, and marks the owner `IsUpdated`.
  Decline already does the same re-queue, so I'm following that pattern.
- In `Reaction_01109`, when the risk being cancelled is `_01140`, walk the
  card's reactions and call `revertCancellation` on the `Reaction_01140`
  instance. Reactions live on the card object and `getCardById` returns the
  cached/persisted instance with `eventCardMoving` intact.

## WHY this approach over alternatives
- A generic "Risk was cancelled → undo its reactions' side effects" hook would
  be cleaner, but there's no such event in the EventHub and only two cases
  matter today (_01169 and _01140). Following the existing special-case
  pattern keeps this small and local.
- Could have made `Reaction_01140` listen for `EventRiskReactionTriggered`
  with internalId of any ICancelReaction targeting its owner, but that's a lot
  of new coupling to detect "I was the target". The 01109-side dispatch is
  more direct.
- Re-queue (vs. attempting to un-cancel in place) matches the decline path,
  which is already known-good.

## Side-effect to remember
Re-queueing the `EventCardMoving` means other Stubborn copies in the opponent's
hand will get a fresh chance to react. That matches game rules — the movement
is happening again, so any Reaction listening for it can trigger. The original
01140-a is safe from re-trigger (it's used and no longer in LOCATION_HAND).

## Other ICancelReactions
01122, 01135, 01088 also implement ICancelReaction but only 01140 stores an
event it cancelled, so only it needs the revert. If new cancel-style Risks
that store cancelled events get added, they'll need the same treatment.

## Earlier in session
Also touched description/buttons for 01109 to show the specific Risk name
("...cancel Stubborn just played" rather than "...the Risk just played"). The
button text was further tightened by the user/linter to "Cancel %s".
