---
name: create-city-character
description: Implement or finish a City Character (modules/php/cards/<expansion>/_NNNNN.php where the class extends CityCharacter). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a City Character, or when they reference a city-deck character whose class extends CityCharacter and has unimplemented Text. Triggers on phrases like "implement this city character", "finish _03cdNN" (when it extends CityCharacter), "wire up the City Forced", "wire up the City Action", or natural-language descriptions of a character that lives in the city deck and is mustered out of it (Penya-style).
---

# Creating a City Character

City characters are city-deck cards that are **playable Characters** (not events, not attachments) — they sit at a city location until a player musters them, then they enter play as a Character with stats, traits, and abilities. They combine the `Character` lineage (stats, wounds, attachments, techniques) with the `CityDeckCardTrait` lineage (`CityCardNumber`, lives in the city deck, can be shuffled back in).

The canonical reference is `modules/php/cards/faf/_03cd01.php` (Penya). At time of writing it is the **only** CityCharacter in the codebase, so most patterns below are drawn from a single example — when in doubt, mirror Penya rather than invent.

> **Sibling skills:**
> - `create-city-event-card` — for stubs that `extends CityEventCard`.
> - `create-city-attachment` — for stubs that `extends CityAttachment`.
> If the stub extends one of those instead of `CityCharacter`, use the matching sibling.

## Base Anatomy

`CityCharacter extends Character implements ICityDeckCard, IWealthCost` and mixes in `CityDeckCardTrait` + `WealthCostTrait`. It adds a single field — `public bool $Negotiable` — over the base `Character`.

