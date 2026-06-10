---
name: create-city-character
description: Implement or finish a City Character (modules/php/cards/<expansion>/_NNNNN.php where the class extends CityCharacter). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a City Character, or when they reference a city-deck character whose class extends CityCharacter and has unimplemented Text. Triggers on phrases like "implement this city character", "finish _03cdNN" (when it extends CityCharacter), "wire up the City Forced", "wire up the City Action", or natural-language descriptions of a character that lives in the city deck and is mustered out of it (Penya-style).
---

# Creating a City Character

City characters are city-deck cards that are **playable Characters** (not events, not attachments) — they sit at a city location until a player musters them, then they enter play as a Character with stats, traits, and abilities. They combine the `Character` lineage (stats, wounds, attachments, techniques) with the `CityDeckCardTrait` lineage (`CityCardNumber`, lives in the city deck, can be shuffled back in).

Canonical references:
- `modules/php/cards/faf/_03cd01.php` (Penya) — Negotiable, dashed stats, `canIntervene` ban, paired City Forced, City Action with multi-step state classes.
- `modules/php/cards/faf/_03cd10.php` (Julius Caligari) — Negotiable, multi-step button-based Reaction (letter → trait → opposing target → conditional wound) demonstrating the `EventCharacterRecruited` / `EventCardMoved` trigger pair and the `TraitNames` consumer pattern.
- `modules/php/cards/faf/_03cd18.php` (Kalla and Adelheide) — Negotiable, **branching "Choose one"** post-recruit Reaction with two effect paths (search deck for attachment vs. move + destroy opposing attachment). Demonstrates per-option validity gates at the choice stage, dedupe-by-Name when listing deck cards, and the "no `< Back` once events commit" rule.

When in doubt, mirror one of those three rather than invent.

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
| **`<b>City Reaction:</b>` or `<b>Reaction:</b>`** | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_03cdNN.php` extending `CardReaction`. See "Pattern D — Reaction on a CityCharacter." For "City Reaction" gate triggers on `$event->theah->cardInCity($owner)`. Button-based reactions need **no** new state class, **no** `states.inc.php` edits, **no** JS wiring. |
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

## Pattern D — Reaction on a CityCharacter

Reactions live in `modules/php/cards/<expansion>/reactions/Reaction_03cdNN.php` extending `CardReaction`. The canonical example for a CityCharacter Reaction is **Julius Caligari `_03cd10`** (multi-step trait/target picker via reaction buttons). For a Reaction on a non-City Character the broader canonical example is `Reaction_01014` (Vittoria) — same pattern, just without the city gates.

### Card class wiring

```php
class _03cdNN extends CityCharacter implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        // ... stats, traits, Negotiable, Text, resetCard() ...

        $this->Reactions = [ new Reaction_03cdNN() ];
    }
}
```

If `reactions/` doesn't yet exist under your expansion directory, create it. The namespace is `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions`.

### Reaction class skeleton

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03cdNN extends CardReaction
{
    // Multi-step state lives on private fields. They survive across performReaction
    // invocations because the framework serializes Reactions[] along with the card.
    private string $stage = '';            // '' (idle), 'pickX', 'pickY', ...
    private string $chosenX = '';

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Short button label");
    }

    public function getReactionDescription(Theah $theah): string { /* ... branch on $stage ... */ }
    public function getReactionButtonProperties(Theah $theah): array { /* ... branch on $stage ... */ }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        // ... see "Triggers and gates" below ...
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);
        // ... advance $stage, re-queue, or resolve and setUsed ...
        $game->gamestate->nextState("done");
    }
}
```

### Triggers and gates

The trigger lives in `handleEvent`. Two non-negotiable guards:

1. **`$this->isAvailable()`** — the inherited `CardReaction::handleEvent` resets `Used = false` on `EventDuskEndOfDay`. Gate every branch on `isAvailable()` so the reaction doesn't double-fire within a day.
2. **Identity check** — the event must concern `$owner` itself (`$event->cardId == $owner->Id`, `$event->characterId == $owner->Id`, etc.). Without this, the reaction fires for events that happen to anybody.

