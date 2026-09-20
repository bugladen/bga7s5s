# 06 — Reactions

← [[05 — Actions|CityAttachment Guide Actions]] · [[Index|Implementing a CityAttachment Card]] · Next: [[07 — Steady-state|CityAttachment Guide Steady State And Custom States]]

When the printed text has **Reaction** or **City Reaction**, create an `AttachmentReaction` class and register it on the card.

## File and base class

```
modules/php/cards/<expansion>/reactions/Reaction_NNNNN.php
```

```php
class Reaction_NNNNN extends AttachmentReaction
```

`AttachmentReaction` adds `ownerIsAttached(Theah)` so you can bail out when the parent attachment is not equipped.

## Default gate: must be equipped

The event hub dispatches events to **all** tracked cards. Attachment Reactions should only fire while equipped:

```php
if (! $this->isAvailable()) return;
if (! $this->ownerIsAttached($event->theah)) return;
```

## Pre-commit literals (required)

Somewhere in the Reaction class body the hook must see:

- `$this->setUsed(`
- `$this->isAvailable(`

`ownerIsAttached` is not always linted the same way as FactionAttachment, but you should still call it — detached attachments must not offer Reactions.

## Skeleton (adapted from Sorte Deck `Reaction_01181`)

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
            && ! $event->canceled
            && $this->ownerIsAttached($event->theah)
            && $this->isAvailable())
        {
            $attachment = $this->getOwningCard($event->theah);
            $event->theah->queueEvent(EventFactory::createReactionTransitionEvent(
                $attachment->ControllerId, $attachment->Id, $this->Id
            ));
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'doEffect')
        {
            $this->setUsed($game->theah, true);

            $attachment = $this->getOwningCard($game->theah);
            // Engage cost (common on attachment reactions):
            $game->theah->queueEvent(EventFactory::createCardEngagedEvent(
                $attachment->ControllerId, $attachment->Id, $attachment->Id, $this->Id
            ));
            // … effect …
        }

        $game->gamestate->nextState('done');
    }
}
```

`CardReaction::setUsed` resets at dusk automatically. Pass should **not** mark used.

## Engage as a cost

If text says "engage this card":

1. Gate the **offer** on `!$attachment->Engaged` (and `isAvailable`).
2. Queue `createCardEngagedEvent` on accept in `performReaction`.

### Multi-stage warning

If a later stage still needs another reaction transition, **do not** `setUsed` on the first Engage click. Defer `setUsed` until finalize after the last stage. (Faction examples: Matushka's Shears, Torres Cloak — same rule applies here.)

## Cancel + re-queue

If the reaction *cancels* the trigger and re-queues it later, study `Reaction_01181`'s `releaseEvent` / `skipNextEvent` mechanism. Blind cancel without that pattern can loop or drop the original event.

## Checklist for this pattern

- [ ] `extends AttachmentReaction`
- [ ] Literals: `setUsed`, `isAvailable`
- [ ] Equipped gate via `ownerIsAttached`
- [ ] Multi-stage: deferred `setUsed` until finalize
- [ ] Engage cost parses "this card" vs "equipped character" correctly

## Next

Steady-state bonuses and mid-flow custom states → [[07 — Steady-state and custom states|CityAttachment Guide Steady State And Custom States]]