That means a CityCharacter is, in code, a fully-featured Character (stats, wounds, attachments, techniques via `IHasTechniques`) that ALSO lives in the city deck and costs Wealth to muster (with optional parley).

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _03cdNN extends CityCharacter implements IHasActions   // + IHasReactions / IHasManeuvers / etc. as the text requires
{
    use ActionTrait;   // only if IHasActions
    // use ReactionTrait;
    // use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('...');
        $this->Image           = '03cdNN.jpg';
        $this->ExpansionName   = 'faf';     // or _7s5s / tac
        $this->ExpansionNumber = 3;
        $this->CardNumber      = 0;         // city-deck cards: keep CardNumber = 0
        $this->CityCardNumber  = NN;        // the visible city-deck number on the card

        $this->Title   = clienttranslate('...');     // flavor subtitle ("Hard Knocks Hustler")
        $this->Resolve = 1;

        $this->Combat    = 0;
        $this->DashedCombat = true;                 // dashed = the stat is "—", cannot pressure with it
        $this->Finesse   = 2;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->WealthCost   = 1;            // cost to muster from the city deck
        $this->Negotiable   = true;         // text-driven: "Negotiable" means parley is allowed when paying

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Hero'),
            // ...
        ];

        $this->Text = clienttranslate("<p>...</p>");

        $this->resetCard();

        $this->Actions    = [ new Action_03cdNN() ];      // only if IHasActions
        // $this->Reactions  = [ new Reaction_03cdNN() ];
        // $this->Maneuvers  = [ new Maneuver_03cdNN() ];
    }
}
```

Field notes:
- **`Resolve`** is the wound capacity. Required for any non-attachment Character.
- **`DashedCombat` / `DashedFinesse` / `DashedInfluence`** match the printed dashes on a card's stat block. Dashed stats are visually `—` and the character cannot contribute to that pressure / cannot use that stat in challenges. Set the underlying numeric stat to `0` when dashed.
- **`Negotiable`** is the only field added by `CityCharacter` over `Character`. Set `true` if the card has the printed "Negotiable" keyword (allowing parley payment). Mirrored to the client via `getPropertyArray` automatically.
- **`WealthCost`** is the muster cost.
- **`CityCardNumber`** is the printed city-deck index (1 for Penya). `CardNumber` stays `0` — that is the convention for city-deck cards.

Key runtime state inherited from `Character` / `Card`:
- `$this->Id` — this character's card id.
- `$this->ControllerId` — the player currently controlling the character (0 while in the city deck before muster; the mustering player once in play).
- `$this->Location` — current location. While in the city deck this is the city location they sit at; once mustered, it's wherever they are in the city.
- `$this->Engaged` — engagement state, same semantics as any other Character.
- `$this->Wounds`, `$this->ModifiedResolve` — wound tracking, identical to any Character.

## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code. A single City Character commonly combines several — Penya has all four of: Negotiable, an `eventCheck` ban, a City Forced, and a City Action.

| Card phrase | Pattern |
|---|---|
| **"Negotiable"** keyword | `$this->Negotiable = true;` in the constructor. No further code. |
| **Stat printed as a dash (`—`)** | Set the matching `Dashed<Stat> = true;` flag + numeric stat to `0`. |
| **"<Name> cannot intervene."** (or any other "this character cannot do X") | Override `canIntervene()` (or `canChallenge()`) to return `false`. **Also** override `eventCheck(Event)` to throw a `UserException` when the engine attempts the banned action against this character — that surfaces the rule in the UI before the action commits. See "Pattern A — Hard ban via canIntervene + eventCheck." |
| **`<b>City Forced:</b>`** — auto-triggers while in the city; no choice | Override `handleEvent`. Gate on `$event->theah->cardInCity($this)` (or the equivalent for whatever scope the trigger covers). No Action/Reaction/State files. |
| **`<b>Forced:</b>`** (not City) — auto-triggers while in play | Same as City Forced but without the `cardInCity` gate. Gate on whatever the text scopes ("while engaged," "while at this location," etc.). |
| **`<b>City Action:</b>`** — player spends an action while the character sits in the city | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_03cdNN.php` extending `CharacterAction` (NOT `EventCityAction` — see "Action base class" below). State class(es) + JS wiring per the City Action flow. |
| **`<b>Action:</b>`** (not City) — player spends an action with the character once in play | Same as City Action — `CharacterAction` is the right base class either way. The eligibility check (in city vs in play) is what differs and goes in `isAvailableToPlayer`. |
| **`<b>City Reaction:</b>` or `<b>Reaction:</b>`** | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_03cdNN.php` extending `CardReaction`. |
| **`<b>Technique:</b>` / `<b>Maneuver:</b>`** | The Character lineage already brings `TechniqueTrait`. Add `IHasManeuvers` + `ManeuverTrait` for maneuvers. Implement under `cards/<expansion>/techniques/` or `cards/<expansion>/maneuvers/`. |

## Pattern A — Hard ban via `canIntervene` / `canChallenge` + `eventCheck`

For text like "Penya cannot intervene." or "X cannot be challenged" — there are two layers, both of which Penya implements:

1. **Override the predicate** — `canIntervene()`, `canChallenge()`, `canPressure()`, etc. Return `false`. The engine reads this when offering options to the player. Matches the base `Character::canIntervene` pattern (`Character.php:78-81`). `Theah::interventionCheck` calls it.
2. **Override `eventCheck(Event)`** — throw a `\Bga\GameFramework\UserException` when the engine *processes* the banned event. This is a belt-and-suspenders backstop for code paths that bypass the predicate (forced retargeting, copied effects, future card interactions). Call `parent::eventCheck($event)` first.

Penya's intervention ban:

```php
public function canIntervene(): bool
{
    return false;
}

