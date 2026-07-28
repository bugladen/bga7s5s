> Part of **create-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern E — Techniques and Maneuvers

The Character lineage already brings `TechniqueTrait`. Add `IHasManeuvers` + `ManeuverTrait` for maneuvers. Implement under `cards/<expansion>/techniques/` or `cards/<expansion>/maneuvers/`. The base `create-city-character` skill has the general shape; the notes below are duel-specific patterns that come up often.

### Explicit `setUsed(true)` in the effect handler

The base `Technique` class auto-fires `$this->setUsed($theah, true)` on `EventTechniqueActivated`, so a properly-activated technique gets marked used without explicit code. But the convention in non-trivial techniques (`Technique_01093`, `Technique_03025a`, `Technique_03025b`) is to also call it explicitly in the technique's own effect handler — either on `EventDuelCalculateTechniqueValues` (stat modifiers) or on `EventResolveTechnique` (state-transition effects):

```php
if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id) {
    // ... apply the modifier
    $this->setUsed($event->theah, true);   // explicit; idempotent vs the auto path
}
```

It's idempotent vs the base class's auto-call, but cheap insurance against edge paths where the activation event might not have fired (copied techniques, cancellation/re-issue flows). Base `Technique::handleEvent` already resets `Used` on `EventDuelEnd` (because `ResetOnDuelEnd = true` by default), so the explicit `setUsed(true)` doesn't leak across duels.

### In-duel availability gate

Most Character techniques are duel-only — they're activated during a duel round by the actor. Gate `isAvailableToPlayer`:

```php
public function isAvailableToPlayer(int $playerId, Theah $theah): bool
{
    if (! parent::isAvailableToPlayer($playerId, $theah)) return false;
    if (! $theah->game->globals->get(Game::IN_DUEL, false)) return false;

    $owner = $this->getOwningCharacter($theah);
    $actor = $theah->getDuelRoundActor();
    if ($actor === null || $actor->Id !== $owner->Id) return false;

    // ... card-specific preconditions (adversary state, equipped weapons, etc.)
    return true;
}
```

Helpers worth knowing:
- `$theah->getDuelRoundActor(): ?Character` — the participant whose turn it is this round.
- `$theah->getDuelRoundOpponent(): ?Character` — the other participant. Returns the *last-known* state when the opponent is in discard/locker (e.g., already destroyed).
- `$theah->getDuelChallengerId() / getDuelDefenderId() / getDuelOpponentId($actorId)` — id-only accessors.
- `Game::IN_DUEL` global — true between duel start and end.
- `Game::DUEL_GAMBLED` global — true after the actor locks in a combat card via gamble; cleared at end of round.

### Gambling Technique gate

"**Gambling Technique:** …" — only available if the actor has gambled for their combat card this round. Add one extra check on top of the in-duel gate:

```php
if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false)) return false;
```

WHY use the global (and not query `duel_round.gambled` directly): the global is set in `FrameworkActionsTrait::actChooseGambleCard` at the moment the gambled combat card is locked in, and cleared in `stDoneRound`. It's the cheapest authoritative answer to "has the actor gambled this round." `isAvailableToPlayer` runs on a hot path (every time the action menu refreshes), so the SQL alternative is wasteful.

Reference: `Technique_03002` (Aja).

### Privately look at adversary's hand

For Yevgeni `_03052`: **"Gambling Technique: Look at your adversary's hand."**

Use the standard Gambling Technique availability gates: parent, `IN_DUEL`, `DUEL_GAMBLED`, and `getDuelRoundActor()->Id == owner.Id`. Also require a non-null adversary and a nonempty adversary hand so the menu does not offer a no-op.

On `EventResolveTechnique`, queue `createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN", $this->Id)` — the **owner remains active**, unlike an adversary-discard effect where the adversary becomes active. The state:

- uses `argsForStatePrivate()`; `getArgsFromTechnique` returns the adversary hand's card property arrays and opponent name only to the active owner;
- renders cards in `chooseList` with selection mode `0` (read-only);
- provides a Done button through `actFromCardPass` → `actFromTechniquePass` → `nextState()`;
- clears/hides `chooseList` in `OnLeavingState`.

