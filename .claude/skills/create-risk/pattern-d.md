> Part of **create-risk**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern D — Reaction (`RiskReaction`)

Risk reactions are played from hand (the Risk card is the cost). Pre-commit hook enforces hand-only guard.

**City Reaction on a Risk:** the printed heading is still Pattern D / `RiskReaction` — there is no `RiskCityReaction` base. Add the mechanical City gate on top of the hand guard: `count($theah->getCharactersInCityByPlayerId($owner->ControllerId)) > 0` before offering (mirror scheme/attachment City Reactions such as `Reaction_02053`). The Risk itself stays in hand until paid; "City" here means the controller has presence in the city, not that the Risk is played from a city location.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
// ...

class Reaction_NNNNN extends RiskReaction
{
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCard($event->theah);
        if ($owner === null) return;
        if (! ($owner->Location == Game::LOCATION_HAND)) return;   // required by pre-commit hook

        if ($event instanceof ...) {
            // ... queue reaction transition
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);
        // ... apply effect
        $this->setUsed($game->theah, true);
        $game->gamestate->nextState("done");
    }
}
```

Pre-commit hook requirements on RiskReaction:
- Literal `Location == Game::LOCATION_HAND` somewhere in the file (substring `grep` — `!=` does **not** satisfy it; structure your in-hand guard with the `==` form, e.g. `if (! ($owner->Location == Game::LOCATION_HAND)) return;`).
- Literal `$this->setUsed(` and `$this->isAvailable(` somewhere in the file.

References: `Reaction_01080` (Iron Reply-style — adds Parry during opposing maneuver), `Reaction_01140`, `Reaction_01088`, `Reaction_02048` (Pressure-to-cancel — multi-event family, saved-event re-emit on decline), `Reaction_03010` (cross-player choice flow after pay — see Pattern D.1), `Reaction_03068` (pass-trigger + mandatory opponent Home→City move via buttons — see Pattern D.1.1), `Reaction_03031` (effect-event redirect after pay — see Pattern D.4; structural cousin of `Reaction_02016` on attachments), `Reaction_04020` (adjacent pressure + D.2.2 + D.1.2 engage-or-wound — see Pattern D.1.2 / D.2.2).

### Pattern D.1 — Multi-stage cross-player RiskReaction with pay

When the Risk Reaction's effect itself involves another player choosing something (e.g., "Wound them unless their controller does X"), the standard RiskReaction shape (pay → `EventRiskReactionTriggered` → resolve inline) isn't enough. The pattern that works in-codebase, modeled after `Reaction_03010` (Manipulative):

1. **Internal `$stage` field** on the reaction (`''` / `'choice'` / `'pickN'` …) plus `$targetId` / `$opposingPlayerId` captured at trigger time. `getReactionButtonProperties()` and `getReactionDescription()` switch on `$stage`. Prefer **`public`** stage/id fields when the active player flips to an opponent across serialize/DB round-trips (same WHY as `Reaction_03044` — private can work via PHP serialization of `$theah->cards`, but public is the safer multi-stage discipline).
2. **`handleEvent` on the trigger event** → save target + opposing player ids, set `$stage = ''`, queue `createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id)` (offer to owner).
3. **`performReaction` with `$stage === ''`:**
   - `'use'` → queue `createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id)` + `createReactionPayTransitionEvent(...)`. The framework discards the Risk and fires `EventRiskReactionTriggered` after the pay.
   - `'pass'` → reset saved state (don't `setUsed` — the Risk stays in hand for future triggers).
4. **`handleEvent` on `EventRiskReactionTriggered && $event->internalId == $this->Id`** → set `$stage = 'choice'` and queue `createReactionTransitionEvent($this->opposingPlayerId, $owner->Id, $this->Id)`. The opposing player becomes the active player in `playerReaction`; the reaction's `getReactionButtonProperties()` (driven by `$stage='choice'`) renders the opponent's options. **If the choice is moot (e.g., opponent can't legally pick "return"), apply the wound/effect immediately + `finalize()` instead of transitioning.**
5. **`performReaction` with `$stage === 'choice'`** → branch on `$reactionId` → either apply the wound immediately + `finalize()`, or advance to `$stage = 'pickN'` and queue another reaction transition to the same opposing player.
6. **`finalize()`** → call `$this->setUsed($theah, true)` + reset stage / saved ids. The Risk is already in discard from the pay step.

Key gotchas:
- After the pay step, `$owner->Location` is `LOCATION_<DISCARD>`, **not** `LOCATION_HAND`. The in-hand guard in your `EventApproachCharacterPlayed` / trigger branch correctly skips re-triggering on subsequent triggers; the `EventRiskReactionTriggered` branch doesn't need it (and shouldn't have it).
- `createReactionTransitionEvent($opponentId, …)` still works after the Risk is in discard — the reaction object lives on `$theah->cards` (the discarded Risk is still in the cards map) and the `playerReaction` state machinery is owner-card-agnostic.
- `isAvailable()` returns `!Used`. Don't `setUsed(true)` until `finalize()`, or the mid-flow `playerReaction` state won't be able to render its `$stage`-dependent buttons cleanly.
- Cross-stage notifications: emit the "you used the Reaction" message from your `EventRiskReactionTriggered` handler (after pay) rather than from the offer-stage `performReaction`, so the announce-order matches the actual cost being paid.
- **Prefer reaction buttons over GameStates** when both choosers are small fixed pools (character name buttons, city location buttons). `Reaction_03010` muster pick and `Reaction_01039` / `Reaction_03040` location pick already prove the UI. GameStates + On*.js are for board-highlight choosers that need `ids` args / click-to-select — don't invent them for D.1 when buttons suffice.
- **Player-target buttons** — when the owner must pick an opponent player (not a character), use `opponent-{playerId}` reaction ids with `getPlayerNameById` labels. Same discipline as **"Target opponent"** on Actions (`_04009`) — no `IRiskThatTargetsCharacters` / no Cesca. See `_04020` stage `chooseOpponent`.

### Pattern D.1.2 — RiskReaction: wound target character unless they engage

When the printed text says **Then, wound target character … unless they engage** (or **They may engage. If they do not, wound them**) on a **RiskReaction**, wire the engage-or-wound branch with **reaction buttons only** — mirror `Action_01049` / `Action_01156`, not a GameState.

1. **Owner stage** — after any preceding effects (e.g. pressure penalty), `$stage = 'chooseCharacter'`; buttons `character-{id}` for valid targets at the printed location (usually opposing controlled characters). **`IAbilityThatTargetsCharacters`** on the Reaction + **`IRiskThatTargetsCharacters`** on the Risk (printed **"target character"**).
2. **On character pick** — notify; if `$target->Engaged`, auto-wound + `finalize()` (no choice — already committed).
3. **Else** — `$stage = 'engageOrWound'`; `createReactionTransitionEvent($target->ControllerId, …)` with **Engage** / **Take the Wound** buttons (no Pass — the alternate is the wound).
4. **Engage** → `createCardEngagedEvent` on the target character (the **engage** verb, not engarde). **Wound** → `createCharacterBeingWoundedEvent` + `eventCheck`.
5. **`finalize()`** → `setUsed` + reset stage / saved ids. Do not `setUsed` until the full chain completes (D.1 discipline).

**Optional:** when the Reaction gate was **equipped with a Ranged Weapon**, queue `createRangedAbilityPlayedEvent` on wound resolution (performer id captured at trigger time). Not required for every ranged-gated Reaction — only when the wound is the ranged shot.

**WHY buttons not GameState:** D.1 preference; two fixed choices fit `playerReaction`. `_03061`-style board highlight is for City Actions.

References: `Reaction_04020`, `Action_01049`, `Action_01156`.

### Pattern D.1.1 — Pass-trigger City Reaction: opponent must move Home → City

When the printed text says **City Reaction: After an opponent passes • They must move an en garde character from their Home to a City location**, wire Pattern D.1 against `EventHighDramaPhasePlayerPassed` (queued by `FrameworkActionsTrait::actHighDramaPass`). Reference: `Reaction_03068` (Confusion).

1. **Trigger** — `EventHighDramaPhasePlayerPassed && isAvailable()`, hand guard `Location == Game::LOCATION_HAND`, `$event->playerId != $owner->ControllerId` (opponent passed), City gate `getCharactersInCityByPlayerId(owner) > 0`, and passer has ≥1 en garde at Home (`getCharactersAtHomeByPlayerId($event->playerId)` filtered `! Engaged`). Store `$opposingPlayerId = $event->playerId`.
2. **Hide when the "must" is impossible** — if the passer has no en garde Home character, do **not** offer the Reaction. "They must move" has no legal fulfillment; offering a no-op wastes the Risk.
3. **Pay** — standard D.1 `performReaction('use')` → EnteringPayState + ReactionPayTransition. WealthCost 0 still goes through pay (same as Manipulative `_03010`).
4. **After `EventRiskReactionTriggered`** — notify, set `$stage = 'chooseCharacter'`, `createReactionTransitionEvent($opposingPlayerId, …)`. No Pass button on mandatory stages.
5. **`chooseCharacter`** — buttons `character-{id}` from the en garde Home pool; advance to `$stage = 'chooseLocation'` and re-transition to the same opponent.
6. **`chooseLocation`** — buttons `moveTo-{locationName}` for `array_keys($theah->getCityLocations())` (any City location — text does not say adjacent). Re-validate character still en garde at Home; queue `createCardMovingEvent(..., engage: false, …)` — Move only, no Engage. `finalize()` + `setUsed`.
7. **No `IRiskThatTargetsCharacters`** — printed text never says "Target"/"target"; the chooser is a forced mandatory pick via reaction buttons (same discipline as `_03060` heal-another).

**Pass → events routing (critical):**
- Non-final pass: `actHighDramaPass` → `nextState("pass")` → `HIGH_DRAMA_PLAYER_TURN_EVENTS` (has `"reaction"` / `"pay"`).
- Final pass (everyone has passed): `nextState("end")` → `HIGH_DRAMA_END` → `HIGH_DRAMA_END_EVENTS` (also has `"reaction"` / `"pay"`). Queued `EventHighDramaPhasePlayerPassed` still processes there before Plunder. Both paths must be able to host the offer/pay/opponent-choice loop — do **not** assume only turn-events wiring.

**WHY not GameStates for the opponent choosers:** character + city location both fit reaction buttons (D.1 preference). `_03061` Burn like Mice uses a GameState character chooser because it is a City *Action* with board highlight — different UI channel. Don't copy Action chooser scaffolding onto a RiskReaction that already owns `playerReaction`.

### Pattern D.2 — Single-stage RiskReaction with pay that applies an effect

When the RiskReaction's effect is a single-shot resolution after pay — mutate a global (`CHALLENGE_STAT`), engarde a fixed character, set a flag, etc. — with no cross-player choice and no second transition, the shape collapses to:

1. **`handleEvent` on the trigger event** → gate, store any ids needed for the effect/notify on the reaction object, queue `createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id)`.
2. **`performReaction('use')`** → queue `createEnteringPayStateEvent(PAY_STATE_IN_HAND_REACTION)` + `createReactionPayTransitionEvent`. Don't apply the effect here.
3. **`handleEvent` on `EventRiskReactionTriggered && internalId == $this->Id`** → apply the effect, emit any Sorcerer start/played events, notify, `setUsed`.

**WHY apply the effect in `EventRiskReactionTriggered` and not directly in `performReaction('use')`:** between `performReaction('use')` and the post-pay trigger event, framework cancel-reactions (Hexenjagd-style) can fire and cancel the ability. If you mutated `CHALLENGE_STAT` or queued `createCardEngardedEvent` inside `performReaction('use')`, that side effect would persist even when the Risk play is canceled. Deferring keeps the side effect paired with the resolved pay. Mirror `Reaction_03012` for this discipline; `Reaction_03010` follows the same pattern for its multi-stage variant; `Reaction_03046a`/`b` use it for engarde.

This is single-stage so no `$stage` field is needed. Save only the ids you'll reference inside `EventRiskReactionTriggered` (e.g., `performerId` / `intervenerId` for engarde or Sorcerer events).

**En garde after pay:** queue `createCardEngardedEvent($owner->ControllerId, $character->Id, $owner->Id, $this->Id)` only when `$character->Engaged` is still true at resolve time (engarde is a no-op otherwise). No character chooser → no `IRiskThatTargetsCharacters`.

References: `Reaction_03012` (Subtle — flips `CHALLENGE_STAT`), `Reaction_03046a` / `Reaction_03046b` (Passionate — engarde intervener / challenger).

### Pattern D.2.1 — Pressure-total RiskReaction ("Add +1 to your total for the pressure")

When the printed text says **When a pressure occurs … Add +1 to your total for the pressure** (optionally gated on board position / character counts), wire Pattern D.2 against `EventPressureOccuring`, then apply the bonus inside `UtilitiesTrait::pressureLocation()` — the same channel Solomonia (`_02044`) and Trial of Faith (`Reaction_02019`) use.

1. **Trigger** — `EventPressureOccuring && isAvailable()`, hand guard `Location == Game::LOCATION_HAND`, plus any printed gate (e.g. Loyal: count non-Mercenary controlled characters at `$event->location`; owner's count must be strictly `>` each other controller's count at that location — players with 0 non-Mercs there count as 0).
2. **Pay** — standard `performReaction('use')` → `EnteringPayState` + `ReactionPayTransition`.
3. **Effect in `EventRiskReactionTriggered`** — `setGlobalFlag(PRESSURE_TYPE, <NEW>_PRESSURE_TYPE)` and `globals->set(<NEW>_PLAYER_ID, $owner->ControllerId)` (or a card-id global if you need to look up the controller later, like `SOLOMONIA_ID`). `setUsed`. Notify.
4. **`pressureLocation()` branch** — outside the `foreach ($pressureStats)` loop (so it applies once to "the pressure," not once per pressure type / not Influence-only):

```php
if ($this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::<NEW>_PRESSURE_TYPE))
{
    $playerId = $this->globals->get(Game::<NEW>_PLAYER_ID, 0);
    if ($playerId && isset($playerInfluences[$playerId]))
    {
        $playerInfluences[$playerId]['influence'] += 1;
    }
}
```

5. **Cleanup** — delete the player-id global wherever `PRESSURE_BONUS` / `PRESSURE_TYPE` are reset (`StatesTrait` post-pressure cleanup).

**Do not reuse `Game::PRESSURE_BONUS`.** That global is only read under `PACK_TACTICS_PRESSURE_TYPE` and only when the pressure stat is Influence (`Action_01028`). Loyal-style "+1 to your total for the pressure" is any pressure type and any reacting player — mint the next binary flag (`8192`, `16384`, … after `USSURAN_INTRIGUE_PRESSURE_TYPE = 4096`) plus a dedicated player-id global.

**WHY defer to `EventRiskReactionTriggered`:** same as D.2 — cancel-during-pay must not leave a dangling pressure flag.

References: `Reaction_03035` (Loyal), `_02044` (Solomonia — passive auto-flag on `EventPressureOccuring`, no pay), `Reaction_02019` (Trial of Faith — RiskReaction that sets `TRIAL_OF_FAITH_PRESSURE_TYPE`).

### Pattern D.2.2 — Opponent pressure penalty ("Target opponent applies -1 to their total")

When the printed text says **Target opponent applies -1 to their total** (or similar — a **chosen opponent's** pressure total decreases during the current pressure), wire the inverse of D.2.1:

1. **Trigger** — same as your Reaction's pressure clause (`EventPressureOccuring` ± location/adjacency gates).
2. **Pay** — standard D.1/D.2 pay flow.
3. **Opponent choice** — if multiple opponents have presence at the pressure location (`getCharactersAtLocation($event->location)` → distinct `ControllerId` ≠ owner), `$stage = 'chooseOpponent'` with `opponent-{playerId}` buttons. Single opponent → auto-pick. **"Target opponent"** → player chooser — **no** Cesca (same as `_04009`).
4. **Effect in post-pay flow (before or as first step after notify)** — `setGlobalFlag(PRESSURE_TYPE, <NEW>_PRESSURE_TYPE)` and `globals->set(<NEW>_PLAYER_ID, $chosenOpponentId)`. Do **not** `setUsed` here if D.1 follow-up stages remain.
5. **`pressureLocation()` branch** — outside the per-stat loop:

```php
if ($this->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::<NEW>_PRESSURE_TYPE))
{
    $playerId = $this->globals->get(Game::<NEW>_PLAYER_ID, 0);
    if ($playerId && isset($playerInfluences[$playerId]))
    {
        $playerInfluences[$playerId]['influence'] -= 1;
    }
}
```

6. **Cleanup** — delete the player-id global in `StatesTrait` post-pressure cleanup alongside `LOYAL_PLAYER_ID`.

Mint the next binary flag after `SOLINE_PRESSURE_TYPE = 16384` (e.g. `32768`). **WHY defer flag to post-pay:** same as D.2.1 — cancel-during-pay must not leave a dangling penalty.

**Adjacent-location pressure trigger (Vantage Point shape):** `$event->location` is where pressure resolves; the performer's **En Garde + Ranged Weapon** gate scans `getAdjacentCityLocations($event->location, false)` for owner characters with `!$Engaged` and a `Weapon`+`Ranged` attachment (`Maneuver_01055` attachment loop). Contrast Loyal (`_03035`) which counts non-Mercs **at** `$event->location`, and `_02044` (Solomonia) which reacts when pressure is **adjacent to** Forum while Solomonia ** sits at** Forum.

**Hide when meaningless:** do not offer if no opponent has controlled characters at the pressure location (nothing to penalize) or if the "Then wound target character" clause has zero legal opposing targets at that location.

**Timing:** all Reaction stages (opponent pick, pressure flag, character pick, engage/wound) must complete inside the events loop **before** `stHighDramaPressureLocation` — the penalty flag is read there. Claim-action path: `EventPressureOccuring` → reactions/pay → `endOfEvents` → `HIGH_DRAMA_PRESSURE_LOCATION`.

References: `Reaction_04020` (D.2.2 + D.1.2 composite), contrast `Reaction_03035` (self +1).

### Pattern D.3 — RiskReaction that cancels pending high-priority events in a batch

When the printed text says "Cancel the movement" / "Cancel the [effect]" and the effect being canceled is delivered by **already-queued, high-priority events** (e.g. `EventRenownAddedToLocation` + `EventRenownRemovedFromLocation` with shared `batchId` — see `_01117`, `_01062`, `_01150` for the producer side), the naive Pattern D.2 shape will deadlock on event ordering. Wire it as:

1. **`implements ICancelReaction`** on the Reaction class. The marker interface has no required methods for the cancel case (`revertCancellation` is only invoked by `Reaction_01109` Not Today against `_01140` specifically). The framework checks `instanceof ICancelReaction` in `FrameworkActionsTrait::actChooseCardForReactionPaid` and **flips both `EventRiskReactionTriggered` and `EventRiskPlayed` from `queueEvent` to `stackEvent`** for the post-pay step.
2. **`handleEvent` on the trigger event** → gate, save `$event->batchId ?? 0` on the reaction object, **`stackEvent`** the reaction transition (not `queueEvent`) so it pre-empts the rest of the still-pending batch.
3. **`performReaction('cancel')`** → **`stackEvent`** the pay events (`createReactionPayTransitionEvent` first, then `createEnteringPayStateEvent` — LIFO means the EnteringPayState dequeues first). Don't apply the effect here. `'decline'` → reset saved state, nothing else.
4. **`handleEvent` on `EventRiskReactionTriggered && internalId == $this->Id`** → apply the cancel by deleting the targeted queued events, notify, `setUsed`, reset saved state.

**WHY `ICancelReaction` is load-bearing here, not optional:** without it, the post-pay `EventRiskReactionTriggered` is `queueEvent`'d at `MEDIUM_PRIORITY = 3`. The companion `Added`/`Removed` events sit at `HIGH_PRIORITY = 2` (lower number = higher priority — `getNextEvent` orders by `event_priority` ASC). They dequeue and apply the Renown change *before* your trigger handler runs. With `ICancelReaction`, both post-pay events are `stackEvent`'d, which assigns `min(current priorities) - 1` — guaranteeing they pre-empt every queued high-priority event including the ones you need to delete.

**WHY also `stackEvent` the reaction transition and pay events:** same priority math at every step. Anything you `queueEvent` lands at `MEDIUM_PRIORITY` (the event constructor default) and queues *behind* the `HIGH_PRIORITY` batch members. The user would never get the offer; the Renown move would resolve first. Use `stackEvent` for every event you want to interleave ahead of the pending batch.

**Prefer targeted helpers over `deleteEventBatch` for batch members.** `deleteEventBatch($batchId)` is type-agnostic — fine when you genuinely want to delete every batch member, but the Pattern D.3 contract is "cancel these specific events." Add (or use) `DB::deleteXEventsByBatchId(int $batchId)` helpers (mirror `deleteRenownAddedToLocationEventsByBatchId` / `deleteRenownRemovedFromLocationEventsByBatchId`) and call them from a `Theah` pass-through. Anchor the batchId substring with a trailing semicolon — `'%batchId";i:5;%'` — so `batchId=5` doesn't false-match `batchId=50/51/…` (the bare `deleteEventBatch` has this prefix-collision; the targeted helpers should not).

**`EventRenownMovingBetweenLocations` is informational only** — it has no `EventHub` handler, so canceling/deleting it does nothing on its own. The actual Renown state change is in the `Added`/`Removed` pair queued alongside it with shared `batchId`. To cancel a Renown movement, delete those two; ignore the Moving event itself (it's already been dequeued and processed by the time you reach `EventRiskReactionTriggered`, anyway).

References: `Reaction_03020` (Commanding — Leader Reaction canceling Renown movement from Leader's location); the related but simpler `Reaction_01140` (Stubborn — `ICancelReaction` that cancels an `EventCardMoving` in-place via `$event->canceled = true` + saved-event re-emit on decline, no post-pay batch deletion needed).

### Pattern D.4 — RiskReaction that redirects wound/move/engage effects to another character

When the printed text says an **opponent's ability would wound, move, or engage your character** and a **performer at that location** suffers the effect instead, wire a clone-cancel-reemit redirect adapted from `Reaction_02016` (Cross of the Martyrs / Diplomatic Impunity) but as a **`RiskReaction`** with effect-based triggers.

**Do not copy 02016's trigger gate blindly.** `Reaction_02016` is an `AttachmentReaction` for "redirect a **targeted** ability" — its `shouldReactToEvent` requires `IAbilityThatTargetsCharacters`. Card text that names **effects** ("would wound, move, or engage") and never says "target" must intercept the **effect events themselves**, including abilities that never implement `IAbilityThatTargetsCharacters` (maneuvers, forced wounds, engage-without-chooser, etc.).

#### Trigger events (map printed verbs → events)

| Printed verb | Event | Target id field |
|---|---|---|
| wound | `EventCharacterBeingWounded` | `$event->characterId` |
| move | `EventCardMoving` | `$event->cardId` |
| engage | `EventCardEngaged` | `$event->cardId` |

Add `EventCharacterIntervened` when duel intervention should be redirectable (copy 02016's intervene branch: your character is `$event->newTargetId`, swap `DUEL_DEFENDER` in `releaseEvent`). **Do not** wire heal / `EventCharacterTargeted` / challenge-issued / engarde unless the printed text names those verbs — 02016's broader event set is for general "redirect targeted ability" attachments.

#### Gate shape

```php
private function shouldReactToEvent(Theah $theah, int $sourceId, string $abilityId, ?int $targetCharacterId): bool
{
    $owner = $this->getOwningCard($theah);
    if ($owner === null) return false;
    if (! $this->isOpponentAbility($theah, $sourceId, $abilityId, $owner->ControllerId)) return false;

    $target = $theah->getCharacterById($targetCharacterId);
    if ($target === null || $target->ControllerId != $owner->ControllerId) return false;

    // "Performer at that location" — another of your characters at the same spot
    $others = $theah->getCharactersAtLocationByPlayerId($target->Location, $owner->ControllerId);
    $others = array_filter($others, fn($c) => $c->Id != $target->Id);
    if (count($others) === 0) return false;

    $this->savedSourceId = $sourceId;
    $this->savedAbilityId = $abilityId;
    return true;
}