public function eventCheck(Event $event)
{
    parent::eventCheck($event);

    if ($event instanceof EventCharacterIntervened && $event->newTargetId == $this->Id)
    {
        throw new UserException($event->theah->game->translate("Penya cannot intervene."));
    }
}
```

The field you check on the event (`newTargetId`, `characterId`, `actorId`, etc.) depends on the event — read the event class. Most "this character is being …" events use `characterId`; intervention specifically tracks `newTargetId` because intervention re-targets an in-flight effect.

**Use `UserException` from `Bga\GameFramework\UserException`** — `BgaUserException` is deprecated.

**Why a separate `eventCheck` if `canIntervene` already returns `false`?** Predicates filter the UI; `eventCheck` filters the engine. Many edge cases (zombie passes, copied actions, AI driving things) skip the UI predicate. The thrown exception is the last line of defense.

## Pattern B — City Forced via `handleEvent`

City Forced abilities trigger automatically while the character sits in the city deck (i.e., `cardInCity($this)`). No player choice. Override `handleEvent` and gate the body on:

1. **Event type** — `instanceof EventCharacterBeingWounded`, `EventDuelStarted`, etc.
2. **This card is the relevant target** — `$event->characterId == $this->Id`, or for duels both `challengerId` and `defenderId`.
3. **In city** — `$event->theah->cardInCity($this)`.

Penya's combined "participates in a duel OR would be wounded" trigger:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    // City Forced: When Penya participates in a duel
    if ($event instanceof EventDuelStarted
        && ($event->challengerId == $this->Id || $event->defenderId == $this->Id)
        && $event->theah->cardInCity($this))
    {
        $this->triggerForcedAbility($event);
    }

    // City Forced: When Penya would be wounded
    if ($event instanceof EventCharacterBeingWounded
        && $event->characterId == $this->Id
        && $event->theah->cardInCity($this))
    {
        $event->canceled = true;                     // "would be wounded" — cancel the wound itself
        $this->triggerForcedAbility($event);
    }

    // Self-listening follow-up — see "Event ordering" below
    if ($event instanceof EventCardRemovedFromPlay
        && $event->cardId == $this->Id
        && $event->toLocation == Game::LOCATION_CITY_DECK)
    {
        $game = $event->theah->game;
        $game->getGameDeckObject()->shuffle(Game::LOCATION_CITY_DECK);
        $game->notify->all("message", clienttranslate('The City Deck has been shuffled.'), []);
    }
}
```

### "Would be wounded" vs "When wounded"

- **"Would be wounded"** — listen on `EventCharacterBeingWounded` and set `$event->canceled = true`. The wound never happens. (Penya, Maryam `_01186`.)
- **"When wounded"** — listen on `EventCharacterWounded` (post-event, after the wound has been applied). No cancellation — the wound is already counted.

Match the verb on the card: "would" = preventive; "is/has been" = reactive.

### "Participates in a duel" vs "Is challenged"

- **"Participates in a duel"** — `EventDuelStarted`. This fires AFTER the duel record is created and the "duel started" notification has gone out (`StatesTrait.php` around 1010-1029). A challenge that was rejected never reaches this event.
- **"Is challenged"** — `EventChallengeIssued`. Fires before acceptance/rejection.

Choose by the printed verb. Penya uses `EventDuelStarted` — a rejected challenge should NOT trigger her Forced.

**Caveat — duel-mid-trigger edge case.** If the Forced removes the character from play *while a duel involving them is being set up*, the duel system has already created its DB record and now has a missing combatant. The current code does not solve this end-to-end (open question in the Penya journal). Flag it in the new journal entry; do not invent a workaround.

### Event ordering inside `handleEvent` — the "queue-then-react-to-your-own-event" trick

For events with `runEventHubAfterCards = false` (the default, see `Theah.php:226-243`):
1. EventHub processes the queued event first (does the move/wound/destroy).
2. THEN every card's `handleEvent` fires for that event.

So you can queue `createCardRemovedFromPlayEvent` and have *another branch of the same card's `handleEvent`* listen for the resulting `EventCardRemovedFromPlay` (targeting `$this->Id`) and run cleanup — like Penya shuffling the city deck after she lands in it.

This is the cleanest way to express "do A, then once A has actually happened, do B" without inventing a new state.

### Why use `createCardRemovedFromPlayEvent` (not `createCityCardAddedToCityDeckEvent`) when sending the character back to the deck?

The "removed from play" event drives the proper frontend animation (`cardRemovedFromPlay` notify) — the character visibly leaves the board. `createCardAddedToCityDeckEvent` is designed for cards that are already revealed / in limbo, not for cards visually in play. Penya is on the board, so use `createCardRemovedFromPlayEvent`. (`Theah.php:226-243` documents the difference.)

### Triggering helper

Penya's Forced helper, lifted as a template for any "do this Forced thing once" pattern:

