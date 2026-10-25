# Hexenjagd cost wound vs Pull the Strand

Report: Hexenjagd cancelled Pull the Strand and did not wound a character at the location.

## Why the wound vanished

Pull (`Action_01030`) is `ISorcererAbility` + `IAbilityThatTargetsCharacters`. The offer path is fine: `EventSorcererAbilityStart` with that target reaches `Reaction_01053`, and the button wounds one of your characters at the target's location. That character is usually the target. Often it is the only one there.

On pay, the reaction queued `EventCharacterBeingWounded` for the chosen performer and then called `deleteEventsTargetingCard($this->TargetId)`. That sweep deletes every queued event whose serialized `*Id` equals the target. The cost wound's `characterId` is that id, so the sweep deleted the cost. Cancel still worked because `deleteTransitionEventsBySourceId` removed Pull's `01030_2` transition. The existing test wounded a second character (`Buddy`), so it never saw this.

`holdingEffects` would also swallow a later wound on the target. Clear that before queueing the cost.

## Also covers Pull (_01172)

The sweep lives on the reaction, so it is not special to Pull the Strand. Pull (`Action_01172`) puts the moved character's id on `EventCardMoving.cardId` and on the sorcerer-start `targetId`. Wounding that character is the same deleted-cost bug, and the reorder keeps it.

Pull's own "wound the performer unless Strega" is a different event: `characterId` is the sorcerer's performer, queued ahead of the move, same `batchId`. Hexenjagd does not call `deleteEventBatch`. The move is what `holdEvent` batches away, and that wound has already left the queue by then. Leaving it is the cost before the bullet. Torsten deletes the batch in `performReaction`, which is why that wound dies for him and not here. Do not "fix" Hexenjagd by deleting Pull's batch or the Strega-exception wound will be refunded.

## What I did not change

Did not narrow `deleteEventsTargetingCard`. The sweep is still how the sorcerer's pending effects on the target die. The cost just has to be queued after it. Engage-before-start on Pull is the action's cost (bullet), not something Hexenjagd should undo.
