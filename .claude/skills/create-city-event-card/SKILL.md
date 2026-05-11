---
name: create-city-event-card
description: Implement or finish a City Event Card (modules/php/cards/faf/_03cdNN.php and similar). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a City Event Card, or when they reference a card whose class extends CityEventCard and has unimplemented Text. Triggers on phrases like "implement this city event", "finish _03cdNN", "wire up the Forced ability", or "add a City Action to this event card."
---

# Creating a City Event Card

City event cards are city-deck cards that sit at a city location and modify play through Forced abilities, City Actions, or City Reactions. This skill is the playbook for fleshing out a `_03cdNN.php` (or any `extends CityEventCard`) stub into a working card.

The `faf` branch has established a fairly rich pattern for these cards — separate State classes, dedicated JS files per expansion, multi-player sequential loops via queued transitions. Follow it, even when it feels heavier than strictly needed for a one-clause card.

## Base Anatomy

Every `CityEventCard` lives under `modules/php/cards/<expansion>/` (e.g. `faf/`) and inherits from `CityEventCard`, which itself extends `Card` and uses `CityDeckCardTrait`. Required scaffolding (already present in stubs):

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;

class _03cdNN extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name           = clienttranslate('...');
        $this->Image          = '03cdNN.jpg';
        $this->ExpansionName  = 'faf';   // or _7s5s / tac
        $this->ExpansionNumber = 3;
        $this->CardNumber     = 0;       // city deck cards keep CardNumber = 0
        $this->CityCardNumber = NN;      // the visible city number on the card

        $this->Traits = [ clienttranslate('...'), ];

        $this->Text = clienttranslate("...");

        $this->resetCard();
    }
}
```

Key facts:
- The card's runtime `$this->Location` is the city location it currently occupies (e.g. `Game::LOCATION_CITY_OLES_INN`). Use `$event->theah->cardInCity($this)` before reacting.
- `CityEventCard::handleEvent` clears per-day usage tracking on `EventNewDay`. Always call `parent::handleEvent($event)` first when overriding.
- Text tooltips for events are already wired in `modules/js/Utilities.js` (`createTextTooltipForEvent`) — no JS changes needed for a new event's tooltip.
- File naming: leading underscore + the city-card image stem, e.g. `_03cd08.php` for `03cd08.jpg`. Class name matches the filename.

## Pick the Right Ability Shape

Read the card's `Text` and classify each clause before writing any code:

| Card phrase | Pattern |
|---|---|
| **`<b>Forced:</b>` / `<b>City Forced:</b>`** — auto-triggers, no choice | Override `handleEvent` directly on the card class. No Action/Reaction/State files needed. |
| **`<b>City Action:</b>`** — player spends an action | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_03cdNN.php`. State class(es) + JS wiring if it needs interactive steps. |
| **`<b>City Reaction:</b>`** — player chooses to trigger in response to an event | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_03cdNN.php`. |

A single card can combine these (e.g. Penya `_03cd01` has both a City Forced and a City Action).

## Pattern A — Forced Ability

Override `handleEvent`. Gate the body on (a) event type, (b) `cardInCity($this)`, and (c) any text-specific condition like "at this location" or "when a character equips this card."

Template:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof Event<Something>
        && $event->theah->cardInCity($this)
        && /* text-specific condition */)
    {
        // Apply mutation / queue follow-up events.
        $event->theah->game->notify->all("message", clienttranslate('${card_inject_code}: ...'), [
            'card_inject_code' => $this->getInjectCode(),
        ]);
    }
}
```

If the Forced effect needs to queue further game events (wound, remove from play, transition to a custom state), use `EventFactory::create*Event(...)` and `$event->theah->queueEvent(...)`. See `_03cd05` (wound on equip) and `_03cd01` (queues `CardRemovedFromPlayEvent` and then listens for it to shuffle).

### Event ordering inside handleEvent

For events with `runEventHubAfterCards = false` (the default), EventHub processes the event first, then every card's `handleEvent` fires. This means you can queue `createCardRemovedFromPlayEvent` and have another `handleEvent` branch on this same card listen for the resulting `EventCardRemovedFromPlay` to do follow-up work (e.g., Penya shuffling the city deck after moving into it).