**Look vs reveal is load-bearing.** Do not mirror `Technique_03043`: that card says Reveal, publicly logs card identities, and uses a multiple-active-player acknowledgement. "Look at" exposes identities only to the looking player; the public log may say that a look occurred, but must not name the cards.

Reference: `Technique_03052`; public-reveal contrast `Technique_03043`.

### Look / sink / reorder own Faction Deck (Technique)

For Benci `_04001`: **"Technique: Look at the top two cards of your deck. You may sink one or both and return the others in any order."**

Sibling shapes:
- **Action_04cd15** (Syrneth Puzzle Box) — High Drama City Action, same Faction Deck sink+reorder, public args.
- **Reaction_03052** — City Deck look/sink/reorder, private args.
- **Technique_01010** — look adversary deck, sink any, **no** reorder ("same order" / leave remainder in place).

**Availability:** `IN_DUEL` + actor-is-owner + `count(getCardsOnTopOfPlayerFactionDeck(controller, N)) > 0`. Empty deck → hide.

**On `EventResolveTechnique`:**
1. Snapshot top N property arrays into `Game::CHOSEN_CARD`.
2. Public notify that a look occurred (do **not** name the cards — Look ≠ Reveal).
3. `createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN", $this->Id)` — HIGHEST_PRIORITY before other resolve noise.
4. `$this->setUsed($theah, true)`. Skip the transition if the snapshot is empty (deck emptied between availability and resolve).

**Sink step (`DUEL_CHOOSE_TECHNIQUE_NNNNN`):**
- State `getArgs()` → `argsForStatePrivate()`.
- `#[PossibleAction]` `actFromCardWithIds` + `actFromCardPass` (Pass = sink none → finishReplaceOrReorder). Do **not** use bare `actPass` — that skips reorder.
- Validate ids ⊆ snapshot; **immediately** `$deck->insertCardOnExtremePosition($id, $deckName, false)` per sink. WHY immediate: queued `createCardAddedToFactionDeckEvent` races `finishReplaceOrReorder`'s top inserts before EVENTS drains (04cd15 comment).
- Update `CHOSEN_CARD` to remaining; call finish helper.

**finishReplaceOrReorder:**
- 0 remaining → `nextState("done")` back to `DUEL_CHOOSE_TECHNIQUE_EVENTS`.
- 1 remaining → top-insert that card; `nextState("done")` (order forced).
- 2+ remaining → `nextState("reorder")` → `DUEL_CHOOSE_TECHNIQUE_NNNNN_2`.

**Reorder step:** validate complete permutation of remaining ids; top-insert in JS sort order (`onCardsSorted` — last selected ends on top); `nextState("cardsSorted")`.

**states.inc.php:** only `"NNNNN"` under `DUEL_CHOOSE_TECHNIQUE_EVENTS` — step 2 is reached via the sink state's own `"reorder"` transition, not `createTransitionEvent("NNNNN_2")`.

**JS (`OnEntering` / `OnUpdate` / `OnLeaving`.<expansion>):**
- Sink: chooseList `setSelectionMode(2)`, Sink Selected → `onMultipleChooseListCardsConfirmed`, Pass → `actFromCardPass`. Private path: `args.args._private.args.cards`.
- Reorder: same chooseList, Confirm → `onCardsSorted()`.

**CRITICAL — `EventHandlers.js` `onChooseCardClicked`:** without an entry, the default branch only enables Confirm when **exactly one** card is selected, and **never** calls `addSortTagToCard` — so multi-sink Confirm stays broken and reorder number chips never appear. Wire:

```js
'duelChooseTechnique_NNNNN': () => {
    // multi-select sink — enable when length > 0
},
'duelChooseTechnique_NNNNN_2': () => {
    this.addSortTagToCard(item_id);
    // enable Confirm when all items selected (ordered)
},
```

Mirror `highDramaPhase04cd15` / `highDramaPhase04cd15_2` / `duskPhaseBegin03052_2`.

**"same order" vs "any order":** if the text says return others in the **same** order, omit the reorder state and only sink (01010). If **any** order, include the reorder step (04001 / 04cd15).

