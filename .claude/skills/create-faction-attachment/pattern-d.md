> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern D — AttachmentReaction

Extend `AttachmentReaction` (which extends `CardReaction`). It adds `ownerIsAttached(Theah)` so you can early-out when the parent attachment is detached.

### Default gate: attachment must be in play (equipped)

`Theah::runEvents` dispatches every event to **all** tracked cards, including cards in hand and off-board zones. A card-level reaction on an attachment still receives those events even when the attachment is not equipped.

**Default rule:** Reactions on attachments only trigger when the attachment is in play — call `$this->ownerIsAttached($theah)` in `handleEvent` before queuing a reaction transition. For attachments, "in play" means equipped (`AttachedToId > 0` via `Attachment::isAttached()`), not merely held in hand.

**Exception:** When printed text equips the attachment *from* a specific off-play zone (hand, dueling line, etc.), gate on that zone instead of `ownerIsAttached` — e.g. `Reaction_01155` checks `LOCATION_DUELING_LINE`. Re-equip reactions (already attached, moving to another character) use `ownerIsAttached` plus exclude the current host from targets.

**Pre-commit hook requires all three** literal strings in the class body:
- `$this->setUsed(`
- `$this->isAvailable(`
- `$this->ownerIsAttached(`

Skeleton (simple single-decision):

```php
class Reaction_NNNNN extends AttachmentReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("...");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah)
            . $theah->game->translate('${you} may ...');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Do It'), 'doEffect');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! $this->isAvailable()) return;
        if (! $this->ownerIsAttached($event->theah)) return;

        $owner = $this->getOwningAttachment($event->theah);
        if ($owner == null || $owner->Engaged) return;  // if engage is the cost

        if ($event instanceof EventSomething && /* trigger condition */)
        {
            $owner->IsUpdated = true;
            $transition = EventFactory::createReactionTransitionEvent(
                $owner->ControllerId, $owner->Id, $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId === 'doEffect')
        {
            $owner = $this->getOwningAttachment($game->theah);

            // Engage cost (common on attachment reactions):
            $engageEvent = EventFactory::createCardEngagedEvent(
                $owner->ControllerId, $owner->Id, $owner->Id, $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            // ... effect ...

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
```

`CardReaction::setUsed` resets at dusk automatically (via `EventDuskEndOfDay`).

References: `Reaction_01022` (simple wound-challenger/wound-challenged/pass), `Reaction_01040`, `Reaction_01047` (hard cancel Technique), `Reaction_01146b` (hard cancel Maneuver or Technique on a Scheme), `Reaction_01181` (cancel + re-queue pattern), `Reaction_03044` (cancel unless discard).

### Engage as a cost — gate the trigger on `! $owner->Engaged`

When the card text says "engage this card" as a cost, gate the trigger so the reaction can't fire while already engaged:

```php
$owner = $this->getOwningAttachment($event->theah);
if ($owner == null || $owner->Engaged) return;
```

Then queue `createCardEngagedEvent` in `performReaction` when the player accepts. Engagement resets at dusk along with `Used`.

**Do NOT `setUsed(true)` on the Engage click if a later stage still needs a reaction transition.** `Theah::runEvents` skips `transition == "reaction"` when `!$reaction->isAvailable()` (to prevent duplicate offers). Marking Used early drops the next stage (e.g. adversary "unless discard" choice) and pending MEDIUM resolve events proceed uncanceled. Defer `setUsed` to finalize after the last stage — see `Reaction_03007::finalize`, `Reaction_03044::finalizeAfterEngage`.

### Cancel Maneuver / Technique ("would resolve" / "announces")

**Timing:** Listen on `EventTechniqueActivated` / `EventManeuverActivated`, not `EventResolveTechnique` / `EventResolveManeuver`. "Would resolve" / "announces" means interrupt after activation while Resolve + CalculateValues are still queued. Hard-cancel references: `Reaction_01047`, `Reaction_01146b`.

**`IN_DUEL` gate:** Cancel reactions that target duel Maneuvers/Techniques must require `Game::IN_DUEL` (rules-team ruling on `01146b`).

**Equipped participant's adversary gate — do not copy `Reaction_01047`'s id compare.** `getDuelOpponentId($actorId)` returns a **character** id. `EventTechniqueActivated::$playerId` is a **player** id. Correct gates:

```php
$owningCharacter = $this->getOwningCharacter($theah);
$actor = $theah->getDuelRoundActor();
$adversaryId = $theah->getDuelOpponentId($actor->Id);
// Cloak/attachment must be on the actor's duel adversary
if ($owningCharacter->Id != $adversaryId) return false;
// Activator must be the actor's controller
return $activatingPlayerId == $actor->ControllerId;
```

