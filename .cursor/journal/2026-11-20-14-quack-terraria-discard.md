# Quack missed Terraria Cloud Bottle discard

## Report
Reaction_05Terraria canceled a target on Quack at an uncontrolled location; Reaction_05Quack did not offer.

## Root cause
Quack listened only to `EventCardDiscardedFromHand`. Terraria's cancel cost destroys Cloud Bottle via `createAttachmentDiscardedFromPlayEvent` → `EventCardDiscardedFromPlay`. Cloud Bottle is Alquimia, so that discard should trip Quack.

Covert was a red herring — location was uncontrolled.

## Fix
`Reaction_05Quack` now listens on all three discard pipelines (hand / from-play / city discard), same shape as Cat's Embargo `Reaction_01098`. "you" = `ownerId` (hand/from-play) or city `playerId`.

WHY not change Terraria: destroy cost already emits the right discard event; Quack was under-listening.

WHY city discard too: Discovery/Alquimia city attachments can leave via that pipeline; hand+from-play alone would regress the same class of miss later.

## Skill
Updated Pattern D / checklist 89d / SKILL shape row / references — was documenting hand-only.

## Tests
`Reaction_05Quack_Test`: +from-play Cloud Bottle offers; +opponent from-play does not. 9/9 pass.

## Unfinished
None for this combo. Did not audit other "when you discard" reactions for hand-only under-listen.
