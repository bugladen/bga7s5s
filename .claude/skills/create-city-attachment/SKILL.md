---
name: create-city-attachment
description: Implement or finish a City Attachment (modules/php/cards/<expansion>/_NNNNN.php where the class extends CityAttachment). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a City Attachment, or when they reference a city-deck attachment whose class extends CityAttachment and has unimplemented Text. Triggers on phrases like "implement this city attachment", "finish _03cdNN" (when it extends CityAttachment), "wire up the equip wound", "the equipped character does X", or "add an Action to this attachment."
context: fork
model: haiku
effort: low
---

# Creating a City Attachment

City attachments are city-deck cards that *equip onto a character* at a city location, modifying that character's stats and granting Forced abilities, Actions, or Reactions tied to "the equipped character." Unlike City Event Cards, they have stat modifiers, a `WealthCost`, an `AttachedToId`, and they stay in play across pressures until destroyed or unequipped.

The canonical reference for the full pattern is `_03cd05.php` (Devil Jonah's Bones): a Forced wound on equip + a steady-state gamble modifier + a custom state inserted into the duel flow. Read it before scaffolding anything novel.

> **Sibling skill:** `create-city-event-card` covers `CityEventCard`. If the stub `extends CityEventCard` (not `CityAttachment`), use that skill instead.

## Base Anatomy

City attachments live under `modules/php/cards/<expansion>/` (e.g. `faf/`, `_7s5s/`, `tac/`) and extend `CityAttachment`. `CityAttachment` extends `Attachment` and mixes in `CityDeckCardTrait`.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _03cdNN extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name           = clienttranslate('...');
        $this->Image          = '03cdNN.jpg';
        $this->ExpansionName  = 'faf';     // or _7s5s / tac
        $this->ExpansionNumber = 3;
        $this->CardNumber     = 0;          // city deck cards: keep CardNumber = 0
        $this->CityCardNumber = NN;         // visible city number on the card

        $this->WealthCost = 1;              // attachment equip cost

        // Stat buffs applied while equipped. Default 0.
        $this->ResolveModifier   = 0;
        $this->CombatModifier    = 0;
        $this->FinesseModifier   = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [ clienttranslate('Artifact'), /* ... */ ];

        $this->Text = clienttranslate("<p><b>Forced:</b> ...</p>");

        $this->resetCard();
    }
}
```

Key state-on-the-instance:
- `$this->Id` — the attachment's card id.
- `$this->AttachedToId` — the character id it's equipped to (0 if not equipped).
- `$this->ControllerId` — the player who controls the equipped character.
- `$this->Location` — the city location of the equipped character (mirrored from the character on equip).
- `$this->Engaged` — engagement is a property of the attachment itself; many Reactions cost "Engage this card."

Helpers from `Attachment`:
- `$this->isAttached(): bool` — true when `AttachedToId > 0`. Always gate "equipped character" effects on this.
- `$this->attachedTo(Theah $theah): ?Card` — returns the character, or null when not equipped.
- `$this->canAttachTo(Character $c): bool` — override if the card text restricts targets ("equip to a Sorcerer", etc.). Default `true`.
- `$this->getRequiredAttachTargetId(Theah, int $originalTargetId): int` — override if the equip target must be redirected. **Pre-commit hook requires that any call site of `EventFactory::createAttachmentEquippedEvent` also references `getRequiredAttachTargetId`** — usually handled by the calling Action, not this card.

## Pick the Right Ability Shape

Read each clause of the card text and classify it before writing any code:

| Card phrase | Pattern |
|---|---|
| **Stat modifier** (`+1 [Combat]`, `+2 [Influence]`) | Set `CombatModifier` / `FinesseModifier` / etc. in the constructor. No further code. |
| **Passive grant on the equipped character** ("Gains Duelist", "Cannot be wounded by Risks") | Override `handleEvent` and react to `EventAttachmentEquipped` (add) + `EventAttachmentUnequipped` (remove). See `_01198` (Guild Triskelion) and `tac/_02047` (Temnota) for the canonical `addTrait` / `removeTrait` pair. |
| **`<b>Forced:</b>`** — auto-triggers, no choice | Override `handleEvent` directly. No Action/Reaction class needed. See `_03cd05` Forced wound on equip. |
| **`<b>Forced:</b> the first time an opponent's Risk targets the equipped character each Day** | Pattern G below — cancel five event types, set a once-per-Day condition, clear at `EventDuskEndOfDay`. See `_03cd21` (Silver Spine), modeled on `_01186` (Maryam). |
| **`<b>Action:</b>` or `<b>City Action:</b>`** — player spends an action | Implement `IHasActions`, `use ActionTrait`, create `cards/<expansion>/actions/Action_NNNNN.php` extending `AttachmentAction`. |
| **`<b>Reaction:</b>` or `<b>City Reaction:</b>`** — opt-in response to an event | Implement `IHasReactions`, `use ReactionTrait`, create `cards/<expansion>/reactions/Reaction_NNNNN.php` extending `AttachmentReaction`. |
| **Steady-state property of the play area** ("reveal an additional gamble card", "this character has +1 wounds capacity") | Override the matching `Card::get*` method (e.g. `getNumberOfGambleCardsToReveal`) — *not* via event mutation. See "Steady-state overrides" below. |