Often-needed additional gates for CityCharacter reactions:

- **City scope** (when card says "City Reaction" or "moves to a City location"): `$event->theah->cardInCity($owner)` for events where `$owner->Location` is current, or `$event->theah->locationInCity($event->toLocation)` for `EventCardMoved` (see gotcha below).
- **Valid-target precondition** — if the effect REQUIRES a target (e.g., "target an opposing character"), check that at least one valid target exists BEFORE queuing the reaction transition. Otherwise the player gets a useless prompt they can only Decline. For "opposing", use `Theah::getOpposingCharactersAtLocation($location, $playerId)` (count > 0).
- **"Opposing" semantics** — opposing means BOTH different controller AND same location. Use `getOpposingCharactersAtLocation`, not a hand-rolled `ControllerId !=` filter. (Skill-level memory; if a card text says "opposing" anywhere, this applies.)

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    // "After <Name> is recruited": EventCharacterRecruited fires AFTER the hub sets
    // ControllerId (runEventHubAfterCards defaults to false), so $owner->ControllerId
    // is already the new controller. Recruitment does NOT change Location.
    if ($event instanceof EventCharacterRecruited && $this->isAvailable())
    {
        $owner = $this->getOwningCharacter($event->theah);
        if ($event->characterId == $owner->Id
            && $event->theah->cardInCity($owner)
            && $this->hasValidTarget($event->theah, $owner, $owner->Location))
        {
            $this->beginReaction($event);
        }
    }

    // "After <Name> moves to a City location": EventCardMoved has
    // runEventHubAfterCards = true, so $owner->Location is the OLD location here.
    // Use $event->toLocation for all post-move position checks.
    if ($event instanceof EventCardMoved && $this->isAvailable())
    {
        $owner = $this->getOwningCharacter($event->theah);
        if ($event->cardId == $owner->Id
            && $event->theah->locationInCity($event->toLocation)
            && $this->hasValidTarget($event->theah, $owner, $event->toLocation))
        {
            $this->beginReaction($event);
        }
    }
}

private function beginReaction(Event $event): void
{
    $owner = $this->getOwningCharacter($event->theah);
    $this->stage = 'firstStep';
    $owner->IsUpdated = true;
    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
    $event->theah->queueEvent($transition);
}
```

### The `EventCardMoved` `runEventHubAfterCards` gotcha

`EventCardMoved.runEventHubAfterCards = true`. The hub handler (`EventHub.php:633`) writes `$card->Location = $event->toLocation`, but that handler runs AFTER cards' `handleEvent`. So inside your `handleEvent`:

- ✗ `$owner->Location` — still the OLD location.
- ✓ `$event->toLocation` — the destination.

For any helper called from `handleEvent` for a move event, pass the location explicitly instead of reading it off the card.

By the time `getReactionButtonProperties` / `performReaction` runs (after the player enters the `playerReaction` state), the move has fully committed and `$owner->Location` is correct. The pitfall is only inside `handleEvent`.

### Multi-step button flow

A reaction with N sequential player choices is N "stages". Each stage:

1. `getReactionButtonProperties` returns the buttons for that stage (plus `< Back` if appropriate, plus `Decline`).
2. Player clicks → `performReaction` runs with the chosen `$reactionId`.
3. `performReaction` advances `$this->stage`, then re-queues a `ReactionTransitionEvent` and calls `$game->gamestate->nextState("done")` to re-enter `playerReaction` with the new buttons.

```php
private function requeue(Game $game, int $playerId, int $sourceId): void
{
    $transition = EventFactory::createReactionTransitionEvent($playerId, $sourceId, $this->Id);
    $game->theah->queueEvent($transition);
}

