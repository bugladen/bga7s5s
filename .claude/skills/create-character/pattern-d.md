> Part of **create-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern D — Reaction / City Reaction (CardReaction)

This pattern is **the same as in `create-city-character`'s Pattern D**, with two Character/Leader-specific notes below. Read the city-character skill's Pattern D for the full template, multi-stage button flow, and `< Back` rules.

### Trigger gates for non-city-deck characters

Most Character/Leader reactions don't need a `cardInCity` gate (unless the card text says "City Reaction" — then add the gate). Key gates:

1. **`$this->isAvailable()`** — base `CardReaction::handleEvent` resets `Used = false` on `EventDuskEndOfDay`. Gate every branch on `isAvailable()` so the reaction doesn't double-fire within a day.
2. **Identity check** — usually `$event->sourceId == $owner->Id`, `$event->performerId == $owner->Id`, `$event->actorId == $owner->Id`, or `$event->cardId == $owner->Id`. The field depends on the event.
3. **City scope** (for "City Reaction" only) — `$event->theah->cardInCity($owner)`.
4. **Valid-target precondition** — if the effect requires a target (e.g., "wound an opposing character"), check that at least one valid target exists BEFORE queuing the reaction transition. Otherwise the player gets a useless prompt they can only Decline.
5. **"Opposing" semantics** — opposing means BOTH different controller AND same location. Use `Theah::getOpposingCharactersAtLocation($location, $playerId)` (or hand-filter with `isNotControlledByPlayer($controllerId) && Location == $owner->Location`), not a hand-rolled `ControllerId !=` filter.

### Triggering off a Sorcerer ability the owner just performed

For "After <X> performs a Sorcerer ability …" (Cesca del Rosso, Elina, Cesca Scarpa) — listen on `EventSorcererAbilityPlayed` and check both `sourceId` and `performerId`:

```php
if ($event instanceof EventSorcererAbilityPlayed && $this->isAvailable())
{
    $owner = $this->getOwningCharacter($event->theah);

    if (! $event->theah->cardInCity($owner))   // City Reaction gate — drop for non-city Reactions
    {
        return;
    }

    if ($event->sourceId != $owner->Id && $event->performerId != $owner->Id)
    {
        return;   // some other Sorcerer's ability — not this card's
    }

    // ... valid-target precondition ...

    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
    $event->theah->queueEvent($transition);
}
```

`sourceId` is the card whose ability fired; `performerId` is the character actually performing it. The ability may be on a card other than the owner (e.g., the owner cast a sorcery from her hand) — checking both covers both cases.

### Should the Reaction itself implement `ISorcererAbility`?

Only if the card text says "**Sorcerer** Reaction" or "**Sorcerer** City Reaction." Examples:
- `Reaction_02001` (Andriana, "**Sorcerer** Reaction: …") implements `ISorcererAbility`.
- `Reaction_03001` (Cesca del Rosso, "**City Reaction**: …") does NOT — the text doesn't carry the Sorcerer keyword.
- `Reaction_04003b` (Desideria, "**City Reaction**: After Desideria performs a Sorcerer ability…") does NOT — same rule; wound+draw must not re-emit Sorcerer-played.

This matters because if a Reaction is a Sorcerer ability and it wounds, that wound's `EventSorcererAbilityPlayed` would re-trigger the same "after a Sorcerer ability" type reaction in a loop. `setUsed` breaks the loop in practice, but the cleaner answer is: **follow the card text literally.** If the keyword isn't printed, the ability isn't Sorcerer.

When `implements ISorcererAbility`, you MUST also call both:
- `createSorcererAbilityStartEvent()` at the start of resolution
- `createSorcererAbilityPlayedEvent()` at the end of resolution

The pre-commit hook enforces this.

### En Garde City Reaction / En Garde Reaction (precondition)

Printed: **`<b>En Garde City Reaction:</b>`** or **`<b>En Garde Reaction:</b>`**.

- Gate `!$owner->Engaged` in `handleEvent` (and re-check in `performReaction` if you want belt-and-suspenders).
- **Do not** queue `createCardEngagedEvent` unless Engage is printed as a cost.
- City variant also needs `cardInCity($owner)`.

Same En Garde semantics as Pattern C En Garde City Action (Tijani `_04cd29`). Reference: `Reaction_04003a`.

### Destroyed ally → hand (duel or opponent's effect)

Printed shape (Desideria `_04003`): **"After your Thug at this location is destroyed during a duel or by an opponent's effect, wound <Owner> • Put the Thug in your hand."**

**Trigger:** `EventCharacterDestroyed` with Odette/Vissenta gates (`isAvailable`, `cardInCity` if City, your Trait/controller, `$destroyed->Location == $owner->Location`). WHY Location still matches during `handleEvent`: `runEventHubAfterCards = true` — locker/discard move runs after card handlers (same as `Reaction_03027a`).

**Cause gate — `EventCharacterDestroyed` has no `sourceId`:**

| Arm | How |
|---|---|
| "during a duel" | `$globals->get(Game::IN_DUEL)` at Destroyed time (covers duel threat wounds **and** direct destroys like Dante `Maneuver_01031`) |
| "by an opponent's effect" | On `EventCharacterWounded`, if the wound would kill (`Wounds + event.wounds >= ModifiedResolve + WoundsHealedIncoming`) **and** `source.ControllerId` is a live opponent, mark `$opponentLethalThugId`. Destroyed qualifies if id matches. |

