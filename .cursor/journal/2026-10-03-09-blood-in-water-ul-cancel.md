# Blood in the Water (_04cd19) wound after Unyielding Loyalty cancel

## Ask
04cd19 still wounding a character engaged by Action_01104 even though
Reaction_01032 (Unyielding Loyalty) canceled the action.

## Root cause
`EventCardEngaged` has `runEventHubAfterCards=true` — all cards handle before Hub
applies Engaged. BitW queued `EventCharacterBeingWounded` during that pass with
**no batchId**. UL (later in the same foreach, or after BitW in card order) sets
`canceled=true` + `deleteEventBatch($engageBatchId)`. The wound was outside the
batch, so it still drained. Character never Engaged (Hub no-ops on canceled) but
still took the wound.

Journal `2026-07-24-05-04cd19-blood-in-the-water.md` already called this a
"residual race" and chose not to invent a post-hub engage-done event. That race
is exactly this bug.

Action_01104 batches both engages + both Home moves — UL correctly sweeps those.
BitW's wound was the orphan.

## Fix
In `_04cd19`: when queuing the wound, share the engage's `batchId`. If the
in-flight engage has `batchId === null`, mint one onto `$event` so a later
canceler in the same pass still has an id to `deleteEventBatch`. `!$canceled`
still covers cancelers that ran before BitW.

WHY mint onto the in-flight event (not only the wound): UL/Maryam read
`$event->batchId` when they cancel. Minting only on the wound would leave the
canceler with null and the wound orphaned again.

WHY not defer wound until Engaged is true (LOWEST check): Action_01104 moves
Home at MEDIUM after engage; a deferred location check would see Home and skip
a legal wound. Batch sweep keeps timing at engage time.

## Kept
- Premature Forced notify can still flash if BitW runs before UL then cancel
  deletes the wound. Acceptable; Character wound notify only fires if wound
  resolves. Did not invent post-hub engage-done.

## Skill
Updated create-city-event-card pattern-a / sub-patterns / checklist with the
batchId requirement for engage Forced follow-ups.

## Files
- `modules/php/cards/bas/_04cd19.php`

## Follow-up design: Engaging / Engaged split?

User asked whether EventCardEngaged should split like EventCardMoving / EventCardMoved.

Opinion: yes conceptually for cancel vs After Forced, no as a drive-by for BitW.
- Moving = cancelable; Moved = committed-intent (Location still stale � runEventHubAfterCards).
- Engaged today serves both roles ? race BitW just patched with batchId.
- Split cost: audit many EventCardEngaged listeners; also silent engage-on-move via EventCardMoved (no EventCardEngaged) must be wired through or After-engaged stays incomplete.
- Wound already has BeingWounded ? Wounded; engage is the inconsistent one.
- Do the split only with a deliberate engage-timing pass, not for this bug alone.
