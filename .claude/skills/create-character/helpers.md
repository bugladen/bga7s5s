> Part of **create-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Cross-Cutting Helpers

- `$theah->cardInCity($card): bool` — true when the card is at a city location.
- `$theah->locationInCity(string $location): bool` — true for any active city location. Use inside an `EventCardMoved` handler against `$event->toLocation` — `$owner->Location` is still the OLD location at that point because `EventCardMoved.runEventHubAfterCards = true` defers the state write until after every card's `handleEvent` runs.
- `$theah->getCityLocations(): array` — keyed by location id. `array_keys($theah->getCityLocations())` enumerates the ACTIVE city locations (3 in 2p / 4 in 3p / 5 in 4p — Ole's Inn and Governor's Garden are excluded in smaller games). Always use this over hardcoded `LOCATION_*` arrays for "pick any city location" pickers. The actual city-location constants are `LOCATION_CITY_DOCKS`, `LOCATION_CITY_FORUM`, `LOCATION_CITY_BAZAAR`, `LOCATION_CITY_OLES_INN`, `LOCATION_CITY_GOVERNORS_GARDEN` — there is no `LOCATION_BORDELLO` / `LOCATION_CATHEDRAL` / `LOCATION_DOCKS` / `LOCATION_MARKET` (those are hallucinated names; check before using).
- `$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): array` — all characters at a location (default excludes uncontrolled, which is usually what you want).
- `$theah->getCharactersAtLocationByPlayerId(string $location, int $playerId, bool $includeUncontrolled = false): array` — friendly characters at a location.
- `$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array` — opposing = different controller AND same location.
- `$theah->getCharactersInPlayByPlayerId(int $playerId): array` — all characters in play controlled by a player.
- `$theah->getCharactersInCityByPlayerId(int $playerId): array` — characters in city (not Home, not approach).
- `$theah->getAdjacentCityLocations(string $location, bool $includeHome = true): array` — adjacency for move actions.
- `$game->characterIsInDiscardOrLocker(Character $character): bool` — "is this character out of play (discard or locker)?" The Leader-equivalent of `isInPlay`. Gate phase-event handlers on `! characterIsInDiscardOrLocker($this)`.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${card_inject_code}` placeholder).
- `$this->hasTrait(string $trait): bool` — check a trait against `$this->ModifiedTraits`. English trait strings compare directly against `clienttranslate()`-wrapped values.

Duel-specific (used in Pattern E and the in-duel branch of any ability):
- `$theah->getDuelRoundActor(): ?Character` / `getDuelRoundOpponent(): ?Character` — current round participants.
- `$theah->getDuelChallengerId(): ?int` / `getDuelDefenderId(): int` / `getDuelOpponentId(int $actorId): int` — id-only accessors.
- `$theah->getCombatCardsForCurrentRound(): array` — combat cards played in the current round.
- `$theah->getCurrentDuelThreat(int $characterId): int` — running threat against a participant.
- `EventFactory::createGainLethalEvent(int $actorId, Theah $theah)` — produces a `ThreatModified` event marking the adversary's threat lethal.
- `Game::IN_DUEL` / `Game::DUEL_GAMBLED` globals — round-scoped, see Pattern E.