### Pressure-modifying Forced (very common)

Cards that change *how a pressure is counted* don't need their own state; they set a global flag that `UtilitiesTrait::pressureLocation()` reads while tallying influence.

**Reuse an existing flag when the rule is identical.** `Game::CLAUDE_PRESSURE_TYPE` already means "count only the performer and en garde characters" — both Claude de la Roche (`_01184` Reaction) and Inauguration Day (`_03cd08` Forced) share it. The matching `Game::CLAUD_ID` global stores the card whose `Location` defines the affected pressure.

```php
if ($event instanceof EventPressureOccuring
    && $event->theah->cardInCity($this)
    && $event->location == $this->Location)
{
    $game = $event->theah->game;
    $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE);
    $game->globals->set(Game::CLAUD_ID, $this->Id);

    $game->notify->all("message", clienttranslate('${card_inject_code}: ...'), [
        'card_inject_code' => $this->getInjectCode(),
    ]);
}
```

Existing pressure-type flags (all binary in `Game.php`):
`CLAUDE_PRESSURE_TYPE`, `CAPTAINS_COAT_PRESSURE_TYPE`, `REPUTATION_MERITEE_PRESSURE_TYPE`, `TABARD_PRESSURE_TYPE`, `CONSTANZO_PRESSURE_TYPE`, `CONTEMPT_AND_HATRED_PRESSURE_TYPE`, `PACK_TACTICS_PRESSURE_TYPE`, `PULL_THE_STRAND_PRESSURE_TYPE`, `KASPARS_OCCUPATION_PRESSURE_TYPE`, `TRIAL_OF_FAITH_PRESSURE_TYPE`, `CASTILLIAN_CAPER_PRESSURE_TYPE`, `SOLOMONIA_PRESSURE_TYPE`, `USSURAN_INTRIGUE_PRESSURE_TYPE`.

If the rule is genuinely new:
1. Add a new binary flag (next power of two) and ID-global constant in `Game.php`.
2. Add a branch in `UtilitiesTrait::pressureLocation()` next to the existing `CLAUDE_PRESSURE_TYPE` / `CONSTANZO_PRESSURE_TYPE` blocks.

Reference cards: `_01006` (Don Constanzo, Forced bonus), `tac/_02044` (Solomonia, Forced bonus), `_7s5s/_01184` Reaction (Claude — same flag as `_03cd08`, different trigger style).

## Pattern B — City Action (full faf flow)

Most City Actions need at least one interactive step, so the work spans five layers:

### 1. Card class — wire up the action

```php
class _03cdNN extends CityEventCard implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        // ... base fields ...
        $this->resetCard();

        $this->Actions = [ new Action_03cdNN() ];
    }
}
```

### 2. Action class — `modules/php/cards/<expansion>/actions/Action_03cdNN.php`

Pick the base class carefully:
- `EventCityAction` — one-shot event-card actions that get **discarded after use**. Used by Chance Meeting `_03cd03`. The action does not "engage" a character; its eligibility is gated by friendly-character-at-location.
- `CharacterAction` — actions performed by a specific character (the event card's owner-character if it's a CityCharacter, not a pure event). Penya `_03cd01` uses this because Penya is a CityCharacter, not a CityEventCard. Generally not used for pure event cards.

`setUsed()`, `announceAction()`, and `resetPlayerPassCount()` are **NOT called** from `CharacterAction/AttachmentAction/SchemeAction/SchemeCityAction` subclasses — central code in `actHighDramaInPlayActionConfirm` / `stHighDramaInPlayActionDispatch` handles them. Per CLAUDE.md.

**`EventCityAction` is different** — it is *not* on the centrally-handled list above. `resetPlayerPassCount()` SHOULD be called once from `EventCityAction` subclasses (typically in the handler for the first interactive step). Follow the precedent in `_7s5s/actions/Action_01185.php` and `_03cd03` handleTargetChosen. `setUsed()` is unnecessary because the card is discarded after one use.

Required methods (from `.cursor/rules/card-action-template.mdc`): `isAvailableToPlayer`, `handleEvent`, `getArgsFromAction`, `actFromActionWithId` / `actFromActionWithIds`.