private function isOpponentAbility(Theah $theah, int $sourceId, string $abilityId, int $ownerPlayerId): bool
{
    $source = $theah->getCardById($sourceId);
    if ($source) {
        return $source->ControllerId != $ownerPlayerId && $source->ControllerId != 0;
    }
    $action = $theah->getInPlayActionById($abilityId);
    if ($action && $action instanceof ICardAbility) {
        $owningCard = $action->getOwningCard($theah);
        return $owningCard !== null
            && $owningCard->ControllerId != $ownerPlayerId
            && $owningCard->ControllerId != 0;
    }
    return false;
}
```

**WHY `isOpponentAbility` checks both `sourceId` and in-play action owner:** wound/move/engage events carry `$event->sourceId` (the card that queued the effect) and `$event->abilityId`. Maneuvers and other non-targeting abilities may have no `IAbilityThatTargetsCharacters` implementation but still emit these events with valid source/ability ids.

#### "Performer at that location"

Same mechanical meaning as Hexenjagd (`Reaction_01053`): `getCharactersAtLocationByPlayerId($target->Location, $owner->ControllerId)`, **excluding** the character currently being wounded/moved/engaged. The player picks which other character takes the hit via `redirect-{id}` buttons in `getReactionButtonProperties()`. No `IRiskThatTargetsCharacters` on the Risk class — the Reaction's button UI is the chooser.

#### Clone-cancel-reemit flow (Risk pay split)

1. **`handleEvent` on trigger** — clone the pending event, `$event->canceled = true`, save `$targetCharacterId`, `queueEvent(createReactionTransitionEvent(...))`. Hand guard: `if (! ($owner->Location == Game::LOCATION_HAND)) return;`
2. **`performReaction('redirect-{id}')`** — queue pay only (`createEnteringPayStateEvent(PAY_STATE_IN_HAND_REACTION)` + `createReactionPayTransitionEvent`). **Do not** `releaseEvent` or `setUsed` here.
3. **`handleEvent` on `EventRiskReactionTriggered && internalId == $this->Id`** — notify, then redirect:
   - If `loadAbility()` returns `IAbilityThatTargetsCharacters` → `isValidTargetForAbility` enforces "(If they are able)"; invalid → cancel + message.
   - **Else** → `releaseEvent($characterId)` directly (non-targeting abilities).
   - `setUsed` here (Risk is already in discard from pay).
4. **`performReaction('decline')`** — mirror 02016: only re-`releaseEvent` to the original target for `EventCharacterIntervened` (with `$skipNextEvent = true`); other saved events stay canceled.

`releaseEvent()` mutates the cloned event's target field (`characterId` / `cardId`) and re-queues it. For intervention, also swap `DUEL_DEFENDER` and set `CHOSEN_TARGET` — copy verbatim from `Reaction_02016`.

**WHY defer `releaseEvent` to `EventRiskReactionTriggered`:** same discipline as Pattern D.2 — the Risk must be paid before the redirect lands; framework cancel-reactions during pay should not re-emit a redirected event if the Risk is declined mid-pay.

**Do not copy 02016's wound-on-redirect** unless the card text says so — Cross of the Martyrs wounds the redirect target 1; Altruistic does not.

#### 02016 (AttachmentReaction) vs 03031 (RiskReaction) — when to use which pattern

| | `Reaction_02016` (attachment) | `Reaction_03031` (Risk) |
|---|---|---|
| Base | `AttachmentReaction` — equipped character is the protected target | `RiskReaction` — any of your characters; Risk in hand is the cost |
| Trigger gate | Requires `IAbilityThatTargetsCharacters` | Opponent source only (`isOpponentAbility`) |
| Event breadth | wound/move/engage/heal/targeted/challenge/intervene | Narrow to printed verbs (+ intervene if needed) |
| Resolution | `performReaction` resolves inline (no pay) | Pay in `performReaction`; redirect in `EventRiskReactionTriggered` |
| Owner lookup | `getOwningCharacter` / `getOwningAttachment` | `getOwningCard` (the Risk) |

Reach for `Reaction_01014` (Vittoria — Thug-only redirect) or `Reaction_02016` when adapting attachment reactions; reach for `Reaction_03031` when porting that shape to a hand-paid Risk with effect-based wording.

References: `Reaction_03031` (Altruistic), `Reaction_02016` (structural template on attachments), `Reaction_01053` (Hexenjagd — "performer at that location" chooser semantics on a Risk).

### Pattern D.5 — Deck-reveal Sorcerer Reaction (`CardReaction`, not `RiskReaction`)

Printed (Unravel the Thread `_04010`): **`<b>Sorcerer Reaction:</b> When your performer reveals this card while gambling • Reveal additional cards equal to their [Influence]. Their <b>Sorceries</b> gain +1[Parry] this round.`**

This is **not** a hand-paid RiskReaction. The Risk is peeked from the **faction deck** during gamble; there is no hand pay and no `LOCATION_HAND` guard.

| | Pattern D / `RiskReaction` | Pattern D.5 / `CardReaction` |
|---|---|---|
| Base class | `RiskReaction` | `CardReaction` (+ usually `ISorcererAbility`) |
| Card location at trigger | Hand (cost) | Still in faction deck (revealed peek) |
| Pre-commit | `Location == LOCATION_HAND` + `setUsed` + `isAvailable` | `setUsed` + `isAvailable` only — **no** hand guard |
| Offer UI | `createReactionTransitionEvent` → `playerReaction` | `createTransitionEvent` → dedicated GameState under `DUEL_GAMBLE_REVEALED_EVENTS` |
| When the player chooses | Often **before** combat-card chooseList | **After** revealed cards are visible in chooseList |

#### Hub prerequisite (load-bearing)

`buildCity()` does **not** load faction-deck cards. Without EventHub `addCardToWorld` on each id in `EventDuelGambleCardsRevealed` (hub runs **before** cards — `runEventHubAfterCards=false`), a Reaction on the gambled card never receives the event. This is already in `EventHub` for Unravel; keep it if you add sibling deck-reveal Reactions.

#### Timing — after chooseList, not early `playerReaction`

Ivy-style "before choosing" Reactions use `createReactionTransitionEvent` (priority **6**) → `DUEL_GAMBLE_REVEALED_REACTIONS`. That window is **before** `duelChooseGambleCard` shows chooseList.

Unravel must let the player **see** the revealed cards (including this card) before Use/Pass:

1. On `EventDuelGambleCardsRevealed` when **this** card's id is in `$event->revealedCardIds`, actor is controller's Sorcerer, `isAvailable()` → queue `createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN", $this->Id)`.
2. `EventTransition` sets priority **8** (`TRANSITION_PRIORITY`) — runs **after** reaction transitions (6). Proper Drama C.5 choose-hijack uses the same priority band.
3. Wire `"NNNNN" => States::DUEL_GAMBLE_REVEALED_NNNNN` under **`DUEL_GAMBLE_REVEALED_EVENTS.transitions`**. State id near the gamble family: `52730NNNNN` (see `DUEL_GAMBLE_REVEALED_04010 = 527304010`). Contrast C.5 choose seat `5270NNNNN`.
4. **Public** `cards` via `getArgsFromReaction` + State `argsForState` (client `args.args.args.cards`) — peek with current `GAMBLE_REVEAL_COUNT` / `GAMBLE_REVEAL_FROM_BOTTOM` from the **duel-round actor's** deck (same edge as choose).
5. JS: show chooseList (`selectionMode` 0 — display only) + Use / Pass. Mirror `duelChooseGambleCard_03047` enter/leave; buttons call `actFromCardWithId({id:1})` / `actFromCardPass`.
6. **Both Use and Pass → `DUEL_GAMBLE_REVEALED_EVENTS`**, not straight to `DUEL_CHOOSE_GAMBLE_CARD`. WHY: leftover transitions (e.g. Proper Drama `"03047"`) and then `endOfEvents` → choose must still run; Use also needs the events path for additional-card reveal + Ivy.