A single card commonly combines several — `_03cd05` mixes a Forced (handleEvent), a steady-state override, and a custom-state prompt all in one class.

## Pattern A — Forced Ability on Equip / While Equipped

Override `handleEvent`. Three gates to consider:

1. **Event type** — `EventAttachmentEquipped`, `EventCharacterBeingWounded`, `EventGambleSetup`, etc.
2. **Targeting this attachment** — `$event->attachmentId == $this->Id` for the equip event itself.
3. **"The equipped character does X"** — `$this->isAttached() && $event-><actorId> == $this->AttachedToId`.

Template:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    // Forced: When a character equips this card • <effect>
    if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
    {
        $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
            $event->characterId,
            $this->Id,                  // source: the attachment
            1,
            $this->getInjectCode()
        );
        $event->theah->queueEvent($woundEvent);
    }

    // "When the equipped character <does X>"
    if ($event instanceof EventGambleSetup
        && $this->isAttached()
        && $event->actorId == $this->AttachedToId)
    {
        $character     = $this->attachedTo($event->theah);
        $controllerId  = $character !== null ? $character->ControllerId : $event->playerId;

        // Queue follow-up: usually a TransitionEvent into a player-choice state,
        // or another game event (wound, heal, move, etc.).
        $transition = EventFactory::createTransitionEvent($controllerId, $this->Id, "03cdNN");
        $event->theah->queueEvent($transition);
    }
}
```

**Why use `$this->attachedTo()->ControllerId` instead of `$event->playerId`?** In normal play these are equal — but text scoping says "the equipped character," so defensively reading from the attachment-target keeps it correct if the equipped character ever changes controller (future mechanic). This is the pattern from `_03cd05`.

**Wound source = `$this->Id`.** When the attachment is the trigger, the wound (or other event) originates *from the attachment*. Consistent with `Maneuver_01135` and how all "this card wounds them" effects record provenance.

**Why a Forced in `handleEvent` and not a `CardReaction`?** Forced abilities are mandatory and require no player choice. The precedent (`_01075` Tabard of the Fallen Musketeer, `_03cd01` Penya, `_03cd05` Devil Jonah's Bones) is to handle Forced effects directly in the card class's `handleEvent`. CardReaction would require `setUsed` / `isAvailable` plumbing that doesn't fit a Forced effect — and the pre-commit hook would then demand those calls.

### Event ordering inside handleEvent

For events with `runEventHubAfterCards = false` (the default), the EventHub processes the event *first*, then every card's `handleEvent` fires. That means you can react to your own queued event in the same `handleEvent` (Penya `_03cd01` uses this — queues `CardRemovedFromPlay`, then reacts to it to shuffle the city deck).

## Pattern B — Passive Grant via Equip/Unequip pair

For "the equipped character gains <Trait>" or analogous static buffs, you need the *pair* of events. Forgetting `EventAttachmentUnequipped` leaks the trait when the attachment moves off:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        $character->addTrait($event->theah->game, "Duelist");
    }

    if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        $character->removeTrait($event->theah->game, "Duelist");
    }
}
```

