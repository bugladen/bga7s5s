# 12 — Glossary and helpers

← [[11 — Examples|Scheme Guide Example Schemes To Copy]] · [[Index|Implementing a Scheme Card]]

## Glossary

| Term | Meaning |
|---|---|
| **Theah** | The in-memory game world object (`$game->theah`). Most lookups go through it. |
| **Event** | Something that happened (scheme resolved, planning ended, ability played). Cards react in `handleEvent`. |
| **EventHub** | Dispatches events to cards and framework listeners. |
| **State** | One node in the BGA state machine — waiting for a player, or an automatic game step. |
| **Transition** | Named exit from a state, or a string key looked up on an EVENTS map (`"03005"`). |
| **Initiative** | Scheme field — lower resolves earlier in Planning. |
| **PanacheModifier** | Added to Leader Panache while this scheme is chosen. |
| **When-Revealed** | Fires before any scheme resolve when `hasWhenRevealedEffect()` is true. |
| **Resolve** | The Planning effect for the chosen scheme (`EventResolveScheme`). |
| **Chosen scheme / Home** | After Planning, the scheme sits at `LOCATION_PLAYER_HOME` until Dusk, then Locker. |
| **ControllerId** | Player who chose / controls the scheme this day. |
| **Owner (of an ability)** | The Scheme card that owns the Action/Reaction (`getOwningCard`). |
| **Performer** | Character doing a City Action or challenge — not the scheme card itself. |
| **Opposing** | Different controller **and** same location. |
| **City location** | Docks / Forum / Bazaar / Ole's Inn / Governor's Garden (subset active by player count). |
| **Home** | Player home area. Shared location string across players — be careful with "same location" checks. |
| **Renown (location)** | Tokens on a city location. |
| **Renown (score)** | Player victory points — what **"Spend a Renown"** spends. |
| **Locker / Discard** | Out-of-play zones. Schemes go to Locker at Dusk, not discard after resolve. |
| **Inject code** | Log markup from `getInjectCode()` so names appear styled and hoverable. |
| **Globals** | Per-table key/value store (`CHOSEN_PERFORMER`, `CHOSEN_LOCATION`, …). |
| **GameState class** | Modern per-state PHP class under `modules/php/States/<expansion>/`. Prefer for new work. |

## Expansions

| Code | Name |
|---|---|
| `_7s5s` | Base game |
| `tac` | Tooth and Claw |
| `faf` | Fate & Faith |
| `bas` | Newer expansion folder used for Blood and Steel–era cards |

## State id cheatsheet

| Family | Id shape | Example |
|---|---|---|
| Planning resolve pick | `26` + card number | `2603005` |
| Planning End Forced pick | `28` + card number | `2803041` |
| High Drama Action pick | `40` + card number | `403053` / `4030292` |
| High Drama End Forced pick | `60` + card number | `603061` |

## Useful Theah / Game helpers

```php
$theah->cardInCity($card): bool
$theah->locationInCity(string $location): bool
$theah->getCityLocations(): array
$theah->getCityLocation(string $name): ?CityLocation

$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): array
$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array
$theah->getCharactersInPlayByPlayerId(int $playerId): array  // "control a Trait" checks
$theah->getAvailableAttachmentsAtLocation(string $location): array
$theah->canLocationBeClaimedBy(int $playerId, string $location): bool

$game->getPlayerReknown(int $playerId): int                 // score Renown ("Spend a Renown")
$game->getCardObjectFromDb(int $id): ?Card
$game->getGameDeckObject(int $playerId): Deck
$game->getPlayerDiscardDeckName(int $playerId): string
$game->updateCardObjectInDb($card): void                    // required after Action field mutations
$game->getPlayerNameById(int $id): string                   // not getActivePlayerName()

$this->hasTrait(string $trait): bool
$this->getInjectCode(): string
```

## Event factories you will use often

```php
EventFactory::createRenownAddedToLocationEvent($playerId, $location, $count, $reason, $isMove = false)
EventFactory::createRenownRemovedFromLocationEvent($playerId, $location, $count, $reason)
EventFactory::createRenownMovingBetweenLocationsEvent($playerId, $from, $to, $amount, $description)
EventFactory::createPlayerLosesReknownEvent($playerId, $amount)  // "Spend a Renown"

EventFactory::createCardDrawnEvent($playerId, $reason)
EventFactory::createCardDiscardedFromHandEvent($ownerId, $cardId, $sourceId, $asPayment, $asPlayed, $asEffect)
EventFactory::createCardRemovedFromPlayerDiscardPileEvent($playerId, $cardId)
EventFactory::createCardAddedToHandEvent($playerId, $cardId)

EventFactory::createLocationClaimedEvent($playerId, ?int $performerId, $location)
EventFactory::createTransitionEvent($playerId, $sourceId, string $internalId)
EventFactory::createReactionTransitionEvent($playerId, $sourceId, $reactionId)
EventFactory::createActionResolvedEvent($playerId)
```

## Style reminders

- Typed parameters on every method — no bare `$foo`.
- `UserException` from `Bga\GameFramework\UserException` (not deprecated `BgaUserException`).
- PSR-12, 4-space indent.
- Windows PHP in this repo uses single CRLF (`\r\n`).

## Related guides

- [[Creating a Character|Implementing a Character Card]]
- [[Creating a Leader|Implementing a Leader Card]]

Deep agent reference (not written for beginners): `.claude/skills/create-scheme/` in the main repo.
