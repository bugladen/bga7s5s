# Action_01049 — ControllerId on null (Katain copy)

## Crash
`isValidTargetForAbility` line 82: `$owner->ControllerId` when `$owner` is null.
Stack: actFromActionWithId(HIGH_DRAMA_PLAYER_TURN_01049) → isValidTargetForAbility targeting Angeline (_03025).

## Root cause
`$owner = $this->getOwningAttachment(...)`. Returns null when OwnerId is a Character.

Katain (`Reaction_02011`) copies Action_01049 onto himself with `setOwnerId($katain->Id)` and `originalAttachmentId = $source->Id`. Owner is Character → getOwningAttachment null → fatal.

Sibling Technique_01049 already uses getOwningCard everywhere (engage via originalAttachmentId). Action path was inconsistent: handleEvent / 01049_2 used getOwningCard; isValidTargetForAbility + first half of actFromActionWithId used getOwningAttachment.

## Fix
Both call sites → getOwningCard. Engage cost still uses `originalAttachmentId ?? $owner->Id` so Katain copy engages the real flintlock.

## WHY not getOwningCharacter
Need Card for getInjectCode / ControllerId on the ability host; Character works for location checks but Card matches Technique_01049 and the rest of this action. Same pattern as Reaction_01008 copy-hosting fix (2026-05-29-02).

## Do not regress
Leave originalAttachmentId engage logic alone — without it a Katain-hosted copy would engage Katain instead of the flintlock.
