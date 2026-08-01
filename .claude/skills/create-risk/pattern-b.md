> Part of **create-risk**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern B — Action (`RiskAction`)

Use `RiskAction` for in-hand Actions and in-play Actions that aren't City Actions.

```php
class Action_NNNNN extends RiskAction
{
    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) return false;
        // ... card-specific preconditions
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            // Resolve the effect inline, OR queue a transition to a sub-state if there's a player choice.
            // ...
            $resolvedEvent = EventFactory::createActionResolvedEvent($event->playerId, $event->actionId);
            $event->theah->queueEvent($resolvedEvent);
        }
    }
}
```

The base `RiskAction::isAvailableToPlayer` enforces "Risk is in hand" unless `$overrideInHandCheck` is true. `RiskAction::getPerformersForAction` adds the player's characters in play to the performer pool — **including characters at the player's Home, not just city characters.** When overriding `getPerformersForAction`, start from `parent::getPerformersForAction(...)` and layer trait/state predicates on top; do NOT swap it out for `getCharactersInCityByPlayerId(...)` just because the effect implies city. A character at home has adjacent city locations and can still perform a "Move to adjacent location" Action.

References: `Action_01061` (Well-Equipped's en-garde-equipped-performer Action), `Action_03009` (Sorcerer Strega Action that moves the performer to an adjacent location filtered by contents), `Action_03045` (wound performer + move to adjacent claim-controlled-by-opponent location).

### Pattern B.1 — "Move your performer to an adjacent location where …"

A common Risk Action shape: the player picks an *adjacent location* meeting some predicate (enemy character at it, available Mercenary at it, claimed by an opponent, etc.). See `Action_03009` (content filters) and `Action_03045` (claim-control filter ± wound cost). Wire it as:

1. **Filter performers** by trait gates (`Sorcerer`/`Strega`/etc.) and by "has ≥1 valid destination" — `parent::getPerformersForAction(...)` first, then `array_filter`.
2. **`handleEvent` on `EventActionTriggered`:** queue a `createTransitionEvent(..., "NNNNN", $this->Id)` to a card-specific location-chooser sub-state.
3. **`getArgsFromAction`:** in the sub-state, expose `performerId` + `locationIds` (the valid adjacent destinations).
4. **`actFromActionWithIds($ids)`:** `$ids[0]` is the chosen location string (BGA dispatches `actFromCardWithLocations` → `actFromActionWithIds`). Validate it's in the valid list, then queue effects + `createActionResolvedEvent(...)`. If Sorcerer, bracket with `createSorcererAbilityStartEvent` / `createSorcererAbilityPlayedEvent`. When the text also says **"Wound your performer"** before the move, queue `createCharacterBeingWoundedEvent` **then** `createCardMovingEvent` on confirm (both with `eventCheck`) — mirror `Action_03032` / `Action_03045`. Do not wound in `EventActionTriggered` before the chooser; resolve both costs when the destination is locked in.
5. **`$theah->getAdjacentCityLocations($performer->Location, $includeHome = false)`** is the right helper; pass `$includeHome = false` unless the rules text explicitly admits Home as a destination.
6. **`getCharactersAtLocation($location, $includeUncontrolled = true)`** when "available Mercenary" or other uncontrolled-character predicates are part of the destination filter. The default `$includeUncontrolled = false` will silently drop the available mercenaries.
7. **"Available Mercenary"** = `! $character->isControlled() && $character->hasTrait("Mercenary")`.
8. **"Enemy character"** = controlled by an opposing player: `$character->isControlled() && $character->ControllerId != $performer->ControllerId`. Don't conflate with "opposing" (which also requires same location).
9. **"Location controlled by an opponent" / "claimed by an opponent"** = **claim control**, not character presence. Filter with:

```php
$controller = $theah->game->getControllerForLocation($location);
return $controller != 0 && $controller != $performer->ControllerId;
```

WHY exclude `0`: uncontrolled city locations are not controlled by anyone, so they fail "controlled by an opponent." WHY not reuse `_03009`'s enemy-character scan: a location can have enemy characters and still be uncontrolled (or controlled by you). Claim state lives in `getControllerForLocation` / `$theah->getCityLocation($name)->Controller` (same source — see Pavel `_01120`). Reference: `Action_03045`.

**Location chooser ≠ character chooser:** do not `implements IRiskThatTargetsCharacters` / `IAbilityThatTargetsCharacters` for B.1 Actions. JS is the `highDramaPhase03009` / `03032` / `03045` trio (`makeCityLocationSelectable` + Confirm Location).

### Pattern B.2 — "Equip this card to …" (RiskAttachment)

Some Risks are played from hand as an Action that **equips a FakeAttachment stand-in** onto a character. The original Risk moves to `LOCATION_PERMANENTLY_HIDDEN`; while equipped, its while-equipped Forced / continuous effects live on the attachment class — **not** on the Risk (E.1). Combat-card play of the Risk (Riposte/Parry/Thrust) is unrelated and stays on the Risk constructor.

**Files:**

| Piece | Location |
|---|---|
| Risk | `cards/<expansion>/_NNNNN.php` — `IHasActions` + Action; `IRiskThatTargetsCharacters` only if printed **"target"** |
| Action | `cards/<expansion>/actions/Action_NNNNN.php` — `RiskAction` (± `ISorcererAbility`, ± `IAbilityThatTargetsCharacters`) |
| FakeAttachment | `cards/<expansion>/_NNNNN_<Suffix>.php` — `Attachment implements IRiskAttachment` + `RiskAttachmentTrait` |

**Action shape (mirror `Action_01025` / `Action_04008`):**

1. **Performers:** usually city + opposed (`getCharactersInCityWithOpposingCharacters`) + trait gates (`Sorcerer`/`Strega`). Filter performers that have ≥1 legal equip target at their location.
2. **Targets:** opposing at performer location (`isNotControlledByPlayer`). Layer printed filters (`! hasTrait("Leader")`, etc.).
3. **`EventActionTriggered`:** transition `"NNNNN"` to a character-chooser GameState (bas/faf JS trio: highlight performer + `highlightCardsAsSelectable` + Confirm).
4. **On confirm:** `createSorcererAbilityStartEvent` (if Sorcerer) → `$game->createRiskAttachment($game, "NNNNN_Suffix", $owner->Id, $character->Location, $performer->ControllerId, $performer->ControllerId, $character->Id, $this->Id)` → `createActionResolvedEvent` → `createSorcererAbilityPlayedEvent` (pass `$character->Id` / location as target for Cesca/sorcery observers).
5. **`createRiskAttachment` class name** is the suffix only (`"04008_Silence"`) — `getCardClassName` prepends expansion from the first two digits.

**FakeAttachment constructor:** `$this->FakeAttachment = true;` `$this->ShowStatModifiers = false;` copy Name/Image/Traits from the Risk as needed. Do **not** set Riposte on the attachment for pre-commit FactionAttachment rules — this is not a `FactionAttachment`.

**Forced "At the end of High Drama, if this card is equipped • Destroy it":** on the attachment:

```php
if ($event instanceof EventHighDramaPhaseEnd && $this->isAttached())
{
    $this->removeRiskAttachment($event->theah);
}
```

`removeRiskAttachment` queues unequip → discard FakeAttachment → hide FakeAttachment → restore original Risk to the owner's discard. Mirror `_01025_Burden` / `_04008_Silence`. Do **not** put this Forced on the Risk class (Risk is hidden while equipped).

**"This ability cannot be copied":** Cesca's `Reaction_01008::isCopyable` is an **opt-in allow-list**. Printed "cannot be copied" → **do not** add `Action_NNNNN` to that list and do not add a `copyCard` branch. Historical exceptions (`Action_01025`, `Action_01161`) remain allow-listed despite the same wording — do not expand. Printed **"target"** still requires `IAbilityThatTargetsCharacters` / `IRiskThatTargetsCharacters` (Rules Team / Maryam / other targeted reactions); Cesca copy is a separate gate.

**While-equipped continuous effects** (blank text box, Forced engarde-destroy, stat grants) belong on the FakeAttachment's `handleEvent`. Blank text box → Pattern E.3.

**WHY not invent Maneuvers:** stubs sometimes import leftover `Maneuver_NNNNNa/b` from adjacent cards. If Text has no Maneuver clause, do not create Maneuver files.

References: `_01025` / `Action_01025` / `_01025_Burden` (equip opposing, no printed "target", engarde-destroy Forced), `_04008` / `Action_04008` / `_04008_Silence` (equip **target** opposing non-Leader + E.3 blanking), `_01161` / `Action_01161` / `_01161_Boon` (Sorcerer City Action equip + engage cost + dusk discard).

