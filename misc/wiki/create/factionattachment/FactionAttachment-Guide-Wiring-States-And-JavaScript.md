# 09 — Wiring states and JavaScript

← [[08 — Techniques|FactionAttachment Guide Techniques And Maneuvers]] · [[Index|Implementing a FactionAttachment Card]] · Next: [[10 — Checklist|FactionAttachment Guide Finish Checklist]]

You need this page when an Action or Technique asks the player to **pick something** (location, hand card, acknowledge a reveal). Pure button Reactions and immediate-resolve Actions usually skip this entirely.

Exemplars:

- **Choose location:** Syrneth Compass `_03055` (High Drama Action)
- **Reveal acknowledge:** El Gato's Mask `_03043` (duel Technique)
- **Immediate (no wiring):** Lodestone `_03065`

## The three backend pieces

For each new interactive step you typically add:

1. **State constant** in `modules/php/States.php`
2. **State class** in `modules/php/States/<expansion>/State_....php`
3. **Transition entry** in `states.inc.php` under the correct EVENTS map

### Which EVENTS map?

| Flow | Transition table |
|---|---|
| High Drama AttachmentAction picker | `HIGH_DRAMA_PLAYER_TURN_EVENTS` |
| Duel Technique picker / ack | Duel technique / resolve maps used by sibling Techniques |
| Planning / other | Match whatever your mirror card uses |

New GameState classes are registered through `states.inc.php`. Follow your expansion's modern GameState style (Compass / Mask), not ancient inline-only core patterns unless you are fixing a legacy card.

### Transition keys and sourceId

`EventFactory::createTransitionEvent(..., "NNNNN", ...)` looks up `"NNNNN"` in the EVENTS state's transition table.

For attachment-hosted Actions/Techniques, the transition's **`sourceId` is the attachment id**. Wrong sourceId → empty args / missing ability.

```php
"03055" => States::HIGH_DRAMA_PLAYER_TURN_03055,
```

## State class sketch (location pick)

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase03055 extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_03055,
            type: StateType::ACTIVE_PLAYER,
            name: 'highDramaPhase03055',
            description: clienttranslate('${actplayer} is choosing a location.'),
            descriptionMyTurn: clienttranslate('Syrneth Compass') . clienttranslate(': ${you} must choose a location.'),
            transitions: [
                'locationChosen' => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                'zombie' => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array { return $this->game->argsForState(); }

    #[PossibleAction]
    public function actFromCardWithLocations(string $ids): void
    {
        $this->game->actFromCardWithLocations($ids);
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState('zombie');
    }
}
```

Mirror real Compass / similar Risk location states for exact method names your Action implements (`actFromActionWithIds`, `getArgsFromAction`, …).

## JavaScript (three hooks)

For each new state name, add entries in the expansion's:

- `OnEnteringState.<expansion>.js` — highlight selectable things
- `OnUpdateActionButtons.<expansion>.js` — Confirm / Cancel
- `OnLeavingState.<expansion>.js` — cleanup (`resetCityLocations`, hide chooseList, …)

### Location pickers

- City locations: `makeCityLocationSelectable`
- Home (when allowed): **`makeHomeEndcapMarkerSelectable`**
- Leave: `resetCityLocations`

Do **not** copy bare `03032` / `03045` enter handlers for Home-capable actions — those PHP lists can include Home while their JS never makes Home selectable.

### Reveal acknowledge (multiplayer)

- Show `choose_container` / `chooseList`
- `addCardToDeck` for each revealed card
- Ok → `onMultipleOk()`
- Leave: hide + `chooseList.removeAll()`
- Add the multi state to `ZombieTrait` multipleactiveplayer cases

## Conditions (tooltip JS)

If your card stamps a new `Game::*_CONDITION`:

1. Matching string constant in `seventhseacityoffivesails.js`
2. `*ConditionStarted` / `*ConditionEnded` handlers in `Notifications.js` updating `card.conditions` + `refreshTooltipForCard`

Mirror Lodestone / Harpoon / Soline.

## When you can skip wiring

| Situation | Wiring needed? |
|---|---|
| Immediate Action (Lodestone) | No |
| Button-only Reaction (Engage / Pass / card-N buttons) | No GameState — framework `playerReaction` |
| Choose location / hand / reveal ack | Yes |

## Checklist for this pattern

- [ ] Constant + State class + `states.inc.php` transition
- [ ] Transition key matches the string in `createTransitionEvent`
- [ ] `sourceId` is the attachment for attachment-hosted abilities
- [ ] Enter / Update / Leave JS all present
- [ ] Home selectable in JS when PHP offers Home
- [ ] Multiplayer states registered in `ZombieTrait`

## Next

Finish checklist → [[10 — Checklist|FactionAttachment Guide Finish Checklist]]
