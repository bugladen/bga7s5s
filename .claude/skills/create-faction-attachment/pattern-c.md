> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern C — AttachmentAction

The character the attachment is equipped to performs the action. `AttachmentAction::getPerformersForAction` defaults to `[$this->getOwningCharacter($theah)]`, and `isAvailableToPlayer` already gates on the owning character being non-null.

**There is no `AttachmentCityAction` base class.** "City Action:" on an attachment = `extends AttachmentAction` plus an explicit `$theah->cardInCity($owner)` gate in `isAvailableToPlayer` (mirror `_01073`, `_01075`, `_02047`, `_03055`). Plain "Action:" may include Home.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;

class Action_0300N extends AttachmentAction
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

            // ... apply effect ...

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
```

### "Engage the equipped performer" vs "Engage this card"

Parse the printed cost literally:

| Printed cost | Engage target | Availability gate |
|---|---|---|
| "Engage the equipped performer" / "Engage the equipped character" | `$owner->Id` (character) | `$owner->Engaged` must be false |
| "Engage this card" | `$attachment->Id` | `$attachment->Engaged` must be false; character may already be engaged |

```php
// Engage the equipped performer
$engageEvent = EventFactory::createCardEngagedEvent(
    $owner->ControllerId, $owner->Id, $owner->Id, $this->Id
);

// Engage this card (the attachment) — Reaction_01022 / Action_03055
$engageEvent = EventFactory::createCardEngagedEvent(
    $attachment->ControllerId, $attachment->Id, $attachment->Id, $this->Id
);
```

When the effect also **moves** the equipped character, pass `engage=false` on `createCardMovingEvent` — the printed engage cost already went to the attachment (or was a separate character engage). Do not conflate move-with-engage.

### Immediate-resolve City Action (no picker)

When the printed effect needs **no further choice** (no target, no location, no list), resolve entirely on `EventActionTriggered` — do **not** invent a GameState / transition. Gate `cardInCity`, queue costs + effects + `createActionResolvedEvent`, done.

Canonical: **`Action_03065` (Lodestone)** — Sink this card • Move performer Home.

Tabard-style (`Action_01075`) that transitions into a shared pressure flow is different — that transition exists because pressure has its own multi-step machine. Lodestone has none.

### "Sink this card" cost (equipped attachment)

Printed **"Sink this card"** on an in-play faction attachment means put it on the **bottom of its owner's faction deck** — not the locker, not discard. Canonical sink chain (Dame of Swords `Technique_02055`, Lodestone `Action_03065`):

```php
$unequipEvent = EventFactory::createAttachmentUnequippedEvent(
    $attachment->ControllerId, $owner->Id, $attachment->Id
);
$removedEvent = EventFactory::createCardRemovedFromPlayEvent(
    $attachment->ControllerId, $attachment->Id, $attachment->Location
);
$sinkEvent = EventFactory::createCardAddedToFactionDeckEvent(
    $attachment->OwnerId, $attachment->Id, false  // false = bottom
);
```

Queue unequip first so while-equipped conditions (Pattern B'') clear before subsequent effects. Use `OwnerId` for the faction-deck sink (Neutral / stolen-edge cases). After sink, if the effect also moves the (former) equipped character, pass `engage=false` on the move — sink was the cost, not engage.

Hand/deck "sink one of these looked-at cards" is different (`insertCardOnExtremePosition` on a deck location) — do not mix that shape with sinking an equipped attachment.

### Choose-location City Action (move to a filtered destination)

When text is "Move the equipped character to a location where …", mirror `_03055` (Syrneth Compass) / location-pick Risks like `_03045`:

1. **`EventActionTriggered`** → `createTransitionEvent($attachment->ControllerId, $attachment->Id, "NNNNN", $this->Id)`. WHY `sourceId` = attachment: `actFromCardWithLocations` / `argsForState` hydrate the source card and call `getActionById` on it.
2. **GameState** under `modules/php/States/<expansion>/State_highDramaPhaseNNNNN.php` — activeplayer, `actFromCardWithLocations`, transitions `"locationChosen"` / `"zombie"` → `HIGH_DRAMA_PLAYER_TURN_EVENTS`. Constant e.g. `403055`.
3. Register `"NNNNN" => States::HIGH_DRAMA_PLAYER_TURN_NNNNN` on `HIGH_DRAMA_PLAYER_TURN_EVENTS` in `states.inc.php`.
4. **`getArgsFromAction`**: `performerId` + `locationIds` from a private `getValidDestinations`.
5. **`actFromActionWithIds`**: validate location ∈ destinations; queue engage (if cost) + move + `createActionResolvedEvent`; `nextState("locationChosen")`.
6. **JS** (expansion `OnEnteringState` / `OnUpdateActionButtons` / `OnLeavingState`): Confirm Location via `onCityLocationsSelected`; highlight performer; `resetCityLocations` on leave.

**Pay engage on location resolve (with the move), not on `EventActionTriggered`.** WHY: single resolution step when the player commits to a destination. Tabard-style engage-at-trigger is for effects that proceed without a further picker (pressure). Prefer resolve-time engage when a choose-location (or choose-target) state sits between announce and effect.

**"a location" vs "City location":** if printed text does **not** say City, include `Game::LOCATION_PLAYER_HOME` in destinations when the filter can match there (Scions / Artifacts / etc. can sit at Home). City Action still requires the *performer* to start in the city (`cardInCity`).

**Home in JS:** when `locationIds` may contain Home, special-case like `highDramaPhase03029_3`:

```javascript
if (locationId == this.LOCATION_PLAYER_HOME) {
    this.makeHomeEndcapMarkerSelectable();
} else {
    const imageElement = this.getCityLocationElement(locationId);
    this.makeCityLocationSelectable(imageElement);
}
```

Do **not** copy bare `03032` / `03045` enter handlers for Home-capable actions — those PHP lists can include Home while their JS only calls `makeCityLocationSelectable` (Home never becomes selectable).

### Available vs equipped attachments at a location

Parenthetical "(The Artifact may be available or equipped.)" means check **both**:

```php
// Available = unattached at the location
foreach ($theah->getAvailableAttachmentsAtLocation($location) as $attachment) {
    if ($attachment->hasTrait("Artifact")) { /* match */ }
}

