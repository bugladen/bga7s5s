# 06 — Actions

← [[05 — Passives and Forced|FactionAttachment Guide Passives And Forced]] · [[Index|Implementing a FactionAttachment Card]] · Next: [[07 — Reactions|FactionAttachment Guide Reactions]]

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

The equipped character is the performer. `AttachmentAction` already defaults performers to the owning character.

## Skeleton (immediate resolve)

Use this when the effect needs **no further pick** (no target, no location list):

```php
class Action_NNNNN extends AttachmentAction
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

        // Engage-this-card gate example:
        $attachment = $this->getOwningCard($game->theah);
        if ($attachment->Engaged)
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

Mirror: Lodestone `Action_03065` (sink + move Home, **no** GameState).

## Do **not** call these from AttachmentAction

They run centrally during action confirmation:

- `setUsed`
- `announceAction`
- `resetPlayerPassCount`

The pre-commit hook **does** require `createActionResolvedEvent()` somewhere in the class.

## Engage costs — parse literally

| Printed cost | Engage id | Availability |
|---|---|---|
| Engage the **equipped character** | `$owner->Id` | `!$owner->Engaged` |
| Engage **this card** | `$attachment->Id` | `!$attachment->Engaged` |

When the effect also **moves** the character, pass `engage=false` on `createCardMovingEvent` — the printed engage (or sink) was already the cost.

## "Sink this card" (equipped attachment)

**Sink** means put the attachment on the **bottom of its owner's faction deck** — not the locker, not discard.

Canonical chain (Dame of Swords `Technique_02055`, Lodestone `Action_03065`):

```php
$event->theah->queueEvent(EventFactory::createAttachmentUnequippedEvent(
    $attachment->ControllerId, $owner->Id, $attachment->Id
));
$event->theah->queueEvent(EventFactory::createCardRemovedFromPlayEvent(
    $attachment->ControllerId, $attachment->Id, $attachment->Location
));
$event->theah->queueEvent(EventFactory::createCardAddedToFactionDeckEvent(
    $attachment->OwnerId, $attachment->Id, false  // false = bottom
));
```

Queue **unequip first** so while-equipped conditions clear before later effects. Use **`OwnerId`** for the faction-deck sink.

## Choose-location Actions (need a GameState)

When text is "Move the equipped character to a location where …":

1. On `EventActionTriggered` → `createTransitionEvent($attachment->ControllerId, $attachment->Id, "NNNNN", $this->Id)`.
2. **`sourceId` must be the attachment id** (not the character) so `getActionById` finds this Action.
3. Add a High Drama GameState + JS — see [[09|FactionAttachment Guide Wiring States And JavaScript]].
4. Prefer paying engage **on location resolve** (with the move), not at announce time.

Mirror: Syrneth Compass `_03055`.

### Destination gotchas

| Printed wording | Destinations |
|---|---|
| "City location" | City slots only |
| "a location" (no City) | Include `Game::LOCATION_PLAYER_HOME` when the filter can match there |

If **this attachment itself** carries the trait the destination filter looks for (e.g. Compass is an Artifact), **exclude the current location** or the list always includes a no-op stay.

**Home in JS:** when `locationIds` may include Home, call `makeHomeEndcapMarkerSelectable()` — do not copy city-only enter handlers that only call `makeCityLocationSelectable`.

### "Available or equipped"

Parenthetical "(may be available or equipped)" means check **both**:

- Unattached at the location: `$theah->getAvailableAttachmentsAtLocation($location)`
- Equipped on characters at the location: walk `$character->Attachments` (skip `FakeAttachment`)

## Challenge-issuing City Actions

Some attachments' City Action **issues a challenge** (Guild Triskelion `_01198`). Treat it like a Character Challenge Action for the challenge hand-off, but keep `extends AttachmentAction`. Open the Character challenge guide alongside the Triskelion files when you hit this.

## Checklist for this pattern

- [ ] `extends AttachmentAction` (not EventCityAction, not CharacterAction)
- [ ] City Actions gate `cardInCity`
- [ ] No `setUsed` / `announceAction` / `resetPlayerPassCount`
- [ ] Terminal path queues `createActionResolvedEvent`
- [ ] Immediate effects have **no** invented GameState
- [ ] Picker Actions use attachment id as transition `sourceId`
- [ ] Sink uses unequip → removeFromPlay → faction-deck bottom

## Next

Triggered Reactions → [[07 — Reactions|FactionAttachment Guide Reactions]]
