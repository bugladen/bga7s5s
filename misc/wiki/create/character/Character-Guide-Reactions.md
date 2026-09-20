# 06 — Reactions (Pattern D)

← [[05 — Actions|Character Guide Actions]] · [[Index|Implementing a Character Card]] · Next: [[07 — Techniques|Character Guide Techniques And Maneuvers]]

A **Reaction** (or **City Reaction**) waits for a trigger event, then prompts the controlling player with buttons (Pass, pick a target, etc.).

Good news for beginners: **most button Reactions need no custom state class and no JS wiring.**

File: `modules/php/cards/<expansion>/reactions/Reaction_NNNNN.php`  
Extends: `CardReaction`

## Wire it on the card class

```php
class _NNNNN extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        // ...
        $this->Reactions = [
            new Reaction_NNNNNa(),
            new Reaction_NNNNNb(),
        ];
    }
}
```

Ise wires both Dusk opt-out and "enemy moves here" this way — see `_03016.php`.

## How a Reaction fires

1. Something happens → an event is created (`EventCardMoved`, `EventSorcererAbilityPlayed`, …).
2. Your Reaction's `handleEvent` sees it, checks gates, and queues `createReactionTransitionEvent(...)`.
3. The game shows buttons from `getReactionButtonProperties`.
4. The player clicks → `performReaction` runs the effect.

## Required gates in `handleEvent`

Check these in order:

1. **`$this->isAvailable()`** — Reactions reset at end of day; this stops double-fires.
2. **Identity** — is this event about *my* Character? (`sourceId` / `performerId` / `cardId` / …)
3. **City scope** — if the text says City Reaction: `$theah->cardInCity($owner)`
4. **Valid targets exist** — if the effect needs a target, confirm at least one exists **before** prompting. Otherwise the player only gets a useless Pass.

## Ise: after enemy moves here → pull a friendly

Trigger: `EventCardMoved` (past tense — the move already happened).

Typical gates:

```php
// not self
$event->cardId != $owner->Id
// arrived at my location
$event->toLocation == $owner->Location
$theah->cardInCity($owner)
// is a Character with a controller
$mover instanceof Character && $mover->ControllerId != 0
// enemy
$mover->ControllerId != $owner->ControllerId
// and at least one friendly can be moved here
```

Then queue:

```php
$transition = EventFactory::createReactionTransitionEvent(
    $owner->ControllerId,
    $owner->Id,
    $this->Id
);
$event->theah->queueEvent($transition);
```

Buttons: one per eligible friendly, plus Pass.  
On accept: queue `createCardMovingEvent(..., $engage=false, …)` when Engage is not printed.  
On Pass: return **without** `$this->setUsed(...)`.

Full file: `modules/php/cards/faf/reactions/Reaction_03016b.php`.

For "after **a** character moves here" (allies count too), omit the enemy-controller check — Soline `Reaction_03040`.

## `setUsed` and `isAvailable` (pre-commit)

Every `CardReaction` subclass must contain the literal strings:

- `$this->setUsed(`
- `$this->isAvailable(`

Call `setUsed($theah, true)` when the player **accepts** an effect.  
Do **not** call it on Pass (so they can still react later the same day if another trigger happens — unless the design is Continuous; see below).

## Continuous Reactions

Some Reactions may fire every time the trigger happens (no daily `Used` lock). Runtime: omit `setUsed(true)`.  
Pre-commit still greps for `$this->setUsed(` — keep that literal inside a comment explaining Continuous behavior.

Reference: Angeline `Reaction_03025`, Ekaterina `Reaction_03049`, Aimée `Reaction_04021`.

## En Garde Reaction

Prefix means **unengaged precondition**:

```php
if ($owner->Engaged) {
    return;
}
```

Do not Engage them unless Engage is printed as a cost.

## Sorcerer keyword

| Printed | Implement `ISorcererAbility`? |
|---|---|
| **Sorcerer** Reaction / Sorcerer City Reaction | Yes — also call start + played events |
| Ordinary Reaction that *mentions* a Sorcerer ability | No |

## Paying Wealth inside a Reaction

Do **not** route through `PAY_STATE_PLAY_BRUTE` (tied to the player-turn cycle). Track paid cards inside the Reaction and discard atomically on finalize. Reference: Don Constanzo `Reaction_03003`, Tomas `Reaction_04013`.

## Cancel-and-reissue (Ise Dusk opt-out)

Some Reactions cancel an automatic event (for example Dusk move-Home) and ask the player whether to keep or decline:

1. Listen on the *pre*-event (`EventCardMoving`) with `sourceId == 0` (auto-emitter) + phase gate.
2. Set `$event->canceled = true`.
3. Prompt Keep / Decline.
4. On Decline, re-queue a clone with `cancelDeclinedByCardIds[] = owner->Id` so your Reaction does not catch it again.

Reference: Ise `Reaction_03016a`.

## Next

Duel abilities → [[07|Character Guide Techniques And Maneuvers]] · Finish → [[10|Character Guide Finish Checklist]]
