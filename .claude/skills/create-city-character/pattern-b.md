> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern B — Forced via `handleEvent`

**City Forced** triggers automatically while the character sits in the city (i.e., `cardInCity($this)`). **Forced** (no "City" prefix) triggers while in play under whatever scope the text states (recruit, engage, location, etc.). Pure Forced = no player choice = card class only. Interactive Forced ("must choose" / "target") still needs State + JS — see below.

For pure City Forced, override `handleEvent` and gate the body on:

1. **Event type** — `instanceof EventCharacterBeingWounded`, `EventDuelStarted`, `EventCharacterRecruited`, etc.
2. **This card is the relevant target** — `$event->characterId == $this->Id`, or for duels both `challengerId` and `defenderId`.
3. **In city** (City Forced only) — `$event->theah->cardInCity($this)`.

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

### Pure Forced — "After X musters / is recruited • Wound him"

No picker. Gate on `EventCharacterRecruited` (city mercenary recruit) and/or `EventCharacterMustered` / `EventApproachCharacterPlayed` (faction muster / approach play — Joern `_03015` uses the latter pair). Queue `createCharacterBeingWoundedEvent($this->Id, $this->Id, 1, $this->getInjectCode(), …)`. No State/JS.

### Interactive Forced ("must choose" / "target" / "wound him and an opposing character")

Printed **Forced** that still requires a player to pick a target is **not** a City Action and **not** a Reaction. Do not invent `IHasActions` / Pass / Decline just because there is a picker.

WHY not treat as Reaction: Reaction is optional ("you may"). Forced-with-choice is mandatory when a legal target exists; declining is illegal.

Canonical CityCharacter exemplar: **`bas/_04cd14` Millstone Rhud** — "After you recruit Millstone • Wound him and an opposing character." Older multi-player cousin on a City *Event*: `bas/_04cd11`. Pure self-wound cousin: Joern `_03015`.

Recipe (post-recruit, Millstone shape):

1. **`handleEvent`** on `EventCharacterRecruited && $event->characterId == $this->Id`.
   - WHY `EventCharacterRecruited`: hub sets `ControllerId` before card `handleEvent` (`runEventHubAfterCards` defaults false), so `$this->ControllerId` and location are safe for opposing filters and the transition's active player.
   - Queue the **automatic** half first (wound Millstone).
   - If `count(getOpposingCharactersAtLocation($this->Location, $this->ControllerId)) > 0`, queue `createTransitionEvent($this->ControllerId, $this->Id, "04cdNN")`. If none, skip the picker — Forced cannot require an illegal choose; the self-wound still applies.
2. **Opposing scope** — default to **same location** via `$theah->getOpposingCharactersAtLocation` whenever the text says "an opposing character" / "target opposing character" without a broader range. WHY: matches Sibella / Adelheide wound-opposing; board-wide would overpower a Forced. "Target" means *choose*, not *any in play*.
3. **State class** under `States/<exp>/` — thin wrapper; card owns `argsFromCard` / `actFromCardWithId` (and helpers like `getEligibleOpposingCharacters`). Register the transition key on the EVENTS state that will process the queued transition:
   - Recruit payment already transitions to **`HIGH_DRAMA_PLAYER_TURN_EVENTS`** — register `"04cdNN"` there (Millstone). Do **not** invent a recruit-events transition for post-recruit Forced.
   - Dusk-begin Forced → `DUSK_PHASE_BEGIN_EVENTS` (see city-event `_04cd11`).
4. **No Pass.** Zombie handler auto-picks the first eligible target (call `actFromCardWithId` — do not `nextState` without applying the Forced).
5. **JS** in expansion `OnEntering` / `OnUpdateActionButtons` / `OnLeaving` — `highlightCardsAsSelectable(ids)` + Confirm Character only (`onChooseInPlayCardConfirmed` defaults to `actFromCardWithId` when the state is not in `PlayerActions.js`'s map — no map edit needed).
6. After choice: queue the remaining effect (wound target), then `nextState()` back to the EVENTS runner so wounds process before later transitions.

Queue order on recruit: **self-wound event, then transition** — so the wound runs before the picker UI opens.

```php
if ($event instanceof EventCharacterRecruited && $event->characterId == $this->Id)
{
    // … notify …
    $event->theah->queueEvent(EventFactory::createCharacterBeingWoundedEvent(
        $this->Id, $this->Id, 1, $this->getInjectCode(), (string)$this->Id
    ));

    if (count($this->getEligibleOpposingCharacters($event->theah->game)) > 0)
    {
        $event->theah->queueEvent(EventFactory::createTransitionEvent(
            $this->ControllerId, $this->Id, "04cd14"
        ));
    }
}
```
