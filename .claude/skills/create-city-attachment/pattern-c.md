> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

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
