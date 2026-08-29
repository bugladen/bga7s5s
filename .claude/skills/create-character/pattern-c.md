> Part of **create-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern C — Action / City Action (CharacterAction)

This pattern is **the same as in `create-city-character`'s Pattern C**. The action class extends `CharacterAction` regardless of whether the owning card is a Character, Leader, or CityCharacter. Read the city-character skill's Pattern C for the full template, state class skeleton, and JS wiring. Below are the Character/Leader-specific notes.

### Eligibility differences

- **Regular Action** (`<b>Action:</b>`) — usually requires the character to be in play (`cardInPlay`) but not in the city. The base `parent::isAvailableToPlayer()` covers most of this; add specific preconditions.
- **City Action** (`<b>City Action:</b>`) — additionally gate on `$theah->cardInCity($owner)`. The character must be at one of the city locations to use the ability.

```php
public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
{
    if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
    {
        return false;
    }

    $owner = $this->getOwningCharacter($theah);

    if (! $theah->cardInCity($owner))      // City Action — drop this gate for a non-city Action
    {
        return false;
    }

    // Card-specific preconditions go here.
    return true;
}
```

### CharacterAction does NOT call setUsed / resetPlayerPassCount / announceAction

Per CLAUDE.md, those are run centrally in `actHighDramaInPlayActionConfirm` / `stHighDramaInPlayActionDispatch`. Calling them from a `CharacterAction` subclass causes duplicates.

Still required: **call `createActionResolvedEvent()` once at the end of resolution.** (The pre-commit hook's regex doesn't directly match `extends CharacterAction` — but the call is still mandatory per CLAUDE.md and the convention in every existing CharacterAction.)

### Action `$this->Id` is a STRING composite, not an int

`CardAbilityTrait::setOwnerId` sets `$this->Id = "{$ownerId}_{$this->ClassId}"` — so a `CharacterAction`'s `Id` is a string like `"68_Action_03026"`, not the action's class id or an int. Passing it where an int sourceId is required will throw a type error.

