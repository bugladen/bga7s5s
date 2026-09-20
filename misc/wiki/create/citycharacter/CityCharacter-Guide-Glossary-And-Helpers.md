# 12 — Glossary and helpers

← [[11 — Examples|CityCharacter Guide Example CityCharacters To Copy]] · [[Index|Implementing a CityCharacter Card]]

## Glossary

| Term | Meaning |
|---|---|
| **CityCharacter** | A Character that lives in the city deck until mustered. |
| **City deck** | Shared deck of city cards (characters, attachments, events). |
| **CityCardNumber** | Printed city-deck index. `CardNumber` stays `0` for these cards. |
| **WealthCost** | Wealth paid to muster this hireling from the city. |
| **Negotiable** | Printed keyword — parley is allowed when paying the WealthCost. |
| **Muster** | Putting a CityCharacter into play under a player's control. |
| **Theah** | The in-memory game world object (`$game->theah`). |
| **Event** | Something that happened. Cards react in `handleEvent`. |
| **City Forced** | Automatic ability that fires while the card is in the city (no player choice). |
| **City Action / City Reaction** | Ability scoped to the city — still uses CharacterAction / CardReaction on a CityCharacter. |
| **ControllerId** | Player who controls the card. Often `0` while uncontrolled in the city. |
| **Opposing** | Different controller **and** same location. |
| **City location** | Docks / Forum / Bazaar / Ole's Inn / Governor's Garden (subset active by player count). |
| **Engage / Engaged** | Tired / committed state. |
| **En Garde** | Usually means currently **unengaged** (ready). |
| **Inject code** | Log markup from `getInjectCode()`. |
| **Globals** | Per-table store (`$game->globals`) for ephemeral flow data (`CHOSEN_TARGET`, `IN_DUEL`, …). |

## Expansions

| Code | Name |
|---|---|
| `_7s5s` | Base game |
| `tac` | Tooth and Claw |
| `faf` | Fate & Faith |
| `bas` | Newer expansion folder used for Blood and Steel–era cards |

## Useful Theah / Game helpers (CityCharacter-heavy)

```php
$theah->cardInCity($card): bool
$theah->locationInCity(string $location): bool
$theah->getCityLocations(): array          // active locations — array_keys() for pickers
$theah->getAdjacentCityLocations(string $location, bool $includeHome = true): array

$theah->getCharactersAtLocationByPlayerId(string $location, int $playerId, …): array
$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array

$game->getCardsOnTopOfCityDeck(int $n): array   // raw rows — cast id to int
$game->getGameDeckObject()->shuffle(Game::LOCATION_CITY_DECK)

$game->getPlayerNameById(int $id): string       // not getActivePlayerName()
$this->getInjectCode(): string
$this->hasTrait(string $trait): bool
```

### City location constants (real names)

- `Game::LOCATION_CITY_DOCKS`
- `Game::LOCATION_CITY_FORUM`
- `Game::LOCATION_CITY_BAZAAR`
- `Game::LOCATION_CITY_OLES_INN`
- `Game::LOCATION_CITY_GOVERNORS_GARDEN`
- `Game::LOCATION_CITY_DECK`

Prefer `getCityLocations()` / `getAdjacentCityLocations()` for pickers.

## EventFactory starters

```php
EventFactory::createCardEngagedEvent(...)
EventFactory::createCardMovingEvent(...)
EventFactory::createCardRemovedFromPlayEvent($controllerId, $cardId, $toLocation)
EventFactory::createCityCardAddedToLocationEvent(int $cardId, string $location)
EventFactory::createCharacterBeingWoundedEvent(...)
EventFactory::createTransitionEvent($playerId, $sourceId, $transitionName, $internalId)
EventFactory::createReactionTransitionEvent($playerId, $ownerId, $reactionId)
EventFactory::createActionResolvedEvent(...)
```

Exact parameter lists change — open Penya / Julius and copy a working call.

## Style reminders

- PHP: PSR-12, 4-space indent.
- User-visible strings: `clienttranslate('...')` at definition.
- Throw `\Bga\GameFramework\UserException`, not deprecated `BgaUserException`.
- Do not call raw `$deck->moveCard()` outside approved helpers — use `Game::moveCard`, `moveCardInDeck`, or `parkCard`.

## Pre-commit (short)

| If your class… | Must include |
|---|---|
| Extends `CharacterAction` (etc.) | `createActionResolvedEvent()` — and **no** `setUsed` / `resetPlayerPassCount` / `announceAction` |
| Extends `CardReaction` | `$this->setUsed(` and `$this->isAvailable(` literals |
| `implements ISorcererAbility` | start **and** played sorcerer events |

Full table: project `CLAUDE.md` and `.githooks/pre-commit`.

## Related guides

- [[Creating a Character|Implementing a Character Card]] — faction-deck people
- [[Creating a Leader|Implementing a Leader Card]] — setup Leaders
- Agent skill (deeper / denser): `.claude/skills/create-city-character/` — use when you need an edge-case recipe the wiki does not cover yet

## Back to the start

Return to the [[index|Implementing a CityCharacter Card]] when you begin a new CityCharacter.
