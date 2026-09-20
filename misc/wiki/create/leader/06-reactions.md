# 06 — Reactions (Pattern D)

← [05 — Actions](05-actions.md) · [Index](README.md) · Next: [07 — Techniques](07-techniques-maneuvers.md)

A **Reaction** (or **City Reaction**) waits for a trigger event, then prompts the controlling player with buttons (Pass, pick a target, etc.).

Good news for beginners: **most button Reactions need no custom state class and no JS wiring.**

File: `modules/php/cards/<expansion>/reactions/Reaction_NNNNN.php`  
Extends: `CardReaction`

## Wire it on the card class

```php
class _NNNNN extends Leader implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        // ...
        $this->Reactions = [ new Reaction_NNNNN() ];
    }
}
```

## How a Reaction fires

1. Something happens → an event is created (`EventSorcererAbilityPlayed`, `EventCardMoved`, …).
2. Your Reaction's `handleEvent` sees it, checks gates, and queues `createReactionTransitionEvent(...)`.
3. The game shows buttons from `getReactionButtonProperties`.
4. The player clicks → `performReaction` runs the effect.

## Required gates in `handleEvent`

Check these in order:

1. **`$this->isAvailable()`** — Reactions reset at end of day; this stops double-fires.
2. **Identity** — is this event about *my* Leader? (`sourceId` / `performerId` / `cardId` / …)
3. **City scope** — if the text says City Reaction: `$theah->cardInCity($owner)`
4. **Valid targets exist** — if the effect needs a target, confirm at least one exists **before** prompting. Otherwise the player only gets a useless Pass.

## Cesca: after Sorcerer ability → wound opposing

Trigger: `EventSorcererAbilityPlayed`

Identity check (both fields):

```php
if ($event->sourceId != $cesca->Id && $event->performerId != $cesca->Id) {
    return;
}
```

`sourceId` = card whose ability fired. `performerId` = character performing it. Checking both covers "ability on Cesca" and "Cesca performing a sorcery from elsewhere."

Then queue:

```php
$transition = EventFactory::createReactionTransitionEvent(
    $cesca->ControllerId,
    $cesca->Id,
    $this->Id
);
$event->theah->queueEvent($transition);
```

Buttons: one per opposing character at her location, plus Pass.  
On accept: queue a wound event. On Pass: return **without** `$this->setUsed(...)`.

Full file: `modules/php/cards/faf/reactions/Reaction_03001.php`.

## `setUsed` and `isAvailable` (pre-commit)

Every `CardReaction` subclass must contain the literal strings:

- `$this->setUsed(`
- `$this->isAvailable(`

Call `setUsed($theah, true)` when the player **accepts** an effect.  
Do **not** call it on Pass (so they can still react later the same day if another trigger happens — unless the design is Continuous; see below).

## Continuous Reactions

Some Reactions may fire every time the trigger happens (no daily `Used` lock). Runtime: omit `setUsed(true)`.  
Pre-commit still greps for `$this->setUsed(` — keep that literal inside a comment explaining Continuous behavior.

Reference: Angeline `Reaction_03025`, Ekaterina `Reaction_03049`.

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
| Ordinary Reaction that *mentions* a Sorcerer ability | No (Cesca, Desideria `04003b`) |

## Paying Wealth inside a Reaction

Do **not** route through `PAY_STATE_PLAY_BRUTE` (tied to the player-turn cycle). Track paid cards inside the Reaction and discard atomically on finalize. Reference: Don Constanzo `Reaction_03003`.

## Cancel-and-reissue

Some Reactions cancel an automatic event (for example Dusk move-Home) and ask the player whether to keep or decline. That uses `$event->canceled = true`, a cloned event, and `cancelDeclinedByCardIds` so Decline does not loop. Reference: Ise `Reaction_03016a`.

## Next

Duel abilities → [07](07-techniques-maneuvers.md) · Finish → [10](10-checklist.md)
