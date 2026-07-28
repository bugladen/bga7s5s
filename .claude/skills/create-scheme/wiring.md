> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## State class — new pattern (use this for new schemes)

Each player-choice resolve **or Planning-End Forced** sub-state needs its own GameState class file. Mirror `State_planningPhaseResolveSchemes02052.php` (resolve) or `State_planningPhaseEnd_03041.php` (end Forced).

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_planningPhaseResolveSchemes<NNNNN> extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_<NNNNN>,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_<NNNNN>",

            description: clienttranslate('<Card name>') . clienttranslate(': ${actplayer} must choose ...'),
            descriptionMyTurn: clienttranslate('<Card name>') . clienttranslate(': ${you} must choose ...'),
            transitions: [
                "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsForState();
    }

    #[PossibleAction]
    public function actFromCardWithId(int $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        $this->game->gamestate->nextState();
    }
}
```

Where to put it: `modules/php/States/<expansion>/State_planningPhaseResolveSchemes<NNNNN>.php`. Auto-discovered by the BGA framework based on the namespace and the `id` matching a `States::` constant.

`#[PossibleAction]` methods to include depend on the pick shape:
- Discard pick: `actFromCardWithId(int $id)` + `actFromCardPass()`.
- Location pick: `actFromCardWithLocations(string $locations)` + (optionally) `actFromCardPass()`.
- Card-list pick (cards selected by trait/type): `actFromCardWithId(int $id)` + `actFromCardPass()`.

Inside the state class's `zombie()`, **don't** also add an entry in `ZombieTrait.php` — the state class's own `zombie()` is the one the framework dispatches to. Only the older inline-state pattern (states defined directly in `states.7s5s.php`) needs `ZombieTrait.php` entries.

### Old inline-state pattern (still used by core-set schemes)

The older pattern defines the state inline in `states.7s5s.php` as an array entry, with the zombie handler living in `ZombieTrait.php`. Don't extend this pattern for new schemes — use the GameState class pattern above instead. But you'll see it on `_01044`, `_01045`, `_01071`, `_01125`, `_01126`, `_01143`–`_01152`. Read those when investigating bugs in legacy schemes.

## States.php constant + states.inc.php transition

Both files always need an edit for any new scheme that has a player-choice sub-state.

**`modules/php/States.php` — three ID prefixes:**

```php
const PLANNING_PHASE_RESOLVE_SCHEMES_<NNNNN> = 26<NNNNN>;  // resolve picks
const PLANNING_PHASE_END_<NNNNN>             = 28<NNNNN>;  // Forced at Planning End picks
const HIGH_DRAMA_PLAYER_TURN_<NNNNN>         = 40<NNNNN>;  // City Action / Action picks
```

For additional steps, append `2`, `3`, etc. (`26030302`, `28030412`, `4030292`).

**`states.inc.php` — put the transition key on the matching map:**

| When the pick happens | Transition map |
|---|---|
| During scheme resolve | `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions` |
| During Forced at Planning End | `PLANNING_PHASE_END_EVENTS.transitions` |
| During High Drama action | `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions` |

```php
"NNNNN" => States::PLANNING_PHASE_END_<NNNNN>,   // example: Forced end pick
```

The transition key (`"NNNNN"`) is the string you pass as the third arg of `EventFactory::createTransitionEvent(...)`. It's looked up against the map for the events state that is currently running.

## JS Wiring

For every new player-choice sub-state, wire all three of:

- `modules/js/OnEnteringState.<expansion>.js` — set up the chooser (discard pile / city location / hand / etc.).
- `modules/js/OnUpdateActionButtons.<expansion>.js` — add Confirm + Pass buttons.
- `modules/js/OnLeavingState.<expansion>.js` — clean up (hide chooseList, reset city locations, remove highlights).

State name prefixes:
- Resolve picks → `planningPhaseResolveSchemes_<NNNNN>`
- Planning-End Forced picks → `planningPhaseEnd_<NNNNN>`
- High Drama action picks → `highDramaPhase<NNNNN>`

Hand multi-discard also needs an `EventHandlers.js` entry so the Confirm button enables/disables on selection change.

### Discard-pile chooser (trait-filtered)

```js
'planningPhaseResolveSchemes_<NNNNN>': () => {
    if (this.isCurrentPlayerActive()) {
        dojo.removeClass('choose_container', 'hidden');
        dojo.removeClass('chooseList', 'hidden');
        $('choose_container_name').innerHTML = _('Your Discard Pile');

        const player = this.gamedatas.players[this.getActivePlayerId()];
        player.discard.forEach((card) => {
            if (card.traits && (card.traits.includes('Gang') || card.traits.includes('Crime') || card.traits.includes('Villainous'))) {
                this.addCardToDeck(this.chooseList, card);
            }
        });
        this.chooseList.setSelectionMode(1);

        if (this.chooseList.count() > 0)
            dojo.addClass('actPass', 'disabled');
    }
},
```