// Equipped = on any character at the location (skip FakeAttachment)
foreach ($theah->getCharactersAtLocation($location, $includeUncontrolled = true) as $character) {
    foreach ($character->Attachments as $attachmentId) {
        $attachment = $theah->getAttachmentById($attachmentId);
        if ($attachment instanceof Attachment
            && ! $attachment->FakeAttachment
            && $attachment->hasTrait("Artifact")) { /* match */ }
    }
}
```

Trait gates on characters (e.g. Scion) use `hasTrait` with `$includeUncontrolled = true` unless the text says "your" / "opposing".

### Self-trait destination hazard

If **this attachment** itself carries the trait the destination filter looks for (e.g. Compass is an Artifact), the equipped character's **current** location always matches. **Exclude `$performer->Location`** from valid destinations — "move to" implies a different place. Without the exclude, the action is always available and can offer a no-op stay.

Same hazard for any "move to a location with Trait X" where the host card grants or is Trait X.

### `setUsed` / `announceAction` / `resetPlayerPassCount`

**Do NOT call any of these from `AttachmentAction` subclasses.** They run centrally in `actHighDramaInPlayActionConfirm` and `stHighDramaInPlayActionDispatch`. (Per CLAUDE.md.) The pre-commit hook does not require them on these subclasses.

### Pre-commit hook on actions

`Action_NNNNN extends AttachmentAction → CardAction` — the hook requires `createActionResolvedEvent()` somewhere in the class. Make sure it's queued at the end of effect resolution (after any state loops complete).

References: `_01073` / `_01075` (City Action templates), `_03055` (engage-this-card + choose-location move), `_03065` (immediate-resolve sink + move Home), `_02047` (City Action + available attachments at location).
