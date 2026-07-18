> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Cross-Cutting Helpers

- `$theah->getCharacterById(int $id): ?Character` — hydrate a character by id.
- `$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): Character[]` — every Character at a location. Filter by `ControllerId` for friend/foe.
- `$theah->getAvailableAttachmentsAtLocation(string $location): Attachment[]` — unattached attachments sitting at a location ("available"). Pair with equipped scans when text says "available or equipped."
- `$theah->getAttachmentById(int $id): ?Attachment` — hydrate an equipped (or available) attachment.
- `$theah->getLeaderByPlayerId(int $playerId): ?Leader` — get a player's Leader (returns null if destroyed).
- `$theah->getCardObjectsAtLocation(string $location, int $playerId = 0): Card[]` — all cards in a generic location (`Game::LOCATION_HAND`, `Game::LOCATION_PLAYER_HOME`, etc.). For hand, pass the player id.
- `$theah->locationInCity(string $location): bool` / `$theah->cardInCity(Card $card): bool` — canonical City checks. **Required** for attachment City Actions (no `AttachmentCityAction` base).
- `$theah->getCityLocations(): CityLocation[]` — all five city slots; map `->Name` when building destination lists.
- `$game->getCardObjectFromDb(int $id): ?Card` — hydrate any card from db (works even if it's not in `Theah::$cards`).
- `$game->getGameDeckObject(int $playerId = 0): Deck` — get a player's deck wrapper. `getCardsInLocation(getPlayerDiscardDeckName($playerId))` queries discard; `getPlayerHand($playerId)` queries hand.
- `$game->getPlayerFactionDeckName(int $playerId): string` — the deck-table location string for a player's faction deck.
- `$game->getPlayerNameById(int $playerId): string` — use this instead of deprecated `getActivePlayerName()`.
- `$card->hasTrait(string $trait): bool` — check a trait.
- `$character->addTrait(Game $game, string $trait)` / `$character->removeTrait(Game $game, string $trait)` — for passive trait grants.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${attachment_inject_code}` placeholder).

Event factories you'll likely need:
- `createAttachmentUnequippedEvent($playerId, $characterId, $attachmentId)`
- `createCardDiscardedFromPlayEvent($playerId, $cardId, $location, $sourceId, $asEffect)`
- `createCardEngagedEvent($playerId, $cardId, $sourceId, $abilityId)`
- `createCardMovingEvent($playerId, $cardId, $from, $to, $engage = true, $sourceId = 0, $abilityId = '')` — default `$engage = true`; pass `false` when move is an effect and engage was a separate cost
- `createTransitionEvent($playerId, $sourceId, $transition, $abilityId)` — for attachment Actions, `$sourceId` is usually the **attachment** id
- `createCharacterBeingWoundedEvent($characterId, $sourceId, $wounds, $reason, $abilityId = '')`
- `createReactionTransitionEvent($playerId, $sourceId, $reactionId)`
- `createActionResolvedEvent($playerId)`
- `createSorcererAbilityStartEvent($playerId, $sourceId, $abilityId, $performerId, $targetId = 0, $targetLocation = '')`
- `createSorcererAbilityPlayedEvent($playerId, $sourceId, $abilityId, $performerId, $targetId = 0, $targetLocation = '')`