References: `_01198` (Guild Triskelion → Duelist), `tac/_02047` (Temnota → Sorcerer).

## Pattern C — AttachmentAction

City attachment actions are performed *by the character the attachment is equipped to*. `AttachmentAction::getPerformersForAction` defaults to `[$this->getOwningCharacter($theah)]`, and `isAvailableToPlayer` already gates on the owning character being non-null. Keep `Action_NNNNN extends AttachmentAction`.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;

class Action_03cdNN extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("...");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $owner      = $this->getOwningCharacter($event->theah);
            $location   = $owner->Location;

            // ... apply effect ...

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
```

### "Destroy this card" cost

If the action text reads "**Destroy this card** • …" (e.g. `_01187` Smuggled Item, `_01191` Duckfoot Pistol), queue the unequip + discard up-front, *guarded by `isAttached()` so copied effects don't crash*:

```php
if ($attachment instanceof Attachment && $attachment->isAttached())
{
    $event->theah->queueEvent(EventFactory::createAttachmentUnequippedEvent(
        $event->playerId, $owner->Id, $attachment->Id
    ));
    $event->theah->queueEvent(EventFactory::createCardAddedToCityDiscardPileEvent(
        $event->playerId, $attachment->Id, $location, $attachment->Id, $asEffect = false
    ));
}
```

The `isAttached()` guard is important — `_01191` calls it out explicitly because the action's effect can be *copied* by other cards, and copies must not crash when the original is no longer equipped.

### `setUsed` / `announceAction` / `resetPlayerPassCount`

**Do NOT call any of these from `AttachmentAction` subclasses.** They're handled centrally in `actHighDramaInPlayActionConfirm` and `stHighDramaInPlayActionDispatch`. Per CLAUDE.md. The pre-commit hook does not require them on these subclasses.

### Pre-commit hook on actions

`Action_NNNNN extends AttachmentAction → CardAction` — the hook only requires `createActionResolvedEvent()` somewhere in the class. Make sure it's queued at the end of effect resolution (after any state loops complete).

## Pattern D — AttachmentReaction

Extend `AttachmentReaction` (which extends `CardReaction`). It adds `ownerIsAttached(Theah)` so you can early-out when the parent attachment is detached.

Pre-commit hook requires both `$this->setUsed(...)` and `$this->isAvailable()` to appear in the class body.

Skeleton (adapted from `Reaction_01181` — Sorte Deck):

```php
class Reaction_NNNNN extends AttachmentReaction
{
    public function getReactionDescription(Theah $theah): string { /* ... */ }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array   = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Effect'), 'doEffect');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventSomething
            && !$event->canceled
            && $this->ownerIsAttached($event->theah)
            && $this->isAvailable())
        {
            // ... optionally cancel + clone the event, save context, queue ReactionTransitionEvent
            $attachment = $this->getOwningCard($event->theah);
            $event->theah->queueEvent(EventFactory::createReactionTransitionEvent(
                $attachment->ControllerId, $attachment->Id, $this->Id
            ));
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == "doEffect")
        {
            $this->setUsed($game->theah, true);
            // ...
            // Engage cost (very common on attachment reactions):
            $attachment = $this->getOwningCard($game->theah);
            $game->theah->queueEvent(EventFactory::createCardEngagedEvent(
                $attachment->ControllerId, $attachment->Id, $attachment->Id, $this->Id
            ));
        }

        $game->gamestate->nextState("done");
    }
}
```

`CardReaction::setUsed` resets at dusk automatically.

If the reaction *cancels* the trigger event and re-queues it later, see `Reaction_01181`'s `releaseEvent` / `skipNextEvent` mechanism — necessary when the reaction needs to interpose itself between the trigger and the original event's processing without infinitely looping.

## Pattern E — Steady-State Override (NOT event mutation)

**For properties that are read at fixed points in the game flow, override the corresponding `Card::get*` method. Do NOT mutate globals from `handleEvent`.**

`_03cd05`'s `+1 gamble reveal` was initially drafted as `handleEvent(EventGambleSetup)` mutating a count global. Rejected on review. The right pattern:

```php
public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
{
    $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);

    if ($this->isAttached() && $actor->Id == $this->AttachedToId)
    {
        $count += 1;
        $explanations[] = sprintf(
            $theah->game->translate("%s reveals +1 card when Gambling."),
            $this->getInjectCode()
        );
    }

    return $count;
}
```

Why: `Theah::getNumberOfGambleCardsToReveal` iterates every card in play and sums their contributions. The count is a *steady-state property* of the play area — it should be recomputed from scratch each gamble setup, not stored transiently. Established by Sarafina (`_01010`), Ivy (`_02042`), Roll the Bones (`_01114`).

The same principle applies anywhere `Card` exposes a `get*` hook (pressure tallies via `UtilitiesTrait::pressureLocation`, cost discounts, etc.). When in doubt, grep for whether `Theah` iterates cards summing a `get*` call — if so, override; don't mutate.

## Pattern F — Custom State Inserted Into the Core Flow

If the attachment needs to prompt the equipped character's controller *during* an existing core state (mid-duel, mid-pressure, etc.), and that core state is a `game`-type (auto-running) state, you cannot stuff a player choice into it directly. The framework forbids interactive logic in game states.

**`_03cd05` pattern — splitting setup from execution:**

1. Add a new `<thing>Setup` game state *before* the auto state.
2. Add an immediately-following `<thing>SetupEvents` state that runs queued events (transitions, reactions, pay-for-reaction).
3. Reroute *all* existing transitions into the original auto state to point at the new setup state instead.
4. Have the original auto state run as before; the new setup states provide the choice window.

For `_03cd05` this looked like:

```
* → DUEL_GAMBLE_SETUP (game, stDuelGambleSetup: queues EventGambleSetup)
  → DUEL_GAMBLE_SETUP_EVENTS (game, stRunEvents)
       ↳ "03cd05" → DUEL_GAMBLE_SETUP_03CD05 (activeplayer: top/bottom choice)
       ↳ "reaction" → DUEL_GAMBLE_SETUP_REACTIONS
       ↳ "pay"      → DUEL_GAMBLE_SETUP_PAY_FOR_REACTION
       ↳ "endOfEvents" → DUEL_GAMBLE_REVEALED  ← original entry point