**Do not** fire D.5 in the early `playerReaction` window — that is the regression Eddie hit on `_04010`.

#### On Use

1. `createSorcererAbilityStartEvent` (performer = duel actor).
2. Bump `GAMBLE_REVEAL_COUNT` by `max(0, actor->ModifiedInfluence)`; peek the new cards; `addCardToWorld` each.
3. Re-queue `createDuelGambleCardsRevealedEvent(actor, controller, $additionalIdsOnly)` — **only new ids** so this Reaction does not re-offer; Ivy can still react to newly revealed Sorceries.
4. Round-lasting "Sorceries gain +N Parry": set a **Game global** (controller id), apply in EventHub on `EventDuelCalculateCombatCardStats` for that controller's Sorcery combat cards, clear in `stDuelEndOfRound`. WHY not sticky on the Reaction: after resolve the card often sinks back into the deck and leaves `$theah->cards`.
5. `createSorcererAbilityPlayedEvent` + `setUsed(true)`.

#### Deck-card `setUsed` pitfall

Faction-deck cards are not in `buildCity()`. `FrameworkActionsTrait::actFromCardWithId` loads a fresh instance; `setUsed` → `getCardById` would load a **second** copy and persist `Used=false`. On the Risk class, override `actFromCardWithId` / `actFromCardPass` to `$game->theah->addCardToWorld($this)` before `parent::…` (mirror `_02045` / `_04010`).