`Reaction_01047` compares `ControllerId == getDuelOpponentId(...)` and `actor->Id == event->playerId` — those mix player ids with character ids. Prefer the gates above (`Reaction_03044::isAdversaryActivating`).

**`HIGH_PRIORITY` on every reaction transition in the cancel chain.** `createReactionTransitionEvent` defaults to `REACTION_PRIORITY` (6), which is *later* than MEDIUM (3) Resolve events. Override to `Event::HIGH_PRIORITY` (2) on the offer transition *and* every follow-up cross-player transition, or Resolve fires mid-decision.

**Hard cancel (single click):** `deleteTechniqueEvents` / `deleteManeuverEvents`, clear `CHOSEN_TECHNIQUE` / `CHOSEN_MANEUVER` (+ `CHOSEN_TECHNIQUE_IS_MAIN`), queue `createTechniqueCanceledEvent` / `createManeuverCanceledEvent`. Store `$TechniqueId` / `$ManeuverId` as **public** fields (like `01047` / `01146b`).

### Cancel unless discard (Pattern D + multi-stage) — Torres Cloak `_03044`

Printed: "engage this card • Cancel the effects unless they discard a card."

Reading: cancel is the primary outcome; discard is the escape hatch that *keeps* the Maneuver/Technique. Compose `01047`/`01146b` cancel mechanics + `03007` multi-stage cross-player + `02033` `discardHand-{id}` buttons.

**Use cancel-first on Engage — do not leave Resolve queued during the threat wait.**

Cancel-later (delete only on Accept Cancel) races: Resolve can still fire if `TechniqueId` is lost across the multi-stage serialize, or if any transition is skipped — observed as the cloak player entering `duelChooseTechnique_01093` after Maya’s threat choice. Cancel-first is robust:

1. **Offer** (cloak controller): Engage / Pass.
2. **Engage:** queue `createCardEngagedEvent`; **immediately** `deleteTechniqueEvents` / `deleteManeuverEvents` + clear `CHOSEN_*`. Do **not** fire `*Canceled` yet (discard may restore). Store `actorId`, `adversaryCharacterId`, `activatingPlayerId`, `techniqueWasMain` for restore. Do **not** `setUsed` yet.
3. **Empty hand:** log why, fire `*Canceled`, `finalizeAfterEngage` (`setUsed` + reset).
4. **Threat** (adversary): `discardHand-{id}` → discard as effect + **re-queue** `createResolveTechniqueEvent` / `createResolveManeuverEvent` + CalculateValues + restore `CHOSEN_*`; **or** Accept Cancel → fire `*Canceled`. Then finalize.
5. Both offer and threat transitions: `priority = Event::HIGH_PRIORITY`.

**Rules check when playtesting:** discard-to-keep *should* let the technique resolve (e.g. cloak player may still enter `duelChooseTechnique_01093`). Accept Cancel must *not*.

**Persistence:** keep `$stage`, `$TechniqueId`, `$ManeuverId`, and restore context as **public** properties on the reaction (mirror `01047`). `$owner->IsUpdated = true` after every mutation.

### Pressure fails instead (difference ≤1) — Pompon `_04026` / Objection `_01027`

Printed (attachment): "When a pressure at this location would succeed by one or fewer, engage this card • It fails instead."

Printed (Risk sibling Objection): "When a pressure succeeds with a difference of 1 or less • The pressure fails instead."

**Do not route attachment versions through Objection's wealth-pay path.** Objection is a `RiskReaction` + `ICancelReaction`: `performReaction` stacks pay → `EventRiskReactionTriggered` applies the fail after payment. Attachment cost is **engage this card** — apply engage + fail in `performReaction` directly. No `ICancelReaction`, no `EventRiskReactionTriggered`.

Trigger and gates:

```php
if (! ($event instanceof EventLocationPressured)) return;
if (! $event->success || $event->difference > 1) return;
if (! $this->isAvailable()) return;
if (! $this->ownerIsAttached($event->theah)) return;

$owner = $this->getOwningAttachment($event->theah);
if ($owner === null || $owner->Engaged) return;  // engage cost

$owningCharacter = $this->getOwningCharacter($event->theah);
// "at this location" — omit this gate when printed text has no location clause (Objection)
if ($owningCharacter === null || $event->location != $owningCharacter->Location) return;

// WHY: Mirror Objection — only interrupt another player's pressure.
// Printed text often omits "opponent"; failing your own success is not the intent.
if ($event->playerId == $owner->ControllerId) return;
```