```

Four prior transitions to `DUEL_GAMBLE_REVEALED` were rerouted to `DUEL_GAMBLE_SETUP`:
- `DUEL_CHOOSE_ACTION.chooseGambleCard`
- `DUEL_COMBAT_CARD_EVENTS["01135"]`
- `DUEL_SET_NEXT_COMBAT_CARD.rollTheBones`
- `DUEL_CHOOSE_GAMBLE_CARD_EVENTS["01135"]`

**Mint a new `EventXxxSetup` event** that the attachment listens for. Carries `actorId` (the participating character) and `playerId` (their controller). Follow the shape of `EventDuelAttemptGamble` — minimal fields, no-op handler in `EventHub`, registered in `Events::XxxSetup`, factory in `EventFactory::createXxxSetupEvent`.

**Mint matching globals where needed** for per-trigger choices (e.g. `Game::GAMBLE_REVEAL_FROM_BOTTOM`). Clear them in the matching cleanup state (`stDuelEndOfRound` for duel-scoped flags).

**Defensively reset on the default branch.** If `id == 1` (default/top), explicitly set the global to `false` — don't just "leave it alone." A previous round may have left it `true`, and you want each new gamble to start clean.

### Player-choice state class

State files live in `modules/php/States/<expansion>/` (per the Penya/Chance Meeting pattern). Example for `_03cd05`:

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\States\faf;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelGambleSetup_03cdNN extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: States::DUEL_GAMBLE_SETUP_03CDNN,
            type: StateType::ACTIVE_PLAYER,
            name: "duelGambleSetup_03cdNN",
            description: clienttranslate('${actplayer} is choosing options from <card name>.'),
            descriptionMyTurn: clienttranslate('Card Name') . clienttranslate(': ${you} may ...'),
            transitions: [
                "" => States::DUEL_GAMBLE_SETUP_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array { return $this->game->argsForState(); }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void { $this->game->actFromCardWithId($id); }

    public function zombie(int $playerId): void { $this->game->gamestate->nextState(); }
}
```

