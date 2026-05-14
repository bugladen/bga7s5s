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
| **`<b>City Reaction:</b>`** — player chooses to trigger in response to an event while the card is in a city location | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_03cdNN.php`. |
| **`<b>Reaction:</b>`** (no "City" prefix) — player chooses to trigger while the card is in their **Home** | Same `IHasReactions` + `ReactionTrait` + `reactions/Reaction_03cdNN.php` plumbing as City Reaction. The only difference is the `handleEvent` location guard: check `$owner->Location == Game::LOCATION_PLAYER_HOME` instead of `cardInCity($owner)`. See `_03cd20` (Early Morning Arrangements) — first CityEventCard precedent for a Home-located reaction. Requires the card to actually be able to *land* in a player's Home, which is its own sub-pattern (below). |

A single card can combine these (e.g. Penya `_03cd01` has both a City Forced and a City Action; `_03cd20` has a Reaction at end of Planning while in Home AND a City Action that puts itself into Home).

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

## Pattern C — City Reaction (and "Reaction while in Home")

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

2. Create `modules/php/cards/<expansion>/reactions/Reaction_03cdNN.php` extending `CardReaction`. Implement `getReactionDescription`, `getReactionButtonProperties`, `handleEvent` (detect the trigger, queue a `ReactionTransitionEvent`), and `performReaction` (apply effect, call `$this->setUsed($game->theah, true)`, then `$game->gamestate->nextState("done")`).
3. Pre-commit hook requires `$this->setUsed(...)` AND `$this->isAvailable()` to appear somewhere in the reaction class.
4. `CardReaction`'s base `setUsed` is reset at dusk automatically.

### Pick the right reaction shape

- **Single-stage opt-in** (one decision, then resolve): `_7s5s/reactions/Reaction_01184.php` (Claude — opt in to a pressure-type flag) is the simplest template.
- **Multi-stage button-based** (sequential picks: target, then option, then sub-target): use a private `$stage` field (string enum) plus per-stage state fields (e.g. `$chosenLocation`, `$chosenCharacterId`). Each `performReaction` advances the stage and re-queues `createReactionTransitionEvent($playerId, $cardId, $this->Id)` so the framework re-enters `playerReaction` with a fresh `getReactionButtonProperties` payload. Mark `$owner->IsUpdated = true` after each stage flip so the private fields persist across action handlers. References: `_03cd10` (Julius — letter → trait → target), `_03cd18` (Kalla — option A vs B branches), `_03cd20` (Early Morning Arrangements — character → adjacent city). Provide a `< Back` button on intermediate stages and a `Decline` button on every stage (Decline always wins — set used and exit).
- **In-Home reaction** (text reads `<b>Reaction:</b>` without "City"): same shape as a City Reaction; only the `handleEvent` guard differs (`$owner->Location == Game::LOCATION_PLAYER_HOME` instead of `cardInCity($owner)`). See `_03cd20` and the "CityEventCard living in a player's Home" sub-pattern below.

A multi-stage CardReaction does NOT need new GameState classes, `states.inc.php` edits, or per-state JS files — the framework's built-in `playerReaction` state hosts all stages. Only promote to dedicated State classes if a stage needs richer UI (board highlighting, multi-select, dragging) that can't be expressed as a flat button list.

## Pre-Commit Hook Gotchas (from `.githooks/pre-commit`)

| Pattern | Required |
|---|---|
| `implements ISorcererAbility` | `createSorcererAbilityStartEvent()` AND `createSorcererAbilityPlayedEvent()` |
| `extends Attachment/Card/Character/Risk/RiskCity/Scheme/SchemeCityAction` | `createActionResolvedEvent()` |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed()` AND `$this->isAvailable()` |
| `extends RiskReaction` | Check `Location == Game::LOCATION_HAND` |
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

**Server-side filtering of eligible targets.** When the card text gates targets ("target a player with more Renown than you", "target a player who controls fewer characters than you"), compute the eligible list in `getArgsFromAction` and send only those players to the client — don't send everyone and filter in JS. This also lets you reuse the same eligibility helper from `isAvailableToPlayer` to suppress the City Action button entirely when no valid target exists. See `Action_03cd13::getEligibleTargetPlayerIds()` for the canonical shape.

### Per-player once-per-Day City Action (card stays in play)

