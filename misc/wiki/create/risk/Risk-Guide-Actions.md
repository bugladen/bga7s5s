# 05 — Actions

← [[04 — City Actions|Risk Guide City Actions]] · [[Index|Implementing a Risk Card]] · Next: [[06 — Maneuvers|Risk Guide Maneuvers]]

Use this page when the printed text has **`<b>Action:</b>`** with **no** "City" in the heading (including **Sorcerer Action**, **Leader Action**, **Strega Action**, etc.).

## Base class

```php
class Action_NNNNN extends RiskAction
```

File: `modules/php/cards/<expansion>/actions/Action_NNNNN.php`

Differences from City Action:

| Topic | `RiskAction` | `RiskCityAction` |
|---|---|---|
| Default location of the Risk | Must be in **hand** (unless override) | Same hand rule + city presence |
| Performer pool | Characters in play at **Home and city** | **City only** |

**Beginner trap:** do not replace `parent::getPerformersForAction(...)` with `getCharactersInCityByPlayerId(...)` just because the effect moves someone in the city. A Home character can still have adjacent city destinations.

## Minimal Action skeleton

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_NNNNN extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Example Action');
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) {
            return false;
        }
        // Trait gates, "has ≥1 valid destination", etc.
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            // Inline resolve, OR createTransitionEvent to a chooser state.
            $resolvedEvent = EventFactory::createActionResolvedEvent($event->playerId, $event->actionId);
            $event->theah->queueEvent($resolvedEvent);
        }
    }
}
```

## Location-chooser Actions (very common)

Shape: "Move your performer to an adjacent location where …"

1. Filter performers (traits + has ≥1 valid destination) starting from `parent::getPerformersForAction`
2. On `EventActionTriggered`, queue `createTransitionEvent(..., "NNNNN", $this->Id)`
3. `getArgsFromAction` exposes `performerId` + valid `locationIds`
4. `actFromActionWithIds` validates the pick, queues effects, then `createActionResolvedEvent`

Helpers:

- Adjacent city locations: `$theah->getAdjacentCityLocations($performer->Location, $includeHome = false)`
- Include Home as a destination only when the rules text allows it
- For "available Mercenary" scans, use `getCharactersAtLocation($location, $includeUncontrolled = true)`

**Do not** mark `IRiskThatTargetsCharacters` for location choosers.

References: `Action_03009` (enemy or available Mercenary), `Action_03045` (claim-controlled by opponent ± wound), `Action_03032` (any location + optional extra action).

### Claim-control vs enemy-character filters

```php
// Controlled / claimed by an opponent (Curious _03045):
$controller = $theah->game->getControllerForLocation($location);
return $controller != 0 && $controller != $performer->ControllerId;

// Enemy character present (Follow the Thread _03009):
// scan characters at location for opposing ControllerId
```

Uncontrolled locations have controller `0` — they fail "controlled by an opponent."

## Wound + move

When text wounds the performer **and** moves them: queue **both** on confirm (after the destination is locked), with `eventCheck` on each. Do not wound in `EventActionTriggered` before the chooser.

## Sorcerer Actions

`implements ISorcererAbility` and emit **both**:

- `createSorcererAbilityStartEvent()`
- `createSorcererAbilityPlayedEvent()`

Pre-commit greps for those literal strings.

## "Then they may perform another action" (locked performer)

When italic text says the follow-up must use the same performer:

```php
$game->globals->set(Game::EXTRA_ACTIONS, 1);
$game->globals->set(Game::EXTRA_ACTION_PERFORMER, $performer->Id);
```

`EXTRA_ACTIONS` alone only keeps the same *player*. Pass is still allowed ("may"). Reference: `_03032`.

## Leader Action

Same as Leader City Action: fetch Leader directly; no performer chooser. Reference: `Action_03020` (Commanding).

## Action-only cost discounts

If the card says "−1 cost if your Leader is …" and there is **no** Maneuver, put the discount on the Action via `getActionFromHandDiscount` — do **not** invent a Maneuver just to discount combat-card play. See [[08|Risk Guide Passives Forced And Discounts]] and `_03071` / `_01159` / `_01160`.

## Pre-commit

Literal `createActionResolvedEvent` in the Action file.

## Next

Maneuver text → [[06 — Maneuvers|Risk Guide Maneuvers]]
