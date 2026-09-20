# 09 — Wiring states and JavaScript

← [[08 — Challenges|CityCharacter Guide Challenge Actions]] · [[Index|Implementing a CityCharacter Card]] · Next: [[10 — Checklist|CityCharacter Guide Finish Checklist]]

You need this page when an Action or Technique asks the player to **pick something** (character, location, hand card, attachment). Pure button Reactions usually skip this entirely — Julius needs no wiring.

Penya's two-step City Action is the CityCharacter wiring exemplar.

## The three backend pieces

For each new interactive step you typically add:

1. **State constant** in `modules/php/States.php`
2. **State class** in `modules/php/States/<expansion>/State_....php`
3. **Transition entry** in `states.inc.php` under `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`

### Important: do not use `states.7s5s.php` for new CityCharacter states

New GameState classes are registered through `states.inc.php` only. The older array map in `states.7s5s.php` is not where Penya-style states go.

### State id convention (city-deck cards)

Expansion 3 (`faf`) city cards use ids that avoid colliding with faction card numbers:

- Format idea: `4` (High Drama) + `03` (expansion) + city number + optional step suffix
- Penya step 1: `HIGH_DRAMA_PLAYER_TURN_03CD01 = 4030001`
- Penya step 2: `HIGH_DRAMA_PLAYER_TURN_03CD01_2 = 40300012`

Mirror Penya's constants when adding a neighbor city card.

### Transition keys

`EventFactory::createTransitionEvent(..., "03cd01", ...)` looks up `"03cd01"` in the EVENTS state's transition table:

```php
"03cd01"   => States::HIGH_DRAMA_PLAYER_TURN_03CD01,
"03cd01_2" => States::HIGH_DRAMA_PLAYER_TURN_03CD01_2,  // only if something queues that key
```

Some multi-step flows move step 1 → step 2 only via the state's own `nextState("...")` map (Penya's companion → location uses state transitions + a `"03cd01"` entry from the Action).

Challenge Actions commonly need both `"03cdNN"` and `"03cdNN_2"` — see [[08|CityCharacter Guide Challenge Actions]].

## State class sketch

```php
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
            name: 'highDramaPhase03cdNN',
            description: clienttranslate('${actplayer} is choosing options to perform an Action.'),
            descriptionMyTurn: clienttranslate('Card Name') . clienttranslate(': ${you} must ...'),
            transitions: [
                'zombie' => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                // next edges…
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array { return $this->game->argsForState(); }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void { $this->game->actFromCardWithId($id); }

    public function zombie(int $playerId): void { $this->game->gamestate->nextState('zombie'); }
}
```

Pick the entry point for the selection mode:

| Mode | PossibleAction |
|---|---|
| Single id (character / player) | `actFromCardWithId` |
| List of ids | `actFromCardWithIds` |
| Location picker | `actFromCardWithLocations` |

Step 2 with Back: see `State_highDramaPhase03cd01_2.php` (`actBack` + location confirm).

## The three frontend pieces

For every new state name (example `highDramaPhase03cd01`):

| File | Job |
|---|---|
| `modules/js/OnEnteringState.<expansion>.js` | Highlight what can be selected; store args |
| `modules/js/OnUpdateActionButtons.<expansion>.js` | Add Confirm / Back / Pass buttons |
| `modules/js/OnLeavingState.<expansion>.js` | Clear highlights / selection modes |

Copy Penya's entries in the `faf` JS files and rename the keys.

### Character picker (in play)

```js
'highDramaPhase03cdNN': () => {
    if (this.isCurrentPlayerActive()) {
        this.numberOfCardsSelectable = 1;
        this.highlightCharacterChosen(args.args.args.performerId);
        this.clientStateArgs.performerId = args.args.args.performerId;
        this.clientStateArgs.ids = args.args.args.ids;
        this.highlightCardsAsSelectable(args.args.args.ids);
    }
},
```

### Location picker (Penya step 2)

```js
'highDramaPhase03cdNN_2': () => {
    if (this.isCurrentPlayerActive()) {
        this.numberOfCityLocationsSelectable = 1;
        args.args.args.locationIds.forEach((locationId) => {
            const imageElement = this.getCityLocationElement(locationId);
            this.makeCityLocationSelectable(imageElement);
        });
        // mark already-chosen characters with _7sfs-chosen …
    }
},
```

Confirm buttons:

- In-play card: `onChooseInPlayCardConfirmed()`
- City location: `onCityLocationsSelected()`
- Back: `this.bgaPerformAction('actBack', {})`

Leaving cleanup for locations uses `resetCityLocations()` — there is no `clearCityLocationAsSelectable`.

Build location lists with `array_keys($theah->getCityLocations())` or `$theah->getAdjacentCityLocations(...)` — do not hardcode colloquial constant names that do not exist.

### PlayerActions.js

If your state reuses an existing client action name, extend the map in `modules/js/PlayerActions.js`. Forgetting this is a common cause of "the button does nothing."

## Args shape gotcha

- `OnEnteringState` often reads nested `args.args.args.*`
- `OnUpdateActionButtons` often reads `args.args.*`

When in doubt, mirror Penya's depth exactly.

## Pre-commit reminders related to wiring

- CharacterAction must still end with `createActionResolvedEvent` (except challenge hand-off)
- Reaction classes need literal `$this->setUsed(` and `$this->isAvailable(`
- Sorcerer abilities need start + played events

## Next

Walk the finish list → [[10|CityCharacter Guide Finish Checklist]]