Reference: `Technique_04001`, `Action_04cd15`, `Reaction_03052`, sink-only `Technique_01010`.

### −N Thrust / Riposte cost ("combat card must have at least N")

Parenthetical "(Your combat card must have at least N [Thrust].)" is the printed clarification of a −N technique cost. Gate `isAvailableToPlayer` with `$theah->getCurrentRoundThrust() < N` (or `getCurrentRoundRiposte()` for Riposte costs). Apply the reduction on `EventDuelCalculateTechniqueValues`: `$event->thrust -= N` plus an explanation string.

WHY `getCurrentRoundThrust()` (sum of combat + maneuver + technique columns for the round) rather than reading `$combatCard->Thrust` alone: mirrors `Technique_01050` / `Technique_01093` and accounts for already-committed round modifiers. At technique-pick time `combat_thrust` is already written.

References: `Technique_01050` (−1 Thrust), `Technique_03039` (−2 Thrust), `Technique_01093` (−1 Riposte).

### Adversary discards a card (Technique)

On `EventResolveTechnique`, if the adversary's hand is nonempty, queue `createTransitionEvent($adversary->ControllerId, $owner->Id, "NNNNN", $this->Id)` — **active player becomes the adversary**. State class `State_duelChooseTechnique_NNNNN` (`521` + cardId) under `States/<expansion>/`; constant in `States.php`; `"NNNNN"` entry in `DUEL_CHOOSE_TECHNIQUE_EVENTS.transitions` (not the High Drama map).

`actFromTechniqueWithId`: validate card exists, active player controls it, `Location == LOCATION_HAND`; queue `createCardDiscardedFromHandEvent(..., $asEffect = true)`; `nextState()`. Empty hand → skip the picker entirely (no Pass needed — nothing to discard).

JS (adversary is active, so their `factionHand` is the picker):
- `OnEnteringState.<expansion>.js` — `factionHand.setSelectionMode('single')`
- `OnUpdateActionButtons.<expansion>.js` — Confirm → `onCardDiscarded()` (disabled until selection)
- `OnLeavingState.<expansion>.js` — `setSelectionMode('none')`
- **`EventHandlers.js`** — add the state name to the factionHand click map so Confirm enables on selection (easy to miss; without it the button stays disabled)

Reference: `Technique_01093` Maya (canonical), `Technique_03039` Íñigo (same picker + follow-on effects).

### Post-discard hand-size En Garde + EndOfRound move Home

Composite follow-ons after adversary discard (`Technique_03039`):

1. **En Garde if adversary hand > yours** — printed "Then, if…" means **after** discard. In `actFromTechniqueWithId` the discard event is queued, not flushed — compare `(count(adversaryHand) - 1) > count(ownerHand)`. Empty-hand path (no picker) compares `0 > ownerHand` (never engardes). Queue `createCardEngardedEvent` when the inequality holds.
2. **Move Home at end of round** — set a private `$MoveHome = true` on `EventResolveTechnique` **unconditionally** (do not gate on the hand-size clause unless the text does). On `EventDuelEndOfRound`: clear flag; skip if discard/locker or already `LOCATION_PLAYER_HOME`; queue `createCardMovingEvent(..., LOCATION_PLAYER_HOME, $engage=false, ...)` when Engage is not printed (contrast `_01053`, which engages). Clear on `EventTechniqueCanceled` / leftover `EventDuelEnd`.

Split the −N Thrust onto `EventDuelCalculateTechniqueValues` and the interactive / deferred effects onto `EventResolveTechnique` — both fire when the technique is chosen (`FrameworkActionsTrait` queues Resolve then Calculate).

### Gain Lethal — in-duel vs city-challenge

There are two completely different "Gain Lethal" pipelines depending on context. Don't conflate them.

