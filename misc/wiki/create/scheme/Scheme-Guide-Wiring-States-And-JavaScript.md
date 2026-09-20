# 09 — Wiring states and JavaScript

← [[08 — Challenges|Scheme Guide Challenge Actions]] · [[Index|Implementing a Scheme Card]] · Next: [[10 — Checklist|Scheme Guide Finish Checklist]]

You need this page when a Scheme asks the player to **pick something** during:

- Planning **resolve**
- **Forced** at Planning End / High Drama End
- A High Drama **City Action**

Pure button Reactions usually skip this entirely — Crash the Party's Reaction needs no wiring.

## Three different transition maps

Beginners often put every transition under the resolve map. Schemes use **three** (plus HD End for some Forced):

| When the pick happens | State id prefix | Transition map | State name prefix |
|---|---|---|---|
| During scheme resolve | `26<NNNNN>` | `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS` | `planningPhaseResolveSchemes_<NNNNN>` |
| Forced at Planning End | `28<NNNNN>` | `PLANNING_PHASE_END_EVENTS` | `planningPhaseEnd_<NNNNN>` |
| High Drama Action | `40<NNNNN>` | `HIGH_DRAMA_PLAYER_TURN_EVENTS` | `highDramaPhase<NNNNN>` |
| Forced at HD End | `60<NNNNN>` | `HIGH_DRAMA_END_EVENTS` | `highDramaEnd_<NNNNN>` |

The transition key string (third arg of `createTransitionEvent`) is looked up on the map for the events state that is currently running. The same `"03030"` key can legally exist on Planning resolve **and** High Drama maps.

## The three backend pieces (every new pick state)

1. **Constant** in `modules/php/States.php`
2. **GameState class** in `modules/php/States/<expansion>/State_....php`
3. **Transition entry** in `states.inc.php` (keep `states.7s5s.php` in sync when that file still lists the map)

### Prefer GameState classes for new work

Mirror `State_planningPhaseResolveSchemes03005.php` or `State_planningPhaseResolveSchemes02046.php`.

Put `zombie()` on the state class. Do **not** also add a `ZombieTrait.php` case for new GameState-class states.

Old core-set schemes (`_01044`, `_01071`, …) still use inline states in `states.7s5s.php`. Read them when debugging legacy cards; do not extend that pattern for new Schemes.

### GameState skeleton (resolve pick)

```php
class State_planningPhaseResolveSchemesNNNNN extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN,
            type: StateType::ACTIVE_PLAYER,
            name: "planningPhaseResolveSchemes_NNNNN",
            description: clienttranslate('Scheme') . clienttranslate(': ${actplayer} must choose ...'),
            descriptionMyTurn: clienttranslate('Scheme') . clienttranslate(': ${you} must choose ...'),
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

Which `#[PossibleAction]` methods you need depends on the pick shape (id / ids / locations / pass).

### Transitions with Back

Never pair `""` with `"back"` (or any second key) on the same state. Use named success transitions (`"cardDiscarded"`, `"thingChosen"`, …). Studio error otherwise: "More than one possible transition at this state".

## The three frontend pieces

For every new state name:

| File | Job |
|---|---|
| `modules/js/OnEnteringState.<expansion>.js` | Set up chooser (discard / locations / hand / highlights) |
| `modules/js/OnUpdateActionButtons.<expansion>.js` | Confirm / Pass / Back buttons |
| `modules/js/OnLeavingState.<expansion>.js` | Hide chooseList, `resetCityLocations()`, clear modes |

Copy a same-shaped neighbor in the same expansion file and rename the key.

### Discard-pile chooser

Show `chooseList`, filter by traits/type, selection mode 1, Confirm → `onChooseListCardConfirmed()`, Pass → `actFromCardPass`. Disable Pass when eligible cards exist if the card text requires a pick.

Reference JS patterns: `_03005`, `_01044` (legacy).

### City-location chooser

```js
'planningPhaseResolveSchemes_<NNNNN>': () => {
    if (this.isCurrentPlayerActive()) {
        const locations = this.getListofAvailableCityLocationImages();
        this.numberOfCityLocationsSelectable = 1; // or 2
        locations.forEach((location) => {
            this.makeCityLocationSelectable(location);
        });
    }
},
```

Confirm → `onCityLocationsSelected()`. Leave → `resetCityLocations()`.

**Two-location Renown resolve also needs** `PlayerActions.js` `actionMap`:

```js
'planningPhaseResolveSchemes_<NNNNN>': 'actCityLocationsForReknownSelected',
```

Without that map entry, Confirm falls through to the wrong action.

### Hand multi-discard (Planning End Forced)

Use `factionHand` multi-select. Store needed count in `clientStateArgs.cardsToDiscard`. Add an `EventHandlers.js` entry that enables Confirm only when `getSelection().length === needed` (not merely `> 0`).

Reference: `_03041`.

## High Drama Action states

Same JS habits as Character Actions, but:

- Constants use `40<NNNNN>`
- Transitions live under `HIGH_DRAMA_PLAYER_TURN_EVENTS`
- Logic methods usually live on the **Action** (`getArgsFromAction`, `actFromAction*`), not on the scheme class

## Args shape gotcha

- `OnEnteringState` often reads nested `args.args.args.*`
- `OnUpdateActionButtons` often reads `args.args.*`

When in doubt, `console.log` a working sibling state and match its depth.

## Next

Walk the finish list → [[10|Scheme Guide Finish Checklist]]
