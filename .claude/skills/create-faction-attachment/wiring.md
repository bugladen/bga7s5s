> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pre-Commit Hook (relevant subset)

`.githooks/pre-commit` enforces, for files you touch in the attachment patterns above:

| Pattern | Required |
|---|---|
| `extends AttachmentAction/CardAction/...` | `createActionResolvedEvent()` somewhere in the class |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed(` AND `$this->isAvailable(` |
| `extends AttachmentReaction` | additionally `$this->ownerIsAttached(` |
| `extends Maneuver` | handle `EventManeuverCanceled` (or add the literal comment `EventManeuverCanceled handler not needed`) |
| `extends Technique` | handle `EventTechniqueCanceled` (or add the literal comment `EventTechniqueCanceled handler not needed`) |
| `implements ISorcererAbility` | both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()` *in the same file* (concerns the Action that performs the equip, not the attachment being equipped) |
| **Forbidden in `AttachmentAction` subclasses** | `setUsed` / `resetPlayerPassCount` / `announceAction` — these run centrally |
| **Forbidden anywhere** | implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` on the same class |

The attachment card class itself (the file under `cards/<expansion>/_NNNNN.php`) usually has none of these checks active — the hook focuses on Action/Reaction/Technique/Maneuver files.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- **Typed PHP parameters required.** Every function/method signature must declare a type for every parameter — no bare `$foo`. Use concrete types (`Card $owner`, `Character $performer`, `Game $game`, `Theah $theah`, `Event $event`, `int $cardId`, `string $reactionId`). Add the `use` import.
- **Traits in `TraitNames::$TraitsJson`** — add missing ones in alphabetical order.
- **"Opposing"** means BOTH different controller AND same location.
- **"Strega" / "Mercenary" / "Diplomat" / etc.** are **mechanical performer-trait gates**, not flavor. Enforce via `hasTrait("Strega")` on the chosen performer. They are NOT Sorcerer abilities. Only the literal "Sorcerer" keyword triggers `ISorcererAbility`. They can stack.
- **"Action" vs "City Action" performer scope:** "City Action:" restricts performers to characters in the city (`cardInCity`); plain "Action:" includes characters at Home. There is **no** `AttachmentCityAction` — gate manually on `AttachmentAction`. (Memory feedback.)
- **"Engage this card" vs "Engage the equipped performer":** engage `$attachment->Id` vs `$owner->Id`; gate availability on the matching card's `Engaged`. Move effects that accompany an attachment engage cost use `createCardMovingEvent(..., engage=false)`.
- **"a location" vs "City location" destinations:** literal "City" → city slots only. Unqualified "a location" → include `LOCATION_PLAYER_HOME` when the filter can match. JS must use `makeHomeEndcapMarkerSelectable` for Home — do not copy city-only enter handlers from `03032`/`03045`.
- **Self-trait destination hazard:** if this attachment *is* the trait the destination looks for (Artifact, etc.), exclude the performer's current location or every destination list includes a no-op stay.
- **"Available or equipped" attachments:** `getAvailableAttachmentsAtLocation` (unattached) **and** walk `character->Attachments` at the location. Skip `FakeAttachment`.
- **"Gambling Technique/Maneuver"** is a duel-round gate (`DUEL_GAMBLED` + actor identity), not a trait. See Pattern E.
- **Cancel Maneuver/Technique:** Activated (not Resolve); `HIGH_PRIORITY` transitions; cancel-first when multi-stage "unless discard"; correct character/player id gates — do not copy `Reaction_01047`'s compare. See Pattern D / `_03044`.
- Namespaces:
  - Attachment class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:           `...\cards\<expansion>\actions`
  - Reaction:         `...\cards\<expansion>\reactions`
  - Technique:        `...\cards\<expansion>\techniques`
  - Maneuver:         `...\cards\<expansion>\maneuvers`