The card class's `actFromCardWithId` interprets the `$id` (1 = default, 2+ = chosen options) and writes the global. Zombie players fall through to `nextState()` without setting the global — defaults to the safe-baseline behavior.

### State ID convention

Follow the faf pattern. New duel-flow states use a duel-scoped prefix; new high-drama-action states use `403XXXX` for expansion 3 (4 = high drama, 03 = expansion, XX = card number). `_03cd05`'s setup state constant is `States::DUEL_GAMBLE_SETUP_03CD05`.

### JS wiring is required

Without JS, the new state activates server-side but the player sees nothing. For an attachment that adds a duel-setup prompt:
- `modules/js/OnUpdateActionButtons.<expansion>.js` — render the choice buttons (e.g. "Reveal from Top" / "Reveal from Bottom").
- Ensure the expansion's JS file is included from the master `OnUpdateActionButtons.js` chain.

For action-flow states (high-drama actions), additionally wire:
- `OnEnteringState.<expansion>.js` — selection setup.
- `OnLeavingState.<expansion>.js` — selection teardown.
- `PlayerActions.js` — extend the client-side action map if you reuse an existing client action like `onMusterCardSelected`.

## Pattern G — Forced Once-Per-Day Cancel of Opponent Risks

Card text shape: "**Forced:** Each Day, the first time an opponent's Risk targets the equipped character • Cancel the effects." Canonical: `_03cd21` (Silver Spine), modeled on `_01186` (Maryam — same shape but on a `CityCharacter`).

A Risk that targets a character can fire one of five event types. You must intercept all five:

| Event | Target field | Notes |
|---|---|---|
| `EventCardMoved` | `cardId` | Risk moves the character. |
| `EventCardEngaged` | `cardId` | Risk engages the character. |
| `EventChallengeIssued` | `defenderId` | Risk issues a challenge against the character. |
| `EventCharacterBeingWounded` | `characterId` | Risk wounds the character. |
| `EventAttachmentEquipping` | `characterId` | Risk equips a (hostile) attachment onto the character. **Requires a manual discard branch — see below.** |

### Skeleton (adapt from `_03cd21`)

```php
public function handleEvent(Event $event)
{
    if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
        (($event instanceof EventCardMoved && $event->cardId == $this->AttachedToId && $event->sourceId != 0) ||
        ($event instanceof EventCardEngaged && $event->cardId == $this->AttachedToId && $event->sourceId != 0))
    ) {
        if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId)) {
            $this->markAbilityUsed($event->theah->game);
            $event->canceled = true;
            return;
        }
    }
    // ... repeat for EventChallengeIssued (defenderId), EventCharacterBeingWounded (characterId),
    //     EventCharacterTargeted (targetId), and EventAttachmentEquipping (characterId — with
    //     manual discard, see below).

    parent::handleEvent($event);

    if ($event instanceof EventDuskEndOfDay && $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED)) {
        $this->removeCondition(Game::SILVER_SPINE_ABILITY_USED);
        $event->theah->game->notify->all("silverSpineAbilityRemoved", "", ["cardId" => $this->Id]);
    }
}

private function isOpponentRiskTargetingCharacters(Event $event, int $sourceId): bool
{
    $source = $event->theah->getCardById($sourceId);
    return $source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters
        && $source->ControllerId != $this->ControllerId;
}
```

