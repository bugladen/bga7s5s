> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern C — AttachmentAction

City attachment actions are performed *by the character the attachment is equipped to*. `AttachmentAction::getPerformersForAction` defaults to `[$this->getOwningCharacter($theah)]`, and `isAvailableToPlayer` already gates on the owning character being non-null. Keep `Action_NNNNN extends AttachmentAction`.

There is **no** `AttachmentCityAction` base class. Gate **"City Action:"** manually with `$theah->cardInCity($owner)`. Plain **"Action:"** may also need `cardInCity` when the effect only makes sense in the city (e.g. "adjacent City location").

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

### Multiple printed Actions on one card

When Text has two (or more) Action / City Action clauses, create **separate** `Action_NNNNN` / `Action_NNNNNb` classes and list both in `$this->Actions` — do not merge into one mega-action. Canonical city example: `bas/_04cd01` (Penya) → `Action_04cd01` (engage + move) + `Action_04cd01b` (sink + play Risk).

### "Engage this card" vs "Engage the equipped performer"

Printed **"Engage this card"** means engage the **attachment** (`$attachment->Id`), not the character. Gate availability on `$attachment->Engaged`. Printed **"Engage the equipped performer"** engages `$owner->Id` and gates on `$owner->Engaged`.

When the effect also **moves** the equipped character, pass `engage=false` on `createCardMovingEvent` — the printed engage cost already went to the attachment. Do not conflate move-with-engage.

Canonical engage-this-card + choose adjacent City location: **`Action_04cd01`** (Penya) / **`Action_03055`** (Syrneth Compass) / **`Action_01046a`** (Dark Gift).

**"adjacent City location"** → `getAdjacentCityLocations($owner->Location, $includeHome = false)`. Literal "City" excludes Home.

### Choose-location Action (move equipped character)

1. **`EventActionTriggered`** → `createTransitionEvent($attachment->ControllerId, $attachment->Id, "NNNNN", $this->Id)`. WHY `sourceId` = attachment: `actFromCardWithLocations` / `argsForState` hydrate the source card and call `getActionById` on it.
2. **GameState** under `modules/php/States/<expansion>/State_highDramaPhaseNNNNN.php` — activeplayer, `actFromCardWithLocations`, transitions `"locationChosen"` / `"zombie"` → `HIGH_DRAMA_PLAYER_TURN_EVENTS`.
3. Register `"NNNNN" => States::HIGH_DRAMA_PLAYER_TURN_NNNNN` on `HIGH_DRAMA_PLAYER_TURN_EVENTS` in `states.inc.php`. (State classes auto-register — no `states.7s5s.php` entry needed for new expansions.)
4. **`getArgsFromAction`**: `performerId` + `locationIds`.
5. **`actFromActionWithIds`**: validate location; queue engage (if cost) + move (`engage=false`) + `createActionResolvedEvent`; `nextState("locationChosen")`.
6. **JS** (expansion `OnEnteringState` / `OnUpdateActionButtons` / `OnLeavingState`): Confirm Location via `onCityLocationsSelected`; highlight performer; `resetCityLocations` on leave.

**Pay engage on location resolve (with the move), not on `EventActionTriggered`.** WHY: single resolution step when the player commits to a destination.

### "Destroy this card" cost

If the action text reads "**Destroy this card** • …" (e.g. `_01187` Smuggled Item, `_01191` Duckfoot Pistol), queue the unequip + **city discard** up-front, *guarded by `isAttached()` so copied effects don't crash*:

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

### "Sink this card" cost (equipped CityAttachment)