In `handleEvent`, when you see `EventActionTriggered && $event->actionId == $this->Id`, queue a transition into your first state:

```php
$transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03cdNN", $this->Id);
$event->theah->queueEvent($transition);
```

The transition string key (`"03cdNN"`) is what `states.inc.php` maps to your state constant.

### 3. State classes — `modules/php/States/<expansion>/State_<name>.php`

Each interactive step is its own class extending `Bga\GameFramework\States\GameState`. Constants live in `States.php`.

State ID convention (expansion 3 / `faf`):
- Format: `403XXXX` (4 = high drama, 03 = expansion, XX = card number).
- Multi-step suffix: append `2` for step 2, etc. Example: `4030001` and `40300012` for Penya step 1 and step 2.

Template (copy from `State_highDramaPhase03cd03.php`):

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
                "zombie"      => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "<next-edge>" => States::HIGH_DRAMA_PLAYER_TURN_03CDNN_2, // or back to HIGH_DRAMA_PLAYER_TURN_EVENTS
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array { return $this->game->argsForState(); }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void { $this->game->actFromCardWithId($id); }
    // Use actFromCardWithIds when picking a location/list. See State_highDramaPhase03cd01_2.

    public function zombie(int $playerId): void { $this->game->gamestate->nextState("zombie"); }
}
```

Transitions back to `States::HIGH_DRAMA_PLAYER_TURN_EVENTS` when the action sequence completes; the events queue (transitions, discard, actionResolved) takes over from there.

### 4. Register state IDs and transitions

- Add the constants in `modules/php/States.php` under the per-card section.
- Register transitions in `states.inc.php` under the `HIGH_DRAMA_PLAYER_TURN_EVENTS` state's `transitions` array, keyed by the same string you passed to `createTransitionEvent`. Example:

```php
"03cd01"   => States::HIGH_DRAMA_PLAYER_TURN_03CD01,
"03cd01_2" => States::HIGH_DRAMA_PLAYER_TURN_03CD01_2,
```

Do NOT add anything to `states.7s5s.php` for new state classes — that file is the older array-style state map. New states use class files; only their transition keys appear in `states.inc.php`.

### 5. JS wiring — required, easy to forget

Without JS, the state activates server-side but the player sees nothing. Add handlers in the expansion-specific files:
- `modules/js/OnEnteringState.faf.js` — UI setup (highlight selectables, mark already-chosen characters, set selection modes).
- `modules/js/OnUpdateActionButtons.faf.js` — Confirm / Back / per-player buttons / Decline / etc.
- `modules/js/OnLeavingState.faf.js` — reset selection modes and styling.
- `modules/js/PlayerActions.js` — extend the action map (e.g., `onMusterCardSelected`) if your state reuses an existing client-side action.

Make sure the expansion files are included from `OnEnteringState.js` / `OnLeavingState.js` / `OnUpdateActionButtons.js` (the faf branch already chains them).

### 6. Discarding the event card and signaling completion

When the action's effects are done, the event card is discarded and an action-resolved event is queued:

```php
$discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent(
    $triggererId,
    $owner->Id,
    $location,
    $owner->Id,    // sourceId
    $asEffect = true
);
$game->theah->queueEvent($discardEvent);

