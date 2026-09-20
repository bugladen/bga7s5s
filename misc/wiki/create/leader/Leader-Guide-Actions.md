# 05 — Actions (Pattern C)

← [[04 — Passives|Leader Guide Passives]] · [[Index|Implementing a Leader Card]] · Next: [[06 — Reactions|Leader Guide Reactions]]

An **Action** (or **City Action**) is something the player chooses from the High Drama menu while they control the Leader.

File: `modules/php/cards/<expansion>/actions/Action_NNNNN.php`  
Extends: `CharacterAction`

If the Action **issues a challenge**, also read [[08 — Challenges|Leader Guide Challenge Actions]].

## Wire it on the card class

```php
class _NNNNN extends Leader implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        // ...
        $this->Actions = [ new Action_NNNNN() ];
    }
}
```

## Skeleton

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_NNNNN extends CharacterAction
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

        // City Action only:
        if (! $theah->cardInCity($owner)) {
            return false;
        }

        // Card-specific gates (must have a Strega with wounds, must have a legal target, …)
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
                'NNNNN',      // key in states.inc.php → your picker state
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    // Then: getArgsFromAction, actFromActionWithId / actFromActionWithIds
    // At the end of the final step: createActionResolvedEvent + nextState()
}
```

## City Action vs Action

| Keyword | Extra gate |
|---|---|
| `Action:` | Usually in play is enough (`parent::isAvailableToPlayer`) |
| `City Action:` | Also `$theah->cardInCity($owner)` |

## Do not call these from CharacterAction

These run **centrally** when the action is confirmed:

- `$this->setUsed(...)`
- `$this->resetPlayerPassCount(...)`
- `$this->announceAction(...)`

Calling them yourself duplicates work.

**Do** call `EventFactory::createActionResolvedEvent(...)` once when the Action finishes (unless you handed off to the challenge machine — see [[08|Leader Guide Challenge Actions]]).

## Action id is a string

`$this->Id` on an Action is a composite string like `"68_Action_03001"`.

| Parameter type | Pass |
|---|---|
| `int $sourceId` on wound / move / discard factories | `$owner->Id` |
| `string $abilityId` | `$this->Id` |
| 4th arg of `createTransitionEvent` | `$this->Id` |

## When you need custom states

| UI need | Custom state? |
|---|---|
| One-click effect with no picks | Sometimes none — rare for CharacterActions |
| Pick a character / location / hand card | Yes — see [09 — Wiring](Leader-Guide-Wiring-States-And-JavaScript) |
| Two picks in sequence (source then target) | Yes — step 1 and step 2 states |

Cesca's City Action is two steps:

1. Pick the wounded Strega (source).
2. Pick the opposing non-Leader (destination of the wound).

Open `Action_03001.php` and the matching `State_highDramaPhase03001*.php` files.

## `IAbilityThatTargetsCharacters`

Add this interface **only** when the printed text says **target**.

It requires `isValidTargetForAbility(Game $game, Character $character): array` returning `[true, ""]` or `[false, "error message"]`.

## Multiple Actions on one card

Split into `Action_NNNNNa` and `Action_NNNNNb`. Separate names, availability, transition keys (`"03038a"`, `"03038b"`), and state ids. Reference: Damya `_03038`.

## Hand vs in-play pickers (client)

| Picking from… | Client helper |
|---|---|
| Characters / attachments in play | `highlightCardsAsSelectable(ids)` |
| Cards in hand | `factionHand.setSelectionMode('single')` + `onCardDiscarded` |

Using the in-play highlighter on hand cards crashes the client (`className` of null).

## Arrays for JS must be sequential

```php
$args['ids'] = array_values(array_map(fn($c) => $c->Id, $cards));
```

Without `array_values`, JSON becomes an object and `ids.forEach` throws on the client.

## Next

Reactions → [[06|Leader Guide Reactions]] · Wiring for your states → [[09|Leader Guide Wiring States And JavaScript]]
