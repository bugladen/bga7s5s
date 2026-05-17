---
name: create-scheme
description: Implement or finish a Scheme card (modules/php/cards/<expansion>/_NNNNN.php where the class directly extends Scheme). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Scheme, or when they reference a card whose class extends Scheme and has unimplemented Text. Triggers on phrases like "implement this scheme", "finish _NNNNN" (when it extends Scheme), "wire up the When Revealed effect", "add the Renown adds to this scheme", or natural-language descriptions of a scheme card (Initiative + Panache modifier, lives in the player's scheme deck, revealed at Dawn).

---

# Creating a Scheme

This skill covers cards that extend `Scheme` — the cards a player chooses each turn and reveals during the Planning Phase. They are **not** city-deck cards; each player has their own scheme deck and selects one scheme per turn.

Canonical references (read at least the ones that match your card shape before writing code):

- `modules/php/cards/_7s5s/_01044.php` (Armed and Marshaled) — **Resolve = Renown + pick-attachment-from-discard**, old inline-state pattern with `actFromCardWithId` / `actFromCardPass` and a `ZombieTrait` entry.
- `modules/php/cards/_7s5s/_01151.php` (Shifting Tides) — **When-Revealed effect** + multi-step resolve, multi-player sequential loop via queued reaction transitions.
- `modules/php/cards/_7s5s/_01071.php` (Épée Sanglante) — Resolve adds Renown to a player-chosen city location.
- `modules/php/cards/tac/_02004.php` (Crash the Party) — **Scheme with a City Reaction.** Simple Renown adds on resolve; reaction triggers on `EventPressureOccuring`.
- `modules/php/cards/tac/_02014.php` (Kaspar's Occupation) — **Scheme with a City Action.** Two-option resolve (add OR move Renown).
- `modules/php/cards/tac/_02046.php` (Winter's Wind) / `modules/php/cards/tac/_02052.php` (Gutter Full of Roses) — **New GameState-class pattern** for the resolve sub-state. Use this pattern for new schemes.
- `modules/php/cards/faf/_03005.php` (No Mercy) — **Resolve = Renown + pick-trait-filtered-card-from-discard, AND a Reaction.** Uses the new GameState class pattern. Reaction triggers on `EventChallengeRejected` when a Red Hand character (controlled by the scheme owner) had its challenge refused; stored location is then claimed.

When in doubt, mirror one of those rather than invent.

> **Sibling skills:** `create-character`, `create-city-character`, `create-city-event-card`, `create-city-attachment`. A *lot* of runtime semantics overlap (button-based Reactions, state classes, JS wiring) — read the relevant sibling skill alongside this one when the scheme has an Action or Reaction shape that closely matches a non-scheme card.

## Base Anatomy

`Scheme extends Card`. It adds two fields beyond `Card`:

- `$Initiative` — int. Lower = earlier in the scheme-resolution order. Required.
- `$PanacheModifier` — int. Added to (or subtracted from) the controller's Leader Panache while the scheme is revealed.

Schemes also have a `hasWhenRevealedEffect(): bool` hook (default `false`).

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class _NNNNN extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('...');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = 'faf';   // or '_7s5s' / 'tac'
        $this->ExpansionNumber = 3;
        $this->CardNumber      = N;

        $this->initializeFaction('Vodacce');

        $this->Initiative      = 91;
        $this->PanacheModifier = -1;

        $this->Traits = [
            clienttranslate('Villainous'),
            clienttranslate('Duress'),
        ];

        $this->Text = clienttranslate("<p>...</p>");

        $this->resetCard();
    }
}
```

Field notes:
- **`initializeFaction(...)` is mandatory** — schemes belong to a faction deck.
- **`CardNumber` matches the `NNNNN` in the filename.**
- **Traits must exist in `TraitNames::$TraitsJson`** (`modules/php/TraitNames.php`). Add missing ones in alphabetical order. (Memory feedback.)
- **`Initiative` is non-zero.** It's the tie-breaker (alongside Leader Panache) for scheme resolution order during planning. Don't leave at the constructor default 0.

## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code. The clauses above the horizontal rule (`<hr>`) are the **scheme effect** (resolved automatically during planning). Clauses below the rule are usually **City Action / Action / Reaction / City Reaction** keywords — the same shapes as on Characters.

| Card phrase | Pattern |
|---|---|
| **"Add a Renown to [Location]"** / **"Move a Renown from X to Y"** | Pattern A — resolve via `EventResolveScheme`. Queue `createReknownAddedToLocationEvent` / `createReknownRemovedFromLocationEvent`. No state class if the choice is forced; add a state class if the player picks the source/target. |
| **"When this scheme is revealed, …"** | Pattern B — When-Revealed effect. Override `hasWhenRevealedEffect()` to `true` AND handle `EventCardWhenRevealedEffect` in `handleEvent`. The When-Revealed fires *before* the resolve (and before other schemes' resolves), per card text. |
| **"Put a card from your discard into your hand"** / **"Search your discard for X"** | Pattern A resolve with a transition to a discard-pick state. New state class + JS wiring (chooseList). Reference: `_01044`, `_03005`. |
| **"Add a Renown to a city location"** (player choice) | Pattern A resolve with a transition to a location-pick state. JS uses `makeCityLocationSelectable` / `onCityLocationsSelected`. Reference: `_01071`, `_01072`, `_02046`. |
| **"Then, each opponent does X"** | Pattern C — multi-player sequential loop. Queue per-opponent reaction transitions during your own resolve. Reference: `_01151`. |
| **`<b>City Action:</b>`** / **`<b>Action:</b>`** | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_NNNNN.php` extending the right action base (`SchemeCityAction` if it's a City Action on a scheme — see action-base table below). The Action lives next to the scheme, not on the scheme class itself. |
| **`<b>City Reaction:</b>` / `<b>Reaction:</b>`** | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_NNNNN.php` extending `CardReaction`. Pre-commit hook enforces `setUsed`/`isAvailable` literal calls. Reference: `_02004` (City Reaction), `_03005` (Reaction). |
| **`<b>Forced:</b>`** | Override `handleEvent` directly on the scheme class. No Action/Reaction/State files. Reference: `_02052`'s Forced clause (`EventCharacterDestroyed` at Bazaar during duel). |

A single scheme can combine these freely. `_03005` has a two-clause resolve (Renown adds + pick-from-discard) AND a Reaction. `_01044` has a resolve (Renown + pick attachment) AND a City Action. `_02014` has a one-clause resolve (add OR move Renown) AND a Leader City Action.

## Pattern A — Resolve via `EventResolveScheme`

Every scheme that has any text above the `<hr>` needs an `EventResolveScheme` handler. Always call `parent::handleEvent($event)` first.

### Trivial resolve (no player choice)

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
    {
        $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ...'), [
            "scheme_inject_code" => $this->getInjectCode(),
        ]);

        $event1 = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
        $event->theah->queueEvent($event1);

        $event2 = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
        $event->theah->queueEvent($event2);
    }
}
```

Reference: `_02004` (Crash the Party).

### Resolve with player choice — transition to a sub-state

```php
if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
{
    // Queue the automatic part first.
    $event->theah->queueEvent(EventFactory::createReknownAddedToLocationEvent(
        $this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode()));

    // Then queue a transition into the player-choice state.
    $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "NNNNN");
    $transition->priority = Event::MEDIUM_PRIORITY;
    $event->theah->queueEvent($transition);
}
```

The transition's third arg (`"NNNNN"`) is the key looked up in the `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions` map of `states.inc.php`. Always set `priority = Event::MEDIUM_PRIORITY` so the automatic Renown events resolve first.

### Discard-pile pick state

Almost always `actFromCardWithId` style: the JS shows a `chooseList` populated with eligible discard cards, the player clicks one and confirms.

PHP-side method on the scheme:

```php
public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
{
    parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

    if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN)
    {
        $playerId = $game->getActivePlayerId();
        $card = $game->getCardObjectFromDb($id);
        if (! $card)
        {
            throw new UserException($game->translate("Invalid card"));
        }

        // Re-validate the trait/type filter — JS can be tampered with.
        if (! $this->cardMatchesTraits($card))
        {
            throw new UserException($game->translate("Card must have ..."));
        }

        // Verify it's actually in the discard pile.
        $deck = $game->getGameDeckObject($playerId);
        $discardPileName = $game->getPlayerDiscardDeckName($playerId);
        $cardObjects = $deck->getCardsInLocation($discardPileName);
        if (! in_array($card->Id, array_column($cardObjects, 'id')))
        {
            throw new UserException($game->translate("Card is not in your discard pile."));
        }

        $remove = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($playerId, $card->Id);
        $game->theah->eventCheck($remove);

        $add = EventFactory::createCardAddedToHandEvent($playerId, $card->Id);
        $game->theah->eventCheck($add);

        $game->theah->queueEvent($remove);
        $game->theah->queueEvent($add);

        $game->gamestate->nextState("");
    }
}

public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
{
    parent::actFromCardPass($game, $state, $stateName, $internalId);

    if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN)
    {
        // Throw if any eligible card remains — the text rarely says "if able".
        $playerId = $game->getActivePlayerId();
        $deck = $game->getGameDeckObject($playerId);
        $discardPileName = $game->getPlayerDiscardDeckName($playerId);
        foreach ($deck->getCardsInLocation($discardPileName) as $row)
        {
            $card = $game->getCardObjectFromDb($row['id']);
            if ($card && $this->cardMatchesTraits($card))
            {
                throw new UserException($game->translate("There is an eligible card in your discard pile that you must choose."));
            }
        }

        $game->gamestate->nextState("");
    }
}
```

Reference: `_01044` (filter by `Attachment` instanceof), `_03005` (filter by trait list). Both throw if a card is available; both `nextState("")` on success.

### Location-pick state

When the player picks a city location, use `actFromCardWithIds` (plural — the framework hands locations in as a string array).

```php
public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
{
    $args = parent::argsFromCard($game, $state, $stateName, $internalId);

    if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN)
    {
        $locations = $game->theah->getCityLocations();
        $args["locationIds"] = array_values(array_map(fn($l) => $l->Name, $locations));
    }

    return $args;
}

public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
{
    parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

    if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN)
    {
        $locationName = $ids[0];
        // Re-validate the location is a city location, has Renown, etc.
        // Queue createReknownAddedToLocationEvent / RemovedFromLocationEvent.
        $game->gamestate->nextState();
    }
}
```

Reference: `_01071`, `_02014`, `_02046`, `_02052`.

## Pattern B — When-Revealed effect

Override `hasWhenRevealedEffect()` so the framework knows to fire `EventCardWhenRevealedEffect` against this scheme *before* any scheme's resolve.

```php
public function hasWhenRevealedEffect(): bool
{
    return true;
}

public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventCardWhenRevealedEffect && $event->cardId == $this->Id)
    {
        // pre-resolve cleanup or setup work
    }

    if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
    {
        // normal resolve
    }
}
```

Reference: `_01151` (Shifting Tides) — When Revealed discards all city cards from city locations *before* any other scheme resolves.

## Pattern C — Multi-player sequential loop

When the text says "Then, each opponent does X", queue one transition per opponent (in turn order) after your own resolve completes. Each opponent's state runs, calls `nextState("")`, and the next opponent's transition fires.

```php
if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN_PLAYER1)
{
    $sql = "SELECT player_id FROM player ORDER BY turn_order";
    $list = $game->getCollectionFromDB($sql);
    foreach ($list as $playerId => $player)
    {
        if ($player['player_id'] == $this->ControllerId)
        {
            continue;
        }

        $transition = EventFactory::createTransitionEvent($playerId, $this->Id, "NNNNN_2");
        $transition->priority = Event::HIGH_PRIORITY;   // higher than the MEDIUM owner-resolve transition
        $game->theah->queueEvent($transition);
    }
}
```

Reference: `_01151` — first state is the owner's pick, second state (`"01151_2"`) is each opponent's pick, queued per-opponent in turn order with `HIGH_PRIORITY` so they fire in order.

## State class — new pattern (use this for new schemes)

Each player-choice resolve sub-state needs its own GameState class file. Mirror `State_planningPhaseResolveSchemes02052.php`.

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

Both files always need an edit for any new scheme that has a player-choice resolve sub-state.

**`modules/php/States.php`:**

```php
const PLANNING_PHASE_RESOLVE_SCHEMES_<NNNNN> = 26<NNNNN>;
```

State ID convention: `26` + 5-digit `CardNumber`. For additional steps, append `2`, `3`, etc. (`260<NNNNN>2`).

**`states.inc.php`:** Add to `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions`:

```php
"NNNNN" => States::PLANNING_PHASE_RESOLVE_SCHEMES_<NNNNN>,
```

The transition key (`"NNNNN"`) is the string you pass as the third arg of `EventFactory::createTransitionEvent(...)` from your `EventResolveScheme` handler. It's looked up against this map.

## JS Wiring

For every new scheme resolve sub-state, wire all three of:

- `modules/js/OnEnteringState.<expansion>.js` — set up the chooser (discard pile / city location / hand / etc.).
- `modules/js/OnUpdateActionButtons.<expansion>.js` — add Confirm + Pass buttons.
- `modules/js/OnLeavingState.<expansion>.js` — clean up (hide chooseList, reset city locations, remove highlights).

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

## Reactions on Schemes

Schemes can carry Reactions (use `IHasReactions` + `ReactionTrait` + `reactions/Reaction_NNNNN.php`). The reaction shape is identical to character-borne `CardReaction`s — see `create-character`'s Pattern D for the full recipe.

### Lifecycle: scheme reactions DO fire during High Drama

This was a worry during `_03005` implementation. Verified: `Theah::buildCity()` populates `$this->cards` from every persistent location including each player's discard pile. After a scheme resolves, it lands in discard — but it's still in `$this->cards`, so the framework still calls its `handleEvent`, which still propagates to the reaction's `handleEvent` via `Card::handleEvent`. `EventChallengeRejected` (which fires during High Drama City Action challenges) reaches the reaction normally. Don't add liveness guards based on "the scheme is no longer in play."

`CardReaction::handleEvent` resets `Used` to `false` on `EventDuskEndOfDay`, so a scheme reaction is once-per-day, same as a character reaction.

### Identity gates inside the reaction's `handleEvent`

`$this->getOwningCard($event->theah)` returns the *scheme* (which is the owner of this reaction). The scheme's `ControllerId` is set to the player who chose it for this turn. Standard idiom:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventChallengeRejected && $this->isAvailable())
    {
        $owner = $this->getOwningCard($event->theah);
        if ($owner == null) return;

        $challenger = $event->theah->getCharacterById($event->challengerId);
        if ($challenger == null) return;
        if ($challenger->ControllerId != $owner->ControllerId) return;
        if (! $challenger->hasTrait("Red Hand")) return;

        // Capture event-time context onto the reaction object so performReaction can use it.
        $this->location = $challenger->Location;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }
}
```

Reference: `Reaction_03005` (claim a location after Red Hand's challenge refused), `Reaction_02004` (move adjacent performer when opponent initiates pressure at scheme controller's location).

### Capturing context onto the reaction

The triggering event has only a snapshot of args (`$event->challengerId`, etc.). If the reaction needs context that isn't on the event (the location of the challenge, the destroyed character's name, etc.), capture it into a `private` property on the reaction at trigger time, **then clear it** in `performReaction`. `$owner->IsUpdated = true` persists the property to DB. See `Reaction_02004::$location` and `Reaction_03005::$location` for the pattern.

### Pre-commit hook compliance

`CardReaction` subclasses must include the literal strings `$this->setUsed(` and `$this->isAvailable(` somewhere in the file. The `handleEvent` `isAvailable` check + the `setUsed(true)` in `performReaction`'s success branch satisfy both.

### Schemes that target city locations / can't always claim

`createLocationClaimedEvent($playerId, ?int $performerId, string $location)` — `performerId` is `null` when the claim isn't tied to a specific performer (e.g., scheme-driven claims). Compare `Action_03cd13.php` which passes the performer for an Action-driven claim. Don't invent a "fake performer" — `null` is correct.

## Actions on Schemes

When a scheme has a City Action / Action / Leader City Action / Risk City Action, the action lives in a separate file `actions/Action_NNNNN.php` extending the appropriate base class:

| Card phrase | Action base class |
|---|---|
| **`<b>City Action:</b>`** on a scheme | `SchemeCityAction` |
| **`<b>Leader City Action:</b>`** on a scheme | `SchemeCityAction` (with a `hasTrait("Leader")` performer-filter) |
| **`<b>Risk City Action:</b>`** | `RiskCityAction` (the Risk shape — pressures/wagers; see `create-character`) |

`SchemeCityAction extends Scheme` — so the action class IS the scheme. (Same shape as `CharacterAction extends Character`.) The action class is what gets registered in `$this->Actions = [new Action_NNNNN()]` on the scheme card.

Pre-commit hook: `SchemeCityAction` subclasses must call `createActionResolvedEvent()`. Don't call `setUsed` / `resetPlayerPassCount` / `announceAction` directly — those run centrally during `actHighDramaInPlayActionConfirm` (same as character actions).

Reference: `_01044`'s `Action_01044`, `_02014`'s `Action_02014`.

## Walkthrough: implementing `_03005` (No Mercy)

A concrete worked example combining most patterns above. Card text:

> Add a Renown to [Bazaar] and [Forums]
> Put a **Gang**, **Crime**, or **Villainous** card from your discard into your hand.
> **Reaction:** After your **Red Hand**'s challenge is refused • Claim that location.

1. **Constructor.** `initializeFaction('Vodacce')`, set `Initiative = 91`, `PanacheModifier = -1`, Traits = Villainous + Duress. Both traits already in `TraitNames::$TraitsJson`.
2. **Resolve.** `EventResolveScheme` handler queues `createReknownAddedToLocationEvent` for Bazaar and Forum, then a `createTransitionEvent($playerId, $this->Id, "03005")` with `MEDIUM_PRIORITY` to move into the discard-pick state.
3. **Discard-pick state.** New GameState class `State_planningPhaseResolveSchemes03005` in `States/faf/`. `#[PossibleAction]` for `actFromCardWithId(int)` and `actFromCardPass()`. `zombie()` calls `nextState()`. **No `ZombieTrait.php` edit.**
4. **State constant.** `States::PLANNING_PHASE_RESOLVE_SCHEMES_03005 = 2603005`.
5. **Transition map.** `"03005" => States::PLANNING_PHASE_RESOLVE_SCHEMES_03005` in `states.inc.php`'s `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions`.
6. **Scheme class methods.** `actFromCardWithId` validates the card is in discard AND has Gang/Crime/Villainous trait, queues remove-from-discard + add-to-hand, `nextState("")`. `actFromCardPass` throws if any eligible card remains in discard.
7. **JS.** Add to `OnEnteringState.faf.js` (populate `chooseList` from `player.discard` filtered by `card.traits.includes(...)`), `OnUpdateActionButtons.faf.js` (Confirm + Pass), `OnLeavingState.faf.js` (hide and `chooseList.removeAll()`).
8. **Reaction.** `Reaction_03005 extends CardReaction`. Listens for `EventChallengeRejected` where the challenger is controlled by the scheme owner AND has the "Red Hand" trait. Captures `$challenger->Location` onto `$this->location`. Queues `createReactionTransitionEvent`. `performReaction` queues `createLocationClaimedEvent($owner->ControllerId, null, $this->location)` and `setUsed($theah, true)` if the player clicks "Claim …"; clears `$this->location` and `nextState("done")` either way.
9. **Pre-commit compliance.** `Reaction_03005` calls `$this->setUsed(` and `$this->isAvailable(` — hook satisfied.

Full implementation lives at `modules/php/cards/faf/_03005.php`, `modules/php/cards/faf/reactions/Reaction_03005.php`, `modules/php/States/faf/State_planningPhaseResolveSchemes03005.php`.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Scheme class:   `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:         `...\cards\<expansion>\actions`
  - Reaction:       `...\cards\<expansion>\reactions`
  - State class:    `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`
- **State ID convention:** `26<NNNNN>` for scheme resolve states. Don't engineer around hypothetical CD-card collisions — `2603005` is the right ID for scheme `_03005`, even though `_03cd05` exists. (Memory feedback.)
- **"Opposing"** means BOTH different controller AND same location.
- **Traits in `TraitNames::$TraitsJson`** — add missing ones in alphabetical order.

## Cross-Cutting Helpers

- `$theah->getCityLocation(string $name): ?CityLocation` — current Renown/controller for a city location. Returns `null` for non-city locations (defensive guard).
- `$theah->getCityLocations(): array` — all city locations in play (3 in 2p, 4 in 3p, 5 in 4p).
- `$theah->cardInCity($card): bool` — true when the card is at a city location.
- `$game->getCardObjectFromDb(int $id): ?Card` — hydrate a card from any location by id.
- `$game->getGameDeckObject(int $playerId): Deck` — get a player's deck wrapper. `getCardsInLocation(getPlayerDiscardDeckName($playerId))` is the discard query.
- `$game->getPlayerDiscardDeckName(int $playerId): string` — the deck-table location string for a player's discard pile.
- `$card->hasTrait(string $trait): bool` — check a trait. English strings compare directly against `clienttranslate()`-wrapped values.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${scheme_inject_code}` placeholder).

Event factories you'll likely need:
- `createReknownAddedToLocationEvent($playerId, $location, $count, $reason, $isMove = false)`
- `createReknownRemovedFromLocationEvent($playerId, $location, $count, $reason)`
- `createCardRemovedFromPlayerDiscardPileEvent($playerId, $cardId)` (notification-only)
- `createCardAddedToHandEvent($playerId, $cardId)` (does the actual move)
- `createLocationClaimedEvent($playerId, ?int $performerId, $location)`
- `createTransitionEvent($playerId, $sourceId, string $internalId)` — for moving into a sub-state.
- `createReactionTransitionEvent($playerId, $sourceId, $reactionId)` — for the reaction's transition.

## Reference Implementations

| File | What it demonstrates |
|---|---|
| `modules/php/cards/Scheme.php` | Base class. `$Initiative` + `$PanacheModifier`, `hasWhenRevealedEffect()` default. |
| `modules/php/cards/_7s5s/_01044.php` (Armed and Marshaled) | **Resolve = Renown adds + pick attachment from discard.** Old inline-state pattern with `actFromCardWithId` / `actFromCardPass`. Plus a City Action. |
| `modules/php/cards/_7s5s/_01045.php` (The Song of Eisen) | Pick a Mercenary from the **city** discard pile. Same inline-state pattern as `_01044`. |
| `modules/php/cards/_7s5s/_01071.php` (Épée Sanglante) | Add Renown to a player-chosen city location. `actCityLocationsForReknownSelected`. |
| `modules/php/cards/_7s5s/_01072.php` (Réputation Méritée) | Pick a location that has no Renown. Pass guard if no such location exists. |
| `modules/php/cards/_7s5s/_01151.php` (Shifting Tides) | **When-Revealed effect** + **multi-player sequential loop.** First state is the owner's pick, second state is each opponent's pick queued per-player in turn order. |
| `modules/php/cards/tac/_02004.php` (Crash the Party) | **Scheme with a City Reaction.** Simple Renown adds resolve. `EventPressureOccuring` reaction with captured-location state. |
| `modules/php/cards/tac/_02014.php` (Kaspar's Occupation) | Two-option resolve (add OR move Renown via `actFromCardWithId`/`actFromCardWithIds`). Plus a Leader City Action. |
| `modules/php/cards/tac/_02046.php` (Winter's Wind) | **New GameState-class pattern** for the resolve sub-state. Location picker. |
| `modules/php/cards/tac/_02052.php` (Gutter Full of Roses) | **New GameState-class pattern** with a move-renown source pick. Plus a Forced ability on `EventCharacterDestroyed`. |
| `modules/php/cards/faf/_03005.php` (No Mercy) | **Renown adds + trait-filtered discard pick + Reaction.** New GameState-class pattern. Reaction on `EventChallengeRejected` with captured location and `createLocationClaimedEvent`. |
| `modules/php/cards/faf/reactions/Reaction_03005.php` | Scheme reaction with `$location` capture, button-based Claim/Pass, `setUsed`/`isAvailable` discipline. |
| `modules/php/cards/tac/reactions/Reaction_02004.php` | Scheme reaction with adjacent-character target picker; captures the pressured location. |
| `modules/php/States/faf/State_planningPhaseResolveSchemes03005.php` | Reference for the new GameState-class shape, `#[PossibleAction]` methods, and inline `zombie()`. |

## When You Finish

1. Walk each clause of the printed Text — confirm each maps to exactly one pattern (Resolve / When-Revealed / Forced / City Action / Reaction). The Renown/Initiative numbers go on the constructor and are not a "pattern."
2. Confirm: `initializeFaction(<faction>)` is called, `CardNumber` matches the filename's NNNNN, `Initiative` is set, `PanacheModifier` is set (often 0 or ±1), all Traits exist in `TraitNames::$TraitsJson`.
3. For every player-choice resolve sub-state, ensure all three of: the GameState class in `modules/php/States/<expansion>/`, the constant in `States.php` (`26<NNNNN>`), and the transition entry in `states.inc.php` under `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions`.
4. Don't add a `ZombieTrait.php` case when using the GameState class pattern — the state's own `zombie()` method handles it.
5. JS triple: `OnEnteringState.<expansion>.js` (chooser setup) + `OnUpdateActionButtons.<expansion>.js` (Confirm + Pass) + `OnLeavingState.<expansion>.js` (cleanup).
6. Scheme reactions live through High Drama via `Theah::buildCity()` populating `$this->cards` from discard piles. Don't add "is the scheme still in play" guards.
7. Capture event-time context onto the reaction as a `private` property; clear it in `performReaction`. Use `$owner->IsUpdated = true` to persist.
8. Pre-commit hook checks on every file:
   - **Reaction subclass:** `$this->setUsed(` AND `$this->isAvailable(` literal strings present.
   - **SchemeCityAction subclass:** `createActionResolvedEvent()` called.
   - **`implements ISorcererAbility`:** both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` called.
   - No class implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards`.
9. Lint touched PHP files (`php -l`) before committing.
