# 05 — Actions

← [[04 — Passives and Forced|CityAttachment Guide Passives And Forced]] · [[Index|Implementing a CityAttachment Card]] · Next: [[06 — Reactions|CityAttachment Guide Reactions]]

When the printed text has **Action** or **City Action**, create an `AttachmentAction` class and register it on the card.

## File and base class

```
modules/php/cards/<expansion>/actions/Action_NNNNN.php
```

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
```

**There is no `AttachmentCityAction`.** City Action = `extends AttachmentAction` plus `$theah->cardInCity($owner)` in `isAvailableToPlayer`.

The equipped character is the performer. `AttachmentAction` already defaults performers to the owning character, and `isAvailableToPlayer` already requires a non-null owning character.

## Skeleton (immediate resolve)

Use this when the effect needs **no further pick**:

```php
class Action_03cdNN extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Short action name');
    }

    public function isAvailableToPlayer(Game $game, int $playerId, ?CardAbility $triggeredBy = null): bool
    {
        if (! parent::isAvailableToPlayer($game, $playerId, $triggeredBy))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($game->theah);
        if ($owner === null)
        {
            return false;
        }

        // City Action gate:
        if (! $game->theah->cardInCity($owner))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $owner      = $this->getOwningCharacter($event->theah);

            // … pay costs, apply effects …

            $event->theah->queueEvent(
                EventFactory::createActionResolvedEvent($owner->ControllerId)
            );
        }
    }
}
```

Mirror: Smuggled Item `Action_01187`, Duckfoot Pistol `Action_01191`, Guild Triskelion `Action_01198` (challenge hand-off).

## Do **not** call these from AttachmentAction

They run centrally during action confirmation:

- `setUsed`
- `announceAction`
- `resetPlayerPassCount`

The pre-commit hook **does** require `createActionResolvedEvent()` somewhere in the class.

## "Destroy this card" cost (city discard)

If the action text reads "**Destroy this card** • …", queue unequip + **city discard**, guarded by `isAttached()` so copied effects do not crash:

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

**Why `isAttached()`?** Duckfoot Pistol `_01191` calls this out: the Action can be *copied*, and copies must not crash when the original is no longer equipped.

**City vs faction:** City Attachments destroy into the **city discard**. Do not copy FactionAttachment **sink** chains (bottom of faction deck) unless the printed text literally says sink to a faction deck.

## Engage costs — parse literally

| Printed cost | Engage id | Availability |
|---|---|---|
| Engage the **equipped character** | `$owner->Id` | `!$owner->Engaged` |
| Engage **this card** | `$attachment->Id` | `!$attachment->Engaged` |

## Choose-location / multi-step Actions

When the player must pick a location, card, or target:

1. On `EventActionTriggered` → `createTransitionEvent($controllerId, $attachment->Id, "NNNNN", $this->Id)`.
2. **`sourceId` must be the attachment id** so `getActionById` finds this Action.
3. Add a High Drama GameState + JS — see [[08|CityAttachment Guide Wiring States And JavaScript]].

## Challenge-issuing City Actions

Guild Triskelion `_01198` issues a challenge from an AttachmentAction. Treat the challenge hand-off like a Character Challenge Action, but keep `extends AttachmentAction`. Open Triskelion's Action files when you hit this.

## Checklist for this pattern

- [ ] `extends AttachmentAction` (not EventCityAction, not CharacterAction)
- [ ] City Actions gate `cardInCity`
- [ ] No `setUsed` / `announceAction` / `resetPlayerPassCount`
- [ ] Terminal path queues `createActionResolvedEvent`
- [ ] Destroy uses unequip + **city** discard, with `isAttached()` guard
- [ ] Picker transitions use attachment `sourceId`

## Next

Triggered Reactions → [[06 — Reactions|CityAttachment Guide Reactions]]
