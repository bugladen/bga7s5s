# Vantage Point missed Cloud Bottle targeting

## Report
Cloud Bottle (`Reaction_05Terraria`) did not offer when the equipped Covert character was targeted by Vantage Point (`Reaction_04020`).

## Root cause
Same class as Bleed Out (journal `2026-11-20-13`): Vantage Point implements `IAbilityThatTargetsCharacters` and prints "target character", but `chooseCharacter` called `resolveTargetCharacter` inline — engage/wound / engageOrWound stage with no `EventCharacterTargeted`. Terraria only intercepts CharacterTargeted / ChallengeIssued.

## Fix
On `Reaction_04020` character pick:
1. notify + queue `EventCharacterTargeted` with `batchId` only
2. `handleEvent` on surviving CharacterTargeted → `resolveTargetCharacter` (engaged auto-wound or engageOrWound transition)
3. Shared `batchId` on wound / engageOrWound transition so if this Reaction queues before Cloud Bottle's HAND cancel during dispatch, `deleteEventBatch` strips siblings

WHY not change Terraria: reaction was correct; the Risk was the missing emitter.

WHY leave −1 pressure if targeting canceled: opponent targeting already resolved before character pick; Cloud Bottle cancels effects of targeting the *character*, not the prior opponent penalty.

WHY no finalize on cancel: Risk already left hand after pay; dirty stage fields on a discarded card cannot re-offer. RangedAbilityPlayed stays tied to surviving finalize only.

## Tests
- `Reaction_04020_Test`: pick = CharacterTargeted only; survive = engageOrWound / auto-wound; cancel = no continue
- `Card_05Terraria_Test`: Vantage Point abilityId offers the reaction
- 15/15 pass on those two suites

## Docs
Updated Pattern D.1.2 + checklist 26c/58 + references — CharacterTargeted cancel hook is required for printed "target character" on RiskReaction engage-or-wound.

## Unfinished
Did not audit other RiskReactions that pick a "target character" without CharacterTargeted (D.1.2 was the only documented engage-or-wound RiskReaction; other multi-stage character picks may still miss the hook).
