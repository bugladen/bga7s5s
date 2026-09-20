# 11 — Glossary and helpers

← [[10 — Examples|CityAttachment Guide Example CityAttachments To Copy]] · [[Index|Implementing a CityAttachment Card]]

## Glossary

| Term | Meaning |
|---|---|
| **CityAttachment** | Equipment from the **city deck** that equips onto a character for Wealth. |
| **FactionAttachment** | Equipment from a player's faction deck — different base class and lifecycle. |
| **CityEventCard** | One-shot city event — not lasting gear. |
| **WealthCost** | Wealth paid to equip this attachment from the city. |
| **CityCardNumber** | Visible city-deck number on the card. |
| **Equip** | Attach the card to a character (`AttachedToId` set). |
| **AttachedToId** | Character id this attachment sits on (`0` if unequipped). |
| **ControllerId** (on city attachment) | Usually mirrors the equipped character's controller. |
| **Theah** | In-memory game world (`$game->theah`). |
| **Event** | Something that happened; cards react in `handleEvent`. |
| **City Action / City Reaction** | Ability scoped to the city — still `AttachmentAction` / `AttachmentReaction` + `cardInCity`. |
| **Engage / Engaged** | Tired / committed. Attachments can be engaged as a cost. |
| **Destroy** (city attachment) | Unequip then send to **city discard**. |
| **Sink** | Usually faction-deck bottom — rare on city gear; read the printed text. |
| **Steady-state** | A play-area property recomputed via `get*` overrides (e.g. gamble reveal count). |
| **Condition** | Stamped string (`Game::*_CONDITION` / ability-used markers) for lasting locks or once-per-Day flags. |
| **Inject code** | Log markup from `getInjectCode()`. |
| **Globals** | Per-table store (`GAMBLE_REVEAL_FROM_BOTTOM`, `IN_DUEL`, …). |

## Expansions

| Code | Name |
|---|---|
| `_7s5s` | Base game |
| `tac` | Tooth and Claw |
| `faf` | Fate & Faith |
| `bas` | Newer expansion folder used for Blood and Steel–era cards |

## Useful helpers (attachment-heavy)

```php
$this->isAttached(): bool
$this->attachedTo(Theah $theah): ?Card
$this->canAttachTo(Character $character): bool

$theah->cardInCity(Card $card): bool
$theah->locationInCity(string $location): bool
$theah->getAdjacentCityLocations(string $location, bool $includeHome = true): array
$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): array
$theah->getAvailableAttachmentsAtLocation(string $location): array
$theah->getAttachmentById(int $id): ?Attachment
$theah->getCharacterById(int $id): ?Character

$character->hasTrait(string $trait): bool
$character->addTrait(Game $game, string $trait)
$character->removeTrait(Game $game, string $trait)
$character->addCondition($condition) / hasCondition / removeCondition

$game->updateCardObjectInDb($card)
$game->getPlayerNameById(int $id): string   // not getActivePlayerName()
```

### Deck helpers (Bones / bottom-reveal)

```php
$game->getCardsOnTopOfPlayerFactionDeck($playerId, $nbr)
$game->getCardsOnBottomOfPlayerFactionDeck($playerId, $nbr)
// insertCardOnExtremePosition($card, $location, $bOnTop)
//   $bOnTop = true  → place on top
//   after bottom-reveal, sink uses top; after top-reveal, sink uses bottom
```

## EventFactory starters

```php
EventFactory::createAttachmentUnequippedEvent($playerId, $characterId, $attachmentId)
EventFactory::createAttachmentDiscardedFromPlayEvent($attachment, $sourceId, $asEffect)
EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $cardId, $location, $sourceId, $asEffect)
EventFactory::createCharacterBeingWoundedEvent($characterId, $sourceId, $wounds, $injectCode)
EventFactory::createCardEngagedEvent($playerId, $cardId, $sourceId, $abilityId)
EventFactory::createTransitionEvent($playerId, $sourceId, $transition, $abilityId)
EventFactory::createReactionTransitionEvent($playerId, $ownerId, $reactionId)
EventFactory::createActionResolvedEvent($playerId)
```

Exact parameter lists change — open Bones / Triskelion / Smuggled Item / Sorte Deck / Silver Spine and copy a working call.

## Pre-commit reminders (city attachment subset)

| Pattern | Required |
|---|---|
| `AttachmentAction` | `createActionResolvedEvent()`; no `setUsed` / `announceAction` / `resetPlayerPassCount` |
| `AttachmentReaction` | `setUsed` **and** `isAvailable` |
| Calls `createAttachmentEquippedEvent()` | Must also reference `getRequiredAttachTargetId` **in the same file** (usually the Action that equips — not the attachment listening for equip) |
| `ISorcererAbility` | Start **and** Played sorcerer events |
| `CityAttachment` | **Does not** require `Riposte` (unlike FactionAttachment) |

## Related guides

- [[Creating a FactionAttachment|Implementing a FactionAttachment Card]] — faction-deck gear
- [[Creating a CityCharacter|Implementing a CityCharacter Card]] — city hirelings
- [[Creating a Character|Implementing a Character Card]] — faction-deck people
- [[Creating a Scheme|Implementing a Scheme Card]] — scheme deck
- [[Creating a Leader|Implementing a Leader Card]] — setup Leaders

## Back to the start

[[Index — Creating a CityAttachment|Implementing a CityAttachment Card]]
