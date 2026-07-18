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
