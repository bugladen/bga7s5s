# Claude + Crystal Eye Influence

## What the user saw

Claude (05Dandy) sitting at 4 Influence with Crystal Eye equipped and no Renown left at the location. Crystal Eye is +1 Influence. The Pay 2 Wealth collect did not log that his Influence went up.

## Why

Equip does not emit EventCharacterInfluenceModified. addAttachment adds InfluenceModifier onto ModifiedInfluence only. Claude's Renown passive keeps UnclampedInfluence and rebuilds the chip from that.

Sequence that matches the report: 1 Renown drops him to 3 (Unclamped 3). Crystal Eye then makes the chip 4 and leaves Unclamped at 3. Collecting that Renown computes newUnclamped = 3 + 1 - 0 = 4, sees the chip is already 4, writes 4, and returns without queuing the log event. The +1 is gone from the model. He stays at 4 with 0 Renown. Location labels also skip the equip, because AttachmentEquipped was not in eventAffectsLocationInfluenceTotals — they only move when the Influence event fires, and that event was swallowed.

Aldo was avoided on purpose because absolute Influence + count wipes attachments. This hole is the other direction: the attachment never entered Unclamped, so the sync that tries to preserve it overwrites it.

## Fix

TrackedAttachmentInfluence is how much InfluenceModifier is already inside Unclamped. foldAttachmentInfluence adds the delta, then the chip is max(0, Unclamped). Called from handleEvent (so equip/unequip/the next Renown event see it) and from sync (blanking does not go through handleEvent). An Influence lock returns null and is left alone — setLockedValues owns that chip.

A table already in the bad state (Unclamped 4, chip 4, Crystal Eye still on, tracked 0) heals in Theah::buildCity via repairAttachmentInfluence. It queues the 4→5 event once and saves the card so a second load does not queue it again. The log shows up when that event is processed, not retroactively on the collect that already resolved.

Location labels now also refresh on equip, unequip, and move. The +1 is on the chip before that refresh (hub addAttachment runs first).

Do not "simplify" this back to reading ModifiedInfluence as the source of Unclamped. The clamp-at-0 case is why Unclamped exists. Do not fold when an attachment has InfluenceLocked.
