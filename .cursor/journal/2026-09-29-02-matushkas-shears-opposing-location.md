# Matushka's Shears (03007) — opposing location gate

## Bug
Shears offered its Sorcerer City Reaction when an opposing character died at a *different* city location from the equipped Strega.

## Cause
`Reaction_03007::handleEvent` only checked different controller (`$event->playerId != $owner->ControllerId`). No same-location check, no `cardInCity` City Reaction gate.

In this ruleset **"opposing" = same location + different controller** (`feedback_opposing_definition`, Blood Money `Reaction_04004`, Kaiser Schnurrbart `Reaction_03019`). Different-controller-anywhere is wrong.

## Fix
In `handleEvent`:
1. `getOwningCharacter` + `cardInCity` (City Reaction)
2. Skip if destroyed `ControllerId` is 0 or same as Strega
3. Skip if `$card->Location != $owningCharacter->Location`
4. Set `opponentId` from `$card->ControllerId` (not event.playerId — same value usually, but controller on the card is the source of truth)

WHY Location still works: `EventCharacterDestroyed.runEventHubAfterCards = true`, so destroy-time city Location is readable during card handleEvent (same as 04004).

## Not touched
Cesca copy path (`Reaction_01008` → `beginCopy`) — copy is driven off SorcererAbilityPlayed after a valid Shears fire, so the location gate on the original is enough. Brute/locker skip from 2026-09-25 left alone.
