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

### Pattern B.3 — En Garde Action: Target opponent forces a challenge onto your performer

Printed (Rattle the Rigging `_04009`): **`<b>En Garde Action:</b> Target opponent chooses one of their characters opposing your performer. The chosen character issues a [Combat] challenge to your performer. If your performer is a <b>Duelist</b>, their first combat card gains +1[Riposte].`**

This is the **invert** of Defending Honor `_01078` ("Target enemy character issues a challenge to one of your characters — their choice"):

| | `_01078` Defending Honor | `_04009` Rattle the Rigging |
|---|---|---|
| Ability target wording | **Target enemy character** | **Target opponent** (player) |
| Cesca interfaces | Yes (`IRiskThatTargetsCharacters`) | **No** — player Target; opponent's character pick is not your ability target |
| Who picks the challenger | You (enemy is `CHOSEN_PERFORMER`) | Opponent (among their opposing `canChallenge` characters) |
| Who picks the defender | Opponent (shared `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`) | Fixed = your En Garde performer |
| Challenge type | `DEFENDING_HONOR_CHALLENGE_TYPE` off auto-engage | Fresh type off auto-engage |

**Recipe:**

1. **`RiskAction`** + `RequiresPerformerSelected = true`. **En Garde** heading → filter performers `!$Engaged` **and** ≥1 opposing character at their location with `canChallenge($theah)`. Start from `parent::getPerformersForAction` (home eligible in principle; opposing usually forces city).
2. **No `IAbilityThatTargetsCharacters` / `IRiskThatTargetsCharacters`.** "Target opponent" is a player chooser (`CHOSEN_OPPONENT` + opponent name buttons). Opponent then picks a character — that chooser is **their** selection, not the ability's Cesca target. Contrast `_01078` which prints "Target enemy character."
3. **`EventActionTriggered`:** stash `$DefenderId = CHOSEN_PERFORMER` (will be overwritten). Collect opponent ids who have ≥1 `canChallenge` character at the defender's location. One opponent → auto-set `CHOSEN_OPPONENT` and transition `"NNNNN_2"` with **that opponent** as the transition `playerId`. Multiple → transition `"NNNNN"` (you pick opponent) then from `actFromActionWithId` queue `"NNNNN_2"` via EVENTS so `EventTransition` changes active player.
4. **Opponent character confirm:** validate opposing + same location + `ControllerId == CHOSEN_OPPONENT` + `canChallenge`. Then:
   - `CHOSEN_PERFORMER` = chosen enemy (challenger)
   - `CHOSEN_TARGET` = `$DefenderId` (your performer)
   - `CHALLENGE_STAT` = printed bracket (`STAT_COMBAT`)
   - Mint a fresh `CHALLENGE_TYPE` and keep it **off** `stIssueChallenge` auto-engage list
   - Transition `"NNNNN_3"` → `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` (skip shared choose-target — defender is fixed)
5. **WHY custom type off auto-engage:** forced *enemy* "issues a challenge" must not free-engage them for you. Same trichotomy seat as Defending Honor / Sanjay. Contrast Arrogant `_03008` / Courageous `_03058` where **your** performer issues → `NORMAL` auto-engage is correct. Do **not** add Engage unless printed.
6. **Optional "If your performer is a Duelist, their first combat card gains +X[Riposte]":** on confirm, if `$defender->hasTrait("Duelist")`, set sticky `$FirstCombatCardRiposteCharacterId = $defender->Id` on the Action (`IsUpdated`). On `EventDuelCalculateCombatCardStats` when `$event->actorId` matches, `addRiposte(X)` once and clear. Clear unused arm on `EventDuelEnd` and on `EventActionResolved` when `!IN_DUEL` (cancel/refuse — arm must not leak into a later duel; discard is in `buildCity` so the Action still receives events).
7. **Pre-commit:** `// createActionResolvedEvent() is called when the challenge is resolved` comment (same as other challenge-issuing Actions).
8. **Wire:** `"NNNNN"` / `"NNNNN_2"` → GameState classes; `"NNNNN_3"` → `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` under `HIGH_DRAMA_PLAYER_TURN_EVENTS`. Matching JS int for the new `CHALLENGE_TYPE`. bas/faf JS trio: opponent buttons on step 1; character highlight+Confirm on step 2.