Crew Cap / Dawn / own self-wound outside duel do **not** qualify (no mark, not `IN_DUEL`).

**Hand return recipe** (after EventHub has reinjected a fresh card into locker or discard):

- Brute → discard; everyone else → locker (`EventHub` CharacterDestroyed).
- Locker: `createCardRemovedFromLockerEvent` then `createCardAddedToHandEvent`.
- Discard: `createCardRemovedFromPlayerDiscardPileEvent` then `createCardAddedToHandEvent`.

**CRITICAL — defer Hand while `IN_DUEL`:**

`stDuelNextPlayer` only preserves leftover threat when the dead participant is still in `Locker-` / `Discard-`. Moving them to Hand makes `!$actorIsDead && locations differ` → nullify remaining adversary threat → duel can end with threat still on the board (`_results/2026-07-28-duel-continue-on-death.md`).

Do **not** copy Object of Wonder's reaction-instance `WaitAfterDuel` alone:

- Locker cards are **not** loaded by `Theah::buildCity`, so EventDuelEnd on the Thug never fires across requests.
- If Owner's wound cost kills her, EventHub reinstantiates the card and wipes private reaction fields.

Canonical deferral (Desideria):

1. On accept during `IN_DUEL`: queue Owner wound now; stash thug id in a **per-player game global**; leave Thug in Locker/Discard.
2. Outside duel: Hand return immediate.
3. `StatesTrait::stDuelEnd` (after clearing `IN_DUEL`) calls a static `flushPendingRecovers($game)` on the Reaction class to move any pending ids to hand.

Reference: `Reaction_04003a`; attachment deferral sibling `Reaction_01202` (OK to use instance flags — attachment stays in play).

When `implements ISorcererAbility`, you MUST also call both:
- `createSorcererAbilityStartEvent()` at the start of resolution
- `createSorcererAbilityPlayedEvent()` at the end of resolution

The pre-commit hook enforces this.

### "Put into play from hand or discard"