| Event | When it fires | Use case |
|---|---|---|
| `EventGenerateChallengeThreat` | City-action challenge resolution (no duel; single threat roll) | Techniques granting Lethal during a non-duel challenge. Set `$event->adversaryThreatIsLethal = true` directly on the event. |
| `EventDuelCalculateTechniqueValues` | Per-technique calculation phase during a duel round | Techniques granting Lethal during a duel. Queue `EventFactory::createGainLethalEvent($event->actorId, $event->theah)` — this internally creates a `ThreatModified` event that marks the adversary's threat lethal regardless of which side the actor is. |

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
    {
        $lethalEvent = EventFactory::createGainLethalEvent($event->actorId, $event->theah);
        $event->theah->queueEvent($lethalEvent);
    }
}
```

A technique can handle BOTH events if it's usable in both contexts (see `Technique_01049` and the generic `Technique_GainLethal` helper). A Gambling Technique is duel-only, so only `EventDuelCalculateTechniqueValues` matters — gambling is exclusively a duel-round mechanic.

`createGainLethalEvent($actorId, $theah)` reads as: "the actor's strike against the adversary is now lethal." The naming inside the produced event (`challengerThreatIsLethal` / `defenderThreatIsLethal`) describes whose threat is lethal — i.e., the threat dealt TO that role. The factory figures out the sign for you; just pass the actor's id.

References: `Technique_GainLethal` (generic two-pipeline helper), `Technique_01049` (in-duel + city-context), `Technique_03002` (Aja, in-duel only via Gambling Technique gate).

### `EventDuelCalculateTechniqueValues` field shape

Unlike `EventDuelCalculateCombatCardStats` (which exposes `addRiposte`/`addParry`/`addThrust`/`removeRiposte`/etc. methods and respects `dashedX` flags), `EventDuelCalculateTechniqueValues` has plain int fields `$riposte`/`$parry`/`$thrust` you mutate directly:

```php
if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
{
    $event->parry  += 1;
    $event->thrust -= 1;
    $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds +1 Parry."), $owner->getInjectCode(), $this->Name);
}
```

Reference: `Technique_01050` (–1 Thrust + wound), `Technique_03004` Elena (+1 Parry + wound). You can queue follow-on events (e.g., `createCharacterBeingWoundedEvent`, `createGainLethalEvent`) from inside the same calc handler — the queued events fire after the calc resolves.

### "If <owner>'s combat card is a <trait>" gate

For techniques gated on the actor's combat card having a particular trait (`_03004` Elena's "if combat card is a Sorcery"):

```php
$combatCards = $theah->getCombatCardsForCurrentRound();
foreach ($combatCards as $card)
{
    if ($card->ControllerId == $owner->ControllerId && $card->hasTrait("Sorcery"))
    {
        return true;
    }
}
return false;
```

`getCombatCardsForCurrentRound()` returns BOTH players' combat cards. Filter by `$card->ControllerId == $owner->ControllerId` to isolate the actor's own combat card. (Since the technique already gates on `actor->Id == owner->Id`, this is the actor's own combat card.) Cesca Scarpa's `Technique_02003` is similar but cares about *any* Sorcery played in the round, so it skips the ControllerId filter — match the card text literally.

**OR of traits** ("Flourish or Sorcery" — `_03050` Daichi): same loop; accept when either `hasTrait` matches:

```php
if ($card->ControllerId == $owner->ControllerId
    && ($card->hasTrait("Flourish") || $card->hasTrait("Sorcery")))
{
    return true;
}
```

Plain +N Riposte/Parry/Thrust after the gate still uses `EventDuelCalculateTechniqueValues` (`$event->riposte += N` etc.) — no state class/JS when there is no player choice. Reference: `Technique_03050` (+1 Riposte), `Technique_03004` (+1 Parry + wound).

### "If <Owner> is equipped with X **or** there is an X card in his dueling line" gate

For techniques gated on a trait being present on either the owner's attachments OR the owner's side of the dueling line (`_03014` Kaspar — "equipped with an Eisenfaust attachment or there is an Eisenfaust card in his dueling line"). Check BOTH sources, OR them, and gate `isAvailableToPlayer` on the OR:

```php
private function hasEisenfaust(Theah $theah, Character $owner): bool
{
    // Attachments: $owner->Attachments is an array of *ids*. Look each up.
    foreach ($owner->Attachments as $attachmentId)
    {
        $attachment = $theah->getCardById($attachmentId);
        if ($attachment !== null && $attachment->hasTrait("Eisenfaust"))
        {
            return true;
        }
    }

    // Dueling line: per-player, keyed on the owner's ControllerId.
    $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $owner->ControllerId);
    foreach ($cards as $card)
    {
        if ($card->hasTrait("Eisenfaust"))
        {
            return true;
        }
    }
    return false;
}
```

WHY `getCardObjectsAtLocation(LOCATION_DUELING_LINE, $owner->ControllerId)` is safe inside an `IN_DUEL` gate: the dueling line is per-player and accumulates combat cards over the duel's rounds; outside a duel it's empty (the line is cleared at duel end). With the standard `isAvailableToPlayer` gate on `IN_DUEL` + `actor == owner`, the cards returned are the owner's combat cards from this duel's prior rounds (plus the current round once a combat card has been picked). If the card text said "his dueling line *this round*" you'd switch to `getCombatCardsForCurrentRound()` filtered by controller; "his dueling line" without qualifier means the cumulative line.

WHY iterate `$owner->Attachments` by id rather than calling `hasWeaponEquipped` / similar helper: there's no `hasAttachmentWithTrait($trait)` helper on `Character`. The id-list-then-`getCardById` pattern is the one in use across the codebase (e.g. `Maneuver_01054`'s `if ($attachment && $attachment->hasTrait("Eisenfaust"))`). Don't roll a new helper — match the existing shape.

### Wound-as-cost: queue the wound event at `EventResolveTechnique` BEFORE the transition

For techniques whose printed cost is "Wound <Owner> • <effect>" (Daniella Dietrich `_03013`), the wound is part of the cost — paid before the effect resolves. The natural place is the `EventResolveTechnique` handler, where you queue BOTH the wound event and the technique-transition event, in that order:

```php
if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
{
    $owner = $this->getOwningCharacter($event->theah);

    // Pay the cost: wound the owner. Cost-before-effect per the "Wound X •" split.
    $woundedEvent = EventFactory::createCharacterBeingWoundedEvent(
        $owner->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
    );
    $event->theah->queueEvent($woundedEvent);

    // Effect: transition into the target-picker state.
    $transition = EventFactory::createTechniqueTransitionEvent(
        $owner->ControllerId, $owner->Id, "NNNNN", $this->Id
    );
    $event->theah->queueEvent($transition);
}
```

WHY at resolve-time and not inside `actFromTechniqueWithId`: by the time the player picks a swap target in `actFromTechniqueWithId`, the cost has already been paid — the wound fired earlier when `EventResolveTechnique` flushed. Putting the wound in the act handler would invert the cost/effect order printed on the card and let a player back out of the cost by declining the picker. Queue at resolve and the wound is committed regardless of whether the player completes the effect.

The wound-event factory signature mirrors `Technique_01063`'s use: `($characterId, $sourceCharacterId, $wounds, $sourceDescription, $techniqueId)`.

### Swap mechanics inline in `actFromTechniqueWithId` — challenge vs duel context

For "swap <Owner> with another character" techniques (Daniella Dietrich `_03013` — Wound + swap with Hunter/Zealot at this location), don't defer the swap to event handlers. Do it inline in `actFromTechniqueWithId` so the player's commit unambiguously commits the swap. Branch on the state to handle the challenge-time and duel-time contexts differently:

```php
public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
{
    parent::actFromTechniqueWithId($game, $state, $stateName, $id);

    if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_NNNNN
        || $state == States::DUEL_CHOOSE_TECHNIQUE_NNNNN)
    {
        // ... target validation, notification ...

        $this->swapId = $target->Id;

        if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_NNNNN)
        {
            // Challenge context: duel not yet built. Redirect CHOSEN_PERFORMER
            // and move DUEL_CHALLENGER condition so the new challenger is the
            // one who actually enters the duel.
            $game->globals->set(Game::CHOSEN_PERFORMER, $target->Id);
            $owner->removeCondition(Game::DUEL_CHALLENGER);
            $target->addCondition(Game::DUEL_CHALLENGER);
            $owner->IsUpdated = true;
            $target->IsUpdated = true;
            $game->updateCardObjectInDb($owner);
            $game->updateCardObjectInDb($target);

            $challengerSwappedEvent = EventFactory::createChallengerSwappedEvent(
                $owner->ControllerId, $owner->Id, $target->Id
            );
            $game->theah->queueEvent($challengerSwappedEvent);
        }
        else  // DUEL_CHOOSE_TECHNIQUE_NNNNN — already inside a duel
        {
            // Duel context: rewrite the duel's stored participant list so the
            // target takes Daniella's seat for the rest of the duel.
            $duelId = $game->globals->get(Game::DUEL_ID);
            $round  = $game->globals->get(Game::DUEL_ROUND);
            $game->theah->swapParticipantsInDuel($duelId, $round, $owner->Id, $target->Id);
            $game->updateCardObjectInDb($owner);
            $game->updateCardObjectInDb($target);
        }

        $game->gamestate->nextState();
    }
}
```

Keep ONE thing in `handleEvent` — the `EventGenerateChallengeThreat` `actorId` redirect. That mutation can only happen at event-fire time:

```php
if ($event instanceof EventGenerateChallengeThreat
    && $event->techniqueId == $this->Id
    && $this->swapId != 0)
{
    // WHY: the event is in flight when threat is being calculated. Character
    // ::handleEvent (which adds the actor's stat to adversaryThreat when
    // actorId matches) and the EventHub threat notification both key on
    // $event->actorId. Without the redirect they still reference the original
    // challenger, even though DUEL_CHALLENGER condition has already moved.
    $event->actorId = $this->swapId;
}
```

WHY split the work this way (vs. mirroring Bastien's all-in-events approach in `Technique_01063Swap`): Bastien defers the condition swap into `EventGenerateChallengeThreat` (with a `CHALLENGE_ACCEPTED` guard) so the swap doesn't fire if the challenge is rejected. That's a stricter, more conservative shape. The in-`actFromTechniqueWithId` shape is cleaner to read and matches the user's preference (see project history), but if your card text says the swap is *conditional on the challenge being accepted*, prefer Bastien's pattern instead so a rejection doesn't leave a stuck DUEL_CHALLENGER condition on a character that never enters a duel.

### Optional engage Artifact for upgraded Parry ("+1 Parry. You may engage an Artifact … for +2 instead.")

For text like Ekaterina's Technique (`Technique_03049`): a base stat bonus that can be upgraded by engaging an equipped Artifact. Sibling: Katain `Technique_02011` is **mandatory** engage of a Ranged Weapon for +1 Parry (availability requires the attachment; no base option).

**Availability:** `IN_DUEL` + actor-is-owner only. Do **not** require an Artifact for the Technique to appear — the printed base (+1) is always legal.

**Resolve → optional choice → Calculate:**

```php
// EventResolveTechnique:
$artifacts = /* unengaged Artifact attachments on owner (skip FakeAttachment) */;
if (count($artifacts) == 0) {
    $this->ParryBonus = 1;   // no Artifact option — lock base
} else {
    // HIGHEST_PRIORITY so the choice runs before EventDuelCalculateTechniqueValues
    $event->theah->queueEvent(EventFactory::createTechniqueTransitionEvent(
        $owner->ControllerId, $owner->Id, "NNNNN", $this->Id
    ));
}