### Critical adapters when porting Maryam's character-side pattern to an attachment

1. **Target field = `$this->AttachedToId`**, not `$this->Id`. The trigger fires when the *equipped character* is the target, not the attachment itself.
2. **`isAttached()` guard at the top of every branch.** While the attachment is in the city deck / discard / itself being equipped, it has no protected character — the trigger must no-op. Without this guard, `$this->AttachedToId == 0` matches "card id 0" coincidentally and you get spurious cancels.
3. **`$source->ControllerId != $this->ControllerId`** for "opponent's." On a city attachment, `ControllerId` mirrors the equipped character's controller, so this naturally reads as "Risk played by someone other than the player who controls the equipped character." Skip this check only if the card says "any Risk" (Maryam) instead of "opponent's Risk."
4. **`Conditions` live on the attachment, not on the character.** The once-per-Day is a property of *the artifact*. If it's unequipped and re-equipped mid-day, the spent state travels with the card — which is the rules-correct read.

### `EventAttachmentEquipping` needs a manual discard branch

When you cancel `EventAttachmentEquipping`, the matching `EventAttachmentEquipped` never fires — so the would-be attachment is left in limbo (DB says it's at the character's location, but in-memory state never finalized). Discard it by hand inside the same branch:

```php
$attachment = $event->theah->getCardById($event->attachmentId);
if ($attachment) {
    $removedEvent = EventFactory::createCardRemovedFromPlayEvent($event->playerId, $attachment->Id, $attachment->Location);
    $event->theah->queueEvent($removedEvent);

    if ($attachment instanceof CityAttachment) {
        $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($event->playerId, $attachment->Id, $attachment->Location);
        $event->queueEvent($discardEvent);
    } else {
        $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location);
        $event->queueEvent($discardEvent);
    }
}
```

`Reaction_02048` (Blood Like Winter) and `_01186` use the identical CityAttachment-vs-faction split — keep them in sync if you change it. The 2026-04-14-04 RiskClone journal documents a related bug where `createCardInLocation` cards didn't land in `Theah::$cards` and the discard branch silently desynced; that fix is now in `Action_02008`, but be aware if you mint a new "Risk that equips a clone" mechanic.

### `sourceId` nullability across the five events

`EventAttachmentEquipping::sourceId` is `?int`; the other four are `int`. `$event->sourceId != 0` works for both — in PHP loose comparison, `null != 0` is `false`, so null-source events filter out (no Risk = nothing to cancel). Keep this idiom; the strict variant adds a `!== null && !== 0` for no behavior change.

### Five-file UI plumbing for the once-per-Day condition + chip

Maryam, Carmella, and Silver Spine all share this dance. Pick a `<CARD>_ABILITY_USED` condition name and replicate it in:

1. **`modules/php/Game.php`** — `final const <CARD>_ABILITY_USED = "<Display Name>";` near the other condition consts.
2. **`seventhseacityoffivesails.js`** — `this.<CARD>_ABILITY_USED = '<Display Name>';` near the other condition aliases.
3. **`modules/js/Notifications.js`** — add two entries to the notif-list (`['<card>AbilityUsed', 500], ['<card>AbilityRemoved', 500]`) and two handler functions (`notif_<card>AbilityUsed` / `notif_<card>AbilityRemoved`).
4. **`modules/js/Utilities.js`** — add a chip-render block inside `createAttachmentCard` (or `createCharacterCard` for character-side patterns). **There is no generic conditions loop for attachments — you must add the `if (attachment.conditions?.includes(this.<CARD>_ABILITY_USED)) { ... }` block by hand.** Without it, the chip is missing on page refresh after the ability has been used.
5. **`seventhseacityoffivesails.css`** — `._7sfs-<card>-ability-used-chip` class with a 25x25 background-cropped chip image and an anchor position.

