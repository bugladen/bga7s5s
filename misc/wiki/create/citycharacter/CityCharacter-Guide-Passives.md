# 04 — Hard bans, Forced, and passives

← [[03 — Classify|CityCharacter Guide Classify The Printed Text]] · [[Index|Implementing a CityCharacter Card]] · Next: [[05 — Actions|CityCharacter Guide Actions]]

These abilities have **no player choice**. When something happens, the CityCharacter's card class reacts automatically.

Three common CityCharacter shapes live here:

1. **Hard bans** — "cannot intervene / challenge"
2. **City Forced** — auto-trigger while in the city
3. **Ordinary passives** — bonuses / listeners while in play (same idea as faction Characters)

Always:

```php
parent::handleEvent($event);   // first line of any handleEvent override
parent::eventCheck($event);    // first line of any eventCheck override
```

## Hard bans (two layers)

Text like *"Penya cannot intervene."* needs **both**:

1. **Predicate override** — hide the illegal option from the UI  
2. **`eventCheck` backstop** — block engine paths that skip the UI (copied effects, forced retargets, …)

```php
public function canIntervene(): bool
{
    return false;
}

public function eventCheck(Event $event)
{
    parent::eventCheck($event);

    if ($event instanceof EventCharacterIntervened && $event->newTargetId == $this->Id) {
        throw new \Bga\GameFramework\UserException(
            $event->theah->game->translate('Penya cannot intervene.')
        );
    }
}
```

**Why both?** Predicates filter menus. `eventCheck` filters the engine. Many edge cases never ask the UI.

Use `\Bga\GameFramework\UserException` — not the deprecated `BgaUserException`.

The field you check (`newTargetId`, `characterId`, …) depends on the event class. Intervention uses `newTargetId` because intervention retargets an in-flight effect.

Full file: `modules/php/cards/faf/_03cd01.php`.

## City Forced via `handleEvent`

City Forced triggers automatically while the character is still a city hireling. Gate every body on:

1. **Right event** — `instanceof EventDuelStarted`, …
2. **About me** — `$event->challengerId == $this->Id` / `$event->characterId == $this->Id`, …
3. **In city** — `$event->theah->cardInCity($this)`

### Penya: duel or would-be-wounded → reveal top city card, then shuffle Penya back

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventDuelStarted
        && ($event->challengerId == $this->Id || $event->defenderId == $this->Id)
        && $event->theah->cardInCity($this))
    {
        $this->triggerForcedAbility($event);
    }

    if ($event instanceof EventCharacterBeingWounded
        && $event->characterId == $this->Id
        && $event->theah->cardInCity($this))
    {
        $event->canceled = true;   // "would be wounded" — stop the wound
        $this->triggerForcedAbility($event);
    }

    // Follow-up: after Penya lands in the city deck, shuffle
    if ($event instanceof EventCardRemovedFromPlay
        && $event->cardId == $this->Id
        && $event->toLocation == Game::LOCATION_CITY_DECK)
    {
        $game = $event->theah->game;
        $game->getGameDeckObject()->shuffle(Game::LOCATION_CITY_DECK);
        $game->notify->all('message', clienttranslate('The City Deck has been shuffled.'), []);
    }
}
```

### Forced helper sketch

```php
private function triggerForcedAbility(Event $event): void
{
    $game = $event->theah->game;
    $location = $this->Location;

    $game->notify->all('message', clienttranslate('${card_inject_code}: Forced ability triggered.'), [
        'card_inject_code' => $this->getInjectCode(),
    ]);

    $topCards = $game->getCardsOnTopOfCityDeck(1);
    if (count($topCards) > 0) {
        $topCard = array_values($topCards)[0];
        // getCardsOnTopOfCityDeck returns raw rows — cast id to int
        $cityCardEvent = EventFactory::createCityCardAddedToLocationEvent((int) $topCard['id'], $location);
        $event->theah->queueEvent($cityCardEvent);
    }

    // Remove from play INTO the city deck (drives the leave-board animation)
    $removeEvent = EventFactory::createCardRemovedFromPlayEvent(
        $this->ControllerId,
        $this->Id,
        Game::LOCATION_CITY_DECK
    );
    $event->theah->queueEvent($removeEvent);
}
```

**Why `createCardRemovedFromPlayEvent` (not "add to city deck" alone)?** Penya is visually on the board. Removed-from-play drives the correct client animation. The shuffle listens on the resulting `EventCardRemovedFromPlay`.

**Gotcha:** `getCardsOnTopOfCityDeck($n)` returns raw `card_info` rows, not card objects. Cast `'id'` to `int`.

## Ordinary passives (in play)

Once mustered, ongoing bonuses ("+1 Combat while wounded", location-counting auras, …) work the same as on faction Characters. See the [[Character passives page|Character Guide Passives]] for those recipes, and still call `parent::handleEvent` first.

Common "still in play?" guards for phase / ongoing bonuses:

```php
if ($this->ControllerId == 0) {
    return;
}
if ($game->characterIsInDiscardOrLocker($this)) {
    return;
}
```

## Forced vs Action vs Reaction

| Situation | Use |
|---|---|
| Auto effect, no choice | This page (Forced / passive / ban) |
| Player activates from High Drama menu | [Actions](CityCharacter-Guide-Actions) |
| Player chooses after a trigger | [Reactions](CityCharacter-Guide-Reactions) |

## Next

If your CityCharacter has an Action → [[05 — Actions|CityCharacter Guide Actions]]  
If only a Reaction → [[06 — Reactions|CityCharacter Guide Reactions]]
