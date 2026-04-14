# Maryam Benu Pleroma - EventAttachmentEquipping Support

Added `EventAttachmentEquipping` handling to `_01186.php` (Maryam Benu Pleroma). Her "Impervious Champion" forced ability was missing coverage for this event type - she already canceled `EventCardMoved`, `EventCardEngaged`, `EventChallengeIssued`, and `EventCharacterBeingWounded` from opponent risks, but not attachment equipping.

Used the same pattern as the other blocks: check `$event->characterId == $this->Id` (matching the field used in `Reaction_02048` for the same event type) plus the standard source validation.

This was prompted by `Reaction_02048` (Blood Like Winter) which handles all five event types including `EventAttachmentEquipping`. The user asked to update `_01186` to match.

## Attachment discard on cancel

When Maryam cancels an `EventAttachmentEquipping`, the attachment card that was being equipped needs to go to the correct discard pile. Without this, the card would be left in limbo since `EventAttachmentEquipped` (which normally handles placement) never fires.

WHY the CityAttachment vs else split: Follows the exact same pattern used in `Character.php:290-293` (`unEquipAllAttachments`). City attachments go to City Discard via `createCardAddedToCityDiscardPileEvent`. Faction attachments go to their owner's discard via `createCardDiscardedFromPlayEvent` using `$attachment->OwnerId`.

WHY `$event->playerId` for city attachment discard: `createCardAddedToCityDiscardPileEvent` takes a playerId param but city discard is a shared pile — the playerId is just for context/logging, not routing. For faction attachments we use `$attachment->OwnerId` which is the faction deck owner, matching the Character.php pattern.