When the City Action does NOT discard the card and each player can take it once per Day (Siren's Scream `_01179`, Crabs in a Bucket `_03cd13`):

1. **Private `$playersUsed` array on the Action class** — holds player ids that have used the ability this Day. NOT on the card class (the base `CityEventCard::playersThatUsedMeToday` is private to the card and not accessible from Actions). Action_01179 / Action_03cd13 both define their own.
2. **`isAvailableToPlayer`** — return `false` if `in_array($playerId, $this->playersUsed)`.
3. **`eventCheck`** — re-validate `EventActionTriggered && actionId == $this->Id`, throw `UserException` if `playersUsed` contains the player. Defense in depth against race conditions / stale clients.
4. **In the action's `handleEvent`** after the effect resolves: `$this->playersUsed[] = $playerId;` then `$this->notifyUsedList($game, $owner->Id)` and `$this->setUsed($event->theah, false)` (defensive — keeps the framework from marking the card consumed).
5. **Reset on dusk**: `if ($event instanceof EventDuskEndOfDay) { $this->playersUsed = []; $card->IsUpdated = true; $this->notifyUsedList($game, $card->Id); }` — sends a notify with an empty list so clients clear the per-card UI.
6. **Do NOT queue `createCardAddedToCityDiscardPileEvent`** — the card stays.

Critical: per-player tracking is the *only* gate. The card's `Used` flag is global ("any player has used it") and would lock everyone out after the first use.

### Display per-player usage on the card element

The Siren's Scream / Crabs in a Bucket UI: a small list of player-colored names overlaid on the city card image, showing who has used the City Action this Day. Reuse the existing `_7sfs-card-player-list` CSS class — no new styles needed. Pattern (copy from `_01179` end-to-end):

1. **Action class** — `public function getUsedListData(Game $game): array` returning `[{playerId, playerName, playerColor}, ...]`. `private function notifyUsedList(Game $game, int $cardId)` emits a `xxxUsedListUpdated` notification with `{cardId, usedList}`.
2. **Card class** — `public function getXxxUsedListData(Game $game): array` that delegates: `return $this->Actions[0]->getUsedListData($game);`. Thin delegate so `Game::getAllDatas` can fetch it without poking the Action directly.
3. **`Game.php::getAllDatas`** — add a branch in the `foreach ($this->theah->getAllCards() as $card)` loop:
   ```php
   if ($card instanceof cards\<expansion>\<class> && $this->theah->cardInCity($card)) {
       $result["xxxUsedList"] = ['cardId' => $card->Id, 'usedList' => $card->getXxxUsedListData($this)];
   }
   ```
   Initialize `$result["xxxUsedList"] = null;` before the loop.
4. **`modules/js/Templates.js`** — `window.jstpl_xxx_used_list = \`<div id="xxx-used-list" class="_7sfs-card-player-list"></div>\`;`.
5. **`modules/js/Utilities.js`** — `displayXxxUsedList(cardId, usedList)` and `removeXxxUsedList()` mirroring `displaySirensScreamUsedList`. The display function `dojo.place`s the template inside `${card.divId}_image`, then iterates `usedList` creating colored `<span>` children.
6. **`modules/js/Notifications.js`** — register channel `['xxxUsedListUpdated', 1]` (priority 1 = instant UI update) and add `notif_xxxUsedListUpdated` handler that calls `displayXxxUsedList`.
7. **`modules/js/Setup.js`** — `if (gamedatas.xxxUsedList && gamedatas.xxxUsedList.usedList.length > 0) { this.displayXxxUsedList(...) }` for page-refresh.

Gotcha: the Action class is the natural owner of the data because `handleEvent` already has `$event->theah->game` for emitting notifies. The card-class delegate exists purely so `getAllDatas` can find the right Action without reflection.

### Highlight the chosen performer in a target-picker state

For a target-player state that follows performer selection (e.g. Crabs in a Bucket), add `performerId` to the state args and highlight it in the JS so the player sees which character they're about to engage:

1. **Action `getArgsFromAction`**: `$args["performerId"] = (int)$game->globals->get(Game::CHOSEN_PERFORMER);`
2. **`OnEnteringState.faf.js`**: `this.highlightCharacterChosen(args.args.args.performerId)`, and stash `this.clientStateArgs.performerId = performerId` for the leave handler.
3. **`OnLeavingState.faf.js`**: `this.unhighlightCharacterChosen(this.clientStateArgs.performerId)`.

These helpers (`highlightCharacterChosen` / `unhighlightCharacterChosen`) are the same ones `highDramaPhase03cd01` uses.

### `_7sfs-chosen` cleanup for cards that aren't discarded

`03cd03` adds `_7sfs-chosen` to the city card image on enter but **has no leave handler** — that's only fine because the card is discarded immediately after the action resolves, so the DOM element vanishes. For a multi-use City Action where the card stays in play (e.g. `03cd13`), you MUST add a leave handler that removes `_7sfs-chosen`, otherwise the highlight persists into subsequent High Drama turns. Stash the cardId on `clientStateArgs` during enter so leave can find the element after `gamedatas` has moved on.

### "At the end of High Drama" Forced trigger

`EventHighDramaPhaseEnd` exists and is dispatched centrally by `StatesTrait` at high drama end. Listen for it directly in `handleEvent`:

```php
if ($event instanceof EventHighDramaPhaseEnd && $event->theah->cardInCity($this))
{
    // ...
}
```

Reference cards: `_03cd12` (Equal Claim, makes location uncontrolled), `_7s5s/_01025_Burden` (removes itself at end of high drama). Note `_01025_Burden` is an Attachment, not a CityEventCard, but the trigger plumbing is identical.

Do **not** invent a custom "end of high drama" hook or piggyback on `EventDuskEndOfDay` / `EventPhaseHighDrama` — they fire at the wrong granularity.

### "Each player has equal X" check

Iterate `$theah->game->loadPlayersBasicInfos()` (returns `[playerId => playerRow]`), build a flat array of per-player counts, then `count(array_unique($counts)) === 1`. Treats zero-zero as equal, which matches the literal text of cards like Equal Claim.

```php
$counts = [];
foreach ($theah->game->loadPlayersBasicInfos() as $playerId => $_)
{
    $counts[] = count($theah->getCharactersAtLocationByPlayerId($this->Location, $playerId));
}
if (count(array_unique($counts)) !== 1) return;
```

### Making a city location uncontrolled

Queue `EventFactory::createLocationBecomesUncontrolledEvent($playerId, $location)`. The hub handler in `EventHub.php` (≈ line 953) takes care of `setControllerForLocation`, the `Controller = 0` mutation on the in-memory `CityLocation`, and the `locationUncontrolled` notify. Do not call `setControllerForLocation` directly from a card.

The `$playerId` arg is the **current controller losing control**, not the triggerer (mirrors `Action_01130` / `Action_01112a`). For a Forced card, pull it from `$theah->getCityLocation($this->Location)->Controller`.

Gate the queue on `$location->isControlled()` — queuing the event when the location is already uncontrolled is wasted work and emits a confusing notify.

The hub's `locationUncontrolled` notify doesn't say *why*, so emit a card-attributed `${card_inject_code}: ...` notify on the card before queuing the event so the log reads top-down (see `_03cd08` for the convention).

### CityEventCard living in a player's Home

Some cards (e.g. `_03cd20` Early Morning Arrangements) move themselves from a city location into a specific player's Home as part of an effect, then become reactive while sitting there. The default `CityEventCard` data model fully supports this (`Location` is just a string, `ControllerId` is just an int), but three things have to line up or the card vanishes from the UI.

1. **`ControllerId` must be set to the target player BEFORE queueing the move event.** `EventCardMoved`'s handler calls `$deck->moveCard($card->Id, $event->toLocation, $card->ControllerId)` — the third arg becomes `card_location_arg` in the DB. For `LOCATION_PLAYER_HOME` that's the player id. Pattern:
   ```php
   $owner->ControllerId = $playerId;
   $owner->IsUpdated = true;   // re-serializes the card after the event loop tick
   $moveEvent = EventFactory::createCardMovingEvent(
       $playerId, $owner->Id, $owner->Location,
       Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id
   );
   $theah->queueEvent($moveEvent);
   ```
   `$theah->getCardById()` returns the same cached object inside the handler, so the freshly-set `ControllerId` is visible when `deck->moveCard` runs.

2. **The `cardMoved` client notify carries `controllerId`** (added to `EventHub.php`'s `EventCardMoved` handler). `notif_cardMoved` writes it back to `card.controllerId` before recomputing the destination, so `createCardId(card, PLAYER_HOME)` returns `${playerId}-${id}` and `getTargetElementForLocation(PLAYER_HOME, playerId)` resolves to that player's `home-anchor`. If you're moving a CityEventCard to home and the UI silently lands it under the wrong player (or nowhere), the controllerId-on-notify wire is the first thing to check.

3. **No JS template changes are needed.** `Utilities.js::createCard` already dispatches `card.type === 'Event'` to `createEventCard`, which places `jstpl_card_event` "before" the home-anchor endcap. `Setup.js`'s home-cards loop hits the same path on refresh because `getCardPropertiesAtLocation(LOCATION_PLAYER_HOME)` returns the card with the right `controllerId` and the filter at the top of the loop picks it up.

**Triggering "Reaction:" abilities while in Home.** The card's `Reaction` class checks `$owner->Location == Game::LOCATION_PLAYER_HOME` inside `handleEvent` and queues a `ReactionTransitionEvent` from there — exactly the same shape as a City Reaction but with a different location guard. `EventPhasePlanningEnd`, `EventDuskPhaseEnd`, etc. are the natural phase triggers. See `_03cd20` Reaction.

**Adjacency from Home is "all city locations".** `Theah::getAdjacentCityLocations(Game::LOCATION_PLAYER_HOME, $includeHome = false)` returns every city location available at the current player count (DOCKS / FORUM / BAZAAR / + OLES_INN at 3p / + GARDEN at 4p). When a card-in-Home effect says "move to an adjacent City location," that's the full city — not a restricted subset. Don't hand-write a different adjacency table.

**Going back out — discard from Home.** `createCardAddedToCityDiscardPileEvent($owner->ControllerId, $owner->Id, $owner->Location, $owner->Id, true)` works regardless of whether `$owner->Location` is a city or `LOCATION_PLAYER_HOME`. The hub handler clears `ControllerId` and `Engaged`, sets `Location = LOCATION_CITY_DISCARD`, and the `cardAddedToCityDiscardPile` notify destroys the DOM element — so the home-anchor cleans up automatically.

### Pressure-this-location City Action (with a stat)

For text like "**City Action:** Pressure this location with [Finesse/Combat/Influence/Resolve] • If successful, …", the action is an `EventCityAction` with `RequiresPerformerSelected = true` and no custom state. The pressure result flows back through the framework's `HIGH_DRAMA_PRESSURE_LOCATION` state and your `handleEvent` listens for the result.

Recipe (lifted from `Action_02014` / `Action_02035` / `Action_03cd20`):

```php
class Action_03cdNN extends EventCityAction
{
    public function __construct() {
        parent::__construct();
        $this->Name = clienttranslate("…short description…");
        $this->RequiresPerformerSelected = true;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array {
        $performers = parent::getPerformersForAction($playerId, $theah);   // friendly chars at this location
        return array_values(array_filter($performers, fn($p) => !$p->Engaged));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) return false;
        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    public function handleEvent(Event $event) {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCard($event->theah);
            $game  = $event->theah->game;

            $game->globals->set(Game::PRESSURING_PLAYER, $event->playerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_FINESSE);    // pick the stat

            $performer = $game->theah->getCharacterById((int)$game->globals->get(Game::CHOSEN_PERFORMER));
            $pressureStats = $game->theah->getPressureStats($performer, $performer->Location, Game::STAT_FINESSE);

            $event->theah->queueEvent(
                EventFactory::createPressureOccuringEvent($event->playerId, $performer->Id, $performer->Location, $pressureStats)
            );
            $event->theah->queueEvent(
                EventFactory::createTransitionEvent($event->playerId, $owner->Id, "pressureLocation", $this->Id)
            );
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id) {
            if ($event->success) {
                // …apply success effect…
            }
            $event->theah->queueEvent(EventFactory::createActionResolvedEvent($event->playerId));
        }
    }
}
```

Key points:

- The `"pressureLocation"` transition key is **already** registered globally in `states.inc.php` (under `HIGH_DRAMA_PLAYER_TURN_EVENTS`). You do NOT need to add it — and you do NOT need a custom state class for the pressure itself.
- Pass `$this->Id` as the transition's `$internalId`. The framework propagates it through the pressure flow and you get it back on `EventLocationPressureResult` as `$event->abilityId`. That's how you distinguish your card's pressure from somebody else's during the same High Drama turn.
- Set `PRESSURE_TYPE = NORMAL_PRESSURE_TYPE` (a fresh `set`, not `OR`) unless the card *also* changes how pressure is counted. Only call `setGlobalFlag(PRESSURE_TYPE, …)` if your card has a Forced clause like "count only en-garde characters" (see `_03cd08`, `_01184`).
- `getPressureStats($performer, $location, $stat)` is the canonical way to compute the stat array passed to `createPressureOccuringEvent`. Don't hand-roll it.
- `createActionResolvedEvent` always fires (success or failure) so the engine advances out of the action.
- Pressure engages the performer as part of resolution — that's why `getPerformersForAction` filters out engaged characters. Don't manually queue `createCardEngagedEvent`.

For the "If successful, add a city card to this location" follow-up, queue `createCityCardAddedToLocationEvent((int)$topCard['id'], $location)` using `getCardsOnTopOfCityDeck(1)` (see Penya `_03cd01::triggerForcedAbility` for the exact shape).

## Reference Implementations

| Card | What it demonstrates |
|---|---|
| `modules/php/cards/faf/_03cd01.php` + `actions/Action_03cd01.php` + 2 state files | CityCharacter with City Forced (duel/wound → city-deck swap) AND a two-step CharacterAction (companion + adjacent location). Best canonical example of the full faf state-class + JS-wiring flow. |
| `modules/php/cards/faf/_03cd03.php` + `actions/Action_03cd03.php` + 2 state files | Pure CityEventCard with an `EventCityAction`. Demonstrates multi-player sequential loop in initiative order via queued EventTransitions, discard-deferred-until-loop-ends, `CURRENT_PLAYER` for actionResolved. |
| `modules/php/cards/faf/_03cd05.php` + `State_duelGambleSetup_03cd05.php` | CityAttachment, not CityEventCard, but shows: Forced wound on equip via `handleEvent`, a custom state inserted into core flow (DUEL_GAMBLE_SETUP), state-class file pattern. |
| `modules/php/cards/faf/_03cd08.php` | Minimal Forced pressure-flag card. Reuses `Game::CLAUDE_PRESSURE_TYPE` rather than minting a new flag. |
| `modules/php/cards/faf/_03cd12.php` | Pure Forced CityEventCard listening to `EventHighDramaPhaseEnd`. Demonstrates the "each player equal X" check via `loadPlayersBasicInfos` + `array_unique`, and queues `createLocationBecomesUncontrolledEvent` to flip a location uncontrolled. |
| `modules/php/cards/faf/_03cd13.php` + `actions/Action_03cd13.php` + `State_highDramaPhase03cd13.php` | CityEventCard with both a Forced (per-player conditional draw on reveal) and a multi-use City Action (engage + claim). Canonical example of: server-filtered target picker, per-player once-per-Day tracking via `playersUsed` (card stays in play), used-list display on the card, performer highlight in the target-picker state. |
| `modules/php/cards/faf/_03cd20.php` + `actions/Action_03cd20.php` + `reactions/Reaction_03cd20.php` | CityEventCard with a **Reaction-while-in-Home** (`EventPhasePlanningEnd`, multi-stage button-based picker — character → adjacent city → discard) AND a **Pressure-with-Finesse City Action** that puts the card back in the active player's Home on success. Only CityEventCard precedent for living in a player's Home. Demonstrates the `ControllerId`-update-then-move-to-home recipe, the `cardMoved` notify's `controllerId` field (added in this card's PR), and the multi-stage `CardReaction` with private `$stage` field. |
| `modules/php/cards/_7s5s/_01179.php` + `actions/Action_01179.php` | Original multi-use City Action / used-list display pattern (Siren's Scream). The `_03cd13` pattern is a direct descendant. Reference for the `setUsed(false)` defensive call and the `EventDuskEndOfDay` clear-and-renotify. |
| `modules/php/cards/_7s5s/_01184.php` + `reactions/Reaction_01184.php` | City Reaction template (sets the same `CLAUDE_PRESSURE_TYPE` flag opt-in instead of Forced). |
| `modules/php/cards/_7s5s/_01006.php` | Forced pressure bonus (`CONSTANZO_PRESSURE_TYPE`) — alternate flag/branch pattern. |
| `modules/php/UtilitiesTrait.php::pressureLocation()` | Where pressure flags are consumed. Add a branch here if minting a new pressure type. |

## When You Finish

1. Re-read the card text and walk through each clause — confirm each maps to exactly one branch you wrote.
2. For every new state class, verify all three are present: the class file, the `States.php` constant, and the entry in `states.inc.php`'s `HIGH_DRAMA_PLAYER_TURN_EVENTS` transitions.
3. For every new state, verify JS wiring in `OnEnteringState.faf.js`, `OnUpdateActionButtons.faf.js`, and (if you set selection modes / styling) `OnLeavingState.faf.js`. Add to `PlayerActions.js` action map if reusing client actions.
4. Mentally run the pre-commit hook checks against the files you touched.
5. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md` covering the WHY (which existing flag/pattern you reused, what alternatives you considered, anything that looks weird). Read the related faf journals first — they encode hard-won knowledge about edge cases (zombie handling, priority ordering, `CURRENT_PLAYER` vs active player).