```php
private function triggerForcedAbility(Event $event): void
{
    $game     = $event->theah->game;
    $location = $this->Location;

    $game->notify->all("message", clienttranslate('${card_inject_code}: Forced ability triggered.'), [
        "card_inject_code" => $this->getInjectCode(),
    ]);

    // Effect 1 — Put the top card of the City Deck at his location
    $topCards = $game->getCardsOnTopOfCityDeck(1);
    if (count($topCards) > 0)
    {
        $topCard = array_values($topCards)[0];
        // NB: $topCard is raw card_info, NOT a card object. Cast id to int.
        $cityCardEvent = EventFactory::createCityCardAddedToLocationEvent((int)$topCard['id'], $location);
        $event->theah->queueEvent($cityCardEvent);
    }

    // Effect 2 — Remove this character from play to the city deck
    // (the city-deck shuffle is handled by the EventCardRemovedFromPlay branch above)
    $removeEvent = EventFactory::createCardRemovedFromPlayEvent($this->ControllerId, $this->Id, Game::LOCATION_CITY_DECK);
    $event->theah->queueEvent($removeEvent);
}
```

**Gotcha:** `getCardsOnTopOfCityDeck($n)` returns raw card_info rows, not card objects. Cast `$topCard['id']` to `int` before passing to `createCityCardAddedToLocationEvent`. The EventHub handler for that event loads the card object on its end.

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

## Pattern D — Reactions, Techniques, Maneuvers

These follow the standard Character patterns — nothing CityCharacter-specific. Quick pointers:

- **Reaction** — Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_03cdNN.php` extending `CardReaction`. Pre-commit hook requires `$this->setUsed(...)` AND `$this->isAvailable()` to appear in the class.
- **Technique** — `IHasTechniques` is already on `Character` via `TechniqueTrait`. Just add the technique class under `cards/<expansion>/techniques/` and push it into `$this->Techniques` in the constructor.
- **Maneuver** — Implement `IHasManeuvers`, `use ManeuverTrait`, push into `$this->Maneuvers`.

For a Reaction whose trigger is "while in the city," gate the body on `cardInCity($this)` — same as the Forced pattern.

## JS Wiring (required, easy to forget)

For every new state, wire BOTH:

### `modules/js/OnEnteringState.faf.js`

UI setup — highlight selectables, mark already-chosen characters, set selection counts.

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

For a location-selection second step:

```js
'highDramaPhase03cdNN_2': () => {
    if (this.isCurrentPlayerActive()) {
        this.numberOfCityLocationsSelectable = 1;
        args.args.args.locationIds.forEach((locationId) => {
            const imageElement = this.getCityLocationElement(locationId);
            this.makeCityLocationSelectable(imageElement);
        });

        // Visually mark already-chosen characters so the player remembers
        let card = this.cardProperties[args.args.args.performerId];
        dojo.addClass($(`${card.divId}_image`), '_7sfs-chosen');
        this.clientStateArgs.performerId = args.args.args.performerId;

        card = this.cardProperties[args.args.args.targetId];
        dojo.addClass($(`${card.divId}_image`), '_7sfs-chosen');
        this.clientStateArgs.targetId = args.args.args.targetId;
    }
},
```

### `modules/js/OnUpdateActionButtons.faf.js`

```js
'highDramaPhase03cdNN': () => {
    this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
    dojo.addClass('actChooseCardSelected', 'disabled');
},

'highDramaPhase03cdNN_2': () => {
    this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
    this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
    dojo.addClass('actCityLocationsSelected', 'disabled');
},
```

Reusable client-side handlers:
- **Character / in-play card selection**: `onChooseInPlayCardConfirmed()` + `highlightCardsAsSelectable(ids)`.
- **Location selection**: `onCityLocationsSelected()` + `makeCityLocationSelectable(element)`.
- **Marking a "chosen" character (carry-over visual)**: `dojo.addClass($(`${card.divId}_image`), '_7sfs-chosen')`.

If your state uses an existing client action like `onMusterCardSelected`, extend the action map in `modules/js/PlayerActions.js` to include the new state name. Forgetting this is a common cause of "the button does nothing in my new state."

The expansion JS files (`*.faf.js`) are already chained from the master JS files — no extra include wiring needed for `faf`. For a new expansion, ensure the chain is in place.

## Pre-Commit Hook (relevant subset)

`.githooks/pre-commit` enforces, for the files you touch when implementing a City Character:

| Pattern | Required |
|---|---|
| `extends CharacterAction/AttachmentAction/CardAction/RiskAction/...` | `createActionResolvedEvent()` somewhere in the class. |
| **Forbidden in `CharacterAction` subclasses** | `setUsed`/`resetPlayerPassCount`/`announceAction` — these run centrally. |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed()` AND `$this->isAvailable()`. |
| `implements ISorcererAbility` | both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()`. |
| Implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` on one class | **Forbidden.** Split into two classes. |