#### Cesca

Deck-reveal Sorcerer Reactions are not Cesca-copyable as hand Risks. The **Action** half of the same card may still be (`Reaction_01008` allow-list + `copyCard`) when Cesca is the performer — see `_04010` Action / journal `2026-08-03-04`.

References: `Reaction_04010`, `State_duelGambleRevealed_04010`, EventHub `EventDuelGambleCardsRevealed`, contrast C.5 `Maneuver_03047a` / Ivy `Reaction_02042`.

### Adjacent-location pressure + Ranged Weapon equipped (trigger gates)

When text says **When a pressure occurs at an adjacent location, if your performer is equipped with a Ranged Weapon**:

- **Pressure location** = `$event->location` on `EventPressureOccuring` (where totals will be computed).
- **Performer location** = one of `getAdjacentCityLocations($event->location, false)` — scan each adjacent spot for owner's `!$Engaged` characters with a `Weapon`+`Ranged` attachment (`Maneuver_01055` / `Action_01055` attachment loop).
- **Not** the same as Loyal (`_03035`), which counts non-Mercs **at** `$event->location`, nor `_02044` (Solomonia buffs Influence when pressure is at a location **adjacent to** Forum while Solomonia ** sits at** Forum).

**`<b>En Garde Reaction:</b>`** uses the same `!$Engaged` precondition as En Garde Action — the label only changes ability type (Pattern D vs B), not the mechanical gate.

