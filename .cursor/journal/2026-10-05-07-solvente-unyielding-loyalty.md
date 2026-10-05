# Solvente Universal (_04040) vs Unyielding Loyalty — cancel after destroy

## Bug
Opponent played Solvente Universal, chose attachment. Unequip + send-to-Locker
notifications fired, then Unyielding Loyalty was offered on the wound. Playing UL
canceled only the wound — attachment already in The Locker.

## Root cause
`Action_04040` queued unequip → locker → wound in one shot on attachment confirm.
UL reacts to wound (and CharacterTargeted, which was never fired). By the time
wound was dequeued, unequip/locker had already applied. `deleteEventBatch` only
strips pending siblings — cannot undo processed destroy.

Same class as Giacinto / Amour / Come Hither: multi-effect Target ability must
expose a cancel hook *before* irreversible effects are processed (ideally before
they are even queued).

## Fix
Amour (`Action_01104`) / Giacinto (`Action_01205`) shape:

1. Attachment confirm: stash `CHOSEN_ATTACHMENT`, queue `EventCharacterTargeted`
   with `batchId`. No unequip/locker/wound yet.
2. `handleEvent` on targeting `!canceled`: queue unequip → locker → wound (same
   `batchId`) + ActionResolved.
3. UL intercepts targeting → cancel + `deleteEventBatch` → reaction prompt before
   any destroy events exist. Pass / Night of Drinking re-queue targeting clone →
   effects emit for real.

## WHY not just batchId on the old queue
Even with shared batchId, unequip+locker process before wound. UL on wound is
too late. Hook must be an earlier event (CharacterTargeted).

## WHY not fire targeting at character-choose (Come Hither)
Would also work and skip the attachment picker on cancel. User ask was specifically
destroy-before-prompt; attachment-confirm gate is the minimal Amour-shaped fix and
keeps the existing `characterChosen` → `_2` direct wiring.

## Files
- `modules/php/cards/bas/actions/Action_04040.php`
- Skill A.13: pattern-a.md, checklist.md, references.md
