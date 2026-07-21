> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

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