For Reactions whose effect is "put a card into play" (e.g., Don Constanzo's "Put a different **Thug** into play at your **Home** from your hand or discard pile"):

- **Source filtering.** For hand: `$theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId)`. For discard pile: `$theah->getCardObjectsAtLocation($game->getPlayerDiscardDeckName($owner->ControllerId), $owner->ControllerId)`. Both per-player.
- **The muster event does the move.** `EventFactory::createCharacterMusteredEvent($playerId, $cardId, $location)` is the only event needed for the actual location change — its handler calls `$deck->moveCard(...)` on the game deck which physically moves the card.
- **`createCardRemovedFromPlayerDiscardPileEvent` is notification-only** in the default code path — it sends a `cardRemovedFromPlayerDiscardPile` notification (and only physically moves the card if `permanentlyHide=true`). The actual remove-from-discard happens implicitly when `createCharacterMusteredEvent`'s `$deck->moveCard` runs. So:
  - Fire `createCardRemovedFromPlayerDiscardPileEvent` BEFORE the muster event so JS clients (which filter `player.discard` on that notification) update their state in the right order.
  - Don't expect it to move the card; that's the muster event's job.
  - Reference: `Action_01024` (Bravos) follows this exact ordering for Thug-from-discard mustering.

### After a character moves to this location

For "Reaction: After <enemy/opposing> character moves to this location • <effect>" (`_03016` Ise). Listen on `EventCardMoved` (past-tense — the move has already committed). Required gates:

```php
if (! ($event instanceof EventCardMoved)) return;
if (! $this->isAvailable()) return;

$owner = $this->getOwningCharacter($event->theah);
if ($owner === null) return;
if (! $event->theah->cardInCity($owner)) return;   // enemies can't enter your Home
if ($event->cardId == $owner->Id) return;          // skip the Owner's own moves
if ($event->toLocation != $owner->Location) return;

$character = $event->theah->getCardById($event->cardId);
if (! ($character instanceof Character)) return;   // attachments and other cards also move
if ($character->ControllerId == 0) return;          // uncontrolled / mercenary — skip
if ($character->ControllerId == $owner->ControllerId) return;   // "enemy" gate — ONLY when text says enemy/opposing

// Valid-effect-target precondition: if no eligible action, don't prompt the player.
if (count($this->getEligibleEffectTargets($event->theah, $owner)) == 0) return;
```

**When the text says "a character" (not "enemy" / "opposing")** — omit the enemy-controller line. Allies arriving also trigger. Reference: `Reaction_03040` Soline. Still keep self-move / non-Character / `ControllerId == 0` skips.

WHY the `cardInCity($owner)` gate: enemy characters can't enter your Home location (Home is per-controller scope), so the `toLocation == $owner->Location` check would silently never match for an Owner at Home. The explicit gate documents the intent and skips the per-event work entirely.

WHY `Character` instanceof check (not just `getCardById`): `EventCardMoved` fires for *any* card that moved — attachments equipping, schemes being placed, etc. Filter to Character explicitly.

WHY `ControllerId == 0` skip: uncontrolled characters (mercenaries in transit, cards being mustered with no controller yet) shouldn't trigger. Skipping them is the consistent behavior across the codebase.

WHY the valid-target precondition: if no eligible effect exists, the player would get a useless prompt they could only Pass. The general Pattern D rule (skill section "Trigger gates") applies here verbatim.

**Effect: "Move <Owner> to any City location"** — button-per-destination, no state class:

```php
foreach (array_keys($theah->getCityLocations()) as $locationName) {
    if ($locationName == $owner->Location) continue;  // exclude current
    $array[] = $this->createButtonProperty($game, sprintf($game->translate('Move to %s'), $locationName), "moveTo-$locationName");
}
$array[] = $this->createButtonProperty($game, $game->translate('Pass'), 'pass');
```

On select: `createCardMovingEvent(..., $engage=false, …)` when Engage is not printed; `setUsed(true)` only on a real move (Pass early-returns before `setUsed`). Adjacent-only variant: `getAdjacentCityLocations($owner->Location, $includeHome = false)` — `Reaction_01089` Leader Soline.

For the *self-moves* analogue ("after this character moves to a new location, do X for nearby allies"), the receiver isn't a Reaction — it's a `handleEvent` on the card itself. See Pattern A "Location Technique grant aura" (`_01067` Jean Urbain, `_02022` Stranahan, `_03051` Yepikhodov).

### After a character equips an attachment at a location

For "City Reaction: After a character equips an attachment at [The Grand Bazaar] • Draw a card" (`_03028` Térence). Listen on `EventAttachmentEquipped`. Required gates:

```php
if (! ($event instanceof EventAttachmentEquipped)) return;
if (! $this->isAvailable()) return;

$owner = $this->getOwningCharacter($event->theah);
if ($owner === null) return;
if ($event->theah->game->characterIsInDiscardOrLocker($owner)) return;
if (! $event->theah->cardInCity($owner)) return;                    // City Reaction
if ($owner->Location != Game::LOCATION_CITY_BAZAAR) return;          // owner must be at the named location

$attachment = $event->theah->getAttachmentById($event->attachmentId);
if ($attachment === null || $attachment->FakeAttachment) return;

$character = $event->theah->getCharacterById($event->characterId);
if ($character === null) return;
if ($character->Location != Game::LOCATION_CITY_BAZAAR) return;      // equip happened at the named location
```

WHY **not** gate on `$event->characterId == $owner->Id`:

- Card text says "a character" — any character equipping at that location triggers the reaction while the owner is present. `Reaction_01039` (Philip) is the self-only analogue: it gates on `$event->characterId == $philip->Id`.

WHY skip `FakeAttachment`:

- Fake attachments are bookkeeping placeholders, not real equips. Same skip as `Reaction_01039`.

Button shape: Draw/Pass (`Reaction_03028`, `Reaction_01146a`). Pass early-returns before `setUsed`.

### After the Owner herself moves to a city location

For "After <Owner> moves to a city location • Reaction: do X" (`_03025` Angeline). The trigger filter is the OPPOSITE of `Reaction_03016b` — gate on `cardId == $owner->Id`, not `!=`. The full gate set:

```php
if (! ($event instanceof EventCardMoved)) return;
if (! $this->isAvailable()) return;

$owner = $this->getOwningCharacter($event->theah);
if ($owner === null) return;

if ($event->cardId != $owner->Id) return;                       // owner herself
if (! $event->theah->locationInCity($event->toLocation)) return; // city dest only

// Valid-target precondition — read eligibility at $event->toLocation, NOT $owner->Location
if (count($this->getEligibleTargets($event->theah, $owner, $event->toLocation)) == 0) return;
```

**Gotcha — `$owner->Location` at handleEvent time is the OLD location.** `EventCardMoved` sets `runEventHubAfterCards = true`, so the EventHub state update (which writes `$card->Location = $event->toLocation`) runs AFTER every card's `handleEvent`. Inside an `EventCardMoved` handler, `$owner->Location` is still `$event->fromLocation`. Read the destination as `$event->toLocation` for any "now that the move has happened, who else is at the new location" lookups. By the time `performReaction` runs, the move HAS resolved and `$owner->Location` is the new location — so the target-validation check there can use `$owner->Location` directly.

Pattern reference: `Reaction_03025` (Angeline) — `cardId == $owner->Id` filter, `locationInCity($event->toLocation)` gate, `getEligibleTargets(..., $event->toLocation)` precondition.

### Pass button — Reactions with an optional second effect

For Reactions where the printed text bundles a mandatory first effect with an *optional* second effect ("X. Then, you may Y"), or where the Reaction is purely optional and the player might want to decline at the prompt without burning the daily use, **add a `'pass'` button**. Cumulative pattern across `Reaction_01062`, `Reaction_03016b`, `Reaction_03027a/b`:

```php
public function getReactionButtonProperties(Theah $theah): array
{
    $array = parent::getReactionButtonProperties($theah);
    // ... per-target / per-source buttons ...
    $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
    return $array;
}

public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
{
    parent::performReaction($game, $state, $internalId, $reactionId);

    if ($reactionId == 'pass')
    {
        $game->gamestate->nextState("done");
        return;   // EARLY return — do NOT call setUsed; reaction stays available
    }

    // ... mandatory effect (if any) ...
    // ... optional/branched effect ...

    $this->setUsed($game->theah, true);
    $game->gamestate->nextState("done");
}
```

WHY a per-card `'pass'` button instead of relying on the framework's Decline:

- `Reaction::performReaction` in `CardReaction.php` line 59 explicitly handles `'pass'` (and `'decline'`) by SKIPPING the `createReactionActivatedEvent` emission. So the button label "Pass" arrives in `performReaction` like any other id; the early-return-before-`setUsed` shape mirrors the framework's intent.
- The framework's Decline button (handled by `actFromReactionPass`) is functionally similar but routes through a different code path. The `'pass'` button keeps everything in one method and makes the "do not setUsed" intent explicit.
- For Reactions with mandatory first effect + optional second (`_03027a` heal + optional renown), the `'pass'` button means "I decline the whole reaction" — neither the heal nor the renown move runs. The button labels for the active choices (`'healOnly'`, `'moveFrom-<loc>'`) carry the mandatory effect.

WHY early-return BEFORE `setUsed(true)`:

- The reaction's daily-use slot is a resource. A player who declines at the prompt should NOT lose that slot — they should still be able to fire the reaction later when the trigger recurs (e.g., a second character dies at the same location later in the day).
- Mirrors `Reaction_01062`: `if ($reactionId != "pass") { ... setUsed ... }` — Pass falls through to `nextState` without touching `setUsed`.

### Hide buttons whose effect would be a no-op

When a Reaction button's effect would do nothing (e.g., "Heal only" when the character has 0 wounds), hide that button from `getReactionButtonProperties` rather than letting the player click a no-op. Mirror the gate from the trigger-availability check:

```php
if ($owner->Wounds > 0)
{
    $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Heal only'), 'healOnly');
}
```

The same discipline applies inside `performReaction` — gate the no-op effect on its precondition so you don't queue an empty event:

```php
if ($owner->Wounds > 0)
{
    $healEvent = EventFactory::createCharacterBeingHealedEvent(...);
    $game->theah->queueEvent($healEvent);
    $game->notify->all("message", ...);
}
```

WHY skip the no-op queue: `createCharacterBeingHealedEvent` against a 0-wound character is clamped by the engine and does nothing, but it still emits a notification ("X heals a wound") that misleads the log. Skip the event entirely when there's nothing to heal.

### Moving Renown between locations — three-event batch

For "Move N Renown from location A to location B" (Reaction_01062, Reaction_03027a, any similar Action). Queue THREE events with a shared `batchId`:

```php
$batchId = $game->getNextEventBatchId();

$movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent(
    $owner->ControllerId, $fromLocation, $toLocation, 1, $owner->getInjectCode()
);
$movingEvent->batchId = $batchId;
$game->theah->eventCheck($movingEvent);
$game->theah->queueEvent($movingEvent);

$removedEvent = EventFactory::createRenownRemovedFromLocationEvent(
    $owner->ControllerId, $fromLocation, 1, $owner->getInjectCode()
);
$removedEvent->batchId = $batchId;
$game->theah->eventCheck($removedEvent);
$game->theah->queueEvent($removedEvent);

$addedEvent = EventFactory::createRenownAddedToLocationEvent(
    $owner->ControllerId, $toLocation, 1, $owner->getInjectCode(), $isMove = true
);
$addedEvent->batchId = $batchId;
$game->theah->eventCheck($addedEvent);
$game->theah->queueEvent($addedEvent);
```

WHY three events with `batchId`:

- `EventRenownMovingBetweenLocations` is the umbrella event that other cards (and the UI animator) hook to see "renown is moving from A to B" as one logical motion.
- `EventRenownRemovedFromLocation` + `EventRenownAddedToLocation` are the granular bookkeeping events that actually mutate the source/destination renown counts. Pass `$isMove = true` to the added-event so it knows the renown originated from another location (not a fresh add).
- The shared `batchId` (from `$game->getNextEventBatchId()`) groups all three under one logical operation in the log/UI. Without it, the player sees three separate log lines.
- Call `eventCheck($event)` on each before queueing — gives other cards a chance to cancel or modify (e.g., a card that prevents renown moves).

Eligible source locations come from `$theah->getAdjacentCityLocations($owner->Location, $includeHome = false)` filtered by `$theah->getCityLocation($name)->Renown > 0`. Don't queue any of the three events if the source has 0 renown.

Reference: `Reaction_01062` (Odette Leader's existing reaction), `Reaction_03027a` (the new Odette character's destroyed-trigger reaction).

### Continuous Reaction — never set to Used

For "After X happens, you may Y" with no per-round/per-turn/per-game cap (e.g. `_03025` Angeline's "After Angeline moves to a city location, wound an engaged opposing character"; `_03049` Ekaterina's "After Ekaterina's location is claimed, she may move…"). The Reaction should fire every time the trigger event recurs. Omit the `$this->setUsed($theah, true)` call in `performReaction` — let the reaction stay available indefinitely.

**Unlabelled "After … may …"** (no `<b>Reaction:</b>` keyword) is still a Pattern D Continuous Reaction when there is a player choice — same shape as Angeline. The once-per-day cap only applies when the Ability keyword says Reaction/Action and you call `setUsed(true)`.

**Pre-commit hook gotcha.** `.githooks/pre-commit` greps for the literal string `$this->setUsed(` in every `CardReaction` subclass and fails the commit if absent. A continuous Reaction has no runtime `setUsed(true)` call, so satisfy the hook by mentioning the literal in a comment:

```php
// Continuous Reaction: intentionally do NOT call $this->setUsed(true).
// The reaction remains available and can fire on every recurrence of the trigger.
```

The grep matches the literal inside the comment — no behavior change, hook passes. Same trick works for `$this->isAvailable(` if the reaction doesn't otherwise call it (rare; `isAvailable()` is the standard gate in `handleEvent`).

### Beginning-of-Dusk private look, sink, and reorder

For Yevgeni `_03052`: **"At the beginning of Dusk, you may look at the top three cards of the City Deck. If you do, sink one and replace the others in any order."**

- Treat the unlabelled player choice as a **Continuous Reaction** on `EventDuskPhaseBegin`. Use Look/Pass buttons. It only fires once per day naturally, but Continuous matches the absence of a printed Reaction keyword.
- Before prompting, require a live controlled owner and at least one City Deck card. On Look, snapshot `getCardsOnTopOfCityDeck(N)` into `Game::CHOSEN_CARD`; do not repeatedly query the top cards during the two picker steps.
- The Reaction button handler queues `createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN", $this->Id)` after storing the snapshot. This is the `Reaction_01144` pattern: a button Reaction may launch dedicated states when the effect needs richer UI.
- Both sink and reorder states call `argsForStatePrivate()`. **Looking is private information**; public `argsForState()` leaks card properties to every client.
- Sink exactly one snapshotted card with `createCardAddedToCityDeckEvent($owner->ControllerId, $id, false)`. `false` means bottom. Do not copy `Action_02014`, which moves cards to `LOCATION_CITY_DISCARD`; **sink is not discard**.
- Remove the sunk card from the stored snapshot. If zero remain, finish. If one remains, put it on top and finish because its order is forced. Only enter the reorder state for two or more cards.
- Reorder validation must require exactly the remaining ids, with no omissions or foreign ids. JS uses `onChooseListCardConfirmed()` for the one-card sink and `onCardsSorted()` for all-card ordering; wire `EventHandlers.js` for the sort state's enable/disable behavior.
- The same transition string may safely be used in different event dispatch maps (e.g. `"03052"` in `DUSK_PHASE_BEGIN_EVENTS` and `DUEL_CHOOSE_TECHNIQUE_EVENTS`). Each dispatcher resolves its own map.

Reference: `Reaction_03052`; private top-deck UI sibling `_01177` Penya; sink event sibling `Action_01035`; discard contrast `Action_02014`.

### After the Owner's location is claimed

For "After <Owner>'s location is claimed, she may move to a different City location" (`Reaction_03049` Ekaterina). Listen on `EventLocationClaimed`.

Required gates:

```php
if (! ($event instanceof EventLocationClaimed)) return;
if (! $this->isAvailable()) return;
$owner = $this->getOwningCharacter($event->theah);
// liveness: ControllerId > 0, !characterIsInDiscardOrLocker, cardInCity
if ($event->location != $owner->Location) return;
if (count($this->getEligibleDestinations($event->theah, $owner)) == 0) return;
```

**Any claimer vs opponent-only:**

| Text | Gate |
|---|---|
| "After <Owner>'s location is claimed …" (no "opponent" / "you") | Any `$event->playerId` — competitive notes for Leader Ekaterina: *all* claim effects |
| "After an **opponent** claims this location …" | `$event->playerId != $owner->ControllerId` — base-game `Reaction_01117` |

Effect: Pattern D "Move <Owner> to any City location" button list (`Reaction_03040` shape) — `createCardMovingEvent(..., $engage=false, …)` when Engage is not printed; Pass without `setUsed`. For Continuous (Ekaterina), also omit `setUsed(true)` on a successful move.

WHY Continuous for Leader Ekaterina: unlabelled After…may + Tomoe end-of-HD multi-claim abuse lines need every claim prompt, not a once-per-day Reaction slot. Reference: `Reaction_03049`, Continuous sibling `Reaction_03025`.

### Opponent engages your other Trait → they may En Garde

For Aimée `_04021`: **"After an opponent's effect engages your other Musketeer at this location, they may en garde."** Unlabelled After…may → **Continuous** Pattern D (no `setUsed(true)`; keep `$this->setUsed(` in a comment).

**Trigger:** `EventCardEngaged` with `!$event->canceled`.

**Gates:**

1. `$this->isAvailable()`
2. Owner in play (`ControllerId > 0`, `!characterIsInDiscardOrLocker`)
3. **Opponent's effect** — mirror `Reaction_03031::isOpponentAbility`:
   - `$source = getCardById($event->sourceId)` → `ControllerId` is a live opponent (`!= owner` and `!= 0`), **or**
   - in-play action via `getInPlayActionById($event->abilityId)` whose owning card has an opponent controller
   - **`sourceId == 0` is NOT an effect** — Challenge / framework auto-engage (`FrameworkActionsTrait`) omits source; do not prompt
4. Engaged card is a `Character`, **not** Owner (`cardId != owner.Id`), same controller, same location, has the printed Trait (e.g. Musketeer)

**Timing trap:** `EventCardEngaged.runEventHubAfterCards = true` — Hub sets `Engaged = true` **after** card `handleEvent`. Do **not** require `$musketeer->Engaged` at trigger time. Stash a public `$engagedMusketeerId` for serialize; in `performReaction` re-validate `Engaged` (and location/trait/controller) before `createCardEngagedEvent`.

**Buttons:** En Garde / Pass. Effect = `createCardEngardedEvent(owner.ControllerId, musketeer.Id, owner.Id, $this->Id)`. Clear stash after Pass or En Garde. Continuous → omit `setUsed(true)` on success.

Contrast: Ved'ma `Reaction_01124` (self Engaged by own Sorcery Risk — daily Reaction, not Continuous, not opponent-gated).

Reference: `Reaction_04021`; opponent-ability helper sibling `Reaction_03031`; Continuous siblings `Reaction_03049` / `Reaction_03025`.

### Cancel-and-reissue Reaction — opt out of an auto-emitted event

For text like "During Dusk, you may choose not to move <Owner> Home" (`_03016` Ise). The framework's `stDuskPhaseCleanup` emits a `createCardMovingEvent(..., LOCATION_PLAYER_HOME, $engage=false, $sourceId=0)` for every non-Home controlled character. The Reaction intercepts that event, asks the player, and either keeps it canceled (effect: stay) or re-queues it (effect: go home as normal).

Skeleton (mirror `Reaction_03016a` Ise, in-hand sibling `Reaction_01140`):

```php
class Reaction_NNNNN extends CardReaction
{
    private ?EventCardMoving $cardMovingEvent = null;
    private string $fromLocation = '';

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Keep in city'), 'stay');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoving)) return;
        if ($event->canceled || $event->unstoppable) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;

        if ($event->cardId != $owner->Id) return;
        if ($event->toLocation != Game::LOCATION_PLAYER_HOME) return;
        if ($event->sourceId != 0) return;                              // auto-emitter signal
        if (in_array($owner->Id, $event->cancelDeclinedByCardIds)) return;  // re-queue guard

        $turnPhase = (int) $event->theah->game->getGameStateValue(Game::TURN_PHASE);
        if ($turnPhase != Game::DUSK) return;

        $this->cardMovingEvent = clone $event;
        unset($this->cardMovingEvent->theah);
        $this->fromLocation = $event->fromLocation;
        $event->canceled = true;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->stackEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);
        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId == 'stay')
        {
            // Already canceled in handleEvent; just announce + setUsed.
            $this->setUsed($game->theah, true);
            $this->cardMovingEvent = null;
            $this->fromLocation = '';
            $owner->IsUpdated = true;
        }

        if ($reactionId == 'decline')
        {
            // Re-queue the move with a self-marker so handleEvent doesn't re-trigger.
            $this->cardMovingEvent->cancelDeclinedByCardIds[] = $owner->Id;
            $game->theah->queueEvent($this->cardMovingEvent);
            $this->cardMovingEvent = null;
            $this->fromLocation = '';
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
```

WHY `sourceId == 0` is the auto-emitter signal: grepping `createCardMovingEvent(...LOCATION_PLAYER_HOME...)` confirms every ability-driven move-home passes a non-zero sourceId (action id / reaction id / etc.); only `stDuskPhaseCleanup` and `_01126`'s own self-recall emit with the default `$sourceId=0`. For the Dusk opt-out, that's exactly the signal you want — abilities that *also* try to move the Owner home should not be intercepted, because the player already chose to play that ability.

WHY the redundant `TURN_PHASE == DUSK` gate: belt-and-suspenders. Cheap, authoritative, and protects against any future code path that emits a zero-source move-home outside Dusk.

WHY clone + `unset($this->cardMovingEvent->theah)`: storing the event for later re-queue. The `theah` reference holds the Theah/Game graph; unsetting prevents recursive serialization when the reaction-instance state is persisted via `IsUpdated`. This matches `Reaction_01140`'s shape.

WHY `cancelDeclinedByCardIds` instead of "just delete the stored event": when the player picks "Decline", the move MUST still happen — the framework's downstream logic (engardment cleanup at dusk, etc.) depends on every non-Home character routing home. Re-queueing the cloned event with `cancelDeclinedByCardIds[] = $owner->Id` lets the move proceed without `handleEvent` immediately re-catching the re-queued event. Same dance as `Reaction_01140`.

WHY `stackEvent` (not `queueEvent`) for the transition: stacking puts the reaction prompt ahead of other queued cleanup events so the player decision happens BEFORE subsequent dusk cleanup events fire for other characters. Matches `Reaction_01140`'s convention.

Reference: `Reaction_03016a` (Ise Dusk opt-out, on a Character in play), `Reaction_01140` (in-hand RiskReaction sibling — same dance for player-driven moves).

**Discard events also support `cancelDeclinedByCardIds`.** `EventCardDiscardedFromPlay` and `EventCardAddedToCityDiscardPile` carry the same array field as `EventCardMoving` (added for Tomas `_04013`). Clone before `$event->canceled = true`, Decline re-queues with `cancelDeclinedByCardIds[] = $owner->Id`. Do not invent a parallel marker.

### Would-be-discarded attachment → equip paying costs

Printed shape (Tomas `_04013`): **"City Reaction: When a non-Artifact attachment equipped to your character at this location would be put into a discard pile • Equip it to your character at this location instead, paying all costs."**

This is cancel-and-reissue **plus** click-to-pay equip — not a past-tense "after discarded" Reaction (the card must never reach the discard pile on Accept).

**Trigger both discard pipelines:**

| Pipeline | Event |
|---|---|
| Faction / Risk attachments | `EventCardDiscardedFromPlay` |
| City attachments | `EventCardAddedToCityDiscardPile` |

Both set `runEventHubAfterCards = true`, so `$event->canceled = true` in card `handleEvent` prevents the Hub move (same as Mysta `_02037` Forced sink).

**WHY stash on `EventAttachmentUnequipped` (map `attachmentId => hostId`):**

1. Destroy/type-limit/death always queue **unequip then discard**. Unequip has `runEventHubAfterCards = false` → Hub clears `AttachedToId` **before** discard card handlers run.
2. Discard `sourceId` is **not** reliably the host — `Technique_02026b` / destroy effects pass the ability owner; only `Character::unEquipAllAttachments` / `Reaction_AttachmentTypeLimit` pass the character id.
3. A single pending id loses multi-attachment death chains (A/B/C unequip then discard FIFO). Use a **map**; clear entries on equip of that id, dusk end, Accept, Decline.

Gates on discard: `isAvailable`, `cardInCity($owner)`, non-Artifact, not `FakeAttachment`, `fromLocation == owner.Location`, host was your character at that location (stash or still-attached fallback), **and** ≥1 affordable eligible equip target (`canAttachTo` + `!hasEquipRestrictions` + `handWealthCount >= equipCost`). Skip the prompt when nobody can be paid for.

**Stages:** `'pick'` (button per eligible character with printed cost) → `'pay'` (Don Constanzo click-to-pay) → finalize. Cost 0 skips pay. Cost = `max(0, WealthCost - getEquipDiscount(performer, attachment))`.

**Do NOT use `PAY_STATE_EQUIP_ATTACHMENT`.** That success path returns to High Drama player-turn; discard salvage fires mid-duel / type-limit / character death. Roll payment inside the Reaction.

**Finalize:** queue payment hand discards (`asPayment = true`) atomically → `getRequiredAttachTargetId` → `createAttachmentEquippedEvent(..., $asAction = true, discount, cost, explanations)` → `eventCheck` → queue. `setUsed(true)` only on Accept.

**Decline:** re-queue cloned discard with `cancelDeclinedByCardIds[] = owner.Id`; do **not** `setUsed` (another attachment the same day can still salvage). Use `stackEvent` for the reaction transition so the prompt jumps ahead of later discards in the same batch.

Reference: `Reaction_04013`; payment sibling `Reaction_03003`; cancel-dance sibling `Reaction_03016a`; Forced discard-cancel sibling `_02037` Mysta.

### Reactions that need to pay a wealth cost — click-to-pay

For Reactions where the effect costs Wealth (e.g., Don Constanzo's "at -1 cost", Tomas's "paying all costs" re-equip), the framework's `PAY_STATE_PLAY_BRUTE` / `actPayForBrute` / **`PAY_STATE_EQUIP_ATTACHMENT`** is usually NOT a fit because:

- Its success transition is hard-coded to `HIGH_DRAMA_PLAYER_TURN_EVENTS`, but reactions can fire outside high drama (dawn cleanup, pressure, duel cleanup, mid-duel discard) and must return to whatever state cycle invoked them.
- It requires the paid-for card to be in `LOCATION_HAND`. Reactions like "from hand or discard pile" or "already in play, about-to-discard" don't fit.

Instead, do the payment **inside the Reaction class** using the standard `playerReaction` loop. Pattern:

1. **Reaction-instance state** for the running payment:
   ```php
   private array $paidCardIds = [];       // cards selected so far
   private int $paidWealth = 0;           // running wealth sum
   private bool $paidHasWealthCard = false; // true if any selected card has the "Wealth" trait
   ```
   Plus a `$stage` field (e.g., `'pick'` → `'pay'`).
2. **`getReactionButtonProperties` during the `'pay'` stage** lists every card in hand as a button (`Pay with <name> (+N Wealth)`), excluding cards already in `$paidCardIds` and excluding the card being put into play (when it's the hand-source one). Always include `< Back` and `Decline`.
3. **Each click runs `handlePay`**: validate the card, append to `paidCardIds`, increment `paidWealth` by `$card->hasTrait("Wealth") ? 2 : 1`, set `paidHasWealthCard` if applicable.
4. **`isPaymentComplete($cost)` mirrors `UtilitiesTrait::isValidWealthPayment`** — exact match OR `paidWealth == cost + 1 && paidHasWealthCard` (the "overpay by 1 using a Wealth card" rule).
5. **Filter button list to valid-next-clicks** via a `wouldClickProduceValidPayment` helper. Suppress buttons that would put paid beyond `cost + 1` or beyond `cost` without using a Wealth card.
6. **Queue discards atomically at finalize**, not per-click. WHY: `Decline` becomes a clean rollback (no cards were ever queued for discard), AND downstream reactions to `EventCardDiscardedFromHand` don't see partial-payment intermediate states.
7. **Always set `$owner->IsUpdated = true`** on every reaction-instance state mutation so the framework persists the running totals across reaction-loop iterations.
8. **Skip the `'pay'` stage entirely when `cost == 0`** — go straight to finalize.

Reference: `Reaction_03003` (Don Constanzo) — the canonical muster/pay implementation. Equip-paying sibling: `Reaction_04013` (Tomas) — same click-to-pay loop, cost from `getEquipDiscount`, finalize via `createAttachmentEquippedEvent`.

### Reaction examples

| File | Demonstrates |
|---|---|
| `Reaction_01006` | `IRiskReaction`-shaped pre-end-of-day cleanup ("Reaction: Before the end of the Day"). |
| `Reaction_01008` | "Cesca Scarpa copies the Sorcerer ability just played" — listens on `EventSorcererAbilityPlayed`, branches on the ability instance to copy actions/cards/etc. The original kitchen-sink Sorcerer-after-Sorcerer reaction. |
| `Reaction_01013` | Canonical "after my Red Hand is destroyed" Reaction — `EventCharacterDestroyed` trigger + button-based draw choice. Reference for the trait/controller/location identity gates. |
| `Reaction_01014` (Vittoria) | "Instead of me" target swap on `EventCardEngaged`/`EventChallengeIssued`/etc. ⚠ Re-emitting `EventCardEngaged` on an already-engaged character will trip this. Pattern F users beware. |
| `Reaction_01089` | Soline el Gato's "after an Action resolves" — `EventActionResolved` + button-per-adjacent-location. |
| `Reaction_01116a`, `Reaction_01116b` | Yevgeni's paired Reactions on a single Leader. |
| `Reaction_01118` | Elina's "after a Sorcerer ability targets a character at her location, move Renown to her location" — `sourceId == owner` OR `performerId == owner` pattern. |
| `Reaction_02001` | Andriana — Sorcerer Reaction (so implements `ISorcererAbility`); button-prompts to wound a non-Sorcerer. |
| `Reaction_03001` | Cesca del Rosso's "after Cesca performs a Sorcerer ability, wound an opposing character" — button-per-opposing-character target picker, with a Pass button. |
| `Reaction_03003` (Don Constanzo) | Multi-stage Reaction with hand/discard source selection, **incremental click-to-pay wealth handling** rolled inside the reaction (no PAY_STATE_PLAY_BRUTE coupling), and muster-at-Home. Canonical reference for cost-bearing Reactions and "put into play from hand or discard pile." |
| `Reaction_04013` (Tomas — salvage attachment) | **Would-be-discard → equip paying costs.** Cancel both discard events; unequip-stash map for host identity; pick character + click-to-pay (`getEquipDiscount`); Decline re-queues discard via `cancelDeclinedByCardIds`. No `PAY_STATE_EQUIP_ATTACHMENT`. |
| `Reaction_03016a` (Schwester Ise — Dusk opt-out) | **Canonical cancel-and-reissue Reaction.** Listens on `EventCardMoving` for the Dusk auto-move home (`sourceId == 0`, `toLocation == LOCATION_PLAYER_HOME`, `TURN_PHASE == DUSK`). Cancels and prompts; "Keep in city" calls `setUsed`, "Decline" re-queues the cloned event with `cancelDeclinedByCardIds[] = owner.Id`. Uses `stackEvent` so the prompt jumps ahead of other queued dusk cleanup. In-hand sibling: `Reaction_01140`. |
| `Reaction_03016b` (Schwester Ise — pull a friendly) | **Canonical "after enemy moves to my location" reaction.** Listens on `EventCardMoved` with `cardId != owner.Id`, `toLocation == owner.Location`, `cardInCity(owner)`, enemy controller check; button per eligible mover (own characters not at owner's location); queues `createCardMovingEvent` for the chosen character to the owner's location. |
| `Reaction_03040` (Soline el Gato — any character arrives) | **"After a character moves here"** without enemy gate — allies trigger too. Effect: button-per-other-city-location + Pass; `createCardMovingEvent(engage=false)` for Soline herself. Contrast `Reaction_03016b` (enemy-only) and `Reaction_01089` (adjacent-only after Action resolves). |
| `Reaction_04003a` (Desideria — Thug destroy → hand) | **En Garde City Reaction** + duel/opponent cause gate + **deferred mid-duel Hand return**. `EventCharacterWounded` marks opponent lethal; Destroyed ORs `IN_DUEL`; locker/discard → hand; `stDuelEnd` flush. |
| `Reaction_04003b` (Desideria — after Sorcerer ability) | Wound self + draw; **not** `ISorcererAbility`; Cesca/Elina `sourceId`/`performerId` identity. |
| `Reaction_04021` (Aimée — opponent engages ally Musketeer) | **Continuous** on `EventCardEngaged`; opponent-effect gate (`sourceId==0` skip); other Trait at location; En Garde via `createCardEngardedEvent`; Engaged re-check in `performReaction` (`runEventHubAfterCards`). |