$actionResolvedEvent = EventFactory::createActionResolvedEvent($triggererId);
$game->theah->queueEvent($actionResolvedEvent);
```

**Use `Game::CURRENT_PLAYER`** (not `getActivePlayerId()`) as the triggerer when discarding at the end of a multi-player loop — the active player at that point is whoever acted last, not the player whose high-drama turn this is. `CURRENT_PLAYER` is the player whose high-drama turn this is and is the correct identity for the `actionResolved` signal. `Theah::runEvents()` falls back to it when resetting active player, so it is reliable for the whole turn lifecycle.

### 7. Multi-player sequential loops (Initiative-order responses)

When the card text says "in order of Initiative, each player may X," **do not use `multipleactiveplayer`** — it allows simultaneous responses. Instead:

1. Compute eligible players, ordered by `player.turn_order` (turn_order = the day's initiative order).
2. Store remaining-players list as a JSON global (e.g. `chanceMeetingRemaining`).
3. Queue one `createTransitionEvent($playerId, ...)` per eligible player.
4. The step-2 state's `actFromActionWithId` pops the acting player from the list and calls `nextState(...)` which routes back to `HIGH_DRAMA_PLAYER_TURN_EVENTS` to consume the next transition.
5. When the remaining list is empty, queue discard + actionResolved.
6. **Handle the empty-eligible-list-upfront case**: if no player is eligible at target-chosen time, run the same `queueDiscardAndResolve()` immediately so the card still discards and the action resolves.

**Event priority gotcha:** `TRANSITION_PRIORITY = 8`, `MEDIUM_PRIORITY = 3` (lower number = higher priority = runs *first*). If you queue [transitions..., discardEvent] up front, the discard runs before any transition — the card vanishes before opponents act. Either queue the discard only after the last player acts (the Chance Meeting pattern), or assign a higher priority.

**"May X" means Decline is a valid choice.** When the text says "may" (not "must"), wire id=0 / a Decline button as a first-class option in the step-2 state. Treat Decline identically to acting for the purposes of popping the remaining list and progressing the loop.

**Zombie handling in the loop**: in the step-2 state's `zombie()` method, call `actFromActionWithId(0)` (i.e. Decline) so the player is popped from REMAINING and the loop continues. Do not transition straight to `HIGH_DRAMA_PLAYER_TURN_EVENTS` — that would leave the player on the remaining list and either re-trigger them or leave the global dirty.

**Clean up card-specific globals** inside the final discard step (the one that runs after the last loop iteration). Delete the remaining-players list AND any target-selection global (e.g. `CHOSEN_TARGET`) so state doesn't leak to subsequent triggers or across the day. As an extra safety, reset target-selection globals defensively in `stNextPlayer`.

### 8. Pre-commit hook for City Actions

The hook in `.githooks/pre-commit` requires `createActionResolvedEvent()` for any `extends Attachment/Card/Character/Risk/RiskCity/Scheme/SchemeCityAction`. It does *not* require `setUsed` / `resetPlayerPassCount` / `announceAction` on these subclasses — those are explicitly forbidden by the comment in CLAUDE.md (run centrally).

## Pattern C — City Reaction

1. On the card class:

```php
class _03cdNN extends CityEventCard implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        // ... fields ...
        $this->resetCard();

        $this->Reactions = [ new Reaction_03cdNN() ];
    }
}
```

2. Create `modules/php/cards/<expansion>/reactions/Reaction_03cdNN.php` extending `CardReaction`. Use `_7s5s/reactions/Reaction_01184.php` as the template — implement `getReactionDescription`, `getReactionButtonProperties`, `handleEvent` (detect the trigger, queue a `ReactionTransitionEvent`), and `performReaction` (apply effect, call `$this->setUsed($game->theah, true)`, then `$game->gamestate->nextState("done")`).
3. Pre-commit hook requires `$this->setUsed(...)` AND `$this->isAvailable()` to appear somewhere in the reaction class.
4. `CardReaction`'s base `setUsed` is reset at dusk automatically.

## Pre-Commit Hook Gotchas (from `.githooks/pre-commit`)

| Pattern | Required |
|---|---|
| `implements ISorcererAbility` | `createSorcererAbilityStartEvent()` AND `createSorcererAbilityPlayedEvent()` |
| `extends Attachment/Card/Character/Risk/RiskCity/Scheme/SchemeCityAction` | `createActionResolvedEvent()` |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed()` AND `$this->isAvailable()` |
| `extends RiskReaction/CancelReaction` | Check `Location == Game::LOCATION_HAND` |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()` |
| **Forbidden** | Implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` on one class |
| **Forbidden in subclasses** | `setUsed`/`resetPlayerPassCount`/`announceAction` in `CharacterAction/AttachmentAction/SchemeAction/SchemeCityAction` |

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line (see `.cursor/rules/php-brace-style.mdc`).
- Namespaces:
  - Card class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action: `...\cards\<expansion>\actions`
  - Reaction: `...\cards\<expansion>\reactions`
  - State: `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`

## Reusable Sub-Patterns

