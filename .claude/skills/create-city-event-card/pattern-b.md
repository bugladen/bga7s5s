> Part of **create-city-event-card**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

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
- `EventCityAction` — event-card actions. Eligibility is gated by "friendly character at this location." Two flavors:
  - **One-shot** (card is discarded after use) — Chance Meeting `_03cd03`. The action does not engage; the action handler queues `createCardAddedToCityDiscardPileEvent` once the effect resolves.
  - **Multi-use** (card stays in play, each player can take it once per Day) — Siren's Scream `_01179`, Crabs in a Bucket `_03cd13`. The card stays; per-player usage is tracked in a private `$playersUsed` array on the Action. Call `$this->setUsed($theah, false)` defensively at the end of the handler and DO NOT queue a discard. See "Per-player once-per-Day City Action" sub-pattern below.
- `CharacterAction` — actions performed by a specific character (the event card's owner-character if it's a CityCharacter, not a pure event). Penya `_03cd01` uses this because Penya is a CityCharacter, not a CityEventCard. Generally not used for pure event cards.

**`RequiresPerformerSelected = true` and `EventCityAction`** — when set on an `EventCityAction`, the framework runs its built-in performer-selection UI **before** firing `EventActionTriggered`. The chosen performer's id is in `Game::CHOSEN_PERFORMER` when your `handleEvent` runs, so you can engage it without needing a state for the performer pick. Override `getPerformersForAction` to filter out engaged characters (or whatever the text requires).

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

**`EventCityAction` and the hook:** the hook's regex matches the *literal* parent name on the `extends` line — so `extends EventCityAction` is NOT matched by the `(CardAction|RiskAction|RiskCityAction)` check, even though `EventCityAction` extends `CardAction`. The `createActionResolvedEvent` requirement is therefore not enforced for `EventCityAction` subclasses, but you should still call it — the framework expects an action-resolved event to advance state.
