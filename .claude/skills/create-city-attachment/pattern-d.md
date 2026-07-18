> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

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