### "Controls fewer/more characters than X"

Use `Theah::getCharactersInPlayByPlayerId($playerId)` — it counts characters in the city or at player home, and correctly excludes purgatory, hand, and dueling line. Iterate other players via `ORDER BY turn_order` from the player table (turn_order = today's initiative order). Always exclude the comparison target themselves from the eligible set.

### Muster from approach deck

Reuse the pattern from `_7s5s/actions/Action_01072.php` (Réputation Méritée):

1. Get the player's cards in `Game::LOCATION_APPROACH`.
2. Filter to `instanceof Character`.
3. Validate the chosen card id is in that filtered set.
4. `EventFactory::createCharacterMusteredEvent($playerId, $cardId, $location)` and queue it. The EventHub handler moves the character into play and fires any reactions (e.g. crew cap — handled centrally, no card-specific work needed).

For "may Muster" text, also accept id=0 as Decline and skip the muster event. Wire a Decline button in `OnUpdateActionButtons.faf.js` for that state.

If your state reuses the client-side `onMusterCardSelected` flow, **extend the action map in `modules/js/PlayerActions.js`** to include your state name (e.g. `highDramaPhase03cd03_2`). Forgetting this is a common cause of "the muster button does nothing in my new state."

### Target-a-player picker

Store the chosen target as `Game::CHOSEN_TARGET` (a shared global). Mirror the 02002 Elisabetta pattern in `OnUpdateActionButtons.faf.js`: render one button per non-self player. Clear `CHOSEN_TARGET` in your discard step and as a defensive reset in `stNextPlayer`.

## Reference Implementations

| Card | What it demonstrates |
|---|---|
| `modules/php/cards/faf/_03cd01.php` + `actions/Action_03cd01.php` + 2 state files | CityCharacter with City Forced (duel/wound → city-deck swap) AND a two-step CharacterAction (companion + adjacent location). Best canonical example of the full faf state-class + JS-wiring flow. |
| `modules/php/cards/faf/_03cd03.php` + `actions/Action_03cd03.php` + 2 state files | Pure CityEventCard with an `EventCityAction`. Demonstrates multi-player sequential loop in initiative order via queued EventTransitions, discard-deferred-until-loop-ends, `CURRENT_PLAYER` for actionResolved. |
| `modules/php/cards/faf/_03cd05.php` + `State_duelGambleSetup_03cd05.php` | CityAttachment, not CityEventCard, but shows: Forced wound on equip via `handleEvent`, a custom state inserted into core flow (DUEL_GAMBLE_SETUP), state-class file pattern. |
| `modules/php/cards/faf/_03cd08.php` | Minimal Forced pressure-flag card. Reuses `Game::CLAUDE_PRESSURE_TYPE` rather than minting a new flag. |
| `modules/php/cards/_7s5s/_01184.php` + `reactions/Reaction_01184.php` | City Reaction template (sets the same `CLAUDE_PRESSURE_TYPE` flag opt-in instead of Forced). |
| `modules/php/cards/_7s5s/_01006.php` | Forced pressure bonus (`CONSTANZO_PRESSURE_TYPE`) — alternate flag/branch pattern. |
| `modules/php/UtilitiesTrait.php::pressureLocation()` | Where pressure flags are consumed. Add a branch here if minting a new pressure type. |

## When You Finish

1. Re-read the card text and walk through each clause — confirm each maps to exactly one branch you wrote.
2. For every new state class, verify all three are present: the class file, the `States.php` constant, and the entry in `states.inc.php`'s `HIGH_DRAMA_PLAYER_TURN_EVENTS` transitions.
3. For every new state, verify JS wiring in `OnEnteringState.faf.js`, `OnUpdateActionButtons.faf.js`, and (if you set selection modes / styling) `OnLeavingState.faf.js`. Add to `PlayerActions.js` action map if reusing client actions.
4. Mentally run the pre-commit hook checks against the files you touched.
5. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md` covering the WHY (which existing flag/pattern you reused, what alternatives you considered, anything that looks weird). Read the related faf journals first — they encode hard-won knowledge about edge cases (zombie handling, priority ordering, `CURRENT_PLAYER` vs active player).