**Capture pressured context** onto the reaction at trigger time (flat fields: `playerId`, `performerId`, `location`, `pressureType`, `totalsExplanation`, `highDramaBasicAction`, `abilityId`) + `$owner->IsUpdated = true`. Needed to rebuild the failed Result after the original event is gone. Clear after Fail.

**Offer transition:** `createReactionTransitionEvent` with `priority = Event::HIGH_PRIORITY` so the choice interrupts before `EventLocationPressureResult` resolves (same as Objection / cancel Techniques).

**On Fail in `performReaction`:**
1. `createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id)`
2. Notify
3. `$game->theah->deletePressureResultEvents()`
4. `createLocationPressureResultEvent(..., success: false, ...)` with stored context → `queueEvent`
5. `setUsed` + clear stored context

No GameState / JS — standard `playerReaction` buttons (Fail Pressure / Pass).

**Risk vs Attachment footgun:** copying Objection wholesale into an AttachmentReaction leaves a dead pay-state path and never engages the card. Copy the *pressure math* (Pressured + delete + failed Result); replace the *cost plumbing* with engage.

References: `Reaction_04026` (attachment), `Reaction_01027` (Risk sibling — pressure math only).

### Self-equip Reaction ("After a Hunter or Berserker equips this card • …")

When the Reaction lives **on this attachment** and triggers when **this card** is equipped:

```php
if (! ($event instanceof EventAttachmentEquipped)) return;
if (! $this->isAvailable()) return;
if (! $this->ownerIsAttached($event->theah)) return;

$owner = $this->getOwningAttachment($event->theah);
if ($owner === null || $event->attachmentId != $owner->Id) return;

$character = $event->theah->getCharacterById($event->characterId);
if (! ($character instanceof Character)) return;

// Trait OR gate on the *host* — not an equip restriction (Pattern A).
if (! $character->hasTrait('Hunter') && ! $character->hasTrait('Berserker')) return;

if ($character->Wounds <= 0) return;  // skip offer when heal would be a noop

$transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
$event->theah->queueEvent($transition);
```

**WHY not Pattern A:** "After a \<Trait\> equips this card" gates the *Reaction offer*, not who may equip. Characters without the trait still equip; they simply never see the Reaction. Only add `canAttachTo` / `eventCheck(EventAttachmentEquipping)` when text also says **"May only equip…"**.

**Heal effect:** `createCharacterBeingHealedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id)` + notify. Re-check trait + `Wounds > 0` in `performReaction` before healing. SourceId for the reaction transition = **attachment** id (same as `Reaction_01022`).

Reference: `Reaction_04016` (Drachenblut). Heal siblings: `Reaction_03027a`, `Reaction_01181`. Character-side equip Reactions (not self): `Reaction_01039`, `Reaction_01146a`.

### "After an opposing character moves to an adjacent location" triggers

Trigger on `EventCardMoved` (fires *after* the move resolves — `EventCardMoving` fires before). The classic gates:

```php
if (! ($event instanceof EventCardMoved)) return;
if (! $this->isAvailable()) return;
if (! $this->ownerIsAttached($event->theah)) return;

$owner = $this->getOwningAttachment($event->theah);
if ($owner == null || $owner->Engaged) return;  // if engage is the cost

$owningCharacter = $this->getOwningCharacter($event->theah);
if ($owningCharacter == null || ! $event->theah->cardInCity($owningCharacter)) return;

$character = $event->theah->getCardById($event->cardId);
if (! ($character instanceof Character)) return;
if ($character->ControllerId == $owningCharacter->ControllerId) return;

// "opposing" = same location before the move. After the move they are *not*
// at our location, so we check fromLocation, not the current location.
if ($event->fromLocation != $owningCharacter->Location) return;

if (! $event->theah->locationInCity($event->toLocation)) return;

$adjacent = $event->theah->getAdjacentCityLocations($owningCharacter->Location, false);
if (! in_array($event->toLocation, $adjacent)) return;
```

**Why `fromLocation == owningCharacter->Location`:** per `feedback_opposing_definition`, "opposing" requires same-location + different-controller. Since the move has already resolved by the time `EventCardMoved` fires, "opposing" can only be evaluated against the **prior** location. `_01066` (Horatio) is the canonical character-side mirror of this pattern; `_03019` (Kaiser Schnurrbart) is the attachment-side mirror.

Without the `fromLocation` gate, the reaction would fire on any enemy that happens to end at an adjacent location — including teleports, recruits-into-location, and far-away moves — which is broader than printed text.

### `createCardMovingEvent` and the `$engage` parameter