For event factories that take `int $sourceId` (`createCardDiscardedFromHandEvent`, `createCharacterBeingWoundedEvent`, `createCharacterBeingHealedEvent`, `createCardMovingEvent`, etc.), use **`$owner->Id`** (the character's int id), not `$this->Id`. The action's string composite id is the right value for `abilityId` parameters (which are typed `string`) and for the 4th arg to `createTransitionEvent($playerId, $sourceId, $transitionName, $internalId = "")`.

```php
$discardEvent = EventFactory::createCardDiscardedFromHandEvent(
    $owner->ControllerId,
    $cardId,
    $owner->Id,             // ✓ int — character id
    // NOT $this->Id        ✗ string — action's composite id
    false, false, true
);
```

### PHP arrays handed back to JS must be sequential — `array_values()`

`getCardObjectsAtLocation` (DB.php:205) returns an array **keyed by card id**: `$cards[(int)$result['id']] = ...`. `array_map` preserves keys. When that array is JSON-encoded for the client, non-sequential int keys serialize as a JSON object `{12345: ..., 67890: ...}` — not an array — and `.forEach` / `.map` throws `is not a function`.

Wrap any picker `ids` payload (and any helper that may return associative keys) in `array_values` before assigning to args:

```php
$args['ids'] = array_values(array_map(fn($card) => $card->Id, $hand));
```

Symptom on the JS side: `Uncaught TypeError: ids.forEach is not a function`. If you see that error and you're sure the field is set server-side, the cause is almost certainly an associative-keyed array.

### Hand-card picker — use `factionHand.setSelectionMode`, NOT `highlightCardsAsSelectable`

For "Discard a card from your hand" / "Reveal a card from your hand" steps where the player picks from their hand:

- `highlightCardsAsSelectable(ids)` is for **in-play cards** (characters, attachments). It looks up `this.cardProperties[id]` and `$(card.divId + '_image')` — hand cards aren't in `cardProperties` under that scheme and the lookup returns `null`, throwing `Cannot read properties of null (reading 'className')`.
- Hand cards use the dedicated `factionHand` widget. Pattern (mirror `highDramaPhase01069`):

```js
// OnEnteringState.<expansion>.js
'highDramaPhaseNNNNN': () => {
    if (this.isCurrentPlayerActive()) {
        var translated = dojo.string.substitute(_("(${amount} card(s) to discard)"), { amount: 1 });
        $('faction_hand_info').innerHTML = translated;
        this.factionHand.setSelectionMode('single');
    }
},

// OnUpdateActionButtons.<expansion>.js — REUSE the existing onCardDiscarded handler
'highDramaPhaseNNNNN': () => {
    this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardDiscarded());
    dojo.addClass('actChooseDiscardCards', 'disabled');
},

// EventHandlers.js::onFactionCardClicked — toggle the confirm button
'highDramaPhaseNNNNN': () => {
    if (this.factionHand.getSelection().length > 0) dojo.removeClass('actChooseDiscardCards', 'disabled');
    else dojo.addClass('actChooseDiscardCards', 'disabled');
},

// OnLeavingState.<expansion>.js — cleanup
'highDramaPhaseNNNNN': () => {
    if (this.isCurrentPlayerActive()) {
        this.factionHand.setSelectionMode('none');
        $('faction_hand_info').innerHTML = '';
    }
},
```

`onCardDiscarded` in `PlayerActions.js` already submits via `actFromCardWithId` with the selected card id, so the server-side `actFromActionWithId(int $id)` handler works as-is. Reference: `_01069` (Maxime), Angeline `_03026` step 1, Damya `Action_03038a`.

### Draw-then-discard — queue the draw on `EventActionTriggered`

For **"Draw a card. Then, discard a card."** (Damya `Action_03038a`):

```php
if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
{
    $owner = $this->getOwningCharacter($event->theah);

    $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
    $event->theah->queueEvent($drawEvent);

    $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "NNNNNa", $this->Id);
    $event->theah->queueEvent($transition);
}
```

WHY draw before the discard state: printed order is Draw → Discard; events process before the client enters the picker, so the drawn card is already in `factionHand`. Do **not** draw only after the player confirms discard.

Availability must guarantee a discardable card after the draw:

```php
$hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
if (count($hand) > 0) return true;  // (after cardInCity etc.)

$deck = $theah->game->getGameDeckObject();
$faction = $theah->game->getPlayerFactionDeckName($playerId);
$discard = $theah->game->getPlayerDiscardDeckName($playerId);
return $deck->countCardsInLocation($faction) + $deck->countCardsInLocation($discard) > 0;
```

Empty hand + empty deck + empty discard → action unavailable (otherwise the discard state hangs).

### Multiple Actions on one card — `Action_NNNNNa` / `Action_NNNNNb`

When Text has two separate `<b>City Action:</b>` (or Action) clauses, **do not** cram both into one class. Split:

- `Action_NNNNNa.php` / `Action_NNNNNb.php` — each with its own `$this->Name`, `isAvailableToPlayer`, states, transition key
- Card constructor: `$this->Actions = [ new Action_NNNNNa(), new Action_NNNNNb() ];`
- Transition keys and state names: `"03038a"` / `"03038b"` / `"03038b_2"`; JS state names `highDramaPhase03038a` etc.
- State IDs: append digit for which action — `HIGH_DRAMA_PLAYER_TURN_03038a = 4030381`, `…_03038b = 4030382`, `…_03038b_2 = 40303822` (same scheme as `01152a`/`01152b` = `4011521`/`4011522`)

Reference: `_03038` Damya, `_01095` Patricia (`Action_01095a` / `Action_01095b`).

### Destroy an attachment — unequip + discard-from-play

There is **no** `createAttachmentDestroyedEvent`. Destroy means:

```php
$unequipEvent = EventFactory::createAttachmentUnequippedEvent(
    $attachment->ControllerId, $attachment->AttachedToId, $attachment->Id
);
$game->theah->eventCheck($unequipEvent);
$game->theah->queueEvent($unequipEvent);

$discardEvent = EventFactory::createCardDiscardedFromPlayEvent(
    $attachment->OwnerId, $attachment->Id, $attachment->Location, $owner->Id, $asEffect = true
);
$game->theah->queueEvent($discardEvent);
```

If the effect draws cards equal to printed cost (+N), **read `$attachment->WealthCost` before queueing destroy** — after unequip/discard the card is no longer a reliable in-play cost source. Cost `0` → still draw `0 + 1 = 1` when the text says "plus one."

Skip `$attachment->FakeAttachment` when building destroy/equip eligibility lists (Boons, Burdens, etc. are not real equipment).

### Attachment picker — button list, not board highlight

For "choose one of this character's attachments" steps (Damya step 2, Adelheide `01194` step 1), pass `attachments` as `[['id' => …, 'name' => …], …]` from `getArgsFromAction` and render buttons:

```js
// OnUpdateActionButtons — note args.args.attachments (not args.args.args)
'highDramaPhaseNNNNN_2': () => {
    args.args.attachments.forEach((attachment) => {
        this.addActionButton(
            `actChooseAttachment-${attachment.id}`,
            attachment.name,
            () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id})
        );
    });
},
```

WHY buttons over `highlightCardsAsSelectable`: equipped attachment art is small/stacked; buttons are the established UX. `OnEnteringState` still highlights the owner / moved character as `_7sfs-chosen` context. Each button click submits immediately — no separate Confirm.

### "Equipped character moves to this location" eligibility

- Controllers match; ≥1 destroyable (non-fake) attachment.
- **Exclude characters already at the owner's location** when the text says "moves to this location" — same spirit as pull-to-here movers in `Reaction_03016b`. That also excludes the owner herself while she is at "this location."
- Move: `createCardMovingEvent(..., $engage = false, ...)` when Engage is not printed.
- Store mover id in `Game::CHOSEN_TARGET` for the destroy step; clear it when the action finishes.
- No "target" in text → no `IAbilityThatTargetsCharacters`; use private helpers (`isEligibleMover`, `getDestroyableAttachments`).

### Move to Leader's location, then En Garde an attachment

For text like Yepikhodov `_03051`: **"City Action: Move <Owner> to your Leader's location. Then, en garde your attachment there."**

**Engage vs En Garde (do not mix these up):**

| Printed word | Factory | Effect |
|---|---|---|
| **Engage** | `createCardEngagedEvent` | `$card->Engaged = true` (spend / tap) |
| **En Garde** | `createCardEngardedEvent` | `$card->Engaged = false` (ready / untap) |

**Availability:**

1. `cardInCity($owner)` (City Action).
2. `$theah->getLeaderByPlayerId($playerId)` exists.
3. `$owner->Location != $leader->Location` — strict "moves to"; already-there is unavailable.
4. ≥1 Engaged non-`FakeAttachment` that will be at the destination after the move:
   - Owner's own engaged attachments (they travel with him), **plus**
   - Engaged attachments on other controlled characters already at `$leader->Location`.
5. Helper must **not** double-count the owner when looking up destination characters (skip `$character->Id == $owner->Id` in the destination loop) — works both before the move (availability) and after (args/act once EVENTS has flushed the move).

WHY require Engaged: En Garde on an already-ready attachment is a no-op (`EventCardEngarded` unconditionally sets `Engaged = false`). Gating on Engaged also synergizes with Techniques that Engage attachments as a cost.

**Flow:**

```php
// EventActionTriggered:
$moveEvent = EventFactory::createCardMovingEvent(
    $owner->ControllerId, $owner->Id, $owner->Location, $leader->Location,
    $engage = false, $owner->Id, $this->Id
);
$event->theah->queueEvent($moveEvent);
$event->theah->queueEvent(EventFactory::createTransitionEvent(
    $event->playerId, $owner->Id, "NNNNN", $this->Id
));

// HIGH_DRAMA_PLAYER_TURN_NNNNN — attachment button picker (Adelheide / Damya _2 shape):
// getArgsFromAction → args['attachments'] = [['id'=>…,'name'=>…], …]
// actFromActionWithId → createCardEngardedEvent(..., $attachment->Id, ...) + createActionResolvedEvent
```

Destination may be **Home** when the Leader is Home — City Action only requires the *performer* in city. Move still uses `engage=false` (Engage not printed).

Reference: `Action_03051`, attachment-button sibling `Action_03038b_2` / `Action_01194`.

### Move target Mercenary Home ("in play and not available")

Printed: **City Action: Move target Mercenary at this location Home. *(The Mercenary must be in play and not available)***

One-step Pattern C character picker (Makepeace `_01092` UX).

| Printed phrase | Implementation |
|---|---|
| **target** | `implements IAbilityThatTargetsCharacters` + `isValidTargetForAbility` |
| **Mercenary at this location** | `hasTrait("Mercenary")` + same `Location` as owner |
| **in play and not available** | **`isControlled()`** — recruited/controlled. Uncontrolled city Mercenaries are "available" recruit fodder and are **not** legal targets. |
| No "opposing" | Own Mercenaries at the location are legal — do not invent an enemy-controller gate |
| Move Home, Engage not printed | `createCardMovingEvent(..., Game::LOCATION_PLAYER_HOME, $engage = false, …)` |

```php
private function getEligibleMercenaries(Theah $theah, Character $owner): array
{
    $characters = $theah->getCharactersAtLocation($owner->Location);
    return array_values(array_filter(
        $characters,
        fn(Character $c) => $c->hasTrait("Mercenary") && $c->isControlled()
    ));
}
```

Availability: `cardInCity($owner)` + `count(getEligibleMercenaries) > 0`. Transition `"NNNNN"` → highlight `ids` + Confirm. Always `createActionResolvedEvent` after the move.

Sibling: Makepeace `Action_01092` moves an **opposing engaged** character with ≤ Influence — same move-Home / Confirm shape, different eligibility filters.

Reference: `Action_04011` Hans, `Action_01092` Makepeace.

### City-location picker for CharacterActions — override `actFromActionWithIds`

For step-N states where the player picks a city location (the JS submits via `onCityLocationsSelected → bgaPerformAction('actFromCardWithLocations', ...)`):

- The state class's `#[PossibleAction]` is **`actFromCardWithLocations(string $locations)`** (NOT `actFromCardWithId`).
- The framework's `FrameworkActionsTrait::actFromCardWithLocations` JSON-decodes the payload and routes into `$card->actFromCardWithIds(...)` → `$action->actFromActionWithIds(Game, int, string, array)`.
- **Override `actFromActionWithIds(array $ids)`** on the action, NOT `actFromActionWithId(int $id)`. Each entry in `$ids` is a location-name string (e.g. `"The Forums"`), not an int.

Symptom of using `actFromActionWithId` instead: the framework can't dispatch the location payload to your action; the state spins waiting for an action it never receives, which presents as an "infinite loop" on the client.

```php
public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
{
    parent::actFromActionWithIds($game, $state, $stateName, $ids);

    if ($state == States::HIGH_DRAMA_PLAYER_TURN_NNNNN_2)
    {
        $owner = $this->getOwningCharacter($game->theah);
        $newLocation = $ids[0];  // string — location name

        $valid = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        if (! in_array($newLocation, $valid))
            throw new UserException($game->translate('Location must be adjacent.'));

        // ... queue the move event with $newLocation as the string toLocation ...
    }
}

public function getArgsFromAction(Game $game, int $state, string $stateName): array
{
    $args = parent::getArgsFromAction($game, $state, $stateName);

    if ($state == States::HIGH_DRAMA_PLAYER_TURN_NNNNN_2)
    {
        $owner = $this->getOwningCharacter($game->theah);
        $args['locationIds'] = array_values($game->theah->getAdjacentCityLocations($owner->Location, false));
    }
    return $args;
}
```

The state class:

```php
#[PossibleAction]
public function actFromCardWithLocations(string $locations): void
{
    $this->game->actFromCardWithLocations($locations);
}
```

Don't add an `actBack` button on the picker unless your state also declares a `"back"` transition and a `#[PossibleAction] actBack` method — a button that submits an unhandled action will misbehave.

Reference: `Action_01068` (Léontine), Angeline `_03026` step 2.

### Don't add `IAbilityThatTargetsCharacters` unless the text says "target"

The memory note `feedback_targets_characters_interface.md` covers the *positive* case ("if a card's Text targets a character, class must implement IAbilityThatTargetsCharacters"). The inverse also holds: text that says "wound an opposing character" / "engage a character" / "discard a character" — without the word "target" — is NOT a targeted ability and should NOT implement the interface. Other cards' "before being targeted" hooks should not see these.

When you still need validation logic ("must be opposing", "must be at my location"), write a plain private helper:

```php
private function isValidWoundCandidate(Character $owner, Character $character): bool
{
    if ($character->ControllerId == $owner->ControllerId || $character->ControllerId == 0) return false;
    return $character->Location == $owner->Location;
}
```

Don't reuse the `isValidTargetForAbility` name — that name implies the interface contract.

Reference: `_03026` Angeline (wounds without targeting), `Action_03038b` Damya ("Your equipped character moves" without "target"), vs. `Action_03020` (commanding — *does* target).

### State ID encoding

For regular Character cards (not city deck), use `4` + the 5-digit `CardNumber` for step 1. Append `2`/`3`/`4` for multi-step suffixes. Examples:

- `_01007` (Aldo) step 1: `HIGH_DRAMA_PLAYER_TURN_01007 = 401007`
- `_01008` (Cesca Scarpa) step 1: `HIGH_DRAMA_PLAYER_TURN_01008 = 401008`
- `_01008` step 2/3/4: `4010082` / `4010083` / `4010084`
- `_03001` (Cesca del Rosso) step 1: `HIGH_DRAMA_PLAYER_TURN_03001 = 403001`
- `_03001` step 2: `HIGH_DRAMA_PLAYER_TURN_03001_2 = 4030012`

When one card has **multiple Action classes** (`a`/`b`), append a digit for which action, then the step suffix:

- `_03038a` discard: `HIGH_DRAMA_PLAYER_TURN_03038a = 4030381`
- `_03038b` step 1 / 2: `4030382` / `40303822`
- Same idea as `_01152a` / `_01152b` = `4011521` / `4011522`

**Don't engineer around hypothetical city-deck-card collisions.** Memory `feedback_state_id_encoding.md`: the user prefers the simple `4` + cardId scheme. If a future CD card wants the same number, that collision gets resolved then.

### `states.inc.php` transition-name mapping

When you call `EventFactory::createTransitionEvent($playerId, $cardId, $transitionName, $abilityId)`, the framework looks `$transitionName` up in `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions` to know which state to enter. So you need an entry for **every transition name your action passes to `createTransitionEvent`** — and only those.

```php
"03001"   => States::HIGH_DRAMA_PLAYER_TURN_03001,        // entered from EventActionTriggered
```

**Do NOT blindly add `"03001_2"`** unless your action actually calls `createTransitionEvent($playerId, $cardId, "03001_2", ...)`. The step 1 → step 2 jump **sometimes** happens via `$game->gamestate->nextState("stregaChosen")` using only the state's own `transitions` array — in that case the lookup table does not need `"03001_2"`.

**Do add `"NNNNN_2"` (etc.) when you queue `createTransitionEvent` into a later step** — common for multi-step actions that `nextState` back to `HIGH_DRAMA_PLAYER_TURN_EVENTS` so the event queue can process a move/discard/draw before entering step 2. Examples: Angeline `"03026_2"` / `"03026_3"`, Damya `"03038b_2"`, challenge actions `"NNNNN_2"` → technique-available (Pattern F).

**Exception reminder: "issue a challenge" actions ALWAYS need a `<card>_2` entry.** See Pattern F — those actions cross from player-turn states into the challenge sub-state machine via `createTransitionEvent("<card>_2", ...)`.

### Named transitions, and the `""` (empty) transition rule

A state's `transitions` array maps a transition name (the argument you pass to `nextState(...)`) to a destination state. **An empty-string transition `"" => ...` is only valid when it's the ONLY transition out of the state.** With multiple transitions, name each one:

```php
// CORRECT — multiple named transitions
transitions: [
    "zombie"       => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
    "targetChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
],

// WRONG — mixing "" with another named transition errors out
transitions: [
    ""       => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
    "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
],
```

When the zombie path is the only escape hatch besides the success path (typical for picker states), give both a name. Wilhelm's `State_highDramaPhase02013_2` gets away with the single-`""` form because it doesn't declare a separate zombie transition — its `zombie()` method calls `nextState()` (empty), which lands on `""`. If you want a distinct zombie path, you must name both.

**Giacinto lesson (`04032_2`):** a choice state that both Passes and Reveals must **not** declare both `""` and `"pass"` to the same EVENTS destination — BGA throws "More than one possible transition" when `nextState("")` runs. Use a **single** `"" => EVENTS` (Crimson Roger `02036_2` shape) and queue the next step with `createTransitionEvent` before `nextState("")`. Do **not** add a sibling named transition on that state that also goes to EVENTS.

### Reveal hand or Pass → Owner chooseList → discard or move both

For **"En Garde City Action: Target an opposing character • Move <Owner> and that character to the same adjacent City unless their controller reveals their hand and discards a card."** (`Action_04032` Giacinto):

Compose Crimson Roger `Action_02036a` (Reveal/Pass prevent move) + Depose `Action_04028` (move both to adjacent city).

| Step | Who | What |
|---|---|---|
| `_` | Owner | Pick opposing character at Owner's location (`IAbilityThatTargetsCharacters`) |
| `_2` | Target's controller | **Reveal Hand** or **Pass** (buttons only — do **not** pick the discard card yet) |
| Pass path `_3` | Owner | Pick adjacent city via `getAdjacentCityLocations`; move both with `engage=false`, shared `batchId` |
| Reveal path `_4` | **Owner** | Read-only `chooseList` of the revealed hand + Ok (ACTIVE_PLAYER) |
| Bridge `_6` | game | `changeActivePlayer` → hand owner (01192_2 shape — not inside activeplayer `onEnteringState`) |
| `_5` | Hand owner | Discard **one** of the revealed card ids (`REVEALED_CARDS` global + `factionHand` restricted select) |

**En Garde** = `!$owner->Engaged` in `isAvailableToPlayer` only — not an Engage cost on the moves. Availability also needs ≥1 opposing target **and** ≥1 adjacent city (otherwise the action cannot complete).

**Reveal implementation:**
1. For each hand card: `addCardToWorld` + `notify->all` with inject codes (public log).
2. Stash ids in `Game::REVEALED_CARDS` (JSON) so args survive state hops — action-object fields alone are fragile across rebuilds.
3. Queue `createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN_4", $this->Id)` then `nextState("")` through EVENTS.

**chooseList for Owner is ACTIVE_PLAYER, not multiplayer.**

WHY: the UX goal is "Owner sees the revealed hand in chooseList, not only the log." Multiplayer acknowledge (`stMultiPlayerInitCardRevealAcknowledgeSansInitiatingPlayer`) is the wrong tool — Auto-Acknowledge Card Reveals (pref 110) and zombies clear the only remaining seats and the state leaves immediately. Technique `_03043` multi-ack is for duel "everyone must Ok a public reveal"; High Drama "show Giacinto the hand" is a single Ok on an activeplayer state.

JS for `_4`:
- `OnEnteringState`: show `choose_container` / `chooseList`, `addCardToDeck` each card from `args.args.args.cards`, `setSelectionMode(0)`.
- `OnUpdateActionButtons`: Ok → `bgaPerformAction('actPass', {})` (state `actPass` just `nextState("ok")` — do **not** call `Game::actPass`, which logs "passes").
- `OnLeavingState`: hide/clear chooseList.

JS for `_5`: restricted `factionHand` + Confirm (`onChooseHandCardConfirmed` / EventHandlers enable) — validate pick ∈ `REVEALED_CARDS`.

**Transition wiring:** register every `createTransitionEvent` name (`"04032"`, `"04032_2"`, `"04032_3"`, `"04032_4"`) on `HIGH_DRAMA_PLAYER_TURN_EVENTS`. The game bridge `_6` is reached by a **named** transition from `_4` (`"ok"`), not via EVENTS.

Reference: `Action_04032`; Pass/prevent sibling `Action_02036a`; move-both sibling `Action_04028`; chooseList private-look contrast `Technique_03052`; public multi-ack contrast `Technique_03043` (duel only).

### Action examples

| File | Demonstrates |
|---|---|
| `Action_01008` | Multi-step Sorcerer Action; reveal-top-of-deck → optional sink. Branching states (`_2`, `_3`, `_4`). |
| `Action_01076` | Sorcerer Action; multi-step with `RequiresPerformerSelected`, location + character pick, queues `createSorcererAbilityStartEvent` / `createSorcererAbilityPlayedEvent` pair. |
| `Action_02010` | Two-step "move wound from character A to character B"; the heal+wound recipe. |
| `Action_03001` | Two-step "move wound from your Strega to opposing non-Leader"; the heal+wound recipe applied to a Leader's City Action. |
| `Action_01035` | Engage-as-cost + reveal-from-city-deck-until-Mercenary action on a Leader. |
| `Action_03038a` | Draw-then-discard City Action — draw queued on `EventActionTriggered`, then `factionHand` discard picker. |
| `Action_03038b` | Move equipped character (`engage=false`) → attachment button destroy → draw `WealthCost + 1`. Dual-action `a`/`b` sibling of `Action_03038a`. |
| `Action_03040` | Engage + Finesse pressure (win ties via dedicated `SOLINE_PRESSURE_TYPE`) → mandatory claim-or-engage choice state. |
| `Action_04032` | En Garde City Action: target → Reveal Hand/Pass → Owner chooseList ack → hand-owner discard **or** adjacent move-both. ACTIVE_PLAYER chooseList (not multi-ack). |

### Pressure (win ties) — Engage + Pressure with [Stat]

For **"Engage <Owner> • Pressure this location with [Stat]. You succeed even if tied."** (`Action_03040` Soline, `Action_01075` Tabard, `Action_01143` Contempt and Hatred):

1. **Availability:** `cardInCity($owner)` (City Action), `!$owner->Engaged` (Engage printed), `$owner->canPressure(Game::STAT_*)`.
2. **On `EventActionTriggered`:** set globals, engage, emit pressure, transition into the shared pressure state:
   ```php
   $game->globals->set(Game::PRESSURING_PLAYER, $owner->ControllerId);
   $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
   $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
   $game->globals->set(Game::PRESSURE_STAT, Game::STAT_FINESSE);  // or STAT_INFLUENCE / STAT_COMBAT
   $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::YOUR_NEW_PRESSURE_TYPE);

   $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
   $event->theah->queueEvent($engageEvent);

   $pressureStats = $event->theah->getPressureStats($owner, $owner->Location, Game::STAT_FINESSE);
   $event->theah->queueEvent(EventFactory::createPressureOccuringEvent(
       $owner->ControllerId, $owner->Id, $owner->Location, $pressureStats
   ));

   $event->theah->queueEvent(EventFactory::createTransitionEvent(
       $owner->ControllerId, $owner->Id, "pressureLocation", $this->Id
   ));
   ```
3. **Win-ties flag:** mint a new `Game::YOUR_CARD_PRESSURE_TYPE = next_power_of_two` constant and OR it into the win-ties block in `UtilitiesTrait::pressureLocation` (alongside `TABARD_PRESSURE_TYPE`, `CONTEMPT_AND_HATRED_PRESSURE_TYPE`, etc.). **Do not reuse another card's flag** — each ability owns its bit so stacked pressures stay distinguishable (same reason `LOYAL_PRESSURE_TYPE` exists).
4. **`PRESSURE_STAT`:** required when the printed stat is not Influence — `stHighDramaPressureLocation` defaults to `STAT_INFLUENCE`.
5. **Success / failure:** handle `EventLocationPressureResult` with `$event->abilityId == $this->Id`. Always call `createActionResolvedEvent` on failure (and on success paths that don't transition into a follow-up picker). The EventHub only auto-resolves basic-claim pressures.

WHY `"pressureLocation"` not a card-specific transition: the shared `HIGH_DRAMA_PRESSURE_LOCATION` game state runs `pressureLocation()`, emits `EventLocationPressured` → `EventLocationPressureResult`, then cards react to the result by ability id.

### Claim or engage after pressure

For **"If successful, claim it or engage an opposing character"** (`Action_03040`):

```php
if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
{
    if ($event->success)
    {
        $canClaim = $event->theah->canLocationBeClaimedBy($owner->ControllerId, $owner->Location);
        $engageable = /* unengaged opposing at owner->Location */;

        if ($canClaim || count($engageable) > 0)
        {
            $event->theah->queueEvent(EventFactory::createTransitionEvent(
                $owner->ControllerId, $owner->Id, "NNNNN", $this->Id
            ));
            return;  // actionResolved fires from the choice state
        }
        // neither option — notify and fall through to actionResolved
    }
    $event->theah->queueEvent(EventFactory::createActionResolvedEvent($owner->ControllerId));
}
```

Choice-state UX:
- **Claim:** `OnUpdateActionButtons` adds a Claim button → `bgaPerformAction('actFromCardWithId', {id: 0})`. Validate `canLocationBeClaimedBy` in `actFromActionWithId`, then `createLocationClaimedEvent`.
- **Engage:** `OnEnteringState` highlights unengaged opposing ids; Confirm via `onChooseInPlayCardConfirmed`. Filter `!Engaged`. Queue `createCardEngagedEvent` on the chosen character.
- **No Pass** when the printed text is a mandatory "or" (Soline). `Action_01105` Drinking Games is the optional-engage sibling and **does** Pass.
- **No `IAbilityThatTargetsCharacters`** — "engage an opposing character" lacks "target". Use a private `isValidEngageCandidate` helper.
- Args nesting: `OnEnteringState` reads `args.args.args.canClaim` / `ids`; `OnUpdateActionButtons` reads `args.args.canClaim` / `ids` (one less nest — established BGA/7s5s quirk).

Auto-claim-only pressures (`Action_01075`) skip the picker and queue `createLocationClaimedEvent` directly on success when `canLocationBeClaimedBy`.

### Move-a-wound recipe

```php
$healEvent = EventFactory::createCharacterBeingHealedEvent(
    $sourceCharacter->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
);
$game->theah->queueEvent($healEvent);

$woundEvent = EventFactory::createCharacterBeingWoundedEvent(
    $targetCharacter->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
);
$game->theah->queueEvent($woundEvent);
```

Heal first, wound second. Both go through the standard event pipeline so other cards can react (Maryam's wound cancel, Silver Spine's risk-target cancel, etc.) — don't try to mutate `$character->Wounds` directly.