// EventDuelCalculateTechniqueValues:
if ($this->ParryBonus > 0) {
    $event->parry += $this->ParryBonus;
    // explanations + setUsed
}
$this->ParryBonus = 0;

// EventTechniqueCanceled: clear ParryBonus
```

**Choice state** (`DUEL_CHOOSE_TECHNIQUE_NNNNN`, id `521` + cardId):

- `getArgsFromTechnique` → `attachments` list `{id, name}` (unengaged Artifacts only).
- `actFromTechniqueWithId`: `id == 0` → `$ParryBonus = 1`; else validate Artifact equipped/unengaged → `createCardEngagedEvent` → `$ParryBonus = 2`.
- JS (`OnUpdateActionButtons.<expansion>.js`): `+1 Parry` button via `actFromCardWithId({ id: 0 })` + one button per `args.args.attachments` (same nest as Katain / Damya). Attachment button label can be just the name — state `descriptionMyTurn` carries the +2 meaning.

WHY `createTechniqueTransitionEvent` (not plain `createTransitionEvent`): HIGHEST_PRIORITY so the picker interrupts before Calculate when both are queued from Resolve. WHY skip the state when no Artifact: avoids a useless single-button prompt.

Reference: `Technique_03049` (optional), `Technique_02011` (mandatory engage-only).

### Engage named character's attachment and copy a Technique (third-party / granted)

For text like Yepikhodov's granted Technique (`Technique_03051`): **"Engage Yepikhodov's attachment • Copy the effects of a Technique on that attachment."** Sibling for the *copy resolve* half: Dame of Swords `Technique_02055` (copies from the **actor's** participant/attachments). Sibling for *mandatory engage-attachment*: Katain `Technique_02011`.

**Who owns the Technique:** the **ally who was granted it** (Jean-style aura). Availability: `IN_DUEL` + `getDuelRoundActor()->Id === getOwningCharacter()->Id` + named aura source at the same location with ≥1 unengaged non-Fake attachment that has copyable techniques.

**Finding the named character from the Technique class — avoid circular require:**

```php
// BAD: use Bga\...\cards\faf\_03051;  // Technique imports card; card grants Technique → circular
// GOOD: identify by ExpansionName + CardNumber (or Image), not instanceof
if ($character->ExpansionName === 'faf' && $character->CardNumber === 51) …
```

**Listing source techniques — do NOT use `isAvailableToPlayer`:**

Dame (`02055`) filters with `$t->isAvailableToPlayer($playerId, $theah)` because the actor **is** the participant those techniques belong to. Here the duel actor is the *ally*; techniques on Yepikhodov's attachments gate on actor==Yepikhodov and would **always** fail. List every non-temporary technique on his unengaged attachments (skip `ClassId === 'Technique_NNNNN'` self and `IsTemporaryCopy`).

**Resolve → picker → Engage + copy (Dame clone recipe):**

```php
// EventResolveTechnique → createTechniqueTransitionEvent(..., "NNNNN", $this->Id)