### Chip-removal id mismatch — DO NOT copy verbatim from Maryam/Carmella

Maryam and Carmella both have a latent bug where the chip is *placed* with `` `${card.divId}_<card>_ability_used` `` (full DOM id, e.g. `${controllerId}-${cardId}`) but *removed* with `` `${args.cardId}_<card>_ability_used` `` (bare card id). `dojo.destroy` silently no-ops when the id doesn't exist. They get away with it because the dusk-end-of-day flow often redraws the play area; new patterns will not. **Use `card.divId` in both placement AND removal:**

```js
// placement (notif_*AbilityUsed)
const id = `${card.divId}_<card>_ability_used`;
// removal (notif_*AbilityRemoved) — MUST also use card.divId
const id = `${card.divId}_<card>_ability_used`;
dojo.destroy(id);
```

### Chip CSS position when chip lives on an attachment

Attachments are splayed under the equipped character via `._7sfs-attached-card { left: calc(var(--attachment-index) * -15px); }`. Only the leftmost ~15px strip of each un-splayed attachment is visible. Anchor your chip at `left: 0; top: 0; z-index: 20;` so it stays visible whether splayed or not. Copying Carmella's `left: 80px; top: 30px;` (designed for a full character card) will hide the chip entirely under the next attachment in the splay.

### `getInjectCode()` for notify messages

When announcing "this card cancels X," use `${card_inject_code}` in `clienttranslate` and pass `"card_inject_code" => $this->getInjectCode()` in the notify args. Gives the player a clickable card-name reference in the log. `Card::getInjectCode()` is defined on the base — works on every card type.

## Cross-Cutting: Common Helpers

- `getCardsOnTopOfPlayerFactionDeck($playerId, $nbr)` — top of a faction deck, reshuffle-aware.
- `getCardsOnBottomOfPlayerFactionDeck($playerId, $nbr)` — added for `_03cd05`. BGA Deck library has no native bottom helper; this one sorts `card_location_arg` ASC and slices the first `$nbr`. Lower `card_location_arg` = bottom, higher = top (confirmed in `_ide_helper.php`).
- `insertCardOnExtremePosition($card, $location, $bOnTop)` — `bOnTop = true` means "place on top." Sinking after a top-reveal passes `false`. Sinking after a bottom-reveal passes `true` (cards sink to the top). **Variable-name landmine:** `$fromBottom` happens to align numerically with `$bOnTop`, but the semantics are unrelated — comment it where you pass it.

## Pre-Commit Hook (relevant subset)

`.githooks/pre-commit` enforces, for files you touch in the attachment patterns above:

| Pattern | Required |
|---|---|
| `extends AttachmentAction/CardAction/...` | `createActionResolvedEvent()` somewhere in the class |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed()` AND `$this->isAvailable()` |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()` *in the same file* (concerns the Action that performs the equip, not the attachment being equipped) |
| **Forbidden in `AttachmentAction` subclasses** | `setUsed` / `resetPlayerPassCount` / `announceAction` — these run centrally |
| `implements ISorcererAbility` | both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` |

When the attachment merely *reacts* to `EventAttachmentEquipped` (as `_03cd05` does on equip-wound), you are not *creating* the event — the `getRequiredAttachTargetId` rule does not apply.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Card class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action: `...\cards\<expansion>\actions`
  - Reaction: `...\cards\<expansion>\reactions`
  - State: `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`

## Reference Implementations

| Card | What it demonstrates |
|---|---|
| `modules/php/cards/faf/_03cd05.php` + `States/faf/State_duelGambleSetup_03cd05.php` | **Canonical CityAttachment.** Forced wound on equip via `handleEvent` listening for `EventAttachmentEquipped`. Steady-state `getNumberOfGambleCardsToReveal` override (rather than global mutation). New `EventGambleSetup` event and a `DUEL_GAMBLE_SETUP` state pair inserted before the existing auto-state to enable a mid-duel player choice. `actFromCardWithId` on the card class dispatches by state. |
| `modules/php/cards/_7s5s/_01198.php` | Passive grant ("equipped character gains Duelist") via paired `EventAttachmentEquipped` / `EventAttachmentUnequipped` handling. |
| `modules/php/cards/tac/_02047.php` | Same passive-grant pattern in a `FactionAttachment` (sibling base class) — useful template even though not a CityAttachment. |
| `modules/php/cards/_7s5s/_01187.php` + `actions/Action_01187.php` | Simple `AttachmentAction` with a "destroy this card" cost — useful template for the unequip + discard pair. |
| `modules/php/cards/_7s5s/_01191.php` + `actions/Action_01191.php` | `AttachmentAction` with the `isAttached()` guard before the destroy step (important for copied effects). |
| `modules/php/cards/_7s5s/_01198.php` + `actions/Action_01198.php` | City Action on a CityAttachment that issues a challenge. |
| `modules/php/cards/_7s5s/_01181.php` + `reactions/Reaction_01181.php` | `AttachmentReaction` with cancel+release+skipNextEvent pattern. Engage-cost reaction. |
| `modules/php/cards/_7s5s/_01075.php` | Tabard of the Fallen Musketeer — established the precedent of Forced abilities in attachment `handleEvent` (not via Reaction). |
| `modules/php/cards/faf/_03cd21.php` | **Canonical Pattern G CityAttachment.** "Forced: each Day, the first time an opponent's Risk targets the equipped character • cancel the effects." Intercepts all five Risk-targeting event types, filters by `$source->ControllerId != $this->ControllerId`, manual discard for the `EventAttachmentEquipping` branch, once-per-Day condition cleared at `EventDuskEndOfDay`. Full chip + tooltip plumbing across PHP/JS/CSS. |
| `modules/php/cards/_7s5s/_01186.php` | Maryam Benu Pleroma — same five-event Pattern G shape on a `CityCharacter`. Use as the side-by-side reference when porting to an attachment. Note: contains the chip-id-mismatch bug — do not copy the removal handler verbatim. |
| `modules/php/cards/tac/reactions/Reaction_02048.php` | Blood Like Winter — a `RiskReaction` variant of Pattern G with a player-chosen pressure response. `isFromOpponentRiskThatTargetsCharacters` is the canonical "opponent's Risk" filter. |
| `modules/php/cards/Attachment.php` | The base class — read for helpers: `isAttached`, `attachedTo`, `getRequiredAttachTargetId`, `canAttachTo`. |

## When You Finish

1. Re-read the card text and walk through each clause. Each clause should map to exactly one pattern (Forced / Passive / Action / Reaction / Steady-state / Custom-state). Stat modifiers go on the constructor and are not a "pattern."
2. If you added a custom state, verify all three are present: the class file in `modules/php/States/<expansion>/`, the constant in `States.php`, and the transition entry in `states.inc.php`. Confirm any rerouted transitions still match the new entry point.
3. If you minted a new event class, register it in `Events.php` (constant), `EventHub.php` (handler — usually no-op), and `EventFactory.php` (factory method).
4. If you minted a new global, clear it in the matching cleanup state (e.g. duel-scoped globals clear in `stDuelEndOfRound`).
5. Verify JS wiring for any new state you added.
6. Mentally run the pre-commit hook checks against the files you touched. Especially: `createActionResolvedEvent` in actions, `setUsed`/`isAvailable` in reactions, no `setUsed` in `AttachmentAction` subclasses.
7. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md` covering the WHY: which existing patterns/flags you reused, what alternatives you considered, anything that looks weird (variable-name landmines, defensive resets, event-priority gotchas). Read the related faf journals first — they encode hard-won knowledge about edge cases (zombie handling, copied-action crashes, event ordering inside handleEvent).