```php
public static function createCardMovingEvent(
    int $initiatingPlayerId, int $cardId,
    string $fromLocation, string $toLocation,
    bool $engage = true,                // <-- defaults to true!
    int $sourceId = 0, string $abilityId = ""
): EventCardMoving
```

The default `$engage = true` is for **moving-as-an-action** (the move costs the character an engagement). When the move is a Reaction or Forced effect (e.g. "Move X to Y and engage that character"), pass `false` and emit the engagement separately — see `Reaction_01037`, `Reaction_01066`, `Reaction_01173`, `Reaction_03019`. This lets you control *who* gets engaged (the mover vs. the prey vs. neither) instead of conflating the move with engagement.

### Resolving ambiguous "that character" / "their" antecedents

Card text like "Move the equipped character to **their** new location and engage **that character**" is grammatically ambiguous — "their" and "that character" can each plausibly refer to either the equipped character or the trigger character.

Resolution heuristics, in priority order:
1. **Pronoun chain consistency.** Once a pronoun ("their") locks onto an antecedent, the next demonstrative ("that character") usually keeps the same referent unless the next sentence explicitly switches. So "their new location" → trigger character → "that character" → trigger character.
2. **Thematic check.** Read the card's name/traits as a tiebreaker. A "Hunter" card pinning prey (engage opposing) reads cleaner than the dog handler exhausting themselves (engage equipped) after running.
3. **Cost/effect balance.** If one reading makes the cost (engage attachment) strictly worse than a baseline move, prefer the other reading.
4. **Flag the call in the journal.** Either way, write a journal entry naming the ambiguity, the chosen interpretation, and how to flip it (one-line edit). A future audit can revisit without re-deriving the analysis.

### Sorcerer Reactions — `implements ISorcererAbility`

If the keyword is **"Sorcerer Reaction:"** or **"Sorcerer City Reaction:"**, the reaction class additionally implements `ISorcererAbility`:

```php
class Reaction_NNNNN extends AttachmentReaction implements ISorcererAbility
{
    // ...
}
```

