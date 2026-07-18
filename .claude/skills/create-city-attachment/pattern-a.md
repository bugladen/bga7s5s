> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

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