**Do not** route through shared `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET` when the defender is fixed — that state is for picking the challenge target. `_01078` uses it because the *defender* is the free choice; here the *challenger* is.

References: `_04009` / `Action_04009` / `State_highDramaPhase04009` + `_2`; invert sibling `_01078` / `Action_01078` / `DEFENDING_HONOR_CHALLENGE_TYPE`.

### Pattern B.4 — Sink up to N from a discard pile • draw • sink this card

Printed (Unravel the Thread `_04010`): **`<b>Sorcerer Action:</b> Sink up to two cards from a single discard pile. Then, draw a card and sink this card.`**

`RiskAction` (+ `ISorcererAbility` when Sorcerer). The Risk is already in discard as a played hand Action by resolve time — exclude **self** from the "up to N" pick.

**Recipe:**

1. **Performers:** `parent::getPerformersForAction` + Sorcerer (or printed trait) gate. Home eligible unless text says City.
2. **Always enter a pile-chooser state** (`"NNNNN"`). List **every** player discard pile **and City Discard**, even when empty. Do **not** auto-skip when 0/1 non-empty piles, and do **not** hide empty piles (Eddie: City Discard must stay visible when empty).
3. **Second state** (`"NNNNN_2"`): multi-select up to N from the chosen pile. **Pass = sink none** (0 cards), then still draw + sink self. Exclude the played Risk's own id from the pick pool.
4. **Sink destinations:**
   - Player discard card → bottom of **that card's owner's** faction deck (`createCardAddedToFactionDeck` / sink helpers).
   - City Discard card → bottom of **City Deck** (not a player faction deck).
5. Then `createCardDrawnEvent` → sink **this** Risk to the bottom of the owner's faction deck → `createActionResolvedEvent`. Bracket with Sorcerer start/played when applicable.
6. **Wire** under `HIGH_DRAMA_PLAYER_TURN_EVENTS`; GameState classes + bas/faf JS trio (pile name buttons; chooseList multi-select + Pass). `EventHandlers.js` if multi-confirm needs a shared helper.
7. **Cesca:** if Sorcerer Action with no character Target, Cesca copies when she is the **performer** — add `Action_NNNNN` to `Reaction_01008` allow-list + `copyCard("NNNNN")` (card copy, not host Action on Cesca — wealth pay + sink-self need a real hand Risk).

**Do not** invent a Maneuver for this clause. No `IRiskThatTargetsCharacters` (discard/card chooser, not character Target).

References: `_04010` / `Action_04010` / `State_highDramaPhase04010` + `_2`.

### Pattern B.5 — En Garde Action: Target adjacent enemy • move performer there • filtered multi-player discard

Printed (Seek Each Devil `_04018`): **`<b>En Garde Action:</b> Target an enemy character at an adjacent <b>City</b> location • Move your performer there. Then, each other player who controls a <b>Sorcerer</b> or <b>Monster</b> there discards a card.`** Often paired with Pattern E.2 **"While your performer is an Academic or Hunter, this card has -1 cost."**

This is **not** B.1 (location chooser) and **not** B.3 (Target opponent / forced challenge). Printed **"Target"** an enemy character → character chooser + Cesca interfaces.

**Recipe:**

