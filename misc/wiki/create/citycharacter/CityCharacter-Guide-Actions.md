# 05 — Actions (Pattern C)

← [[04 — Passives|CityCharacter Guide Passives]] · [[Index|Implementing a CityCharacter Card]] · Next: [[06 — Reactions|CityCharacter Guide Reactions]]

An **Action** (or **City Action**) is something the player chooses from the High Drama menu while they control / can activate the CityCharacter.

File: `modules/php/cards/<expansion>/actions/Action_03cdNN.php`  
Extends: **`CharacterAction`** (not `EventCityAction`)

If the Action **issues a challenge**, also read [[08 — Challenges|CityCharacter Guide Challenge Actions]].

## Why CharacterAction, not EventCityAction?

| Base class | Meant for |
|---|---|
| **`CharacterAction`** | A Character (including CityCharacter) performs an ability and **stays** |
| `EventCityAction` | A city **event** card that is typically **discarded** after use |

Penya does not discard himself when he uses his City Action. Copy `Action_03cd01`, not an event-card Action.

## Wire it on the card class

```php
class _03cdNN extends CityCharacter implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        // ...
        $this->Actions = [ new Action_03cdNN() ];
    }
}
```

## Skeleton

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03cdNN extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Short name for the UI');
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);

        // City Action while still a city hireling / in city:
        if (! $theah->cardInCity($owner)) {
            return false;
        }

        // Most Engage-cost Actions: must not already be engaged
        if ($owner->Engaged) {
            return false;
        }

        // Card-specific gates (companion exists, adjacent location exists, …)
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent(
                $event->playerId,
                $owner->Id,
                '03cdNN',   // key in states.inc.php
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    // Then: getArgsFromAction, actFromActionWithId / actFromActionWithIds / …
    // At the end of the final step: createActionResolvedEvent + nextState()
}
```

Open Penya's `Action_03cd01.php` for a two-step City Action (pick companion → pick adjacent location).

## City Action vs Action

| Keyword | Extra gate |
|---|---|
| `Action:` | Usually in-play / controlled is enough (`parent::isAvailableToPlayer`) |
| `City Action:` | Also `$theah->cardInCity($owner)` (Penya) |

## Do not call these from CharacterAction

These run **centrally** when the action is confirmed:

- `$this->setUsed(...)`
- `$this->resetPlayerPassCount(...)`
- `$this->announceAction(...)`

**Do** call `EventFactory::createActionResolvedEvent(...)` once when the Action finishes (unless you handed off to the challenge machine — see [[08|CityCharacter Guide Challenge Actions]]). The pre-commit hook requires that literal.

## Action id is a string

`$this->Id` on an Action is a composite string like `"68_Action_03cd01"`.

| Parameter type | Pass |
|---|---|
| `int $sourceId` on wound / move factories | `$owner->Id` |
| `string $abilityId` | `$this->Id` |
| 4th arg of `createTransitionEvent` | `$this->Id` |

## Engage as cost, move with `engage = false`

Penya: *Engage Penya • Move Penya and another of your characters…*

```php
$engageEvent = EventFactory::createCardEngagedEvent(
    $owner->ControllerId,
    $owner->Id,
    $owner->Id,
    $this->Id
);
$game->theah->queueEvent($engageEvent);

$moveEvent = EventFactory::createCardMovingEvent(
    $owner->ControllerId,
    $companion->Id,
    $companion->Location,
    $location,
    $engage = false,   // do NOT engage because of the move
    $owner->Id,
    $this->Id
);
$game->theah->queueEvent($moveEvent);
```

If you pass `$engage = true` on the move, you can double-engage or engage companions the card never asked for.

## Sharing picks across steps — `Game::CHOSEN_TARGET`

Step 1 stores the companion; step 2 reads it:

```php
$game->globals->set(Game::CHOSEN_TARGET, $companion->Id);
// later
$targetId = $game->globals->get(Game::CHOSEN_TARGET);
```

## When you need custom states

| UI need | Custom state? |
|---|---|
| One-click effect with no picks | Sometimes none |
| Pick a character / location | Yes — see [09 — Wiring](CityCharacter-Guide-Wiring-States-And-JavaScript) |
| Two picks in sequence | Yes — step 1 and step 2 (Penya) |

## Arrays for JS must be sequential

```php
$args['ids'] = array_values(array_map(fn ($c) => $c->Id, $cards));
```

Without `array_values`, JSON becomes an object and `ids.forEach` throws on the client.

## Finishing the action

```php
$actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
$game->theah->queueEvent($actionResolvedEvent);
$game->gamestate->nextState('locationChosen'); // edge name from this state's transitions
```

## Next

Reactions → [[06|CityCharacter Guide Reactions]] · Wiring for your states → [[09|CityCharacter Guide Wiring States And JavaScript]]
