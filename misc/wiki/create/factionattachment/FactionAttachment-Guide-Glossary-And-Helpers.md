# 12 — Glossary and helpers

← [[11 — Examples|FactionAttachment Guide Example FactionAttachments To Copy]] · [[Index|Implementing a FactionAttachment Card]]

## Glossary

| Term | Meaning |
|---|---|
| **FactionAttachment** | Equipment from a player's faction deck that equips onto a character for Wealth. |
| **CityAttachment** | Equipment from the city deck — different base class and lifecycle. |
| **WealthCost** | Wealth paid to equip this attachment from hand. |
| **Equip** | Attach the card to a character (`AttachedToId` set). |
| **AttachedToId** | Character id this attachment sits on (`0` if unequipped). |
| **ControllerId** (on attachment) | The **equipping** player after equip — not always the host character's controller. |
| **OwnerId** | Deck owner — use for faction-deck **sink**. |
| **OffHand** | Does not count against one-Weapon / one-Armor uniqueness; still one Offhand per character. |
| **CanEquipToOpponents** | Allows equipping onto opposing characters. |
| **Opposing** | Different controller **and** same location. |
| **Theah** | In-memory game world (`$game->theah`). |
| **Event** | Something that happened; cards react in `handleEvent`. |
| **City Action / City Reaction** | Ability scoped to the city — still `AttachmentAction` / `AttachmentReaction` + `cardInCity`. |
| **Engage / Engaged** | Tired / committed. Attachments can be engaged as a cost. |
| **En Garde** | Usually means currently **unengaged** (ready). |
| **Sink** | Put on bottom of faction deck (equipped attachment: unequip → remove → deck). |
| **Discard / Destroy** | Leave play into discard (attachments: prefer `createAttachmentDiscardedFromPlayEvent`). |
| **Condition** | Stamped string on a character (`Game::*_CONDITION`) for lasting locks. |
| **Gambling** | Combat card obtained via duel gamble this round (`DUEL_GAMBLED`) — not a trait. |
| **Inject code** | Log markup from `getInjectCode()`. |
| **Globals** | Per-table store (`CHOSEN_PERFORMER`, `IN_DUEL`, `DUEL_GAMBLED`, …). |

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
$theah->getLeaderByPlayerId(int $playerId): ?Leader
$theah->getDuelRoundActor() / getDuelRoundOpponent()

$character->hasTrait(string $trait): bool
$character->addTrait(Game $game, string $trait)
$character->removeTrait(Game $game, string $trait)
$character->hasWeaponEquipped(Theah $theah): bool
$character->addCondition($condition) / hasCondition / removeCondition

$game->updateCardObjectInDb($card)
$game->getPlayerNameById(int $id): string   // not getActivePlayerName()
$game->getPlayerFactionDeckName(int $playerId): string
$game->characterIsInDiscardOrLocker(Character $character): bool
```

### Opponent detection on moves

Use `$event->sourceId` → source card `ControllerId`.  
**Do not** trust `EventCardMoving::$initiatingPlayerId` for "whose ability moved them."

### City location constants

- `Game::LOCATION_CITY_DOCKS`
- `Game::LOCATION_CITY_FORUM`
- `Game::LOCATION_CITY_BAZAAR`
- `Game::LOCATION_CITY_OLES_INN`
- `Game::LOCATION_CITY_GOVERNORS_GARDEN`
- `Game::LOCATION_PLAYER_HOME`

Prefer `getCityLocations()` / `getAdjacentCityLocations()` for pickers.

## EventFactory starters

```php
EventFactory::createAttachmentUnequippedEvent($playerId, $characterId, $attachmentId)
EventFactory::createAttachmentDiscardedFromPlayEvent($attachment, $sourceId, $asEffect)
EventFactory::createCardRemovedFromPlayEvent($playerId, $cardId, $location)
EventFactory::createCardAddedToFactionDeckEvent($playerId, $cardId, $onTop)  // false = sink bottom
EventFactory::createCardEngagedEvent($playerId, $cardId, $sourceId, $abilityId)
EventFactory::createCardMovingEvent($playerId, $cardId, $from, $to, $engage = true, $sourceId = 0, $abilityId = '')
EventFactory::createCharacterFinesseModifedEvent(...)  // note historical typo "Modifed"
EventFactory::createTransitionEvent($playerId, $sourceId, $transition, $abilityId)
EventFactory::createReactionTransitionEvent($playerId, $ownerId, $reactionId)
EventFactory::createActionResolvedEvent($playerId)
EventFactory::createSorcererAbilityStartEvent(...)
EventFactory::createSorcererAbilityPlayedEvent(...)
```

Exact parameter lists change — open Cavalier Hat / Lodestone / Torres Cloak / Harpoon and copy a working call.

## Pre-commit reminders (attachment subset)

| Pattern | Required |
|---|---|
| `AttachmentAction` | `createActionResolvedEvent()`; no `setUsed` / `announceAction` / `resetPlayerPassCount` |
| `AttachmentReaction` | `setUsed`, `isAvailable`, **and** `ownerIsAttached` |
| `ISorcererAbility` | Start **and** Played sorcerer events |
| `Technique` / `Maneuver` | `*Canceled` handler or literal comment |
| `FactionAttachment` | Always set `Riposte` |

## Related guides

- [[Creating a Character|Implementing a Character Card]] — people who wear attachments
- [[Creating a CityCharacter|Implementing a CityCharacter Card]] — city hirelings
- [[Creating a Scheme|Implementing a Scheme Card]] — scheme deck
- [[Creating a Leader|Implementing a Leader Card]] — setup Leaders

## Back to the start

← [[Index|Implementing a FactionAttachment Card]]