public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
{
    parent::performReaction($game, $state, $internalId, $reactionId);
    $owner = $this->getOwningCharacter($game->theah);

    if ($reactionId === 'decline')
    {
        $this->resetStage();
        $this->setUsed($game->theah, true);
        $owner->IsUpdated = true;
        $game->gamestate->nextState("done");
        return;
    }

    if ($reactionId === 'back')
    {
        if ($this->stage === 'second') { $this->stage = 'first'; /* clear later state */ }
        $owner->IsUpdated = true;
        $this->requeue($game, $owner->ControllerId, $owner->Id);
        $game->gamestate->nextState("done");
        return;
    }

    if (str_starts_with($reactionId, 'first-')) { /* set $chosen, advance stage, requeue */ }
    if (str_starts_with($reactionId, 'final-')) { /* resolve effect, setUsed(true) */ }

    $game->gamestate->nextState("done");
}
```

**Final stage MUST call `$this->setUsed($game->theah, true)`** — this is what writes the "used today" flag and emits the reaction-used notification. The pre-commit hook also enforces that the literal `$this->setUsed(` appears in the file.

**Final stage MUST queue any resulting events** (wounds, moves, etc.) via `EventFactory` + `$game->theah->queueEvent(...)`. The reaction returning doesn't auto-trigger anything.

### `< Back` is only valid before events commit

Offering `< Back` is fine when the previous stage was a pure-choice stage (the player picked something but no events were queued — e.g., "pick a letter," "pick a trait," "pick option A vs B"). It is **not** safe once a stage has queued effect events (a move, an unequip, a wound). Going back would imply un-applying those events, which the engine doesn't support.

Kalla's `moveB → destroyB` transition queues an `EventCardMoving` before requeuing the reaction transition. By the time `destroyB` renders buttons, the move has committed — so `destroyB` omits the `< Back` button entirely. The player's only escapes from `destroyB` are picking a target or `Decline`.

Rule of thumb: if `performReaction` for stage N queues any event other than the reaction transition itself, stage N+1 should not offer `< Back`.

### Branching "Choose one" intro stage

For text like "Choose one: <i>Either</i> X <i>or</i> Y," model the first stage as a one-of-two picker that branches into separate downstream stages:

```php
// Stages: '' → choose → searchA | moveB → destroyB
case 'choose':
    if ($this->hasAttachmentInDeck($game, $owner->ControllerId))
    {
        $array[] = $this->createButtonProperty($game, $game->translate('Search deck for an attachment'), 'optionA');
    }
    if ($this->hasMoveAndDestroyTarget($theah, $owner))
    {
        $array[] = $this->createButtonProperty($game, $game->translate('Move and destroy an opposing attachment'), 'optionB');
    }
    break;
```

Two things to get right:

1. **Per-option validity at the choose stage.** Gate each option's button on its own "is there a legal target for this branch" check. If one option has no legal target, omit its button — don't dump the player into a downstream stage with nothing to pick. (This is the same valid-target gate idea as the trigger-time gate, applied per-branch.)
2. **Trigger only if at least one option is viable.** In `handleEvent`, the OR of all the per-option gates is the trigger-time "should this reaction even fire" check. If none of the branches can do anything, don't queue the reaction transition.

### Multi-step state belongs on the Reaction, not on the card

Private fields on the Reaction class (`$stage`, `$chosenX`) persist across actions because the framework serializes the parent card (including its `Reactions[]` array) to the DB after each action. Don't try to store cross-step state on the card itself — the Reaction is the natural owner.

### What does NOT need wiring for a button-based reaction

- **No new state classes.** The framework's `playerReaction` state is reused with different button output per stage.
- **No `states.inc.php` edits.** The `ReactionTransitionEvent` routes to `playerReaction` automatically.
- **No `OnEnteringState.<expansion>.js` / `OnUpdateActionButtons.<expansion>.js` entries.** Buttons render from `getReactionButtonProperties` server-side.
- **No `PlayerActions.js` map entries.**

If a reaction needs richer UI than buttons (e.g., highlighting cards on the board for selection), at that point promote it to a real state class with full wiring — but YAGNI until then.

### Common effect patterns inside `performReaction`

**Wound a target character:**

```php
$wound = EventFactory::createCharacterBeingWoundedEvent(
    $target->Id,
    $owner->Id,           // sourceId — the reaction's owning character
    1,                    // wounds count
    $owner->getInjectCode(),
    $this->Id             // abilityId — the reaction's own id
);
$game->theah->queueEvent($wound);
```

`createCharacterBeingWoundedEvent` is the standard wounding entrypoint; the engine turns it into actual wounds and gives other cards a chance to react/prevent.

**Reveal random card(s) from a player's hand** (pattern from `_01098` and `Reaction_03cd10`):

```php
$deck = $game->getGameDeckObject();
$hand = array_values($deck->getCardsInLocation(Game::LOCATION_HAND, $playerId));
$count = min($n, count($hand));
if ($count > 0)
{
    $keys = (array)array_rand($hand, $count);   // (array) cast normalizes the count==1 case
    foreach ($keys as $key)
    {
        $card = $game->getCardObjectFromDb($hand[$key]['id']);
        $game->theah->addCardToWorld($card);    // required for the inject code to render
        $game->notify->all("message",
            clienttranslate('${card_inject_code} reveals ${picked_card} from <strong>${player_name}</strong>\'s hand.'),
            [
                "card_inject_code" => $owner->getInjectCode(),
                "picked_card"      => $card->getInjectCode(),
                "card"             => $card->getPropertyArray($game),
                "player_name"      => $game->getPlayerNameById($playerId),
            ]);
    }
}
```

**Decide whether a card has a Trait:** `$card->hasTrait($traitName)`. The check is `in_array($trait, $this->ModifiedTraits)`. `clienttranslate()` is a no-op at runtime, so English Trait strings compare directly against the `clienttranslate()`-wrapped values stored on each card.

**"Name a Trait" abilities:** the canonical Trait list is `TraitNames::$TraitsJson` (`modules/php/Traits.php`). Parse with `json_decode(TraitNames::$TraitsJson, true)['traits']`. If a card you're implementing has a Trait not in that JSON, add it in alphabetical order — `TraitNames` is the source of truth for trait pickers.

**Search your deck for a card matching a filter** (pattern from `Action_02045` and `Reaction_03cd18`):

```php
$deckName = $game->getPlayerFactionDeckName($playerId);
$deck     = $game->getGameDeckObject()->getCardsInLocation($deckName);
$matches  = [];
foreach ($deck as $deckCard)
{
    $card = $game->getCardObjectFromDb($deckCard['id']);
    if ($card instanceof Attachment /* or $card->hasTrait('X') */)
    {
        $matches[] = $card;
    }
}
// ... let the player pick $card from $matches ...

$removeEvent = EventFactory::createCardRemovedFromPlayerFactionDeckEvent($playerId, $card->Id);
$game->theah->eventCheck($removeEvent);
$addEvent = EventFactory::createCardAddedToHandEvent($playerId, $card->Id);
$game->theah->eventCheck($addEvent);
$game->theah->queueEvent($removeEvent);
$game->theah->queueEvent($addEvent);

// If the card text says "(Shuffle your deck...)" — required:
$game->getGameDeckObject()->shuffle($deckName);
$game->notify->all("message", clienttranslate('${player_name} shuffles their deck.'), [
    "player_name" => $game->getPlayerNameById($playerId),
]);
```

**Dedupe by Name when listing cards from a hidden zone for a button picker.** A faction deck commonly holds multiple copies of the same card. Showing every copy as a separate button is noise — the copies are functionally indistinguishable. Dedupe by Name and emit one button per unique name; the underlying card id can be any of the copies, since whichever one resolves goes to hand identically:

```php
$seen = [];
foreach ($this->getAttachmentsInDeck($game, $owner->ControllerId) as $card)
{
    if (isset($seen[$card->Name]))
    {
        continue;
    }
    $seen[$card->Name] = true;
    $array[] = $this->createButtonProperty($game, $card->Name, "searchA-{$card->Id}");
}
```

(This applies to deck/hand pickers; it does NOT apply to in-play card pickers where each copy has distinct state — wounds, attachments, location, controller.)

**Destroy a non-city attachment in play** (pattern from `Action_01174` and `Reaction_03cd18`):

```php
$unequipEvent = EventFactory::createAttachmentUnequippedEvent(
    $attachment->ControllerId, $attachment->AttachedToId, $attachment->Id
);
$game->theah->eventCheck($unequipEvent);
$game->theah->queueEvent($unequipEvent);

$discardEvent = EventFactory::createCardDiscardedFromPlayEvent(
    $attachment->OwnerId, $attachment->Id, $attachment->Location, $owner->Id, $asEffect = true
);
$game->theah->queueEvent($discardEvent);
```

Two events, in this order: unequip from the character, then discard from play. The `$asEffect = true` flag marks this as effect-driven destruction (as opposed to a pay/discard cost). For city attachments, route to `createCardAddedToCityDiscardPileEvent` instead — see `Character::unEquipAllAttachments`.

### Pre-commit hook

For any class extending `CardReaction`:
- `$this->setUsed(` must appear at least once in the file (typically on every terminal path — decline, final-stage resolution).
- `$this->isAvailable(` must appear at least once (typically as a guard at the top of each `handleEvent` branch).

The hook is a literal string match. Calls don't need to be reachable — but they should be, or the reaction is broken.

## Pattern E — Techniques and Maneuvers

These follow the standard Character patterns — nothing CityCharacter-specific:

- **Technique** — `IHasTechniques` is already on `Character` via `TechniqueTrait`. Add the technique class under `cards/<expansion>/techniques/` and push it into `$this->Techniques` in the constructor.
- **Maneuver** — Implement `IHasManeuvers`, `use ManeuverTrait`, push into `$this->Maneuvers`.

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

- `$theah->cardInCity($card): bool` — true when the card sits in the city deck. Gate every City Forced / City Action / City Reaction body on this. Note: inside an `EventCardMoved` handler, the card's `Location` field has NOT been updated yet (the hub handler runs after cards) — use `$theah->locationInCity($event->toLocation)` instead.
- `$theah->locationInCity(string $location): bool` — true for any of the 5 city locations. The right gate when you only have a location string in hand (e.g., `$event->toLocation`).
- `$theah->getCharactersAtLocationByPlayerId(string $location, int $playerId, bool $includeUncontrolled = false): array` — friendly characters at a city location.
- `$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array` — opposing = different controller AND same location. Use this whenever card text says "opposing," never roll your own `ControllerId !=` filter.
- `$theah->getAdjacentCityLocations(string $location, bool $includeHome = true): array` — adjacency for move actions.
- `array_keys($theah->getCityLocations())` — enumerate the **active** city-location names. Respects the player-count rules that exclude Ole's Inn and Governor's Garden in smaller games. Use this for "move to any location" pickers instead of hardcoding the five constants.
- `$game->getCardsOnTopOfCityDeck($n)` — returns raw card_info rows (NOT card objects). Cast `id` to int when passing to event factories.
- `$game->getGameDeckObject()->getCardsInLocation(string $location, int $playerId)` — raw card_info rows in a location; cast `id` to int and pass to `$game->getCardObjectFromDb($id)` to get the card object. Required for "reveal random card from hand" patterns.
- `$game->getGameDeckObject()->shuffle(Game::LOCATION_CITY_DECK)` — shuffle the city deck after sending a card into it.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${card_inject_code}` placeholder).
- `TraitNames::$TraitsJson` (in `modules/php/Traits.php`) — canonical full Trait list for "Name a Trait" abilities. JSON-encoded `{"traits": [...]}`. If a card has a Trait that's missing, add it in alphabetical order.

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
| `modules/php/cards/faf/_03cd10.php` (Julius Caligari) | **Canonical CityCharacter Reaction.** Negotiable + `IHasReactions`/`ReactionTrait` wiring. |
| `modules/php/cards/faf/reactions/Reaction_03cd10.php` | Multi-step button-based reaction (letter → trait → opposing-character target). `EventCharacterRecruited` + `EventCardMoved` triggers, valid-target precondition gate, the `EventCardMoved.runEventHubAfterCards = true` gotcha, `TraitNames::$TraitsJson` consumption, `getOpposingCharactersAtLocation`, random reveal from hand, conditional wound. |
| `modules/php/cards/_7s5s/reactions/Reaction_01014.php` (Vittoria) | Multi-step reaction on a non-City Character — same button-cycling pattern, broader event coverage (engage / engard / move / wound / heal / challenge). |
| `modules/php/cards/faf/_03cd18.php` (Kalla and Adelheide) | CityCharacter with branching post-recruit Reaction. |
| `modules/php/cards/faf/reactions/Reaction_03cd18.php` | Branching "Choose one" reaction: choose → searchA \| moveB → destroyB. Demonstrates per-option validity gates at the choose stage, dedupe-by-Name in the deck picker, "no `< Back` once events commit" (destroyB omits it because moveB queued an `EventCardMoving`), the search-deck recipe with mandated shuffle, and the unequip+discard destroy recipe. |
| `modules/php/cards/_7s5s/_01098.php` (Cat's Embargo) | "Reveal a random card from a hand" reference implementation. |
| `modules/php/cards/tac/actions/Action_02045.php` (Path to Poluchatel) | "Search your deck for a card matching a Trait, reveal it, add to hand, shuffle" reference (Scheme City Action, but the search recipe applies anywhere). |
| `modules/php/cards/_7s5s/actions/Action_01174.php` | "Destroy a non-Unique attachment in play" reference — the canonical unequip + discard sequence. |
| `modules/php/Traits.php` | `TraitNames::$TraitsJson` — canonical Trait list for "Name a Trait" pickers. Add new Traits in alphabetical order. |
| `modules/php/cards/CityCharacter.php` | Base class. Read for the `Negotiable` field and inheritance chain. |
| `modules/php/cards/Character.php` | Parent. `canIntervene` / `canChallenge` defaults and wound/heal handling live here. |
| `modules/php/theah/Theah.php::interventionCheck` (~line 1651) | Where `canIntervene` is consumed by the engine. |

## When You Finish

1. Walk each clause of the printed Text — confirm each maps to exactly one pattern (Negotiable / Dashed stat / Hard ban / Forced / Action / Reaction / Technique / Maneuver). Stat numbers go on the constructor and are not a "pattern."
2. Every new state class needs all three: the class file in `modules/php/States/<expansion>/`, the constant in `States.php`, and the transition entry in `states.inc.php`'s `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`.
3. Every new state needs JS wiring in `OnEnteringState.<expansion>.js` AND `OnUpdateActionButtons.<expansion>.js`. Add `OnLeavingState.<expansion>.js` reset if you set selection modes or styling. Add to `PlayerActions.js` if you reuse a client action.
4. If you minted a new global, clear it in the matching cleanup state (or defensively at turn boundaries).
5. Mentally run pre-commit hook checks on every file you touched. Especially: `createActionResolvedEvent` in the action, no `setUsed`/`resetPlayerPassCount`/`announceAction` in the `CharacterAction` subclass, `$this->setUsed(` and `$this->isAvailable(` literal strings present in every `CardReaction` subclass.
6. For each Reaction you added, walk the `handleEvent` triggers and confirm all required gates are in place: `isAvailable()`, identity check (`$event->cardId == $owner->Id` etc.), city-scope gate (`cardInCity($owner)` or `locationInCity($event->toLocation)` per the gotcha), and a valid-target precondition if the effect needs a target. Missing the valid-target gate leaves the player with a useless "Decline" prompt.
7. If a Reaction reads location from inside a move-event branch, confirm you used `$event->toLocation` and NOT `$owner->Location` (the `EventCardMoved.runEventHubAfterCards = true` gotcha).
8. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md`. Capture the **WHY** of any non-obvious decision — event-type choice (`BeingWounded` vs `Wounded`, `DuelStarted` vs `ChallengeIssued`, `CardMoved` vs `CardMoving`), why `cardInCity` is the right scope gate, why `engage=false` on the move events, why `eventCheck` exists alongside `canIntervene`, why a button-based reaction was chosen over state classes (or vice versa). Read the Penya journal (`2026-04-26-01-penya-03cd01-implementation.md`) and the Julius journal (`2026-05-11-03-julius-caligari-03cd10-implementation.md`) first — between them they encode most of the hard-won knowledge about event ordering, the duel-mid-trigger edge case, and multi-step reaction design.
