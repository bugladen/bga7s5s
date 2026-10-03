# Amour (01104) + UL + Night of Drinking — partial effect restore

## Ask
Amour (Alpha + Beta) → UL cancel → NoD cancels UL → Blood in the Water at location.

Actual: Alpha untouched; Beta engaged + wounded at location.
User said correct: both untouched at location.

## Root cause
Action_01104 queued both engages + both Home moves in one batch from
`actFromActionWithId`. UL intercepts the first matching event on *your* card
(Beta's engage), stores **only that clone**, `deleteEventBatch`s the rest.

`revertCancellation` (NoD → UL) `releaseEvent`s the stored clone → Beta engage
alone. BitW (batched wound fix from 09) correctly wounds on that restored engage.
Alpha's engage + both Home moves stay dead.

Same class as Giacinto (2026-09-17-07) / Come Hither (2026-09-13-12): multi-step
effect must not be queued beside the cancel hook.

## Fix
Giacinto pattern on `Action_01104`:
1. `actFromActionWithId` queues only `EventCharacterTargeted` (batchId + eventCheck).
2. `handleEvent` on surviving targeting queues engage×2 + Home×2 + ActionResolved.

UL/Maryam hold the targeting event; Pass / NoD re-queue it → full Amour package
emits again. Successful UL leave targeting canceled → no effects.

## Rules note vs user's "correct"
NoD undoing UL should restore the cancelled ability (Bleed Out journal
2026-08-31-03). Post-fix expected when NoD cancels UL:

- Amour fully resolves: both engage, BitW wounds both at the location, both go Home.

"Both stay en garde unwounded at location" is the successful-UL outcome, not the
NoD-undo outcome. If Eddie really wants Amour to stay cancelled when NoD hits UL,
that is a `revertCancellation` policy change and would regress Bleed Out — ask
before doing that.

## Follow-up: UL offered twice after Amour Pass

After CharacterTargeted gate: Pass → targeting re-queued → Amour queues
engage×2+Home×2 → UL fires again on Beta engage. Second Pass
`deleteEventBatch`s Alpha+Homes; only Beta engage restored → BitW wound at
location. Matches "Pass both times, only Beta engaged/wounded, both still here."

`skipNextEvent` only covers the immediate re-queued hook. Fix in
`Reaction_01032`: on Pass, remember held event's `batchId` as `declinedBatchId`
and ignore later intercepts with that batch. WHY batch not abilityId: in-play
Actions reuse ability ids across days; batch ids are per-resolution.

## Files
- `modules/php/cards/_7s5s/actions/Action_01104.php`
- `modules/php/cards/_7s5s/reactions/Reaction_01032.php`
