# 05 — Actions

← [[04 — Resolve effects|Scheme Guide Resolve Effects]] · [[Index|Implementing a Scheme Card]] · Next: [[06 — Reactions|Scheme Guide Reactions]]

An **Action** / **City Action** on a Scheme is chosen from the High Drama menu while the scheme is the player's chosen scheme (at Home).

File: `modules/php/cards/<expansion>/actions/Action_NNNNN.php`  
Extends: usually **`SchemeCityAction`**

If the Action **issues a challenge**, also read [[08 — Challenges|Scheme Guide Challenge Actions]].

## Wire it on the card class

```php
class _NNNNN extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        // ...
        $this->Actions = [ new Action_NNNNN() ];
    }
}
```

## Which action base class?

| Card phrase | Base class |
|---|---|
| `<b>City Action:</b>` on a scheme | `SchemeCityAction` |
| `<b>Leader City Action:</b>` on a scheme | `SchemeCityAction` + filter performers with `hasTrait("Leader")` |
| `<b>Action:</b>` (non-city) on a scheme | Check exemplars — often still scheme action bases |

`SchemeCityAction` availability already expects the scheme at `LOCATION_PLAYER_HOME`. That is correct.

## Skeleton (transition into a picker)

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_NNNNN extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Short name for the UI');
        $this->RequiresPerformerSelected = true; // when a performer is needed
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent(
                $event->playerId,
                $owner->Id,
                'NNNNN',   // key under HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    // Then: getArgsFromAction, actFromActionWithId / actFromActionWithIds / …
    // Final step: createActionResolvedEvent + nextState()
}
```

Open `Action_03053.php` (no HD state) or `Action_03029.php` (branched picks) while you work.

## Immediate-resolve City Action (no HD GameState)

Use when the printed City Action needs a performer but **no further picks** after confirm — cost + effects all fire on `EventActionTriggered`.

Canonical: Curry Favor (`_03053`) — Spend a Renown • Claim performer's location • Each opponent draws.

Do **not** invent a High Drama GameState for this shape. The framework already runs performer selection when `RequiresPerformerSelected = true`.

Checklist for this shape:

1. Gate cost in `isAvailableToPlayer` (e.g. `getPlayerReknown($playerId) >= 1`).
2. Filter performers so the **whole** action is legal. If Claim is the payoff, keep only `canLocationBeClaimedBy(...)` — do not offer a dead spend.
3. On trigger: re-validate; queue cost; queue claim (or notify cannot claim); queue trailing effects; queue `createActionResolvedEvent`.
4. Trailing sentences after Claim (e.g. opponent draws) still fire even if claim is blocked at resolve time.

**Spend a Renown** → `createPlayerLosesReknownEvent` (score), **not** a location Renown remove.

## Trait-prefixed City Actions

**Hero / Villain / Scoundrel / Diplomat / Strega / …** = `hasTrait(...)` on the performer. Not Sorcerer unless printed.

`getPerformersForAction` must filter performers for whom the **entire** action is legal (secondary character present, something to move, opposing targets exist, …) — not just the trait.

## Do not call these from SchemeCityAction

These run **centrally** when the action is confirmed:

- `$this->setUsed(...)`
- `$this->resetPlayerPassCount(...)`
- `$this->announceAction(...)`

## Persistence trap

Public fields on the Action (`$MoveMode`, `$pendingMusterId`, …) survive only if you call:

```php
$game->updateCardObjectInDb($owner);
```

`$owner->IsUpdated = true` alone is **not** enough before `stRunEvents` rebuilds cards from the DB.

Do **not** use `Game::CHOSEN_TARGET` for branch state — the challenge framework owns that global.

## Pre-commit

`SchemeCityAction` subclasses must call `createActionResolvedEvent()` somewhere (literal string). Challenge hand-off actions often satisfy this with a short comment — mirror `_03042`.

## Common complex shapes (mirror, don't invent)

| Shape | Mirror |
|---|---|
| Immediate spend → claim → opponents draw | `Action_03053` |
| Choose-one Porté moves (branch first) | `Action_03029` |
| Wound unequipped → pressure → wound+Home | `Action_03054` |
| Move Renown **or** available attachment | `Action_03063` |
| Muster from Locker; return at Dusk | `Action_03062` (Dusk return lives on the **Character**, not the scheme) |
| Engage → challenge | [[08 — Challenges|Scheme Guide Challenge Actions]] |

## Next

Reactions → [[06 — Reactions|Scheme Guide Reactions]]
