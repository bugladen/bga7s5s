# Unyielding Loyalty vs Shoddy Craftsmanship — card targeting

## Q1
Does UL trigger for a character targeted by Shoddy? **No** — Shoddy targets an attachment, not a character. And previously UL never offered even for the attachment.

## User correction
UL text is "when one or more of **your cards** is targeted." Reaction_01032 must not gate CharacterTargeted on getCharacterById only.

## Fix
1. **Reaction_01032** CharacterTargeted handler: `getCardById` + ControllerId (same as engage / Hexenjagd). WHY not a new EventCardTargeted: Hexenjagd already treats CharacterTargeted.targetId as any card; Maryam impervious matches `targetId == $this->Id` so attachment Ids are harmless; new event would duplicate UL hold/release/Pass/NoD machinery.
2. **Action_01174**: Amour/Solvente cancel-hook shape — queue CharacterTargeted with **attachment Id** as targetId + batchId; gate unequip/discard on `!canceled`. WHY attachment Id not host: printed target is the attachment; UL "your cards" is the attachment's ControllerId.
3. Comment on EventCharacterTargeted updated so future agents don't "fix" attachment targetIds back to characters-only.

## Not done
- `_01174` still implements `IRiskThatTargetsCharacters` (no IRiskThatTargetsCards exists). Maryam auto-cancel only when targetId == Maryam, so attachment targets are fine. Left alone to keep this change small.
- Other IAbilityThatTargetsCards Actions that destroy/steal attachments without a targeting hook were not audited.

## Tests
`php tests/run.php` — 393 passed.