// actFromTechniqueWithIds:
$attachment = $technique->getOwningAttachment($game->theah);
// validate: unengaged, non-fake, equipped to the named character
$game->theah->queueEvent(EventFactory::createCardEngagedEvent(
    $actor->ControllerId, $attachment->Id, $owner->Id, $this->Id
));

$copy = clone $technique;
$copy->setOwnerId($actor->Id);
$copy->Id = $actor->Id . "_copy_" . $copy->ClassId;
$copy->IsTemporaryCopy = true;
$copy->Used = false;
$actor->addTechnique($copy, $game, $notify = false);

$game->globals->set(Game::CHOSEN_TECHNIQUE, $copy->Id);
$game->globals->set(Game::CHOSEN_TECHNIQUE_IS_MAIN, false);
$game->globals->set(Game::TRANSITION_INTERNAL_ID, $copy->Id);

// activate (copied=true) → resolve → calculate — same order as Technique_02055
$game->gamestate->nextState("cardChosen");
```

**JS:** `duelChooseTechnique_NNNNN` buttons from `args.args.techniques` via `actFromCardWithIds` + `JSON.stringify([technique.id])` — same nest as Dame `02055`.

WHY Engage before clone: printed cost is Engage the attachment; the copy then resolves as the actor. Temporary copies are cleaned on `EventDuelNewRound` / `EventDuelEnd` by base `Technique::handleEvent`.

Reference: `Technique_03051`, `Technique_02055` (copy pipeline), `Technique_02011` (engage-attachment cost without copy).

### Technique usable in BOTH challenge and duel contexts — two states, two routings, two state classes

A technique that fires in either a challenge-resolve flow or a duel round needs entries in BOTH dispatcher routes:

- **Challenge-time:** state ID `455` + 5-digit cardId (e.g. `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_03013 = 45503013`). Routed from `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS`. State class: `State_highDramaChallengeActionResolveTechnique_NNNNN`.
- **Duel-time:** state ID `521` + 5-digit cardId (e.g. `DUEL_CHOOSE_TECHNIQUE_03013 = 52103013`). Routed from `DUEL_CHOOSE_TECHNIQUE_EVENTS`. State class: `State_duelChooseTechnique_NNNNN`.

Both states live under `modules/php/States/<expansion>/` and extend `GameState`. The technique's `createTechniqueTransitionEvent($controllerId, $ownerId, "NNNNN", $this->Id)` uses the SAME transition-name string (`"NNNNN"`) in both contexts — the dispatcher routes correctly because the lookup is per-dispatcher-state. Both routing maps need the entry:

```php
// states.inc.php — HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS.transitions
"NNNNN" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_NNNNN,

