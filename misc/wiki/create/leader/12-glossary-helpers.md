# 12 — Glossary and helpers

← [11 — Examples](11-examples.md) · [Index](README.md)

## Glossary

| Term | Meaning |
|---|---|
| **Theah** | The in-memory game world object (`$game->theah`). Most lookups go through it. |
| **Event** | Something that happened (dawn ended, card moved, ability played). Cards react in `handleEvent`. |
| **EventHub** | Dispatches events to cards and framework listeners. |
| **State** | One node in the BGA state machine — waiting for a player, or an automatic game step. |
| **Active player state** | Game waits for one player to act. |
| **Transition** | Named exit from a state (`"targetChosen"`, `"zombie"`, …). |
| **ControllerId** | Player id who currently controls the card. `0` = uncontrolled. |
| **Owner (of an ability)** | The Character/Leader card that owns the Action/Reaction (`getOwningCharacter`). |
| **Performer** | Character actually doing a challenge or ability — sometimes different from the Action owner. |
| **Opposing** | Different controller **and** same location. |
| **City location** | Docks / Forum / Bazaar / Ole's Inn / Governor's Garden (subset active by player count). |
| **Home** | Player's home area. Shared location string across players — never count "characters at Home" with `getCharactersAtLocation(HOME)`. |
| **Engage / Engaged** | Tired / committed state. Engaged characters often cannot pay Engage costs again. |
| **En Garde** | Usually means currently **unengaged** (ready). |
| **Renown** | Victory resource on locations / players. |
| **Locker / Discard** | Out-of-play zones. Destroyed characters often go to locker. |
| **Inject code** | Log markup from `getInjectCode()` so names appear styled and hoverable. |
| **Globals** | Per-table key/value store (`$game->globals`) for ephemeral flow data (`CHOSEN_PERFORMER`, `IN_DUEL`, …). |
| **Purgatory / park** | Temporary holding used by some framework moves — prefer public Game helpers over raw deck moves. |

## Expansions

| Code | Name |
|---|---|
| `_7s5s` | Base game |
| `tac` | Tooth and Claw |
| `faf` | Fate & Faith |
| `bas` | Newer expansion folder used for Blood and Steel–era cards |

## Useful Theah / Game helpers

```php
$theah->cardInCity($card): bool
$theah->locationInCity(string $location): bool
$theah->getCityLocations(): array          // active locations only — use array_keys() for pickers
$theah->getAdjacentCityLocations(string $location, bool $includeHome = true): array

$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): array
$theah->getCharactersAtLocationByPlayerId(string $location, int $playerId, …): array
$theah->getCharactersAtHomeByPlayerId(int $playerId): array   // YOUR home characters
$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array
$theah->getCharactersInPlayByPlayerId(int $playerId): array

$game->getControllerForLocation(string $location): int        // 0 = uncontrolled
$theah->canLocationBeClaimedBy(int $playerId, string $location): bool
$game->characterIsInDiscardOrLocker(Character $character): bool
$game->getPlayerNameById(int $id): string                     // not getActivePlayerName()

$this->hasTrait(string $trait): bool
$this->getInjectCode(): string
```

### Duel helpers

```php
$theah->getDuelRoundActor(): ?Character
$theah->getDuelRoundOpponent(): ?Character
$theah->getDuelChallengerId(): ?int
$theah->getDuelDefenderId(): int
$theah->getCombatCardsForCurrentRound(): array

Game::IN_DUEL          // bool global
Game::DUEL_GAMBLED     // bool global — actor gambled this round
```

### City location constants (real names)

- `Game::LOCATION_CITY_DOCKS`
- `Game::LOCATION_CITY_FORUM`
- `Game::LOCATION_CITY_BAZAAR`
- `Game::LOCATION_CITY_OLES_INN`
- `Game::LOCATION_CITY_GOVERNORS_GARDEN`

There is no `LOCATION_BORDELLO` / `LOCATION_CATHEDRAL` / `LOCATION_DOCKS` / `LOCATION_MARKET`. Prefer `getCityLocations()` for pickers.

## EventFactory starters

```php
EventFactory::createCardDrawnEvent($playerId, $reason)
EventFactory::createCardMovingEvent($playerId, $cardId, $from, $to, $engage, $sourceId, $abilityId)
EventFactory::createCardEngagedEvent($playerId, $cardId, $sourceId, $abilityId)
EventFactory::createCharacterBeingWoundedEvent(...)
EventFactory::createCharacterBeingHealedEvent(...)
EventFactory::createTransitionEvent($playerId, $sourceId, $transitionName, $internalId)
EventFactory::createReactionTransitionEvent($playerId, $ownerId, $reactionId)
EventFactory::createTechniqueTransitionEvent(...)
EventFactory::createActionResolvedEvent(...)
EventFactory::createGainLethalEvent($actorId, $theah)
```

Exact parameter lists change — open a mirror Action/Reaction and copy a working call.

## Style reminders

- PHP: PSR-12, 4-space indent.
- User-visible strings: `clienttranslate('...')` at definition; `$game->translate('...')` when you need a translated string at runtime in some contexts.
- Throw `\Bga\GameFramework\UserException`, not deprecated `BgaUserException`.
- Do not call raw `$deck->moveCard()` outside approved helpers — use `Game::moveCard`, `moveCardInDeck`, or `parkCard`.

## Pre-commit (short)

| If your class… | Must include |
|---|---|
| Extends an Action base that resolves | `createActionResolvedEvent()` |
| Extends `CardReaction` | `$this->setUsed(` and `$this->isAvailable(` literals |
| `implements ISorcererAbility` | `createSorcererAbilityStartEvent` **and** `createSorcererAbilityPlayedEvent` |
| Calls `createAttachmentEquippedEvent` | Also `getRequiredAttachTargetId()` |

Full table: project `CLAUDE.md` and `.githooks/pre-commit`.

## Back to the start

Return to the [index](README.md) when you begin a new Leader.