See composite wiring: `Reaction_04020` (D.2.2 + D.1.2).

### `EventHighDramaPhasePlayerPassed` trigger semantics

Fired by `FrameworkActionsTrait::actHighDramaPass` after the player elects to pass their High Drama Action. Field:

- `$event->playerId` — the player who passed (not necessarily the next active player).

Queued alongside `EventActionResolved`, then the state machine goes either to `HIGH_DRAMA_PLAYER_TURN_EVENTS` (`"pass"`) or `HIGH_DRAMA_END` → `HIGH_DRAMA_END_EVENTS` (`"end"` when `PASS_COUNT >= PLAYER_COUNT`). Both EVENTS tables expose `"reaction"` / `"pay"`, so a reaction transition queued from the PlayerPassed handler can run in either path. Hub handler currently only notifies; card handlers own the interesting work (Confusion `_03068` is the first Risk to listen here).

For "After an **opponent** passes", gate `$event->playerId != $owner->ControllerId`. Do **not** confuse with `actPass` / generic Pass buttons inside sub-states — those do not emit this event.

### `EventCharacterIntervened` trigger semantics

Fired by `FrameworkActionsTrait::actHighDramaChallengeActionIntervene` after the intervener replaces the defender. Field semantics for `handleEvent` use:

- `$event->playerId` — the player who chose to intervene (the new defender's controller).
- `$event->oldTargetId` — the previously-targeted character (the original defender).
- `$event->newTargetId` — the **intervener** (the character that replaced the defender).

For a "When your performer intervenes" trigger, gate on `$event->playerId == $owner->ControllerId` (Risk's controller = the intervening player) plus `$intervener->hasTrait(...)` for any trait-prefixed gate. Threat is calculated *after* the intervention/refusal step resolves, so mutating `CHALLENGE_STAT` here lands before threat-calc reads it.

The event fires inside `actHighDramaChallengeActionIntervene` *before* `nextState("")`, so a `createReactionTransitionEvent` queued from your handler runs in the normal reaction-offer flow as the state machine processes pending events.

#### Intervene path ≠ `EventChallengeAccepted` (critical footgun)

`actHighDramaChallengeActionIntervene` sets `Game::CHALLENGE_ACCEPTED = true` and queues `EventCharacterIntervened` (+ usually `EventCardEngaged` for the intervener). It does **not** fire `EventChallengeAccepted`. That event is only emitted from `actHighDramaChallengeActionAccept` (plain accept with no intervention).

So printed text like **"After your performer's challenge is accepted, if their adversary intervened"** maps to **`EventCharacterIntervened` only**, gated on your challenger (`globals->get(Game::CHOSEN_PERFORMER)` controlled by the Risk owner + trait gate). Do **not** also listen for `EventChallengeAccepted` — that path is accept-without-intervene and must not fire the "adversary intervened" clause. Reference: `Reaction_03046b` (Passionate Pirate).

#### Event queue order on intervene

`actHighDramaChallengeActionIntervene` queues in order: `EventCharacterIntervened`, then (usually) `EventCardEngaged` for the intervener. A reaction transition queued from the Intervened handler therefore runs **after** the engage has applied. By offer/pay time the intervener is typically `Engaged == true` (exceptions: Odette Musketeer deferral, Rena weapon deferral). At `EventRiskReactionTriggered`, only engarde if still `Engaged`. Challengers are usually already engaged from `stIssueChallenge` / setup before intervene, so Pirate-style "engarde your challenger" can also gate `Engaged` at trigger time to avoid offering a no-op.

### "Trigger-named performer is the performer" — Reaction trait gates

When the printed text references a character by role ("When **your performer** intervenes," "When **your character** is wounded," etc.), that character IS the performer of the Reaction. Apply trait gates (`Strega`, `Sorcerer`, `Mercenary`, `Duelist`, `Pirate`, …) directly to the character named by the trigger event — do **not** search for a separate trait-bearing performer.

Compare:
- `Reaction_03010` (Manipulative) — "Strega Reaction" with no role-named performer in the trigger. The Reaction searches `getCharactersInPlayByPlayerId(owner->ControllerId)` for any Strega.
- `Reaction_03012` (Subtle) — "Sorcerer Strega Reaction: When **your performer** intervenes." The intervener (from `$event->newTargetId`) IS the performer; the Strega gate checks `$intervener->hasTrait("Strega")` directly. No separate search.
- `Reaction_03046a` (Passionate Duelist) — same intervene role as Subtle; gate `hasTrait("Duelist")` on `$event->newTargetId`, then engarde that character after pay.
- `Reaction_03046b` (Passionate Pirate) — "your performer" is the **challenger** (`CHOSEN_PERFORMER`), not the intervener. Gate `hasTrait("Pirate")` on the challenger; engarde the challenger after pay. Mutually exclusive with the Duelist clause on the same intervene event (you cannot be both intervening player and the challenger's controller for one challenge).
- `Reaction_03031` (Altruistic) — "Your **performer at that location** suffers those effects instead." Here "performer" means **another of your characters at the affected character's location** (`getCharactersAtLocationByPlayerId`, excluding the character being wounded/moved/engaged). The player picks which one via redirect buttons — same pool semantics as Hexenjagd's wound-performer chooser (`Reaction_01053`), not a search for a trait-bearing role elsewhere on the board.

This matters for `ISorcererAbility`'s `createSorcererAbilityStartEvent($performerId)` arg — pass the trigger-named character's id, not a generic "any Strega I control."

### Mutating `Game::CHALLENGE_STAT` mid-challenge

The active challenge stat is held in the `Game::CHALLENGE_STAT` global; threat is read from it later in `StatesTrait::stGenerateChallengeThreat`. Several Actions set it at challenge-issue time (e.g., `Action_03008` Arrogant → `STAT_COMBAT`); a Reaction can flip it mid-flow, between intervention/refusal and threat-calc.

Use `globals->set(Game::CHALLENGE_STAT, Game::STAT_INFLUENCE)` (or other `STAT_*` constant) directly. Do **not** introduce a new `CHALLENGE_TYPE` constant unless intervention or refusal rules also differ — `CHALLENGE_TYPE` controls intervention/refusal gating, `CHALLENGE_STAT` controls the stat used in threat calc, and the two are orthogonal.

### Trigger event distinction: `EventApproachCharacterPlayed` vs `EventCharacterMustered`

These do **not** overlap:
- `EventApproachCharacterPlayed` fires only during the Approach Phase (`StatesTrait::stApproachPhase`). It is the canonical "approach character played from the approach deck to home."
- `EventCharacterMustered` fires only from card effects that muster a character (Chance Meeting `_03cd03`, Réputation Méritée `_01072`, Bravos `_01024`, Don Constanzo's Reaction `_03003`, etc.). It is **not** fired during the Approach Phase.

If your trigger phrasing is "after a character is mustered from an Approach deck," you need **both events**. For `EventCharacterMustered`, filter on `$event->fromLocation == Game::LOCATION_APPROACH` (the field is populated by `EventHub`'s central handler before the move, so by the time card handlers see the event the field reflects the pre-move source). Don't try to read `$character->Location` for the source — `runEventHubAfterCards = false` means the hub has already moved the character to the destination by the time your handler runs.

### `EventCharacterPutIntoApproachDeck` semantics

This is the framework op for sending a character back to their owner's Approach deck (canonical use: `Reaction_01202` Object of Wonder; also `Reaction_03010` Manipulative). The hub handler:
- Moves the card to `LOCATION_APPROACH`.
- **Resets in-play state**: `Wounds`, `WoundsHealedIncoming`, `IsDying`, `Engaged` are all zeroed. A card in the Approach deck has no memory of its prior life.
- **Sends `cardRemovedFromPlay`** when the character was in play (city or `LOCATION_PLAYER_HOME`) at the moment of put-back, so other players' clients animate the leave.
- Sends a private `approachCardsReceived` to the owning player so their approach-deck UI syncs.

You generally don't need to queue a separate `createCardRemovedFromPlayEvent` or manually zero state — the hub does it. Just queue `createCharacterPutIntoApproachDeckEvent($ownerId, $characterId)` and any follow-on events (e.g., `createCharacterMusteredEvent` for a swap).

