> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pre-Commit Hook (relevant subset)

`.githooks/pre-commit` enforces, for files you touch in the attachment patterns above:

| Pattern | Required |
|---|---|
| `extends AttachmentAction/CardAction/...` | `createActionResolvedEvent()` somewhere in the class |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed()` AND `$this->isAvailable()` |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()` *in the same file* (concerns the Action that performs the equip, not the attachment being equipped) |
| **Forbidden in `AttachmentAction` subclasses** | `setUsed` / `resetPlayerPassCount` / `announceAction` — these run centrally |
| `implements ISorcererAbility` | both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` |

When the attachment merely *reacts* to `EventAttachmentEquipped` (as `_03cd05` does on equip-wound), you are not *creating* the event — the `getRequiredAttachTargetId` rule does not apply.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Card class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action: `...\cards\<expansion>\actions`
  - Reaction: `...\cards\<expansion>\reactions`
  - State: `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`