Buttons:

```js
'planningPhaseResolveSchemes_<NNNNN>': () => {
    this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
    this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
    dojo.addClass('actChooseCardSelected', 'disabled');
},
```

Cleanup:

```js
'planningPhaseResolveSchemes_<NNNNN>': () => {
    dojo.addClass('choose_container', 'hidden');
    dojo.addClass('chooseList', 'hidden');
    this.chooseList.removeAll();
},
```

Reference: `_01044` (uses `card.type === 'Attachment'`), `_01045` (uses `card.traits.includes('Mercenary')` against `gamedatas.cityDiscard`), `_03005` (uses multi-trait filter against player's discard).

### City-location chooser

```js
'planningPhaseResolveSchemes_<NNNNN>': () => {
    if (this.isCurrentPlayerActive()) {
        const locations = this.getListofAvailableCityLocationImages();
        this.numberOfCityLocationsSelectable = 1;
        locations.forEach((location) => {
            this.makeCityLocationSelectable(location);
        });
    }
},
```

For **"two different locations"** set `numberOfCityLocationsSelectable = 2` (same enter/leave/button shape). Also add to `PlayerActions.js` `actionMap`:

```js
'planningPhaseResolveSchemes_<NNNNN>': 'actCityLocationsForReknownSelected',
```

Buttons:

```js
'planningPhaseResolveSchemes_<NNNNN>': () => {
    this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
    dojo.addClass('actCityLocationsSelected', 'disabled');
},
```

Cleanup:

```js
'planningPhaseResolveSchemes_<NNNNN>': () => {
    this.resetCityLocations();
},
```

Reference: `_01071`, `_01072`, `_02046`.

### Character-then-City-location resolve (planning)

Two states. State 1 highlights in-play characters (`ids` from args); Confirm → `onChooseInPlayCardConfirmed` → `actFromCardWithId`. State 2 is a filtered city-location chooser (`locationIds`) with Back.

```js
'planningPhaseResolveSchemes_<NNNNN>': () => {
    if (this.isCurrentPlayerActive()) {
        this.numberOfCardsSelectable = 1;
        this.clientStateArgs.ids = args.args.args.ids;
        this.highlightCardsAsSelectable(args.args.args.ids);
    }
},

'planningPhaseResolveSchemes_<NNNNN>_2': () => {
    if (this.isCurrentPlayerActive()) {
        this.numberOfCityLocationsSelectable = 1;
        (args.args.args.locationIds || []).forEach((locationId) => {
            this.makeCityLocationSelectable(this.getCityLocationElement(locationId));
        });
        if (args.args.args.characterId) {
            this.highlightCharacterChosen(args.args.args.characterId);
            this.clientStateArgs.characterId = args.args.args.characterId;
        }
    }
},
```

Buttons: state 1 Confirm only; state 2 Back + Confirm Location (`onCityLocationsSelected` → default `actFromCardWithLocations`). Leave: unhighlight cards / `resetCityLocations` + unhighlight character.

**Server:** state 2 success transition must be named (`"locationChosen"`) when `"back"` / `"zombie"` exist — see helpers.md / `_04004`.

Reference: `_04004` bas JS triple.

### Multi-card hand discard (Planning End Forced / draw-then-discard)

```js
'planningPhaseEnd_<NNNNN>': () => {
    if (this.isCurrentPlayerActive()) {
        const amount = args.args.args.cardsToDiscard;
        this.clientStateArgs.cardsToDiscard = amount;
        $('faction_hand_info').innerHTML = dojo.string.substitute(
            _("(${amount} card(s) to discard)"), { amount: amount }
        );
        this.factionHand.setSelectionMode('multiple');
    }
},
```

Buttons (Confirm → reusable `onCardsDiscarded` from `PlayerActions.js`):

```js
'planningPhaseEnd_<NNNNN>': () => {
    this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardsDiscarded());
    dojo.addClass('actChooseDiscardCards', 'disabled');
},
```

`EventHandlers.js` (exact count — do not enable on `length > 0`):

```js
'planningPhaseEnd_<NNNNN>': () => {
    const needed = this.clientStateArgs.cardsToDiscard || 0;
    if (this.factionHand.getSelection().length === needed) {
        dojo.removeClass('actChooseDiscardCards', 'disabled');
    } else {
        dojo.addClass('actChooseDiscardCards', 'disabled');
    }
},
```

Cleanup: `factionHand.setSelectionMode('none')`, clear `faction_hand_info`, reset `clientStateArgs`.

Reference: `_03041`. Single-card hand discard during HD: `highDramaPhase03038a` / `Action_03038a`.
