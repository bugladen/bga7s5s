> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern A — Resolve via `EventResolveScheme`

Every scheme that has any text above the `<hr>` needs an `EventResolveScheme` handler. Always call `parent::handleEvent($event)` first.

### Trivial resolve (no player choice)

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
    {
        $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ...'), [
            "scheme_inject_code" => $this->getInjectCode(),
        ]);

        $event1 = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
        $event->theah->queueEvent($event1);

        $event2 = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
        $event->theah->queueEvent($event2);
    }
}
```

Reference: `_02004` (Crash the Party).

### Resolve with player choice — transition to a sub-state

```php
if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
{
    // Queue the automatic part first.
    $event->theah->queueEvent(EventFactory::createRenownAddedToLocationEvent(
        $this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode()));

    // Then queue a transition into the player-choice state.
    $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "NNNNN");
    $transition->priority = Event::MEDIUM_PRIORITY;
    $event->theah->queueEvent($transition);
}
```

The transition's third arg (`"NNNNN"`) is the key looked up in the `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions` map of `states.inc.php`. Always set `priority = Event::MEDIUM_PRIORITY` so the automatic Renown events resolve first.

### Discard-pile pick state

Almost always `actFromCardWithId` style: the JS shows a `chooseList` populated with eligible discard cards, the player clicks one and confirms.

PHP-side method on the scheme:

```php
public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
{
    parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

    if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN)
    {
        $playerId = $game->getActivePlayerId();
        $card = $game->getCardObjectFromDb($id);
        if (! $card)
        {
            throw new UserException($game->translate("Invalid card"));
        }

        // Re-validate the trait/type filter — JS can be tampered with.
        if (! $this->cardMatchesTraits($card))
        {
            throw new UserException($game->translate("Card must have ..."));
        }

        // Verify it's actually in the discard pile.
        $deck = $game->getGameDeckObject($playerId);
        $discardPileName = $game->getPlayerDiscardDeckName($playerId);
        $cardObjects = $deck->getCardsInLocation($discardPileName);
        if (! in_array($card->Id, array_column($cardObjects, 'id')))
        {
            throw new UserException($game->translate("Card is not in your discard pile."));
        }

        $remove = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($playerId, $card->Id);
        $game->theah->eventCheck($remove);

        $add = EventFactory::createCardAddedToHandEvent($playerId, $card->Id);
        $game->theah->eventCheck($add);

        $game->theah->queueEvent($remove);
        $game->theah->queueEvent($add);

        $game->gamestate->nextState("");
    }
}

public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
{
    parent::actFromCardPass($game, $state, $stateName, $internalId);

    if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN)
    {
        // Throw if any eligible card remains — the text rarely says "if able".
        $playerId = $game->getActivePlayerId();
        $deck = $game->getGameDeckObject($playerId);
        $discardPileName = $game->getPlayerDiscardDeckName($playerId);
        foreach ($deck->getCardsInLocation($discardPileName) as $row)
        {
            $card = $game->getCardObjectFromDb($row['id']);
            if ($card && $this->cardMatchesTraits($card))
            {
                throw new UserException($game->translate("There is an eligible card in your discard pile that you must choose."));
            }
        }

        $game->gamestate->nextState("");
    }
}
```

Reference: `_01044` (filter by `Attachment` instanceof), `_03005` (filter by trait list). Both throw if a card is available; both `nextState("")` on success.

### Location-pick state

When the player picks a city location, use `actFromCardWithIds` (plural — the framework hands locations in as a string array).

```php
public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
{
    $args = parent::argsFromCard($game, $state, $stateName, $internalId);

    if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN)
    {
        $locations = $game->theah->getCityLocations();
        $args["locationIds"] = array_values(array_map(fn($l) => $l->Name, $locations));
    }

    return $args;
}