The pre-commit hook then requires both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` somewhere in the file. Standard idiom:

- Fire **Start** when the player accepts the reaction (just before queueing effect events).
- Fire **Played** in `finalize()` after all effects + before `setUsed`.

```php
private function finalize(Game $game, Card $owner): void
{
    $performer  = $this->getOwningCharacter($game->theah);
    $performerId = $performer ? $performer->Id : 0;

    $played = EventFactory::createSorcererAbilityPlayedEvent(
        $owner->ControllerId, $owner->Id, $this->Id, $performerId
    );
    $game->theah->queueEvent($played);

    $this->setUsed($game->theah, true);
    $this->resetStage();
    $owner->IsUpdated = true;
}
```

Reference: `Reaction_03007` (Matushka's Shears — Sorcerer City Reaction on a FactionAttachment).

### "Strega Reaction" / "Mercenary Reaction" / etc. are NOT Sorcerer abilities

Trait-prefixed keywords gate the *performer* trait, NOT the ability type. They use `hasTrait("Strega")` checks on the attached character but do NOT implement `ISorcererAbility`. They can stack with "Sorcerer" ("Sorcerer Strega Reaction" is both). (Memory feedback — `feedback_strega_vs_sorcerer_keyword.md`.)

### Multi-stage button-driven reactions (no sub-state)

When the reaction needs several player clicks in sequence (offer → choose → pick), or when the player who clicks *changes* between steps, use a `$stage` field plus `$owner->IsUpdated = true` to persist. Pattern source: `Reaction_03007` (Matushka's Shears), `Reaction_03006` (Premonition — on a Scheme but same shape).

Anatomy:
- A `private string $stage` field (e.g. `''` idle, `'offer'`, `'choose'`, `'pick1'`, `'pick2'`) plus per-stage context fields (`$opponentId`, `$cardsSunk`).
- `getReactionDescription` switches on `$stage` to return the right prompt.
- `getReactionButtonProperties` switches on `$stage` to render the right buttons (Engage/Pass on offer; one `card-{id}` button per hand card on pick stages).
- `performReaction` parses click via `str_starts_with($reactionId, 'card-')`, applies the per-step effect, advances `$stage`, and queues **another** `createReactionTransitionEvent` for whichever player acts next.
- `setUsed($theah, true)` only fires when the multi-stage flow is fully resolved (in `finalize` / the last stage).

```php
private function advanceToNextPick(Game $game, Card $owner): bool
{
    $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
    if (count($hand) == 0) return false;       // pool exhausted — caller treats as "finalize early"

    $this->stage = ($this->cardsSunk == 0) ? 'pick1' : 'pick2';
    $owner->IsUpdated = true;

    $transition = EventFactory::createReactionTransitionEvent(
        $this->opponentId, $owner->Id, $this->Id
    );
    $game->theah->queueEvent($transition);
    return true;
}
```

The `framework` re-enters `playerReaction` with the updated active player + button set. `playerReaction` exists alongside every events state, so this pattern works phase-independently (Planning, High Drama, Dawn, Duels all support it).

Reference: `Reaction_03007::advanceToNextPick`, `Reaction_03006::advanceToNextPick`.

### Cross-player flow ("the opponent must do part of the resolution")

When a Reaction's effect requires the **opposing** player to make a choice (e.g. "they must sink two cards from their hand"), DO NOT route through a dedicated GameState sub-state — Reactions can fire from any phase, and a sub-state mapped under one phase's `*_EVENTS` transitions table only works in that one phase.

Instead, queue `createReactionTransitionEvent($opponentId, $owner->Id, $this->Id)` with the opponent's playerId. The framework makes them the active player in `playerReaction`. Reference: `Reaction_03007`, `Reaction_03006`.

### Sinking cards from a player's hand

Same recipe as `Reaction_03006`/`Reaction_03007`'s `sinkOneFromHand`:

```php
private function sinkOneFromHand(Game $game, Card $owner, int $cardId): void
{
    $hand    = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
    $handIds = array_map(fn($c) => $c->Id, $hand);

    if (! in_array($cardId, $handIds))
    {
        throw new \Bga\GameFramework\UserException($game->translate("Selected card is not in your hand."));
    }

    $card     = $game->getCardObjectFromDb($cardId);
    $deck     = $game->getGameDeckObject();
    $deckName = $game->getPlayerFactionDeckName($this->opponentId);

    $deck->insertCardOnExtremePosition($cardId, $deckName, false);

    $game->notify->player($this->opponentId, "cardRemovedFromHand", clienttranslate('Private: ${reaction_inject_code}: you sink ${card_inject_code} from your hand.'), [
        "reaction_inject_code" => $owner->getInjectCode(),
        "card_inject_code"     => $card->getInjectCode(),
        "playerId"             => $this->opponentId,
        "cardId"               => $cardId,
        "handCount"            => count($deck->getPlayerHand($this->opponentId)),
    ]);

    $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} sinks a card from their hand.'), [
        "reaction_inject_code" => $owner->getInjectCode(),
        "player_name"          => $game->getPlayerNameById($this->opponentId),
    ]);
}
```

"Sink" = back to the faction deck (`insertCardOnExtremePosition($cardId, $deckName, false)`); "discard" = to the discard pile. They're different operations — match the literal printed word.

### "If able" loop termination

When the effect demands N items from a finite pool ("sink two cards", "discard three"), structure the loop to terminate gracefully when the pool is exhausted — the rules implicitly read "if able." Caller treats `false` from `advanceToNext*` as "finalize early":

```php
if ($this->cardsSunk < 2 && $this->advanceToNextPick($game, $owner))
{
    $game->gamestate->nextState("done");
    return;
}

$this->finalize($game, $owner);
```

Reference: `Reaction_03007::advanceToNextPick`, `Reaction_03006::advanceToNextPick`.

### Log when an effect happens automatically

When an edge case skips the player's choice and auto-applies one branch (e.g. "opponent has < 2 cards in hand so we auto-wound the Leader instead"), log the reason *before* the consequent effect:

```php
if (count($hand) < 2)
{
    $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${opponent_name} does not have enough cards in hand to sink two, so their Leader is wounded.'), [
        "reaction_inject_code" => $owner->getInjectCode(),
        "opponent_name"        => $game->getPlayerNameById($this->opponentId),
    ]);

    $this->woundLeader($game, $owner);
    return false;
}
```

Reference: `Reaction_03007::advanceToChoose`. Without the log, players see only the wound message and wonder why no choice was offered.

### Capturing context onto the reaction

The triggering event has only a snapshot of args (`$event->cardId`, `$event->playerId`, etc.). If `performReaction` needs context that isn't on the event (e.g. the destroyed character's name, the location of the triggering challenge), capture it into a `private` property on the reaction at trigger time, **then clear it** in `performReaction` (or `resetStage` for multi-stage reactions). `$owner->IsUpdated = true` persists the property to DB. See `Reaction_03006::$targetCharacterId` and `Reaction_03007::$opponentId` for the pattern.

**Surface captured context in the prompt.** The reaction-button screen is the player's first chance to see *why* they're being prompted — bake the relevant context into `getReactionDescription` so they can make an informed pass/play call.