1. **`RiskAction`** + `RequiresPerformerSelected = true` + `IAbilityThatTargetsCharacters` / Risk `IRiskThatTargetsCharacters`.
2. **En Garde** heading → filter performers `!$Engaged` **and** ≥1 valid adjacent-City enemy. Start from `parent::getPerformersForAction` (home eligible — plain Action, not City Action).
3. **Targets:** controlled characters at `getAdjacentCityLocations($performer->Location, $includeHome = false)` with `ControllerId != performer`. Uncontrolled mercenaries are **not** enemies.
4. **`EventActionTriggered`:** clear sticky discard list; transition `"NNNNN"` to character-chooser GameState (bas JS trio: highlight performer + `highlightCardsAsSelectable` + Confirm).
5. **On confirm:** `eventCheck` + queue `createCardMovingEvent(..., engage: false, …)` (performer → target's location). **Do not** invent Engage — En Garde is only a precondition.
6. **Trailing discard (multi-active, not sequential turns):**
   - Collect **other** player ids who control ≥1 `Sorcerer` or `Monster` at the destination **and** have ≥1 hand card. One discard per player even if they control both traits / multiple such characters.
   - Stash as public `$PlayersToDiscard` on the Action + `$owner->IsUpdated = true`. WHY sticky on Action: Risk is already in discard by resolve; `buildCity` loads discard so State_2 / `actFromActionWithId` still see the Action (same seat as `_04005` / `_04009`).
   - Queue `createActionResolvedEvent` **before** the discard `createTransitionEvent("NNNNN_2")` (priority 3 before 8) — HD action wraps; discard is a trailing multi-player effect (`Action_04005` / `Action_01095b`).
   - Empty list → notify + skip `_2` (do **not** grey the Action when no one will discard — move is the primary effect).
7. **State `_2`:** `StateType::MULTIPLE_ACTIVE_PLAYER`. `onEnteringState` re-filters `$PlayersToDiscard` for non-empty hands and `setPlayersMultiactive(..., "multipleOk")`. **WHY not pass the turn around:** BGA multi-active makes **all** discarders active at once; each calls `setPlayerNonMultiactive` after picking; last one fires `"multipleOk"`. Do **not** invent sequential single-active player states.
8. **Discard act:** `createCardDiscardedFromHandEvent(..., asEffect: true)` + notify; validate player ∈ `$PlayersToDiscard` and card ∈ their hand.
9. **Timing note:** discard-player list may be computed at target confirm against the destination **before** the move event applies. That matches post-move for *other* players' Sorcerer/Monster presence (your performer moving does not change that set; acting player is excluded anyway).
10. **JS:** character-chooser trio for `"NNNNN"`; for `"NNNNN_2"`: `factionHand.setSelectionMode('single')` + `actChooseDiscardCard` / `onCardDiscarded` + `EventHandlers.js` enable when selection length &gt; 0 (mirror `highDramaPhase04005_2`).
11. **Skip `EventCharacterTargeted`** unless you need Vittoria-style redirect sync — bulk of recent Target Actions (`_04008`, `_03011`, `_01115`) omit it; `_01162` / `_01078` are the exceptions that fire it.
12. **No Cesca allow-list** unless the Action is also Sorcerer.

**Contrast:**
| | B.1 (`_03009` / `_03045`) | B.3 (`_04009`) | B.5 (`_04018`) |
|---|---|---|---|
| Chooser | Location | Opponent (player) then enemy character | Enemy character |
| Cesca | No | No ("Target opponent") | Yes ("Target … character") |
| Move | Performer to chosen location | None (challenge) | Performer to target's location |
| Follow-up | None / wound cost | Forced challenge | Filtered multi-player discard |

References: `_04018` / `Action_04018` / `State_highDramaPhase04018` + `_2`; discard seat `_04005` / `State_highDramaPhase04005_2` / Patricia `_01095`; En Garde precondition `_04009`.

### Pattern B.6 — En Garde Action: Engage attachment → issue [Combat] challenge to target opposing

Printed (No More Words `_04019`): **`<b>En Garde Action:</b> Engage a <b>Melee Weapon</b> or <b>Eisenfaust</b> attachment equipped to your performer • They issue a [Combat] challenge to target opposing character.`**

This is **not** B.3 (forced enemy challenger), **not** A.5/A.6 (engage **performer** then challenge), and **not** Yield `_02020` (City Action: target first, then attachment, then opponent may-engage/wound — no challenge).

**Recipe:**

1. **`RiskAction`** + `RequiresPerformerSelected = true` + `IAbilityThatTargetsCharacters` / Risk `IRiskThatTargetsCharacters` (printed **"target opposing character"**).
2. **En Garde** heading → filter performers `!$Engaged` **and** `canChallenge($theah)` **and** ≥1 eligible unengaged attachment **and** ≥1 opposing at location. Start from `parent::getPerformersForAction` (plain Action — home eligible).
3. **Attachment filter** (mirror `Action_02020`):

```php
($attachment->hasTrait("Weapon") && $attachment->hasTrait("Melee"))
    || $attachment->hasTrait("Eisenfaust");
```

Also require `!$attachment->Engaged` and `$attachment->AttachedToId == $performer->Id`.

4. **Two-step flow (printed bullet order: Engage attachment **then** challenge):**
   - `"NNNNN"` → GameState attachment chooser (named buttons from `getArgsFromAction` `attachments[]` — mirror tac `highDramaPhase02020_2` **OnUpdateActionButtons**, not in-play card highlight).
   - On attachment confirm: `createCardEngagedEvent` on the **attachment** (cost paid — irreversible).
   - Set `CHALLENGE_STAT = STAT_COMBAT`.
   - Mint **`NO_MORE_WORDS_CHALLENGE_TYPE`** (or card-specific name) — **not** `NORMAL_CHALLENGE_TYPE`.
   - Transition `"NNNNN_2"` → shared `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`.
   - `nextState("attachmentChosen")` → EVENTS processes transition to choose-target.

5. **WHY custom type when there is no refuse/intervene side effect:** `OnUpdateActionButtons.js` shows Back on `highDramaChallengeActionChooseTarget` **only** when `args.challengeType == NORMAL_CHALLENGE_TYPE`. After attachment Engage, Back would let the player undo past a paid cost. Custom type hides Back in UI; also guard `FrameworkActionsTrait::actBack()` when state is `highDramaChallengeActionChooseTarget` and type matches (blocks API abuse).

6. **WHY custom type still goes ON `stIssueChallenge` auto-engage list:** printed Engage cost is on the **attachment**, not the performer. Performer should still auto-engage when issuing (same as `NORMAL` challenge flow). Contrast Censure `_03057` / Cornered `_03021` where **performer** Engage is paid on `EventActionTriggered` → type stays **off** auto-engage to avoid double-engage.

7. **"They issue a challenge":** attachments do not issue challenges in the engine — **performer** stays `CHOSEN_PERFORMER` for the challenge pipeline.

8. **`isValidTargetForAbility`:** opposing controlled character at performer's location (mirror `Action_01083` / `Action_03058`).

9. **Wire:** `"NNNNN"` → `State_highDramaPhaseNNNNN`; `"NNNNN_2"` → `HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`. Add constant to `Game.php` + `seventhseacityoffivesails.js`. Add type to auto-engage `if` in `StatesTrait::stIssueChallenge`.

10. **JS (bas expansion):** `OnEnteringState` highlight performer; `OnUpdateActionButtons` attachment name buttons (`args.args.attachments.forEach`); `OnLeavingState` unhighlight performer. No Back on attachment step (single forward path).

11. **Pre-commit:** `// createActionResolvedEvent() is called when the challenge is resolved` on the Action.

**Contrast:**

| | Yield `_02020` (City) | B.6 `_04019` |
|---|---|---|
| Base | `RiskCityAction` | `RiskAction` (home performers OK) |
| Order | Target character → attachment → opponent response | Attachment → shared challenge target |
| Engage cost | Attachment | Attachment |
| Follow-up | May engage / wound target | Combat challenge |
| Challenge type | N/A | Custom (not NORMAL — no Back on chooseTarget) |

References: `_04019` / `Action_04019` / `State_highDramaPhase04019`; attachment filter `Action_02020`; challenge target `Action_01083`; Back hide `NO_MORE_WORDS_CHALLENGE_TYPE` + `FrameworkActionsTrait::actBack`.

### Pattern B.7 — En Garde Action: Target opposing non-Leader en garde • they may engage or you claim

Printed (A Costly Accord `_04027`): **`<b>En Garde Diplomat Action:</b> Target an opposing non-<b>Leader</b> that is en garde • They may engage. If they do not, claim this location.`**

This is **not** A.5 (claim-on-refuse after a challenge — no `CHALLENGE_TYPE`), **not** B.6 (no attachment Engage / challenge), and **not** D.1.2 (RiskReaction Engage-or-wound). Same family as Yield `_02020` / Duckfoot `_01049` / Wrath `_01034` "they may engage" — decline consequence here is **you claim**, not wound / engarde performer.

**Recipe:**

1. **`RiskAction`** + `RequiresPerformerSelected = true` + `IAbilityThatTargetsCharacters` / Risk `IRiskThatTargetsCharacters` (printed **"Target"**).
2. **Heading gates stack:** En Garde → `!$Engaged`; **Diplomat** (or other trait in the heading) → `hasTrait("Diplomat")`. Start from `parent::getPerformersForAction` (plain Action — home eligible). Filter performers with ≥1 valid target.
3. **Targets:** opposing at performer location (`getOpposingCharactersAtLocation`), `! hasTrait("Leader")`, en garde (`!$Engaged`).
4. **`EventActionTriggered`:** transition `"NNNNN"` to character-chooser GameState (bas JS trio: highlight performer + `highlightCardsAsSelectable` + Confirm).
5. **On target confirm:** set `CHOSEN_TARGET`; transition `"NNNNN_2"` with **target's ControllerId** as the transition `playerId` (opponent becomes active).
6. **State `_2` — labeled Engage / Decline buttons** (mirror Yield `highDramaPhase02020_3`):
   - `id == 1` → `createCardEngagedEvent` on the target (voluntary engage — use target's ControllerId as the engage `playerId`).
   - `id == 2` → notify decline; if `cardInCity($performer)` **and** `canLocationBeClaimedBy($performer->ControllerId, $location)` → `createLocationClaimedEvent($performer->ControllerId, $performer->Id, $location)`; else notify cannot claim.
7. **WHY labeled Decline+Claim, not Pass:** Wrath `_01034` uses Pass when the alternate is soft ("en garde your performer"). When decline has a concrete location claim, label the button so the opponent sees the stake (Yield uses "Decline and Wound").
8. **WHY `ActionResolved` after the opponent chooses:** the printed effect *is* engage-or-claim. Contrast Yield `_02020` / Seek `_04018`, which fire ActionResolved once costs / primary move are locked and treat the opponent response as trailing. Do **not** resolve before `"NNNNN_2"`.
9. **WHY claimability only at decline emit:** engage may still happen when the location is unclaimable — same discipline as Censure `_03057` / Ambitious `_03067`. Do **not** grey the Action on `canLocationBeClaimedBy`.
10. **No `CHALLENGE_TYPE`:** this never enters the challenge pipeline. Do not clone A.5 correlator plumbing.
11. **Wire:** `"NNNNN"` / `"NNNNN_2"` → GameState classes under `HIGH_DRAMA_PLAYER_TURN_EVENTS`. bas JS: character-chooser trio for step 1; step 2 highlight performer + target + Engage / Decline and Claim buttons.
12. **Stub hygiene:** Traits use `Bureaucracy` (not `Beauracracy`); Montaigne faction stubs sometimes typo `Montagne` — fix to `Montaigne`.

**Contrast:**

| | Yield `_02020` | Wrath `_01034` | A.5 Censure `_03057` | B.7 `_04027` |
|---|---|---|---|---|
| Base | `RiskCityAction` | `RiskAction` | `RiskCityAction` | `RiskAction` |
| Cost before choice | Engage attachment | Wound performer | Engage performer + challenge | None (En Garde precondition only) |
| Opponent UI | Engage / Decline and Wound | Engage / Pass | Challenge accept/refuse | Engage / Decline and Claim |
| Decline effect | Wound target | En garde performer | Claim via `EventChallengeRejected` | Claim via Action act |
| ActionResolved | After attachment paid (before response) | After opponent chooses | Challenge pipeline | After opponent chooses |

References: `_04027` / `Action_04027` / `State_highDramaPhase04027` + `_2`; Yield buttons `Action_02020` / `highDramaPhase02020_3`; claim emit `Action_03057` / `_03057`; Cesca Target `Action_04018`.

### Pattern B.8 — En Garde Action: Target opposing • move both to controlled or Leader City location

Printed (Depose `_04028`): **`<b>En Garde Musketeer Action:</b> Target an opposing character • Move them and your performer to a <b>City</b> location you control, or one where you control a <b>Leader</b>.`**

This is **not** B.5 (moves only the performer to the target's location / adjacent-enemy scan), **not** B.1 (location chooser only — no character Target), and **not** the adjacent-only move-both of Tea and Cakes `_02025` / Giacinto `_01205` (those use `getAdjacentCityLocations`; Depose omits "adjacent").

**Recipe:**

1. **`RiskAction`** + `RequiresPerformerSelected = true` + `IAbilityThatTargetsCharacters` / Risk `IRiskThatTargetsCharacters` (printed **"Target"** opposing character).
2. **Heading gates stack:** En Garde → `!$Engaged`; **Musketeer** (or other trait in the heading) → `hasTrait(...)`. Start from `parent::getPerformersForAction` (plain Action — home eligible).
3. **Targets:** opposing at performer location (`getOpposingCharactersAtLocation`) — no adjacent scan, no Leader/non-Leader filter unless printed.
4. **Destinations** (second chooser — iterate **all** city locations, not adjacency):

```php
foreach ($theah->getCityLocations() as $cityLocation) {
    $name = $cityLocation->Name;
    if ($name === $performer->Location) continue; // WHY: "Move … to" — same spot is a no-op
    $youControl = $theah->game->getControllerForLocation($name) == $performer->ControllerId;
    $leaderThere = ($leader = $theah->getLeaderByPlayerId($performer->ControllerId)) !== null
        && $leader->Location === $name;
    if ($youControl || $leaderThere) { /* offer */ }
}
```

5. **Availability:** grey the Action when the performer has opposing targets but **zero** legal destinations (same discipline as B.1 "has ≥1 valid destination").
6. **Two-step flow** (mirror `_02025` JS, different destination predicate):
   - `"NNNNN"` → character-chooser GameState (`characterChosen` → `_2` **direct** on the GameState — do **not** route `_2` through `HIGH_DRAMA_PLAYER_TURN_EVENTS` unless you need an active-player swap).
   - `"NNNNN_2"` → location-chooser GameState with **Back** to step 1 (`actBack` / `highDramaPhase02025_2` shape).
7. **On location confirm:** shared `batchId`; queue target move then performer move — both `engage=false` (En Garde heading is precondition only; no printed Engage cost). `eventCheck` both; `createActionResolvedEvent` on confirm.
8. **WHY move target first:** matches `_02025` / `_01205` ordering; both share destination and `batchId`.
9. **"Where you control a Leader":** your Leader **character** at that location (`getLeaderByPlayerId` + `Location` match) — distinct from claim-control alone (you may move to an uncontrolled spot where your Leader sits).
10. **Wire:** `"NNNNN"` only under `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`; define both GameState classes + `States::HIGH_DRAMA_PLAYER_TURN_NNNNN` / `_NNNNN_2`. bas JS trio: character Confirm on step 1; `makeCityLocationSelectable` + Back + Confirm Location on step 2 (highlight performer + target as chosen).

**Contrast:**

| | B.5 `_04018` | B.1 `_03045` | `_02025` Tea and Cakes | B.8 `_04028` |
|---|---|---|---|---|
| Target | Adjacent enemy | N/A (location) | Opposing (Influence ≤) | Opposing (same location) |
| Who moves | Performer only | Performer only | Target + performer | Target + performer |
| Destination | Target's location | Adjacent claim-controlled | Adjacent City | Any City you control **or** Leader at |
| Adjacency | Yes (target scan) | Yes | Yes | **No** |
| Cesca | Yes | No | Yes | Yes |

References: `_04028` / `Action_04028` / `State_highDramaPhase04028` + `_2`; move-both + batchId `Action_02025` / `Action_01205`; destination claim-control `Action_03045`; En Garde precondition `_04018`.

