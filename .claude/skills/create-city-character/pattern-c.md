> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern C — City Action / Action (CharacterAction)

City Actions on a CityCharacter use `CharacterAction` — *not* `EventCityAction`. `EventCityAction` is for pure event cards that get discarded after use; a City Character is a Character that performs the action herself and remains in play.

### Action class

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03cdNN extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("...");      // short button text
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);

        // City Actions: gate on being in the city deck
        if (!$theah->cardInCity($owner))
        {
            return false;
        }

        // Most actions: must not be engaged (the action cost engages the character)
        if ($owner->Engaged)
        {
            return false;
        }

        // Any text-specific preconditions ("another of your characters at this location")
        // ...

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03cdNN", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array { /* ... */ }
    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void { /* ... */ }
    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void { /* ... */ }
}
```

### Action base class — pick `CharacterAction`, not `EventCityAction`

The pre-commit hook treats `CharacterAction` subclasses specially: **do NOT call** `$this->setUsed()`, `$this->resetPlayerPassCount()`, or `$this->announceAction()` from your subclass. These are run centrally in `actHighDramaInPlayActionConfirm` / `stHighDramaInPlayActionDispatch`. Per CLAUDE.md.

You DO call `createActionResolvedEvent()` once at the end of action resolution. The hook requires it.

If you find yourself wanting to put the action on `EventCityAction` instead because the character "is in the city deck like an event card" — resist. `EventCityAction` discards the card after use (one-shot consumables). A City Character does not get discarded by performing her City Action — she stays in the deck. `CharacterAction` is correct.

### State class(es)

State files live in `modules/php/States/<expansion>/` (new directory if missing — `States/faf/` was created for Penya).

```php
<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase03cdNN extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_03CDNN,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase03cdNN",
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Card Name') . clienttranslate(': ${you} must ...'),
            transitions: [
                "zombie"          => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "<next-edge>"     => States::HIGH_DRAMA_PLAYER_TURN_03CDNN_2,    // or back to HIGH_DRAMA_PLAYER_TURN_EVENTS
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array { return $this->game->argsForState(); }

    // Pick the right entry point for the state's selection mode:
    //   - actFromCardWithId            ← single id (character picker, player picker)
    //   - actFromCardWithIds           ← list of ids (multi-pick)
    //   - actFromCardWithLocations     ← location picker (string location ids)
    #[PossibleAction]
    public function actFromCardWithId(string $id): void { $this->game->actFromCardWithId($id); }

    public function zombie(int $playerId): void { $this->game->gamestate->nextState("zombie"); }
}
```

For a Back-able second step (companion → location, with "<" return button), use the pattern from `State_highDramaPhase03cd01_2`:

```php
transitions: [
    "back"           => States::HIGH_DRAMA_PLAYER_TURN_03CDNN,
    "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
],

#[PossibleAction]
public function actBack(): void { $this->game->actBack(); }

#[PossibleAction]
public function actFromCardWithLocations(string $locations): void { $this->game->actFromCardWithLocations($locations); }

public function zombie(int $playerId): void { $this->game->gamestate->nextState("back"); }
```

### State ID convention

Expansion 3 (`faf`) uses `403XXXX` to avoid collisions with expansion 1 (`401XXX`) and expansion 2 (`402XXX`):
- Format: `4` (high drama) + `03` (expansion) + `XX` (card number) + optional step suffix.
- Penya step 1: `HIGH_DRAMA_PLAYER_TURN_03CD01 = 4030001`
- Penya step 2: `HIGH_DRAMA_PLAYER_TURN_03CD01_2 = 40300012`

Add the constants in `modules/php/States.php` under the per-card section.

### Register state transitions

In `states.inc.php`, under `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`, key by the string passed to `createTransitionEvent`:

```php
"03cdNN"   => States::HIGH_DRAMA_PLAYER_TURN_03CDNN,
"03cdNN_2" => States::HIGH_DRAMA_PLAYER_TURN_03CDNN_2,
```

Do NOT add anything to `states.7s5s.php` — that file is the older array-style state map. New state classes only appear in `states.inc.php`.

### Sharing state across steps via `Game::CHOSEN_TARGET`

When step 1 picks a target (companion character) and step 2 needs to know about it, write to the shared global in step 1's `actFromActionWithId`:

```php
$game->globals->set(Game::CHOSEN_TARGET, $companion->Id);
```

And read it back in step 2's `getArgsFromAction` / `actFromActionWithIds`:

```php
$targetId  = $game->globals->get(Game::CHOSEN_TARGET);
$companion = $game->theah->getCharacterById($targetId);
```

Defensively clear `CHOSEN_TARGET` at end-of-turn cleanup points if your card's loop is long-running or runs in a multi-player sequence.

### Engage as cost, NOT as move side-effect

Card text like "Engage Penya • Move Penya and another of your characters …" means engagement is the **cost** of the action — apply it explicitly via `createCardEngagedEvent`. The subsequent `createCardMovingEvent` calls then use `$engage = false`, because the cards aren't engaging *because they moved* — Penya is already engaged from the cost, and the companion isn't engaged at all.

```php
$engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
$game->theah->queueEvent($engageEvent);

$moveEvent = EventFactory::createCardMovingEvent(
    $owner->ControllerId, $companion->Id, $companion->Location, $location,
    $engage = false, $owner->Id, $this->Id
);
$game->theah->queueEvent($moveEvent);
```

### Finishing the action

End every successful `actFromActionWith*` path with:

```php
$actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
$game->theah->queueEvent($actionResolvedEvent);

$game->gamestate->nextState("locationChosen");   // edge name from this state's transitions
```

Required by the pre-commit hook (`createActionResolvedEvent`) and required to actually advance out of the state.
