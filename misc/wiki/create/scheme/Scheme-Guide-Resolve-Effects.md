# 04 — Resolve effects

← [[03 — Classify|Scheme Guide Classify The Printed Text]] · [[Index|Implementing a Scheme Card]] · Next: [[05 — Actions|Scheme Guide Actions]]

Everything **above** the printed `<hr>` is the scheme's Planning resolve. You handle it on the scheme class with `EventResolveScheme`.

Always call `parent::handleEvent($event)` first. Always identity-check `$event->scheme->Id == $this->Id`.

## Trivial resolve (no player choice)

Example: Crash the Party — add Renown to named locations.

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
    {
        $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves.'), [
            "scheme_inject_code" => $this->getInjectCode(),
        ]);

        $event->theah->queueEvent(EventFactory::createRenownAddedToLocationEvent(
            $this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode()
        ));
        $event->theah->queueEvent(EventFactory::createRenownAddedToLocationEvent(
            $this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode()
        ));
    }
}
```

Reference: `_02004`. Cross-check location names against the JPG — Forum vs Bazaar swaps are common.

## Resolve with a player pick

When the player must choose (location, discard card, …):

1. Queue any **automatic** Renown events first.
2. Queue a transition into a Planning resolve sub-state.
3. Set `$transition->priority = Event::MEDIUM_PRIORITY` so auto Renown finishes first.

```php
if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
{
    // automatic part…
    $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "NNNNN");
    $transition->priority = Event::MEDIUM_PRIORITY;
    $event->theah->queueEvent($transition);
}
```

The third argument `"NNNNN"` is looked up in **`PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions`** in `states.inc.php`.

You will also need a GameState class + JS — see [[09 — Wiring|Scheme Guide Wiring States And JavaScript]].

## Common pick shapes

### Discard-pile card

- PHP: `actFromCardWithId` + `actFromCardPass` on the **scheme** class.
- Re-validate trait/type server-side (JS can be ignored).
- Confirm the card is actually in the player's discard.
- Usually: throw on Pass if an eligible card still exists (text rarely says "if able").
- Reference: `_03005` (trait filter), `_01044` (Attachment filter). Prefer the newer GameState-class pattern (`_03005`), not the old inline states on `_01044`.

### One city location

- PHP: `argsFromCard` exposes location ids; `actFromCardWithIds` (or the framework location helper) applies Renown.
- JS: `makeCityLocationSelectable`, Confirm → `onCityLocationsSelected`.
- Reference: `_02046`, `_01071`.

### Two different city locations

- JS: `numberOfCityLocationsSelectable = 2`.
- Also map the state in `PlayerActions.js` `actionMap` → `'actCityLocationsForReknownSelected'`.
- Server validates distinctness.
- Reference: `_03005`-era peers `_03006`, `_03030`, `_03053`.

## Multi-player sequential loop ("each opponent does X")

After the owner finishes their pick, queue one transition **per opponent** (turn order), skipping self, with `Event::HIGH_PRIORITY`.

Reference: `_01151` (Shifting Tides) — also has a When-Revealed effect; see [[07|Scheme Guide When Revealed Forced And Passives]].

## What resolve is *not*

| Text | Not resolve — go here instead |
|---|---|
| "When this scheme is revealed…" | [07 When Revealed](Scheme-Guide-When-Revealed-Forced-And-Passives) |
| "Forced: At the end of Planning…" | [07 Forced](Scheme-Guide-When-Revealed-Forced-And-Passives) |
| City Action during High Drama | [05 Actions](Scheme-Guide-Actions) |

## Next

Actions → [[05 — Actions|Scheme Guide Actions]]  
Or jump to [[07 — When Revealed, Forced, and Passives|Scheme Guide When Revealed Forced And Passives]] / [[06 — Reactions|Scheme Guide Reactions]].
