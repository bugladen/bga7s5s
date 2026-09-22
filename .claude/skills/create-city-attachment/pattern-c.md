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

### Engage timing when there is no destination picker

When the printed cost is **"Engage this card"** and the effect begins immediately after in-play confirm (no location/opponent picker first — e.g. look at your deck), **pay engage on `EventActionTriggered`**, then queue the transition. WHY: the player already committed via `actHighDramaInPlayActionConfirm`; there is nothing to back out of before the look starts. Contrast with choose-location Actions above, where engage waits for destination commit.

Canonical: **`Action_04cd15`** (Syrneth Puzzle Box).

### Look at top N of your deck; Sink any; Reorder rest

Printed **"Look at the top [N] cards of your deck. Sink any and replace the rest in any order."** (optionally **"Then, you may discard a card to draw a card."**).

Canonical city attachment: **`Action_04cd15`** + `State_highDramaPhase04cd15` / `_2` / `_3`. Peers for pieces: `Action_01134` / `Action_02002` (High Drama look + reorder), `Maneuver_03059` Academic sink-any Pass + immediate bottom insert.

**Flow:**
1. **`EventActionTriggered`**: engage attachment (if cost); `getCardsOnTopOfPlayerFactionDeck($controllerId, N)` into `Game::CHOSEN_CARD`; notify look; queue transition.
   - Cards present → `"NNNNN"` (sink state).
   - Empty look (0 cards) → skip sink/reorder; transition straight to the optional terminal step (`"NNNNN_3"`) if any, else `createActionResolvedEvent`.
2. **Sink state (`NNNNN`)**: multi-select looked-at cards + **Pass**. **"Sink any" includes zero** — Pass = sink none, then `finishReplaceOrReorder` (same WHY as Maneuver_03059).
3. **`finishReplaceOrReorder` helper**:
   - 0 remaining → next optional step / resolve.
   - 1 remaining → auto `insertCardOnExtremePosition(..., true)` (no reorder UI).
   - 2+ remaining → nextState reorder.
4. **Reorder state (`NNNNN_2`)**: player orders all remaining; each id `insertCardOnExtremePosition(..., true)`. JS: `onCardsSorted` (descending order tags — last selected ends on top).
5. **Optional discard-to-draw (`NNNNN_3`)**: hand single-select + Pass. Discard → `createCardDiscardedFromHandEvent(..., asEffect=true)` + `createCardDrawnEvent` + `createActionResolvedEvent`. Pass → **also** `createActionResolvedEvent` + a decline notify (spectators should see both outcomes). Zombie auto-passes (optional step).

**Sink looked-at faction-deck cards — immediate bottom insert, not queued events:**

```php
// WHY: Immediate bottom insert (Maneuver_03059 / Technique_01010) — queued sink
// events would race finishReplaceOrReorder's top inserts before EVENTS drains.
$deck->insertCardOnExtremePosition($id, $deckName, false);
```

Do **not** switch to `createCardAddedToFactionDeckEvent(..., false)` for this mid-action sink+reorder without fixing that race. (Separate from **"Sink this card"** on the attachment itself → City Deck via queued events — see above.)

**Args / privacy:** High Drama deck-look peers (`01134`, `02002`, `04cd15`) put looked-at cards in **public** `getArgsFromAction` args (active player only populates `chooseList`). Dusk reactions like `Reaction_03052` use `_private` — do not mix the two conventions without cause.

**JS wiring (expansion bas/tac/faf modules + `EventHandlers.js`):**
- Sink: `chooseList` selectionMode `2`; Confirm → `onMultipleChooseListCardsConfirmed`; Pass → `actFromCardPass`; EventHandlers enable Confirm when `length > 0`.
- Reorder: selectionMode `2`; Confirm → `onCardsSorted`; EventHandlers `addSortTagToCard` + enable when all items selected.
- Discard: `factionHand.setSelectionMode('single')`; Confirm → `onCardDiscarded`; EventHandlers enable `actChooseDiscardCards` when hand selection `> 0`.
- Leave: hide/clear `chooseList`; reset hand selection mode; unhighlight performer.

**`states.inc.php`:** register both `"NNNNN"` and any skip target (e.g. `"NNNNN_3"`) on `HIGH_DRAMA_PLAYER_TURN_EVENTS`. Intermediate reorder can be a direct `nextState` from the sink state without an EVENTS hop.

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
