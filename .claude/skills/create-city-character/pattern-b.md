> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern B — City Forced via `handleEvent`

City Forced abilities trigger automatically while the character sits in the city deck (i.e., `cardInCity($this)`). No player choice. Override `handleEvent` and gate the body on:

1. **Event type** — `instanceof EventCharacterBeingWounded`, `EventDuelStarted`, etc.
2. **This card is the relevant target** — `$event->characterId == $this->Id`, or for duels both `challengerId` and `defenderId`.
3. **In city** — `$event->theah->cardInCity($this)`.

Penya's combined "participates in a duel OR would be wounded" trigger:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    // City Forced: When Penya participates in a duel
    if ($event instanceof EventDuelStarted
        && ($event->challengerId == $this->Id || $event->defenderId == $this->Id)
        && $event->theah->cardInCity($this))
    {
        $this->triggerForcedAbility($event);
    }

    // City Forced: When Penya would be wounded
    if ($event instanceof EventCharacterBeingWounded
        && $event->characterId == $this->Id
        && $event->theah->cardInCity($this))
    {
        $event->canceled = true;                     // "would be wounded" — cancel the wound itself
        $this->triggerForcedAbility($event);
    }

    // Self-listening follow-up — see "Event ordering" below
    if ($event instanceof EventCardRemovedFromPlay
        && $event->cardId == $this->Id
        && $event->toLocation == Game::LOCATION_CITY_DECK)
    {
        $game = $event->theah->game;
        $game->getGameDeckObject()->shuffle(Game::LOCATION_CITY_DECK);
        $game->notify->all("message", clienttranslate('The City Deck has been shuffled.'), []);
    }
}
```

### "Would be wounded" vs "When wounded"

- **"Would be wounded"** — listen on `EventCharacterBeingWounded` and set `$event->canceled = true`. The wound never happens. (Penya, Maryam `_01186`.)
- **"When wounded"** — listen on `EventCharacterWounded` (post-event, after the wound has been applied). No cancellation — the wound is already counted.

Match the verb on the card: "would" = preventive; "is/has been" = reactive.

### "Participates in a duel" vs "Is challenged"

- **"Participates in a duel"** — `EventDuelStarted`. This fires AFTER the duel record is created and the "duel started" notification has gone out (`StatesTrait.php` around 1010-1029). A challenge that was rejected never reaches this event.
- **"Is challenged"** — `EventChallengeIssued`. Fires before acceptance/rejection.

Choose by the printed verb. Penya uses `EventDuelStarted` — a rejected challenge should NOT trigger her Forced.

**Caveat — duel-mid-trigger edge case.** If the Forced removes the character from play *while a duel involving them is being set up*, the duel system has already created its DB record and now has a missing combatant. The current code does not solve this end-to-end (open question in the Penya journal). Flag it in the new journal entry; do not invent a workaround.

### Event ordering inside `handleEvent` — the "queue-then-react-to-your-own-event" trick

For events with `runEventHubAfterCards = false` (the default, see `Theah.php:226-243`):
1. EventHub processes the queued event first (does the move/wound/destroy).
2. THEN every card's `handleEvent` fires for that event.

So you can queue `createCardRemovedFromPlayEvent` and have *another branch of the same card's `handleEvent`* listen for the resulting `EventCardRemovedFromPlay` (targeting `$this->Id`) and run cleanup — like Penya shuffling the city deck after she lands in it.

This is the cleanest way to express "do A, then once A has actually happened, do B" without inventing a new state.

### Why use `createCardRemovedFromPlayEvent` (not `createCityCardAddedToCityDeckEvent`) when sending the character back to the deck?

The "removed from play" event drives the proper frontend animation (`cardRemovedFromPlay` notify) — the character visibly leaves the board. `createCardAddedToCityDeckEvent` is designed for cards that are already revealed / in limbo, not for cards visually in play. Penya is on the board, so use `createCardRemovedFromPlayEvent`. (`Theah.php:226-243` documents the difference.)

### Triggering helper

Penya's Forced helper, lifted as a template for any "do this Forced thing once" pattern:

```php
private function triggerForcedAbility(Event $event): void
{
    $game     = $event->theah->game;
    $location = $this->Location;

    $game->notify->all("message", clienttranslate('${card_inject_code}: Forced ability triggered.'), [
        "card_inject_code" => $this->getInjectCode(),
    ]);

    // Effect 1 — Put the top card of the City Deck at his location
    $topCards = $game->getCardsOnTopOfCityDeck(1);
    if (count($topCards) > 0)
    {
        $topCard = array_values($topCards)[0];
        // NB: $topCard is raw card_info, NOT a card object. Cast id to int.
        $cityCardEvent = EventFactory::createCityCardAddedToLocationEvent((int)$topCard['id'], $location);
        $event->theah->queueEvent($cityCardEvent);
    }

    // Effect 2 — Remove this character from play to the city deck
    // (the city-deck shuffle is handled by the EventCardRemovedFromPlay branch above)
    $removeEvent = EventFactory::createCardRemovedFromPlayEvent($this->ControllerId, $this->Id, Game::LOCATION_CITY_DECK);
    $event->theah->queueEvent($removeEvent);
}
```

**Gotcha:** `getCardsOnTopOfCityDeck($n)` returns raw card_info rows, not card objects. Cast `$topCard['id']` to `int` before passing to `createCityCardAddedToLocationEvent`. The EventHub handler for that event loads the card object on its end.
