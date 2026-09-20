# 07 — Techniques and Maneuvers (Pattern E)

← [[06 — Reactions|CityCharacter Guide Reactions]] · [[Index|Implementing a CityCharacter Card]] · Next: [[08 — Challenges|CityCharacter Guide Challenge Actions]]

**Techniques** and **Maneuvers** on a CityCharacter work the same way as on a faction Character. There is nothing city-deck-specific once the person is in a duel.

| Kind | Folder | Card class wiring |
|---|---|---|
| Technique | `cards/<expansion>/techniques/Technique_03cdNN.php` | `$this->Techniques = [ new Technique_03cdNN() ];` — Character already has `TechniqueTrait` |
| Maneuver | `cards/<expansion>/maneuvers/Maneuver_03cdNN.php` | `implements IHasManeuvers` + `use ManeuverTrait` + `$this->Maneuvers = [...]` |

## Basic Technique availability

Most Techniques are "during a duel, while I am the actor":

```php
public function isAvailableToPlayer(int $playerId, Theah $theah): bool
{
    if (! parent::isAvailableToPlayer($playerId, $theah)) {
        return false;
    }
    if (! $theah->game->globals->get(Game::IN_DUEL, false)) {
        return false;
    }

    $owner = $this->getOwningCharacter($theah);
    $actor = $theah->getDuelRoundActor();
    if ($actor === null || $actor->Id !== $owner->Id) {
        return false;
    }

    return true;
}
```

## Gambling Technique

Printed **Gambling Technique** must gate **both** `Game::IN_DUEL` and `Game::DUEL_GAMBLED`.

## Simple +1 Riposte / Thrust / Parry

Prefer subclassing existing helpers (`Technique_PlusOneThrust`, `Technique_PlusOneRiposte`, …) when the text is that simple.

## Techniques that need a picker

On `EventResolveTechnique`, queue `EventFactory::createTechniqueTransitionEvent(...)`, add a duel state under `DUEL_CHOOSE_TECHNIQUE_EVENTS`, and wire JS — see [[09 — Wiring|CityCharacter Guide Wiring States And JavaScript]].

For deeper Technique recipes, the [[Character Techniques page|Character Guide Techniques And Maneuvers]] is the long-form sibling — patterns are identical.

## Do not re-declare TechniqueTrait

`Character` (and therefore `CityCharacter`) already provides Techniques support. On the card class you only assign `$this->Techniques = [...]`.

## Next

Challenge Actions → [[08|CityCharacter Guide Challenge Actions]] · Wiring → [[09|CityCharacter Guide Wiring States And JavaScript]]