The Card class itself (the `_03cdNN extends CityCharacter` file) has no hook-mandated calls — the requirements apply to the Action/Reaction subclasses that live next to it.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Card class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:     `...\cards\<expansion>\actions`
  - Reaction:   `...\cards\<expansion>\reactions`
  - State:      `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`

## Cross-Cutting Helpers

- `$theah->cardInCity($card): bool` — true when the card sits in the city deck. Gate every City Forced / City Action body on this.
- `$theah->getCharactersAtLocationByPlayerId(string $location, int $playerId, bool $includeUncontrolled = false): array` — friendly characters at a city location.
- `$theah->getAdjacentCityLocations(string $location, bool $includeHome = true): array` — adjacency for move actions.
- `$game->getCardsOnTopOfCityDeck($n)` — returns raw card_info rows (NOT card objects). Cast `id` to int when passing to event factories.
- `$game->getGameDeckObject()->shuffle(Game::LOCATION_CITY_DECK)` — shuffle the city deck after sending a card into it.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${card_inject_code}` placeholder).

## Reference Implementations

| File | What it demonstrates |
|---|---|
| `modules/php/cards/faf/_03cd01.php` | **Canonical CityCharacter.** Negotiable + dashed stats + `canIntervene` ban + `eventCheck` backstop + paired City Forced (duel and would-be-wounded) + self-listening `EventCardRemovedFromPlay` cleanup + IHasActions wiring. |
| `modules/php/cards/faf/actions/Action_03cd01.php` | Two-step `CharacterAction` (companion → adjacent location), `CHOSEN_TARGET` global between steps, `engage as cost / move with engage=false`. |
| `modules/php/States/faf/State_highDramaPhase03cd01.php` | First-step state (character picker). |
| `modules/php/States/faf/State_highDramaPhase03cd01_2.php` | Second-step state with `<` back button + location picker. |
| `modules/js/OnEnteringState.faf.js` | UI setup for both Penya steps. |
| `modules/js/OnUpdateActionButtons.faf.js` | Action buttons for both Penya steps. |
| `modules/php/cards/_7s5s/_01186.php` (Maryam) | Comparison for `EventCharacterBeingWounded` + `canceled = true` pattern, with a source filter Penya intentionally omits. |
| `modules/php/cards/CityCharacter.php` | Base class. Read for the `Negotiable` field and inheritance chain. |
| `modules/php/cards/Character.php` | Parent. `canIntervene` / `canChallenge` defaults and wound/heal handling live here. |
| `modules/php/theah/Theah.php::interventionCheck` (~line 1651) | Where `canIntervene` is consumed by the engine. |

## When You Finish

1. Walk each clause of the printed Text — confirm each maps to exactly one pattern (Negotiable / Dashed stat / Hard ban / Forced / Action / Reaction / Technique / Maneuver). Stat numbers go on the constructor and are not a "pattern."
2. Every new state class needs all three: the class file in `modules/php/States/<expansion>/`, the constant in `States.php`, and the transition entry in `states.inc.php`'s `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`.
3. Every new state needs JS wiring in `OnEnteringState.<expansion>.js` AND `OnUpdateActionButtons.<expansion>.js`. Add `OnLeavingState.<expansion>.js` reset if you set selection modes or styling. Add to `PlayerActions.js` if you reuse a client action.
4. If you minted a new global, clear it in the matching cleanup state (or defensively at turn boundaries).
5. Mentally run pre-commit hook checks on every file you touched. Especially: `createActionResolvedEvent` in the action, no `setUsed`/`resetPlayerPassCount`/`announceAction` in the `CharacterAction` subclass.
6. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md`. Capture the **WHY** of any non-obvious decision — event-type choice (`BeingWounded` vs `Wounded`, `DuelStarted` vs `ChallengeIssued`), why `cardInCity` is the right scope gate, why `engage=false` on the move events, why `eventCheck` exists alongside `canIntervene`. Read the Penya journal (`2026-04-26-01-penya-03cd01-implementation.md`) first — it encodes the hard-won knowledge about event ordering inside `handleEvent` and the duel-mid-trigger edge case.
