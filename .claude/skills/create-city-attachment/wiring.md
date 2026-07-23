> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pre-Commit Hook (relevant subset)

`.githooks/pre-commit` enforces, for files you touch in the attachment patterns above:

| Pattern | Required |
|---|---|
| `extends AttachmentAction/CardAction/...` | `createActionResolvedEvent` somewhere in the class (call or comment) |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed()` AND `$this->isAvailable()` |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()` *in the same file* (concerns the Action that performs the equip, not the attachment being equipped) |
| **Forbidden in `AttachmentAction` subclasses** | `setUsed` / `resetPlayerPassCount` / `announceAction` — these run centrally |
| `implements ISorcererAbility` | both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` |

**Not required for CityAttachment:** `$this->Riposte =`. That pre-commit check applies only to `FactionAttachment` (combat-card stats via `FactionCardTrait`). City attachments have no Riposte property — do not invent a dummy one.

When the attachment merely *reacts* to `EventAttachmentEquipped` (as `_03cd05` does on equip-wound), you are not *creating* the event — the `getRequiredAttachTargetId` rule does not apply.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- **"Action" vs "City Action":** no `AttachmentCityAction` — gate City Actions with `cardInCity($owner)` on `AttachmentAction`.
- **"Engage this card"** → engage `$attachment->Id`. **"Engage the equipped performer"** → engage `$owner->Id`. Move effects after an attachment engage cost use `createCardMovingEvent(..., engage=false)`.
- **"Sink this card"** on a CityAttachment → unequip → removeFromPlay → `createCardAddedToCityDeckEvent(..., false)`. Not city discard (destroy), not faction deck (FactionAttachment), not locker.
- **"Destroy this card"** on a CityAttachment → unequip → `createCardAddedToCityDiscardPileEvent`.
- Namespaces:
  - Card class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action: `...\cards\<expansion>\actions`
  - Reaction: `...\cards\<expansion>\reactions`
  - State: `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`

## First-time expansion wiring (e.g. `bas`)

When the expansion has **no** prior State/JS modules (as with Blood & Steel / `bas` for `_04cd01`):

1. **PHP states:** `modules/php/States/<expansion>/State_highDramaPhaseNNNNN.php` (GameState subclass). Constants under the expansion block in `States.php` (bas = Expansion 4, e.g. `4040001`).
2. **Transition only:** add `"NNNNN" => States::…` on `HIGH_DRAMA_PLAYER_TURN_EVENTS` in `states.inc.php`. State classes auto-register — do **not** add entries to `states.7s5s.php`.
3. **JS modules:** create `OnEnteringState.<exp>.js`, `OnUpdateActionButtons.<exp>.js`, `OnLeavingState.<exp>.js` (Dojo declare `seventhseacityoffivesails.on*_<exp>`).
4. **Wire them:**
   - `seventhseacityoffivesails.js` — add the three files to `define([...])` and to the mixin array.
   - `OnEnteringState.js` / `OnUpdateActionButtons.js` / `OnLeavingState.js` — call `this.onEnteringState_<exp>(...)` (etc.) after the existing expansion calls.

`createCardInLocation('<id>_RiskClone', …)` resolves the class via `getCardClassName`: first two characters of the id select the expansion (`04` → `bas` → `cards\bas\_04cd01_RiskClone`).
