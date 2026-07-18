> Part of **create-city-event-card**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern A — Forced Ability

Override `handleEvent`. Gate the body on (a) event type, (b) `cardInCity($this)`, and (c) any text-specific condition like "at this location" or "when a character equips this card."

Template:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof Event<Something>
        && $event->theah->cardInCity($this)
        && /* text-specific condition */)
    {
        // Apply mutation / queue follow-up events.
        $event->theah->game->notify->all("message", clienttranslate('${card_inject_code}: ...'), [
            'card_inject_code' => $this->getInjectCode(),
        ]);
    }
}
```

If the Forced effect needs to queue further game events (wound, remove from play, transition to a custom state), use `EventFactory::create*Event(...)` and `$event->theah->queueEvent(...)`. See `_03cd05` (wound on equip) and `_03cd01` (queues `CardRemovedFromPlayEvent` and then listens for it to shuffle).

### Event ordering inside handleEvent

For events with `runEventHubAfterCards = false` (the default), EventHub processes the event first, then every card's `handleEvent` fires. This means you can queue `createCardRemovedFromPlayEvent` and have another `handleEvent` branch on this same card listen for the resulting `EventCardRemovedFromPlay` to do follow-up work (e.g., Penya shuffling the city deck after moving into it).

### Pressure-modifying Forced (very common)

Cards that change *how a pressure is counted* don't need their own state; they set a global flag that `UtilitiesTrait::pressureLocation()` reads while tallying influence.

**Reuse an existing flag when the rule is identical.** `Game::CLAUDE_PRESSURE_TYPE` already means "count only the performer and en garde characters" — both Claude de la Roche (`_01184` Reaction) and Inauguration Day (`_03cd08` Forced) share it. The matching `Game::CLAUD_ID` global stores the card whose `Location` defines the affected pressure.

```php
if ($event instanceof EventPressureOccuring
    && $event->theah->cardInCity($this)
    && $event->location == $this->Location)
{
    $game = $event->theah->game;
    $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE);
    $game->globals->set(Game::CLAUD_ID, $this->Id);

    $game->notify->all("message", clienttranslate('${card_inject_code}: ...'), [
        'card_inject_code' => $this->getInjectCode(),
    ]);
}
```

Existing pressure-type flags (all binary in `Game.php`):
`CLAUDE_PRESSURE_TYPE`, `CAPTAINS_COAT_PRESSURE_TYPE`, `REPUTATION_MERITEE_PRESSURE_TYPE`, `TABARD_PRESSURE_TYPE`, `CONSTANZO_PRESSURE_TYPE`, `CONTEMPT_AND_HATRED_PRESSURE_TYPE`, `PACK_TACTICS_PRESSURE_TYPE`, `PULL_THE_STRAND_PRESSURE_TYPE`, `KASPARS_OCCUPATION_PRESSURE_TYPE`, `TRIAL_OF_FAITH_PRESSURE_TYPE`, `CASTILLIAN_CAPER_PRESSURE_TYPE`, `SOLOMONIA_PRESSURE_TYPE`, `USSURAN_INTRIGUE_PRESSURE_TYPE`.

If the rule is genuinely new:
1. Add a new binary flag (next power of two) and ID-global constant in `Game.php`.
2. Add a branch in `UtilitiesTrait::pressureLocation()` next to the existing `CLAUDE_PRESSURE_TYPE` / `CONSTANZO_PRESSURE_TYPE` blocks.

Reference cards: `_01006` (Don Constanzo, Forced bonus), `tac/_02044` (Solomonia, Forced bonus), `_7s5s/_01184` Reaction (Claude — same flag as `_03cd08`, different trigger style).
