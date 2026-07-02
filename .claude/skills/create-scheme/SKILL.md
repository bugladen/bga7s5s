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
- `modules/php/cards/faf/_03029.php` (Hour of Blood) — **Trivial Renown resolve + Sorcerer City Action with choose-one Porté move branches.** Three High Drama action sub-states (`03029` → `03029_2` → optional `03029_3`). Reference when a scheme action has "Choose one: Either … or …" before character/location picks.
- `modules/php/cards/faf/_03030.php` (Sworn Swords) — **Two-different-locations resolve + Diplomat City Action where performer (Diplomat) and challenger (Duelist) are different characters.** Two HD sub-states (`03030` → `03030_2`) then standard challenge flow. Custom `CHALLENGE_TYPE` for "Only Duelists may intervene" + `EventGenerateChallengeThreat` bonus on accept.

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
| **"Add a Renown to [Location]"** / **"Move a Renown from X to Y"** | Pattern A — resolve via `EventResolveScheme`. Queue `createRenownAddedToLocationEvent` / `createRenownRemovedFromLocationEvent`. No state class if the choice is forced; add a state class if the player picks the source/target. |
| **"When this scheme is revealed, …"** | Pattern B — When-Revealed effect. Override `hasWhenRevealedEffect()` to `true` AND handle `EventCardWhenRevealedEffect` in `handleEvent`. The When-Revealed fires *before* the resolve (and before other schemes' resolves), per card text. |
| **"Put a card from your discard into your hand"** / **"Search your discard for X"** | Pattern A resolve with a transition to a discard-pick state. New state class + JS wiring (chooseList). Reference: `_01044`, `_03005`. |
| **"Add a Renown to a city location"** (player choice) | Pattern A resolve with a transition to a location-pick state. JS uses `makeCityLocationSelectable` / `onCityLocationsSelected`. Reference: `_01071`, `_01072`, `_02046`. |
| **"Add a Renown to two different locations"** | Single-state two-location pick — use the framework helper `actCityLocationsForReknownSelected` and set `numberOfCityLocationsSelectable = 2` in JS. The helper iterates the JSON array, validates distinctness server-side (throws `UserException` if duplicates submitted), and queues one Renown event per location. JS also enforces distinctness as the first line of defense. Reference: `_01098` (Cat's Embargo), `_03006` (Premonition), `_03017` (Noble Sacrifice). |
| **"After your character at a `City` location is destroyed"** | Reaction listening on `EventCharacterDestroyed`. Gate by `$destroyed->ControllerId == $owner->ControllerId` and `$theah->locationInCity($destroyed->Location)`. `EventCharacterDestroyed` has `runEventHubAfterCards = true` so `$destroyed->Location` is still the destroy-time city slot when the reaction sees the event — capture it onto a `private string $location` because by the time the player clicks, the character has been moved to the locker. Reference: `_03017` (Noble Sacrifice), `Reaction_01013` (Red Hand destroyed). |
| **"Then, each opponent does X"** | Pattern C — multi-player sequential loop. Queue per-opponent reaction transitions during your own resolve. Reference: `_01151`. |
| **`<b>City Action:</b>`** / **`<b>Action:</b>`** | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_NNNNN.php` extending the right action base (`SchemeCityAction` if it's a City Action on a scheme — see action-base table below). The Action lives next to the scheme, not on the scheme class itself. |
| **`<b>City Reaction:</b>` / `<b>Reaction:</b>`** | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_NNNNN.php` extending `CardReaction`. Pre-commit hook enforces `setUsed`/`isAvailable` literal calls. Reference: `_02004` (City Reaction), `_03005` (Reaction), `_03006` (multi-stage Reaction). |
| **`<b>Strega Reaction:</b>`** / **`<b>Mercenary City Action:</b>`** / **`<b>Diplomat …:</b>`** / **`<b>Musketeer …:</b>`** | Trait-prefixed keywords are **mechanical performer-trait gates**, NOT Sorcerer abilities. The chosen performer must have that trait (enforce via `hasTrait("Strega")` etc.). Do NOT `implement ISorcererAbility` for these. Reference: `_03006` (Premonition's Strega Reaction enforces the gate via `findStregaPerformerAtLocation`). |
| **`<b>Sorcerer City Action:</b>` / `<b>Sorcerer Reaction:</b>`** | Mechanical "Sorcerer" keyword — class additionally `implements ISorcererAbility`, must emit `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces both literal calls). Can stack with trait gates: "Sorcerer Strega …" is both. Reference: `Reaction_02001` (Andriana), `Action_03029` (scheme Sorcerer City Action). |
| **`<b>Forced:</b>`** | Override `handleEvent` directly on the scheme class. No Action/Reaction/State files. Reference: `_02052`'s Forced clause (`EventCharacterDestroyed` at Bazaar during duel). |
| **"Choose one: <i>Either</i> … <i>or</i> …" on a City Action** (scheme or character) | **Pattern D — branch-first multi-step High Drama action.** State 1 = button pick (`actFromCardWithId` with ids 1/2); state 2 = character pick; state 3 = location pick only if one branch needs it. Persist the chosen branch on the **action object** (`public int $MoveMode` + `$owner->IsUpdated = true`). Do NOT use `Game::CHOSEN_TARGET` for branch state — the challenge framework owns that global. Use `Game::CHOSEN_CARD` to pass the chosen character between steps 2→3 when a later location pick is needed. Reference: `_03029`, `Reaction_03cd18` (same branch UX, but reactions use `$stage` + `createReactionTransitionEvent`, not HD sub-states). |
| **"Move your character at any location to your performer's location"** | Porté-style pull: `getCharactersInPlayByPlayerId($playerId)` filtered to `Location != $performer->Location` (includes Home). Single-mode cards can use one state like `Action_01085`. Reference: `Action_01068`, `Action_01085`. |
| **"Move your character at your performer's location to any location"** | Porté-style push: characters at `$performer->Location`, destinations = all `getCityLocations()` names + `Game::LOCATION_PLAYER_HOME` (if not already Home), excluding current location. Reference: `Action_01093` (Maya "any location"), `Action_01068`. |
| **"Engage your performer • Your \<trait\> at this location issues a [Combat] challenge"** | **Pattern E — split performer and challenger.** Framework performer pick (trait-gated Diplomat/etc.) → engage performer → HD sub-state pick challenger at that location → sub-state pick target → challenge. `CHOSEN_CARD` preserves performer id; `CHOSEN_PERFORMER` becomes challenger for challenge framework. Reference: `_03030` (Diplomat + Duelist), character parallel `Action_03003` (Thug issues challenge). |
| **"Only \<trait\>s may intervene"** on a challenge | Add a new `Game::…_CHALLENGE_TYPE` constant. Enforce in **`Theah::interventionCheck`**, **`ArgumentsTrait`** (intervene-picker `ids`), and **`Reaction_02058`** (adjacent external intervene) — same trio as `LEGENDARY_REPUTATION_CHALLENGE_TYPE` / `AJA_CHALLENGE_TYPE`. Reference: `_03030` (`SWORN_SWORDS_CHALLENGE_TYPE`, Duelist gate). |
| **"If the challenge is accepted, add a threat to your participant"** | Listen on `EventGenerateChallengeThreat` in the action class; bump `$event->actorThreat` only (not adversary). Fires on accept/intervene path when threat is generated, not on refuse. Reference: `Action_03030` (+1 actor), contrast `Action_02061` (+1 both). |

A single scheme can combine these freely. `_03005` has a two-clause resolve (Renown adds + pick-from-discard) AND a Reaction. `_01044` has a resolve (Renown + pick attachment) AND a City Action. `_02014` has a one-clause resolve (add OR move Renown) AND a Leader City Action. `_03029` has a trivial Renown resolve AND a branched Sorcerer City Action. `_03030` has a two-location resolve AND a split-performer Combat challenge with intervention gate and accept-time threat.

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

        $event1 = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
        $event->theah->queueEvent($event1);

        $event2 = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
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
    $event->theah->queueEvent(EventFactory::createRenownAddedToLocationEvent(
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
        // Queue createRenownAddedToLocationEvent / RemovedFromLocationEvent.
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

The triggering event has only a snapshot of args (`$event->challengerId`, etc.). If the reaction needs context that isn't on the event (the location of the challenge, the destroyed character's name, etc.), capture it into a `private` property on the reaction at trigger time, **then clear it** in `performReaction` (or `resetStage` for multi-stage reactions). `$owner->IsUpdated = true` persists the property to DB. See `Reaction_02004::$location` and `Reaction_03005::$location` for the pattern.

**Surface captured context in the prompt.** The reaction-button screen is the player's first chance to see *why* they're being prompted — bake the relevant context into `getReactionDescription` so they can make an informed pass/play call. Resolve the captured id to a name and `sprintf` it in:

```php
public function getReactionDescription(Theah $theah): string
{
    $base = parent::getReactionDescription($theah);
    $target = $this->targetCharacterId > 0
        ? $theah->getCharacterById($this->targetCharacterId)
        : null;
    $name = $target ? $target->Name : $theah->game->translate('your character');
    return $base . sprintf($theah->game->translate(
        '${you} may force the opponent to sink two cards after they targeted %s: '
    ), $name);
}
```

Always defensively null-check (`$target ? ... : translate('your character')`) — the captured id might point at a character that's since been destroyed/recruited away by the time the prompt renders. Reference: `Reaction_03006::$targetCharacterId` in the `'offer'` description.

### Bundled-effect reactions ("Reaction: … • A. B. C.")

When the bullet text has multiple sentences but no internal "may", the decision point is whether to use the reaction at all — once confirmed, all sub-effects fire atomically. Render a single **Resolve** + **Pass** button pair; don't gate sub-effects behind separate clicks. Resolve-branch queries (e.g. `getCharactersAtLocation($this->location)`) happen at *resolve time*, not at trigger time, so the deictic "that location" reflects the current set of characters there rather than a snapshot from when the trigger fired. Conditional clauses (e.g. "If the destroyed character was a Zealot, draw a card") gate on the *captured* trait snapshot, since the character is gone from play by then. Reference: `Reaction_03017`.

### Pre-commit hook compliance

`CardReaction` subclasses must include the literal strings `$this->setUsed(` and `$this->isAvailable(` somewhere in the file. The `handleEvent` `isAvailable` check + the `setUsed(true)` in `performReaction`'s success branch satisfy both. Decline/Pass branches deliberately skip `setUsed` — the reaction stays available for the next trigger that day. Mirror `Reaction_03005` / `Reaction_02004` / `Reaction_03017` for this discipline.

### `EventCharacterDestroyed` — destroy-time location is readable

`EventCharacterDestroyed` is declared with `runEventHubAfterCards = true`. Card `handleEvent` calls run **before** the hub moves the character to the locker, so `$destroyed->Location` still reports the destroy-time city slot inside your reaction's handler. Capture it into a `private string $location` field (with `$owner->IsUpdated = true`) for use in `performReaction`, because by the time the player clicks the button, the character has been moved out and `$destroyed->Location` no longer matches the city. Also capture any trait/name snapshots the resolve branch needs (`$destroyedWasZealot`, `$destroyedName`) — same reason.

### Schemes that target city locations / can't always claim

`createLocationClaimedEvent($playerId, ?int $performerId, string $location)` — `performerId` is `null` when the claim isn't tied to a specific performer (e.g., scheme-driven claims). Compare `Action_03cd13.php` which passes the performer for an Action-driven claim. Don't invent a "fake performer" — `null` is correct.

### Multi-stage reactions (button-driven, no sub-state)

Use this when the Reaction needs several player clicks in sequence (e.g. offer → pick target → confirm), or when the *player who clicks* changes between steps. Pattern source: `Reaction_03cd10` (Julius Caligari), `Reaction_03006` (Premonition).

Anatomy:

- A `private string $stage` field (e.g. `''` idle, `'offer'`, `'pick1'`, `'pick2'`, etc.) plus any per-stage context (`$opponentId`, `$performerId`, …). Persist with `$owner->IsUpdated = true`.
- `getReactionDescription` switches on `$stage` to return the right prompt text.
- `getReactionButtonProperties` switches on `$stage` to render different button sets (e.g. **Force Sink** / **Pass** for `'offer'`; one `card-{id}` button per hand card for `'pick1'`/`'pick2'`).
- `performReaction` parses the click via `str_starts_with($reactionId, 'card-')` etc., applies the effect, advances `$stage`, then **queues another `createReactionTransitionEvent` for the player whose turn comes next** and calls `nextState("done")`. The framework re-enters `playerReaction` with the updated active player + button set.
- `setUsed($theah, true)` only fires when the multi-stage flow is fully resolved (in `finalize` / the last stage).

Example skeleton:

```php
public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
{
    parent::performReaction($game, $state, $internalId, $reactionId);
    $owner = $this->getOwningCard($game->theah);

    if ($this->stage === 'offer') {
        if ($reactionId === 'sink') {
            // advance to pick1, queue reaction transition (often for a different player)
            $this->stage = 'pick1';
            $owner->IsUpdated = true;
            $transition = EventFactory::createReactionTransitionEvent($this->opponentId, $owner->Id, $this->Id);
            $game->theah->queueEvent($transition);
            $game->gamestate->nextState("done");
            return;
        }
        $this->resetStage(); $owner->IsUpdated = true;
        $game->gamestate->nextState("done");
        return;
    }

    if ($this->stage === 'pick1' || $this->stage === 'pick2') {
        if (str_starts_with($reactionId, 'card-')) {
            $cardId = (int)substr($reactionId, strlen('card-'));
            $this->doOneSink($game, $owner, $cardId);
            $this->cardsSunk++;
            if ($this->cardsSunk < 2 && $this->hasMoreToPick($game)) {
                $this->stage = 'pick2';
                $owner->IsUpdated = true;
                $transition = EventFactory::createReactionTransitionEvent($this->opponentId, $owner->Id, $this->Id);
                $game->theah->queueEvent($transition);
                $game->gamestate->nextState("done");
                return;
            }
            $this->finalize($game, $owner);  // sets Used, resets stage
        }
    }
    $game->gamestate->nextState("done");
}
```

### Cross-player Reactions (opponent performs part of the resolution)

When a Reaction's effect requires the **opposing** player to make a choice (e.g. "they must sink two cards from their hand"), **do not** route through a dedicated GameState sub-state. Reactions can fire from any phase (Planning, High Drama, Dawn, Duels), and a sub-state mapped under one phase's `*_EVENTS` transitions table only works in that one phase.

Instead, queue a `createReactionTransitionEvent($opponentId, $owner->Id, $this->Id)` with the opponent's playerId. The framework makes them the active player in the `playerReaction` state, where the reaction's `getReactionButtonProperties` (driven by `$stage`) renders the appropriate hand-picker buttons for them. `playerReaction` exists alongside every events state, so this works phase-independently.

Why not `createTransitionEvent($opponentId, ...)` (the `_02025` "Tea and Cakes" pattern)? That works only when the reaction fires from a single, predictable events state (`_02025` only resolves during `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS`). For reactions with broad firing surfaces, the reaction-button pattern is the portable choice.

Reference: `_03006` (Premonition — scheme owner clicks Force Sink, then opponent gets buttons for each hand card to sink in `pick1`/`pick2`).

### Listening for "ability that targets a character"

If the trigger is "when your character is targeted by an opposing ability …", you need to react to any ability that `instanceof IAbilityThatTargetsCharacters`. There is no single event for this; abilities propagate via several effect events. Listen to the **full set**:

- `EventSorcererAbilityPlayed`, `EventRangedAbilityPlayed` (both expose `$targetId`/`$targetLocation` directly)
- `EventCardEngaged`, `EventCardEngarded` (target via `$cardId`)
- `EventCardMoving` (target via `$cardId`)
- `EventCharacterBeingWounded`, `EventCharacterBeingHealed` (target via `$characterId`)
- `EventChallengeIssued` (target via `$defenderId`)

Inside each branch, look up the source ability:

```php
private function sourceAbilityTargetsCharacters(Theah $theah, int $sourceId, string $abilityId): bool
{
    if ($abilityId === '') return false;

    $source = $theah->getCardById($sourceId);
    if ($source !== null) {
        $ability = $source->getAbilityById($abilityId);
        if ($ability instanceof IAbilityThatTargetsCharacters) return true;
    }

    // Fallback for BasicChallengeAction which fires with sourceId = 0.
    $action = $theah->getInPlayActionById($abilityId);
    return $action instanceof IAbilityThatTargetsCharacters;
}
```

Both the `getCardById->getAbilityById` AND `getInPlayActionById` lookups are needed — the basic challenge action fires with `sourceId = 0`, so the card lookup returns null and you need the action-by-id fallback. See `Reaction_01014` (Vittoria), `Reaction_01032` (Unyielding Loyalty), `Reaction_03006` for the full pattern.

Wrap the whole `handleEvent` body with an `if (! $this->isAvailable()) return;` near the top. The once-per-day reset handles "one ability fires multiple effect events" — the reaction only triggers on the first event; after the player resolves, `setUsed` blocks further events from the same ability.

### "Your performer's location" on a scheme

Schemes don't have a fixed performer like character actions do. When the printed text says "your performer's location" (e.g. Premonition's "your character at your performer's location"), interpret it as: **the scheme controller picks/identifies a character to act as the performer**, and that character's location is "your performer's location".

For a trait-prefixed reaction ("Strega Reaction" etc.), the performer must have the gating trait. Pattern:

```php
private function findStregaPerformerAtLocation(Theah $theah, int $controllerId, string $location): ?Character
{
    foreach ($theah->getCharactersAtLocation($location) as $character) {
        if ($character->ControllerId == $controllerId && $character->hasTrait("Strega")) {
            return $character;
        }
    }
    return null;
}
```

Capture the performer's id onto the reaction at trigger time (e.g. `$this->performerId = $performer->Id`) so `performReaction` can attribute events to that character. Reference: Cross-of-Martyrs audit (`2026-03-17-04`) — Eddie's correction: "the 'performer' is the character the player CHOOSES to perform the reaction."

### "If able" loop termination

When the effect demands N items from a finite pool ("sink two cards", "discard three", etc.), structure the loop so it terminates gracefully when the pool is exhausted — the rules implicitly read "if able". Pattern:

```php
private function advanceToNextPick(Game $game, Card $owner): bool
{
    $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
    if (count($hand) == 0) return false;          // pool exhausted — finalize
    $this->stage = ($this->cardsSunk == 0) ? 'pick1' : 'pick2';
    $owner->IsUpdated = true;
    $game->theah->queueEvent(EventFactory::createReactionTransitionEvent(
        $this->opponentId, $owner->Id, $this->Id));
    return true;
}
```

Caller treats `false` as "finalize early" and skips remaining picks. Reference: `Reaction_03006::advanceToNextPick`.

## Actions on Schemes

When a scheme has a City Action / Action / Leader City Action / Risk City Action, the action lives in a separate file `actions/Action_NNNNN.php` extending the appropriate base class:

| Card phrase | Action base class |
|---|---|
| **`<b>City Action:</b>`** on a scheme | `SchemeCityAction` |
| **`<b>Leader City Action:</b>`** on a scheme | `SchemeCityAction` (with a `hasTrait("Leader")` performer-filter) |
| **`<b>Risk City Action:</b>`** | `RiskCityAction` (the Risk shape — pressures/wagers; see `create-character`) |

`SchemeCityAction extends Scheme` — so the action class IS the scheme. (Same shape as `CharacterAction extends Character`.) The action class is what gets registered in `$this->Actions = [new Action_NNNNN()]` on the scheme card.

Pre-commit hook: `SchemeCityAction` subclasses must call `createActionResolvedEvent()`. Don't call `setUsed` / `resetPlayerPassCount` / `announceAction` directly — those run centrally during `actHighDramaInPlayActionConfirm` (same as character actions).

Reference: `_01044`'s `Action_01044`, `_02014`'s `Action_02014`, `_03029`'s `Action_03029`.

### High Drama action sub-states (City Action / Sorcerer City Action)

Planning resolve sub-states use `PLANNING_PHASE_RESOLVE_SCHEMES_*` (`26<NNNNN>`) and `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions`. **Scheme actions played during High Drama use a different map:**

| Piece | Where |
|---|---|
| State constant | `States::HIGH_DRAMA_PLAYER_TURN_<NNNNN>` = `40<NNNNN>` (append `2`, `3` for follow-on steps) |
| Transition map | `states.inc.php` → `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions` — keys like `"03029"`, `"03029_2"`, `"03029_3"` |
| State class | `modules/php/States/<expansion>/State_highDramaPhase<NNNNN>.php` (name: `highDramaPhase<NNNNN>`) |
| Action logic | `actions/Action_NNNNN.php` — `handleEvent` on `EventActionTriggered` queues `createTransitionEvent($playerId, $owner->Id, "NNNNN", $this->Id)` |
| JS | Same triple as resolve states, but in `OnEnteringState.<expansion>.js` etc. under `highDramaPhase<NNNNN>` keys |

**Performer-required actions:** set `$this->RequiresPerformerSelected = true` on the action. The framework sets `Game::CHOSEN_PERFORMER` before your first sub-state runs.

**Multi-step with Back:** middle/final states expose `#[PossibleAction] actBack()` and a `"back"` transition to the prior state; JS adds `<` calling `actBack`. Reference: `State_highDramaPhase03029_2`, `State_highDramaPhase03cd01_2`.

**Sorcerer performer gate on a scheme City Action:** override `getPerformersForAction` to filter `hasTrait("Sorcerer")`. In `isAvailableToPlayer`, loop performers and return true only if at least one has a legal target for at least one printed branch — don't gate availability on a single fixed performer.

**`SchemeCityAction` availability:** the base `SchemeAction` requires the scheme owner card at `LOCATION_PLAYER_HOME`. That is correct for normal schemes (they land in discard after Planning resolve and actions fire from there). Only override `isAvailableToPlayer` like `_02045` when the scheme is **placed on a city location** and the action keys off that placement.

**Sorcerer wound + move event order** (matches Porté on characters):

```php
createSorcererAbilityStartEvent(...)
createCharacterBeingWoundedEvent($performer->Id, ...)  // "Wound your performer"
createCardMovingEvent(...)
createSorcererAbilityPlayedEvent(...)
createActionResolvedEvent(...)
```

**Character targeting validation:** even when picks go through sub-states (not the challenge target UI), implement `isValidTargetForAbility(Game $game, Character $character): array` and call it from `actFromActionWithId` — JS can be tampered with.

### Pattern E — Engage performer, different character issues challenge

Use when the printed text separates **who engages** (performer) from **who issues the challenge** (another character at the same location). Reference: `_03030` (Diplomat engages, Duelist challenges), `Action_03003` on Don Constanzo (Thug challenges — no framework performer pick).

**Flow (`_03030` shape):**

1. `RequiresPerformerSelected = true`. `getPerformersForAction` filters performer trait **and** full action legality (see below).
2. `EventActionTriggered`: validate performer → `createCardEngagedEvent` for performer if not engaged → `Game::CHOSEN_CARD = $performerId` (preserve while challenger takes over `CHOSEN_PERFORMER`) → `createTransitionEvent(..., "NNNNN")`.
3. HD state `NNNNN`: pick challenger (e.g. Duelist at performer's location) → `CHOSEN_PERFORMER = $challengerId` → `nextState("…Chosen")`.
4. HD state `NNNNN_2`: pick opposing target → set `CHALLENGE_STAT` / custom `CHALLENGE_TYPE` → `createTransitionEvent(..., "NNNNN_2")` where `"NNNNN_2"` maps to `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` in `states.inc.php` (same as `03003_2`).

**`getPerformersForAction` must encode full legality for the performer picker** — not just the trait gate. For `_03030`, each Diplomat must pass ALL of:

- `hasTrait("Diplomat")` and `!Engaged` (card says "Engage your performer")
- at least one eligible challenger at `$performer->Location` (e.g. Duelist with `canChallenge($theah)`)
- at least one opposing character at `$performer->Location`

`isAvailableToPlayer` should be `return count($this->getPerformersForAction($playerId, $theah)) > 0` so availability matches the picker.

**Engagement:** only engage the printed performer. Do **not** rely on `stIssueChallenge`'s auto-engage for the challenger — register a custom `CHALLENGE_TYPE` excluded from that list (same idea as `DON_CONSTANZO_CHALLENGE_TYPE`).

**Challenger eligibility:** if the card doesn't say "unengaged \<trait\>", allow already-engaged challengers when `canChallenge($theah)` permits (see `Action_03003` Thug comment).

### Custom `CHALLENGE_TYPE` for intervention gates

When the text restricts who may intervene (or refuse — see `create-risk` skill), add `final const …_CHALLENGE_TYPE = N` in `Game.php` and enforce in all three places that filter intervene UI/server checks:

| Location | Role |
|---|---|
| `Theah::interventionCheck` | Server-side reject on illegal intervene click |
| `ArgumentsTrait` (intervene args) | Filter `ids` so UI only shows legal interveners |
| `Reaction_02058::getValidPerformers` | Adjacent external-intervene reaction respects the same gate |

Reference: `LEGENDARY_REPUTATION_CHALLENGE_TYPE` (Leaders only), `AJA_CHALLENGE_TYPE` (3+ Finesse), `SWORN_SWORDS_CHALLENGE_TYPE` (Duelists only on `_03030`).

**Accept-time threat bonus:** handle in the action's `handleEvent` on `EventGenerateChallengeThreat` when `$challengeType` matches — increment `$event->actorThreat` for "your participant" only.

## Walkthrough: implementing `_03005` (No Mercy)

A concrete worked example combining most patterns above. Card text:

> Add a Renown to [Bazaar] and [Forums]
> Put a **Gang**, **Crime**, or **Villainous** card from your discard into your hand.
> **Reaction:** After your **Red Hand**'s challenge is refused • Claim that location.

1. **Constructor.** `initializeFaction('Vodacce')`, set `Initiative = 91`, `PanacheModifier = -1`, Traits = Villainous + Duress. Both traits already in `TraitNames::$TraitsJson`.
2. **Resolve.** `EventResolveScheme` handler queues `createRenownAddedToLocationEvent` for Bazaar and Forum, then a `createTransitionEvent($playerId, $this->Id, "03005")` with `MEDIUM_PRIORITY` to move into the discard-pick state.
3. **Discard-pick state.** New GameState class `State_planningPhaseResolveSchemes03005` in `States/faf/`. `#[PossibleAction]` for `actFromCardWithId(int)` and `actFromCardPass()`. `zombie()` calls `nextState()`. **No `ZombieTrait.php` edit.**
4. **State constant.** `States::PLANNING_PHASE_RESOLVE_SCHEMES_03005 = 2603005`.
5. **Transition map.** `"03005" => States::PLANNING_PHASE_RESOLVE_SCHEMES_03005` in `states.inc.php`'s `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions`.
6. **Scheme class methods.** `actFromCardWithId` validates the card is in discard AND has Gang/Crime/Villainous trait, queues remove-from-discard + add-to-hand, `nextState("")`. `actFromCardPass` throws if any eligible card remains in discard.
7. **JS.** Add to `OnEnteringState.faf.js` (populate `chooseList` from `player.discard` filtered by `card.traits.includes(...)`), `OnUpdateActionButtons.faf.js` (Confirm + Pass), `OnLeavingState.faf.js` (hide and `chooseList.removeAll()`).
8. **Reaction.** `Reaction_03005 extends CardReaction`. Listens for `EventChallengeRejected` where the challenger is controlled by the scheme owner AND has the "Red Hand" trait. Captures `$challenger->Location` onto `$this->location`. Queues `createReactionTransitionEvent`. `performReaction` queues `createLocationClaimedEvent($owner->ControllerId, null, $this->location)` and `setUsed($theah, true)` if the player clicks "Claim …"; clears `$this->location` and `nextState("done")` either way.
9. **Pre-commit compliance.** `Reaction_03005` calls `$this->setUsed(` and `$this->isAvailable(` — hook satisfied.

Full implementation lives at `modules/php/cards/faf/_03005.php`, `modules/php/cards/faf/reactions/Reaction_03005.php`, `modules/php/States/faf/State_planningPhaseResolveSchemes03005.php`.

## Walkthrough: implementing `_03029` (Hour of Blood)

Card text:

> Add a Renown to [City Forum] and [City Docks]
> **Sorcerer City Action:** Wound your performer • Choose one: *Either* move your character at any location to your performer's location, *or* move your character at your performer's location to any location.

1. **Constructor.** `initializeFaction('Montaigne')`, `Initiative = 71`, `PanacheModifier = 0`, Traits = Sorcery + Porté. Register `IHasActions` + `ActionTrait` + `new Action_03029()`.
2. **Resolve.** `EventResolveScheme` queues two `createRenownAddedToLocationEvent` (Forum + Docks). No sub-state — both destinations are fixed.
3. **Action class.** `Action_03029 extends SchemeCityAction implements ISorcererAbility`. `RequiresPerformerSelected = true`. `getPerformersForAction` filters Sorcerer trait. `isAvailableToPlayer` checks each Sorcerer performer for option A and/or B legality.
4. **Branch persistence.** `public int $MoveMode` on the action (values 1 = to performer, 2 = from performer). Set in state 1, read in state 2 args filtering, clear after resolve. `$owner->IsUpdated = true` when mutating.
5. **Three HD sub-states.**
   - `03029`: buttons for each available branch (`optionToPerformerAvailable` / `optionFromPerformerAvailable` in args). `actFromCardWithId(1|2)` → `nextState("optionChosen")`.
   - `03029_2`: character picker filtered by `$MoveMode`. Option A resolves here (wound + move + sorcerer events + `createActionResolvedEvent`). Option B stores `Game::CHOSEN_CARD` → `nextState("characterChosen")`.
   - `03029_3`: location picker (city + Home via `makeHomeEndcapMarkerSelectable` in JS). `actFromActionWithIds` → resolve.
6. **State constants.** `HIGH_DRAMA_PLAYER_TURN_03029 = 403029`, `_03029_2 = 4030292`, `_03029_3 = 4030293`.
7. **Transitions.** `"03029"`, `"03029_2"`, `"03029_3"` in `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`. State 2/3 include `"back"` transitions.
8. **JS (faf).** State 1: conditional action buttons (not card selection). State 2: `highlightCardsAsSelectable` + Confirm + Back. State 3: city locations + Home + Confirm + Back. Highlight performer (and chosen character on step 3).
9. **Pre-commit.** `createActionResolvedEvent()` in resolve path; both sorcerer start/played events in `resolveMove()`.

Full implementation: `modules/php/cards/faf/_03029.php`, `modules/php/cards/faf/actions/Action_03029.php`, `modules/php/States/faf/State_highDramaPhase03029{,_2,_3}.php`.

## Walkthrough: implementing `_03030` (Sworn Swords)

Card text:

> Add a Renown to two different locations.
> **Diplomat City Action:** Engage your performer • Your **Duelist** at this location issues a [Combat] challenge to target opposing character. Only **Duelists** may intervene. If the challenge is accepted, add a threat to your participant.

1. **Constructor.** `initializeFaction('Montaigne')`, `Initiative = 36`, `PanacheModifier = 0`, Traits = Oathsworn + Challenge. Register `IHasActions` + `ActionTrait` + `new Action_03030()`.
2. **Resolve.** Same as `_03006`: `EventResolveScheme` notifies, then `createTransitionEvent($playerId, $this->Id, "03030")` with `MEDIUM_PRIORITY`. Planning state uses `actCityLocationsForReknownSelected` + `numberOfCityLocationsSelectable = 2` in JS. Transition key `"03030"` lives under `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS` — distinct from the HD action's `"03030"` under `HIGH_DRAMA_PLAYER_TURN_EVENTS` (same card number, different maps).
3. **Action class.** `Action_03030 extends SchemeCityAction implements IAbilityThatTargetsCharacters`. `RequiresPerformerSelected = true`.
4. **`getPerformersForAction`.** Filter Diplomats who are `!Engaged`, have ≥1 eligible Duelist at their location (`hasTrait("Duelist") && canChallenge`), AND have ≥1 opposing character at that location. `isAvailableToPlayer` = `count(getPerformersForAction) > 0`.
5. **`EventActionTriggered`.** Engage Diplomat → `CHOSEN_CARD = $diplomatId` → transition `"03030"`.
6. **Two HD sub-states.**
   - `03030`: pick Duelist → `CHOSEN_PERFORMER = $duelistId` → `duelistChosen` → state 2.
   - `03030_2`: pick target → set `SWORN_SWORDS_CHALLENGE_TYPE` + `STAT_COMBAT` → transition `"03030_2"` → `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE`.
7. **`SWORN_SWORDS_CHALLENGE_TYPE = 21`.** Gate Duelist-only intervene in `Theah::interventionCheck`, `ArgumentsTrait`, `Reaction_02058`. `EventGenerateChallengeThreat`: `actorThreat += 1`.
8. **JS (faf).** Planning: copy `_03006` location picker. HD: copy `_03003` character picker (highlight Diplomat on step 1, Duelist on step 2).

Full implementation: `modules/php/cards/faf/_03030.php`, `modules/php/cards/faf/actions/Action_03030.php`, `modules/php/States/faf/State_planningPhaseResolveSchemes03030.php`, `State_highDramaPhase03030{,_2}.php`.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Scheme class:   `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:         `...\cards\<expansion>\actions`
  - Reaction:       `...\cards\<expansion>\reactions`
  - State class:    `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`
- **State ID convention:** `26<NNNNN>` for Planning resolve sub-states; `40<NNNNN>` for High Drama action sub-states (`HIGH_DRAMA_PLAYER_TURN_<NNNNN>`). Don't engineer around hypothetical CD-card collisions — `2603005` is the right ID for scheme `_03005`, even though `_03cd05` exists. (Memory feedback.)
- **Two transition tables:** resolve picks → `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions`; action picks → `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`. Don't register HD action states under the planning map.
- **"Opposing"** means BOTH different controller AND same location.
- **Traits in `TraitNames::$TraitsJson`** — add missing ones in alphabetical order.
- **Typed PHP parameters required.** Every function/method signature must declare a type for every parameter — no bare `$foo`. Use concrete types (`Card $owner`, `Character $performer`, `Game $game`, `Theah $theah`, `Event $event`, `int $cardId`, `string $reactionId`). Add the `use` import.
- **"Strega" / "Mercenary" / "Diplomat" / etc.** are **mechanical performer-trait gates**, not flavor. Enforce via `hasTrait("Strega")` on the chosen performer. They are NOT Sorcerer abilities — do NOT `implement ISorcererAbility` for them. Only the literal "Sorcerer" keyword triggers `ISorcererAbility`. They can stack ("Sorcerer Strega Reaction" is both).
- **Windows line endings:** PHP files in this repo use single CRLF (`\r\n`). Agent-written files sometimes land as `\r\r\n` (double carriage return), which displays as a blank line after every line. After writing scheme/action PHP, verify against a sibling file (e.g. hex dump or line count). Fix with: `$content -replace "`r`r`n", "`r`n"` in PowerShell. Match existing files — do not convert LF-only.

## Cross-Cutting Helpers

- `$theah->getCityLocation(string $name): ?CityLocation` — current Renown/controller for a city location. Returns `null` for non-city locations (defensive guard).
- `$theah->getCityLocations(): array` — all city locations in play (3 in 2p, 4 in 3p, 5 in 4p).
- `$theah->cardInCity($card): bool` — true when the card is at a city location.
- `$theah->locationInCity(string $location): bool` — the canonical "City location" check (Oles Inn / Docks / Forum / Bazaar / Governor's Garden). Use this when you only have the location string in hand (e.g. a captured `$this->location` on a Reaction) rather than a Card object. The printed keyword **"City"** on a card text maps directly to this set — not Home, not Locker, not Discard.
- `$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): Character[]` — every Character at the location. Filter by `ControllerId` to split friend/foe.
- `$game->getCardObjectFromDb(int $id): ?Card` — hydrate a card from any location by id.
- `$game->getGameDeckObject(int $playerId): Deck` — get a player's deck wrapper. `getCardsInLocation(getPlayerDiscardDeckName($playerId))` is the discard query.
- `$game->getPlayerDiscardDeckName(int $playerId): string` — the deck-table location string for a player's discard pile.
- `$card->hasTrait(string $trait): bool` — check a trait. English strings compare directly against `clienttranslate()`-wrapped values.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${scheme_inject_code}` placeholder).

Event factories you'll likely need:
- `createRenownAddedToLocationEvent($playerId, $location, $count, $reason, $isMove = false)`
- `createCharacterBeingWoundedEvent($characterId, $sourceId, $wounds, $reason, $abilityId = '')`
- `createCharacterBeingHealedEvent($characterId, $sourceId, $wounds, $reason, $abilityId = '')`
- `createCardDrawnEvent($playerId, $reason)` — for "draw a card" clauses.
- `createRenownRemovedFromLocationEvent($playerId, $location, $count, $reason)`
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
| `modules/php/cards/faf/_03006.php` (Premonition) | **Two-different-locations resolve via `actCityLocationsForReknownSelected` + multi-stage Strega Reaction.** Single state with `numberOfCityLocationsSelectable = 2`. Reaction is a trait-prefixed gate (Strega), NOT a Sorcerer ability. Multi-stage `$stage` flow: `'offer'` → `'pick1'` → `'pick2'` with cross-player `createReactionTransitionEvent` swapping active player from owner to triggering opponent. Listens to the full `IAbilityThatTargetsCharacters` event set. |
| `modules/php/cards/faf/_03017.php` (Noble Sacrifice) | **Two-different-locations resolve + after-your-character-destroyed Reaction.** Reaction listens on `EventCharacterDestroyed` gated by `locationInCity($destroyed->Location)` and friendly controller, captures `$location` + `$destroyedWasZealot` + `$destroyedName` because the destroyed character has been moved to the locker by the time the player clicks. Single button bundles all sub-effects (wound opposing chars at location + heal own chars at location + conditional draw) — no internal "may", so resolution is atomic. |
| `modules/php/cards/faf/reactions/Reaction_03017.php` | Bundled-effect scheme Reaction. Snapshots destroy-time location and trait at trigger time, queries `getCharactersAtLocation` at resolve time (so movement between trigger and click is reflected). Pass does not consume `setUsed`. |
| `modules/php/cards/faf/reactions/Reaction_03005.php` | Scheme reaction with `$location` capture, button-based Claim/Pass, `setUsed`/`isAvailable` discipline. |
| `modules/php/cards/faf/reactions/Reaction_03006.php` | Multi-stage button-driven Reaction with `$stage` field, cross-player reaction transitions (opponent becomes active for hand-picking), `IAbilityThatTargetsCharacters` multi-event listening with `sourceId=0` BasicChallengeAction fallback. |
| `modules/php/cards/tac/reactions/Reaction_02004.php` | Scheme reaction with adjacent-character target picker; captures the pressured location. |
| `modules/php/States/faf/State_planningPhaseResolveSchemes03005.php` | Reference for the new GameState-class shape, `#[PossibleAction]` methods, and inline `zombie()`. |
| `modules/php/cards/faf/_03029.php` (Hour of Blood) | **Trivial dual Renown resolve + branched Sorcerer City Action.** No planning sub-state; three HD action sub-states for choose-one Porté moves. |
| `modules/php/cards/faf/actions/Action_03029.php` | `SchemeCityAction` + `ISorcererAbility`. `$MoveMode` branch persistence, `isValidTargetForAbility`, Sorcerer performer filter, Porté move pools. |
| `modules/php/States/faf/State_highDramaPhase03029.php` | HD action state 1: branch buttons via `actFromCardWithId`. |
| `modules/php/States/faf/State_highDramaPhase03029_2.php` | HD action state 2: character pick with `actBack`. |
| `modules/php/States/faf/State_highDramaPhase03029_3.php` | HD action state 3: location pick (`actFromCardWithLocations`) with `actBack`. |
| `modules/php/cards/faf/_03030.php` (Sworn Swords) | **Two-different-locations resolve + Diplomat/Duelist split-performer Combat challenge.** Planning + HD both use transition key `"03030"` in their respective maps. |
| `modules/php/cards/faf/actions/Action_03030.php` | Pattern E: engage Diplomat, Duelist challenges. `getPerformersForAction` checks Duelist + opponent at location. `SWORN_SWORDS_CHALLENGE_TYPE`, `EventGenerateChallengeThreat` +1 actor. |
| `modules/php/States/faf/State_planningPhaseResolveSchemes03030.php` | Two-location planning resolve (same shape as `03006`). |
| `modules/php/States/faf/State_highDramaPhase03030.php` | HD state 1: Duelist pick after Diplomat engaged. |
| `modules/php/States/faf/State_highDramaPhase03030_2.php` | HD state 2: opposing target pick → challenge technique flow. |

## When You Finish

1. Walk each clause of the printed Text — confirm each maps to exactly one pattern (Resolve / When-Revealed / Forced / City Action / Reaction). The Renown/Initiative numbers go on the constructor and are not a "pattern."
2. Confirm: `initializeFaction(<faction>)` is called, `CardNumber` matches the filename's NNNNN, `Initiative` is set, `PanacheModifier` is set (often 0 or ±1), all Traits exist in `TraitNames::$TraitsJson`.
3. For every player-choice **Planning resolve** sub-state, ensure all three of: the GameState class in `modules/php/States/<expansion>/`, the constant in `States.php` (`26<NNNNN>`), and the transition entry in `states.inc.php` under `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS.transitions`.
4. For every player-choice **High Drama action** sub-state on the scheme, same three pieces but constant `40<NNNNN>` and transitions under `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`. Action file owns `handleEvent`/`getArgsFromAction`/`actFromAction*` — not the scheme class (unless legacy inline pattern).
5. Don't add a `ZombieTrait.php` case when using the GameState class pattern — the state's own `zombie()` method handles it.
6. JS triple: `OnEnteringState.<expansion>.js` (chooser setup) + `OnUpdateActionButtons.<expansion>.js` (Confirm + Pass/Back) + `OnLeavingState.<expansion>.js` (cleanup). HD action states use `highDramaPhase<NNNNN>` keys, not `planningPhaseResolveSchemes_<NNNNN>`.
7. Scheme reactions live through High Drama via `Theah::buildCity()` populating `$this->cards` from discard piles. Don't add "is the scheme still in play" guards.
8. Capture event-time context onto the reaction as a `private` property; clear it in `performReaction`. Use `$owner->IsUpdated = true` to persist. For **action branch state** (choose-one Either/Or), persist on the action object (`$MoveMode`, etc.) — not `CHOSEN_TARGET`.
9. **Parse keyword(s) literally** before picking interfaces:
   - "Sorcerer …" → `implements ISorcererAbility` + emit start/played events.
   - "Strega …" / "Mercenary …" / "Diplomat …" / etc. → performer-trait gate (`hasTrait("Strega")` check on the chosen performer). NOT a Sorcerer ability.
   - Both can stack.
10. **Cross-player reactions** (opponent must do part of the resolve): use multi-stage `$stage` + `createReactionTransitionEvent($opponentId, ...)`. Do NOT create a dedicated sub-state — reactions can fire from any phase and a sub-state is only reachable from its phase's `*_EVENTS` transitions. **Player's own choose-one on a City Action** during High Drama *does* use HD sub-states — don't route that through reaction `$stage`.
11. **`getPerformersForAction` on trait-gated scheme actions** must filter to performers for whom the *entire* action is legal (eligible secondary character at location, opposing targets present, etc.) — not just `hasTrait(...)`. `isAvailableToPlayer` should delegate to that filtered list.
12. **Custom `CHALLENGE_TYPE` for intervention gates** needs all three: `Theah::interventionCheck`, `ArgumentsTrait` intervene args, `Reaction_02058` (if adjacent external intervene exists in your expansion).
13. **Typed parameters** on every function/method signature. No bare `$foo`. Add `use ...\cards\Card;` (etc.) imports as needed.
14. Pre-commit hook checks on every file:
    - **Reaction subclass:** `$this->setUsed(` AND `$this->isAvailable(` literal strings present.
    - **SchemeCityAction subclass:** `createActionResolvedEvent()` called.
    - **`implements ISorcererAbility`:** both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` called.
    - No class implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards`.
15. Lint touched PHP files (`php -l`) before committing. On Windows, verify line endings are single CRLF (not `\r\r\n`).
