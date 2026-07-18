> Part of **create-risk**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern D — Reaction (`RiskReaction`)

Risk reactions are played from hand (the Risk card is the cost). Pre-commit hook enforces hand-only guard.

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

References: `Reaction_01080` (Iron Reply-style — adds Parry during opposing maneuver), `Reaction_01140`, `Reaction_01088`, `Reaction_02048` (Pressure-to-cancel — multi-event family, saved-event re-emit on decline), `Reaction_03010` (cross-player choice flow after pay — see Pattern D.1), `Reaction_03031` (effect-event redirect after pay — see Pattern D.4; structural cousin of `Reaction_02016` on attachments).

### Pattern D.1 — Multi-stage cross-player RiskReaction with pay

When the Risk Reaction's effect itself involves another player choosing something (e.g., "Wound them unless their controller does X"), the standard RiskReaction shape (pay → `EventRiskReactionTriggered` → resolve inline) isn't enough. The pattern that works in-codebase, modeled after `Reaction_03010` (Manipulative):

1. **Internal `$stage` field** on the reaction (`''` / `'choice'` / `'pickN'` …) plus `$targetId` / `$opposingPlayerId` captured at trigger time. `getReactionButtonProperties()` and `getReactionDescription()` switch on `$stage`.
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