// states.inc.php — DUEL_CHOOSE_TECHNIQUE_EVENTS.transitions
"NNNNN" => States::DUEL_CHOOSE_TECHNIQUE_NNNNN,
```

Both state classes use the default-`""` transition back to their dispatcher EVENTS state (it's the only exit), and both expose `actFromCardWithId` as their `#[PossibleAction]`. Their `getArgsFromTechnique`/`actFromTechniqueWithId` can share a single `if ($state == HIGH_DRAMA... || $state == DUEL_CHOOSE...)` branch since the args shape and act validation are identical — the only divergence is the swap mechanics (see above).

JS handlers live in `modules/js/{OnEnteringState,OnUpdateActionButtons,OnLeavingState}.<expansion>.js`. Both states need their own keyed handler in each file — the args shape and Confirm button are identical to the existing `_01063` Bastien handlers; copy-paste and rename. The `_01063` versions live in the `*.7s5s.js` files; faf cards' versions live in `*.faf.js` files.

WHY `actFromCardWithId` and not `actFromTechniqueWithId` as the `#[PossibleAction]`: the GameState framework's `actFromCardWithId` delegates into `Game::actFromCardWithId`, which the technique framework routes back to the technique's own `actFromTechniqueWithId` via the per-state dispatch in `StatesTrait`. Don't expose `actFromTechniqueWithId` directly as the `#[PossibleAction]` — mirror the existing `_01063` state classes.