Printed **"Sink this card"** on an in-play **city** attachment means put it on the **bottom of the City Deck** — not city discard (destroy), not faction deck (that's FactionAttachment / Lodestone), not the locker.

Canonical sink chain (`Action_04cd01b` Penya City Action):

```php
$unequipEvent = EventFactory::createAttachmentUnequippedEvent(
    $controllerId, $character->Id, $attachment->Id
);
$removedEvent = EventFactory::createCardRemovedFromPlayEvent(
    $controllerId, $attachment->Id, $attachment->Location
);
// WHY: Text says "sink", not discard — bottom of City Deck (Kaspar 01035 / Reaction_03052).
$sinkEvent = EventFactory::createCardAddedToCityDeckEvent(
    $controllerId, $attachment->Id, false  // false = bottom
);
```

**Do not** copy the faction-attachment sink (`createCardAddedToFactionDeckEvent`) onto a CityAttachment.

### Multi-step chooser + sink/destroy cost timing

When the action has a **picker** (opponent, card, location) *and* a sink/destroy cost:

- **Do not** pay the sink on `EventActionTriggered` — the player must be able to back out of later picker steps without losing the attachment.
- Pay the sink at **commit** (the final `actFromActionWith*` that locks in the choice).
- First picker state after in-play confirm: **no Back button** (action is already `Used`); zombie → `HIGH_DRAMA_PLAYER_TURN_EVENTS` only. Later picker states may Back to the previous picker.

Canonical: `Action_04cd01b` sinks Penya when the Risk/action is chosen, not when the City Action triggers.

### Play target Risk from an opponent's discard (RiskClone)

When text is **"Play target risk from an opponent's discard pile, paying all costs. After it resolves, sink it."**, reuse the Improvising (`_01106`) clone machinery adapted for `AttachmentAction`. Canonical city port: **`Action_04cd01b` + `_04cd01_RiskClone`**.

Flow:
1. `EventActionTriggered` → transition to opponent-choose state.
2. Opponent chosen → risk/action-choose state (show discard Risks + available actions).
3. On action chosen (commit):
   - Pay sink cost on the attachment (see above) if still attached — capture `ControllerId` / character **before** unequip.
   - Hide original Risk (`LOCATION_PERMANENTLY_HIDDEN`) + `createCardRemovedFromPlayerDiscardPileEvent`.
   - `createCardInLocation('<id>_RiskClone', LOCATION_HAND, …)` — `getCardClassName` routes by first two digits of the id (`04` → `bas`).
   - Clone Name/Image/WealthCost; `clone` the chosen action onto the RiskClone; set `ClonedCardId`.
   - Set `ABNORMAL_FLOW`; route to `inHandActionChoosePerformer` or `inHandActionPay` like Improvising.
4. RiskClone `handleEvent(EventCardDiscardedFromHand)`: hide clone → sink original Risk to faction-deck bottom → `createActionResolvedEvent`.

**WHY clone:** the pay/play pipeline expects a Risk action in hand. Do not invent a parallel pay path.

**Affordability differences from Improvising (`Action_01106`):**
- Improvising subtracts its own hand wealth (it leaves the hand when played). An **attachment** City Action does **not** — use raw `handWealthCount`.
- Skip `instanceof self` when iterating discard Risk actions (unbounded recursion if another copy of this action is somehow present — same guard as 01106).

**`createActionResolvedEvent`:** deferred to the RiskClone after the played Risk resolves. Put a comment containing the literal `createActionResolvedEvent` in the Action class so the pre-commit hook passes (mirror `Action_01106`).

Also implement `IAbilityThatTargetsCards` on the Action.

### `setUsed` / `announceAction` / `resetPlayerPassCount`

**Do NOT call any of these from `AttachmentAction` subclasses.** They're handled centrally in `actHighDramaInPlayActionConfirm` and `stHighDramaInPlayActionDispatch`. Per CLAUDE.md. The pre-commit hook does not require them on these subclasses.

### Pre-commit hook on actions

`Action_NNNNN extends AttachmentAction → CardAction` — the hook only requires `createActionResolvedEvent` somewhere in the class (call or comment). Make sure it's queued at the end of effect resolution (after any state loops / RiskClone cleanup complete).