public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
{
    parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

    if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN)
    {
        $locationName = $ids[0];
        // Re-validate the location is a city location, has Renown, etc.
        // Queue createRenownAddedToLocationEvent / RemovedFromLocationEvent.
        $game->gamestate->nextState();
    }
}
```

Reference: `_01071`, `_02014`, `_02046`, `_02052`.

### Fixed location + pick another

When the scheme says **"Add a Renown to [City Docks] and another location"** (one destination printed, one chosen):

1. Queue `createRenownAddedToLocationEvent` for the **fixed** location first.
2. Queue `createTransitionEvent(..., "NNNNN")` at `MEDIUM_PRIORITY` into a single planning resolve state.
3. `argsFromCard` / `locationIds` = city location names **excluding** the fixed one. Re-validate on `actFromCardWithIds`.
4. Do **not** use `actCityLocationsForReknownSelected` / `numberOfCityLocationsSelectable = 2` — that helper assumes both picks are free and has no fixed destination. Confirm still goes through `onCityLocationsSelected` → default `actFromCardWithLocations` (no `PlayerActions.js` `actionMap` entry needed).
5. JS: enter with `locationIds` from args + Confirm Location; leave `resetCityLocations`.

Contrast Winter's Wind `_02046` (two sequential free picks, second optional Pass) and Blood Money `_04004` (two *fixed* Renown adds, no location pick for Renown).

Reference: `_04014` / `State_planningPhaseResolveSchemes04014`.

### City Card to fixed location + Renown to a different location

When the scheme says **"Add a City Card to [The Grand Bazaar]. Then add a Renown to a different location"** (Midnight Shipment–style city card, then `_04014`-style Renown pick):

1. `$cityCards = $game->getCardsOnTopOfCityDeck(1)`. If non-empty, queue `createCityCardAddedToLocationEvent((int)$cityCards[0]['id'], $fixed)`. **Cast id** — `getCardsOnTopOfCityDeck` returns raw deck rows (Penya / `_01149`).
2. **Guard empty city deck+discard:** do not index `[0]` when count is 0 — notify and still offer the Then Renown pick. WHY: `_01149` indexes unsafely; empty-city edge should not soft-lock resolve.
3. Queue `createTransitionEvent(..., "NNNNN")` at `MEDIUM_PRIORITY` so the city-card event resolves before the pick state.
4. Renown pick: `locationIds` **exclude** the fixed City Card destination. "Different" means ≠ that location even when no City Card was added.
5. Same JS as fixed-Renown-and-another (`actFromCardWithLocations`, no `actionMap` entry).

Reference: `_04025` (Bazaar + Renown elsewhere). City-card-only sibling: `_01149` (Renown + City Card to Docks, no pick).

### Character-then-City-location resolve (move your \<Trait\>)

When the scheme says **"Then, move your Duelist to a City location"** (or another trait) after automatic Renown:

1. Queue fixed Renown events as usual.
2. Collect eligible characters: `getCharactersInPlayByPlayerId` + `hasTrait(...)` + ≥1 City destination ≠ current location (Home Duelists qualify — any City is valid).
3. **Only** queue `createTransitionEvent(..., "NNNNN")` at `MEDIUM_PRIORITY` when the list is non-empty. Otherwise notify that there is no traited character to move and stop (contingent "Then").
4. **State 1** (`PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN`): highlight `ids` via `argsFromCard` / `actFromCardWithId`. Stash pick in `Game::CHOSEN_CARD`. Transition `"duelistChosen"` (or `"characterChosen"`) → state 2. Do **not** use `CHOSEN_PERFORMER` here — that global belongs to HD City Actions.
5. **State 2** (`…_NNNNN_2`): `locationIds` from city locations excluding the chosen character's current location. `actFromCardWithLocations` → `actFromCardWithIds` on the scheme. `createCardMovingEvent(..., engage=false)` unless Engage is printed.
6. **Named success** on state 2 (`"locationChosen"`) whenever `"back"` / `"zombie"` also exist — `nextState("")` throws "More than one possible transition" (same as Pattern G / `_03042`).
7. JS: state 1 = `highlightCardsAsSelectable` + Confirm; state 2 = city locations + Back + Confirm; leave cleans highlights / `resetCityLocations`.

Same card-number key `"NNNNN"` may also appear under `HIGH_DRAMA_PLAYER_TURN_EVENTS` for a City Action on the same scheme — distinct maps, intentional (see `_03030`, `_04004`).

Reference: `_04004` / `State_planningPhaseResolveSchemes04004{,_2}`.