### Disambiguating same-name characters in state descriptions

Some characters share a name across expansions (e.g., `_01036` "Daniella Dietrich" and `_03013` "Daniella Dietrich, Witch / Hunter"). The state's `descriptionMyTurn` is the only place this is user-visible; disambiguate by appending the `Title` in parens:

```php
descriptionMyTurn: clienttranslate('Daniella Dietrich (Witch, Hunter)')
                   . clienttranslate(': Wound and Swap with a Hunter or Zealot: ${you} must choose a Hunter or Zealot:'),
```

The state classes' `name` field (used by JS) doesn't need disambiguation because state IDs already differ — `_01036`'s state is `duelChooseTechnique_01036`, `_03013`'s is `duelChooseTechnique_03013`.

### Duel-flow events worth knowing

| Event | When it fires |
|---|---|
| `EventDuelStarted` / `EventDuelEnd` | Duel boundaries. |
| `EventNewDuelRound` / `EventDuelEndOfRound` | Round boundaries. |
| `EventDuelAttemptGamble` | Pre-check fired when the actor clicks Gamble. Throw via `eventCheck` to block gambling (Mysta's Technique_02037 pattern). |
| `EventDuelGambleCardsRevealed` | After cards are revealed during gambling. Carries `revealedCardIds`. |
| `EventDuelPlayerGambled` | After the actor selects a card from the gambled reveal — combat card locked in, `DUEL_GAMBLED = true`. |
| `EventTechniqueActivated` | A technique was just activated (the base `Technique::handleEvent` flips `Used` on this for the matching technique). |
| `EventResolveTechnique` | Resolve-time event for a technique. Used to spawn the technique's "side effects" (queue further events, transition into a state). |
| `EventDuelCalculateTechniqueValues` | Per-technique value calculation. Use this to inject Lethal, modify riposte/parry/thrust, etc. |
| `EventDuelCalculateCombatCardStats` | Per-combat-card stat calculation (Yevgeni's pattern). |
| `EventGenerateChallengeThreat` | City-action challenge threat generation (no duel). |
| `EventChallengerSwapped` / `EventDefenderSwapped` | The challenge had its participant changed mid-stream. Re-evaluate any modifier you applied. |

