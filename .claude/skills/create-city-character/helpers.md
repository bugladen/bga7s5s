> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Cross-Cutting Helpers

- `$theah->cardInCity($card): bool` — true when the card sits in the city deck. Gate every City Forced / City Action / City Reaction body on this. Note: inside an `EventCardMoved` handler, the card's `Location` field has NOT been updated yet (the hub handler runs after cards) — use `$theah->locationInCity($event->toLocation)` instead.
- `$theah->locationInCity(string $location): bool` — true for any of the 5 city locations. The right gate when you only have a location string in hand (e.g., `$event->toLocation`).
- `$theah->getCharactersAtLocationByPlayerId(string $location, int $playerId, bool $includeUncontrolled = false): array` — friendly characters at a city location.
- `$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array` — opposing = different controller AND same location. Use this whenever card text says "opposing," never roll your own `ControllerId !=` filter.
- `$theah->getAdjacentCityLocations(string $location, bool $includeHome = true): array` — adjacency for move actions.
- `array_keys($theah->getCityLocations())` — enumerate the **active** city-location names. Respects the player-count rules that exclude Ole's Inn and Governor's Garden in smaller games. Use this for "move to any location" pickers instead of hardcoding the five constants.
- `$game->getCardsOnTopOfCityDeck($n)` — returns raw card_info rows (NOT card objects). Cast `id` to int when passing to event factories.
- `$game->getGameDeckObject()->getCardsInLocation(string $location, int $playerId)` — raw card_info rows in a location; cast `id` to int and pass to `$game->getCardObjectFromDb($id)` to get the card object. Required for "reveal random card from hand" patterns.
- `$game->getGameDeckObject()->shuffle(Game::LOCATION_CITY_DECK)` — shuffle the city deck after sending a card into it.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${card_inject_code}` placeholder).
- `TraitNames::$TraitsJson` (in `modules/php/Traits.php`) — canonical full Trait list for "Name a Trait" abilities. JSON-encoded `{"traits": [...]}`. If a card has a Trait that's missing, add it in alphabetical order.
