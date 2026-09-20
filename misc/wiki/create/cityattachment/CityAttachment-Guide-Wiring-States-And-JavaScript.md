# 08 — Wiring states and JavaScript

← [[07 — Steady-state|CityAttachment Guide Steady State And Custom States]] · [[Index|Implementing a CityAttachment Card]] · Next: [[09 — Checklist|CityAttachment Guide Finish Checklist]]

You need this page when an Action or Forced mid-flow prompt asks the player to **pick something** (top/bottom, location, acknowledge). Pure button Reactions and immediate-resolve Actions usually skip this entirely.

Exemplars:

- **Mid-duel choice:** Devil Jonah's Bones `_03cd05` (`State_duelGambleSetup_03cd05`)
- **High Drama Action picker:** mirror FactionAttachment Compass `_03055` shape if your CityAttachment Action needs a location pick
- **Immediate (no wiring):** Smuggled Item `_01187` destroy path (no new state)

## The three backend pieces

For each new interactive step you typically add:

1. **State constant** in `modules/php/States.php`
2. **State class** in `modules/php/States/<expansion>/State_....php`
3. **Transition entry** in `states.inc.php` (and `states.7s5s.php` when that file mirrors it)

### Which EVENTS map?

| Flow | Transition table |
|---|---|
| High Drama AttachmentAction picker | `HIGH_DRAMA_PLAYER_TURN_EVENTS` |
| Duel setup window (Bones) | `DUEL_GAMBLE_SETUP_EVENTS` (and related duel maps) |
| Other | Match whatever your mirror card uses |

### Transition keys and sourceId

`EventFactory::createTransitionEvent(..., "03cd05", ...)` looks up `"03cd05"` in the EVENTS state's transition table.

For attachment-hosted prompts, the transition's **`sourceId` is the attachment id**. Wrong sourceId → empty args / missing ability.

```php
"03cd05" => States::DUEL_GAMBLE_SETUP_03CD05,
```

## State class sketch (Bones-style choice)

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelGambleSetup_03cdNN extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: States::DUEL_GAMBLE_SETUP_03CDNN,
            type: StateType::ACTIVE_PLAYER,
            name: 'duelGambleSetup_03cdNN',
            description: clienttranslate('${actplayer} is choosing options from <card name>.'),
            descriptionMyTurn: clienttranslate('Card Name') . clienttranslate(': ${you} may ...'),
            transitions: [
                '' => States::DUEL_GAMBLE_SETUP_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array { return $this->game->argsForState(); }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void { $this->game->actFromCardWithId($id); }

    public function zombie(int $playerId): void { $this->game->gamestate->nextState(); }
}
```

The card class's `actFromCardWithId` interprets the choice id and writes any globals.

## JS wiring is required

Without JS, the new state activates server-side but the player sees nothing.

For a duel-setup prompt (Bones):

- `modules/js/OnUpdateActionButtons.<expansion>.js` — render the choice buttons ("Reveal from Top" / "Reveal from Bottom")
- Ensure the expansion's JS file is included from the master `OnUpdateActionButtons.js` chain

For High Drama Action picker states, also wire:

- `OnEnteringState.<expansion>.js` — selection setup
- `OnLeavingState.<expansion>.js` — selection teardown
- `PlayerActions.js` — only if you reuse an existing client action name

**Copy an existing state's JS block** and rename the state key. Do not invent a new UI pattern.

### Home locations

If a location picker can include Home, call `makeHomeEndcapMarkerSelectable()` in enter handlers. City-only handlers that only call `makeCityLocationSelectable` will leave Home unclickable.

## Inserting into an existing auto flow

If you are adding Setup / SetupEvents in front of a core auto state (Bones), re-read [[07|CityAttachment Guide Steady State And Custom States]]. Missing a reroute is the #1 silent bug.

## Checklist for this pattern

- [ ] Constant + State class + `states.inc.php` entry (and twin file if required)
- [ ] Transition key string matches `createTransitionEvent`
- [ ] `sourceId` is the attachment id
- [ ] Enter / Update / Leave JS for each new interactive state
- [ ] Zombie path advances without crashing
- [ ] Multiplayer states listed in `ZombieTrait` when applicable

## Next

Finish checklist → [[09 — Finish checklist|CityAttachment Guide Finish Checklist]]
