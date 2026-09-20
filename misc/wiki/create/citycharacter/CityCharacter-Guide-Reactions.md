# 06 — Reactions (Pattern D)

← [[05 — Actions|CityCharacter Guide Actions]] · [[Index|Implementing a CityCharacter Card]] · Next: [[07 — Techniques|CityCharacter Guide Techniques And Maneuvers]]

A **Reaction** (or **City Reaction**) waits for a trigger event, then prompts the controlling player with buttons (Pass, pick a target, etc.).

Good news for beginners: **most button Reactions need no custom state class and no JS wiring.**

File: `modules/php/cards/<expansion>/reactions/Reaction_03cdNN.php`  
Extends: `CardReaction`

Canonical CityCharacter Reaction: **Julius Caligari** `Reaction_03cd10.php`.  
Branching "Choose one": **Kalla and Adelheide** `Reaction_03cd18.php`.

## Wire it on the card class

```php
class _03cdNN extends CityCharacter implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        // ...
        $this->Reactions = [ new Reaction_03cdNN() ];
    }
}
```

Create `reactions/` under the expansion folder if it does not exist yet.

## How a Reaction fires

1. Something happens → an event is created (`EventCharacterRecruited`, `EventCardMoved`, …).
2. Your Reaction's `handleEvent` sees it, checks gates, and queues `createReactionTransitionEvent(...)`.
3. The game shows buttons from `getReactionButtonProperties`.
4. The player clicks → `performReaction` runs the effect.

## Required gates in `handleEvent`

Check these in order:

1. **`$this->isAvailable()`** — stops double-fires within a day
2. **Identity** — is this event about *my* CityCharacter?
3. **City scope** — if the text is City Reaction / city-scoped: `$theah->cardInCity($owner)` **or** (for moves) `$theah->locationInCity($event->toLocation)`
4. **Valid targets exist** — if the effect needs a target, confirm at least one exists **before** prompting

## CityCharacter-friendly triggers

### After this character is recruited / mustered

`EventCharacterRecruited` fires after `ControllerId` is set. Recruitment does **not** change Location. Gate with `cardInCity($owner)` when the text is city-scoped.

### After this character moves to a city location

`EventCardMoved` has `runEventHubAfterCards = true`. Inside `handleEvent`:

- ✗ `$owner->Location` — still the **old** location
- ✓ `$event->toLocation` — the destination

Use `$theah->locationInCity($event->toLocation)` for the city gate. Pass the destination into helpers instead of reading `$owner->Location`.

By the time buttons render, the move has committed and `$owner->Location` is correct again.

## Multi-step buttons (no new state class)

Store stage on the Reaction object (`private string $stage`, …). Each click either advances the stage and re-queues a reaction transition, or resolves and calls `setUsed`.

```php
$transition = EventFactory::createReactionTransitionEvent(
    $owner->ControllerId,
    $owner->Id,
    $this->Id
);
$event->theah->queueEvent($transition);
```

**`< Back` rule:** only offer Back while previous stages were pure choices (no effect events queued). Once you queued a move / wound / destroy, omit Back on later stages — the engine cannot undo those events. Kalla's destroy stage does this.

## `setUsed` and `isAvailable` (pre-commit)

Every `CardReaction` subclass must contain the literal strings:

- `$this->setUsed(`
- `$this->isAvailable(`

Call `setUsed($theah, true)` when the player **accepts** a terminal effect.  
Do **not** call it on Pass (unless the design is Continuous — then keep the literal in a comment for the hook).

## What button Reactions do *not* need

- No new state classes
- No `states.inc.php` edits
- No `OnEnteringState` / `OnUpdateActionButtons` entries

Only promote to a real picker state if buttons are not enough (board highlighting, location clicks, …).

## Next

Duel abilities → [[07|CityCharacter Guide Techniques And Maneuvers]] · Finish → [[10|CityCharacter Guide Finish Checklist]]
