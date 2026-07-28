> Part of **create-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern A — Passive ability via `handleEvent`

For text that has no player choice ("At the end of Dawn, draw five cards", "Your adversaries at Soline's location have -1 Finesse", "When Yevgeni plays a combat card, it gains +1 Thrust") — override `handleEvent` and gate the body on event type + identity + scope. Always call `parent::handleEvent($event)` first.

### Identity and scope gates

1. **Event type** — `instanceof EventXxx`.
2. **Identity** — usually `$event->cardId == $this->Id`, `$event->characterId == $this->Id`, `$event->playerId == $this->ControllerId`, or `$event->actorId == $this->Id`. The exact field depends on the event class; **read the event source file** to confirm.
3. **Liveness / scope** — at minimum a "this card is in play" check. For a Leader, the right check is usually `! $event->theah->game->characterIsInDiscardOrLocker($this)` (and `$this->ControllerId > 0` as a cheap pre-check). For an "in city" effect, also gate on `$event->theah->cardInCity($this)`.

### End-of-Dawn draw (canonical example — Cesca)

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventPhaseDawnEnding && $this->ControllerId > 0)
    {
        $game = $event->theah->game;
        if ($game->characterIsInDiscardOrLocker($this))
        {
            return;   // dead Leader / destroyed Character — skip the effect
        }

        $game->notify->all("message", clienttranslate('${leader_inject_code}: ${player_name} draws five cards at the end of Dawn.'), [
            "leader_inject_code" => $this->getInjectCode(),
            "player_name"        => $game->getPlayerNameById($this->ControllerId),
        ]);

        for ($i = 0; $i < 5; $i++)
        {
            $drawEvent = EventFactory::createCardDrawnEvent($this->ControllerId, $this->getInjectCode());
            $event->theah->queueEvent($drawEvent);
        }
    }
}
```

WHY `characterIsInDiscardOrLocker` and not just `isControlled()`:

- A destroyed Leader still has a non-zero `ControllerId` — `isControlled()` returns true.
- The actual signal that the Leader is out of play is the `Location` (discard/locker).
- See `UtilitiesTrait::characterIsInDiscardOrLocker` for the canonical check.

Apply the same check on any Character ability that triggers off phase events.

### Gambled combat-card stat bonus

For text like **"Sanjay's gambled combat cards have +1[Riposte]"** (not every combat card — only when the actor gambled this round):

```php
if ($event instanceof EventDuelCalculateCombatCardStats
    && $event->actorId == $this->Id
    && $event->gambled)
{
    $event->explanations[] = sprintf(
        $event->theah->game->translate("%s's gambled combat card gains +1 Riposte"),
        $this->getInjectCode()
    );
    $event->addRiposte(1);
}
```

WHY `$event->gambled` (not `Game::DUEL_GAMBLED` alone): `StatesTrait::stResolveCombatCard` sets `$event->gambled` from `duel_round.gambled` for the current round — the authoritative per-round flag, including Roll-the-Bones paths that still attribute stats through this event. The `DUEL_GAMBLED` global is the right gate for **Gambling Technique** availability (`isAvailableToPlayer`); the calculate-stats event's own field is the right gate for **passive combat-card modifiers**.

Contrast: Yevgeni `_01116` adds +1 Thrust on every combat card (`actorId` only, no gambled gate). Sanjay `_03037` is the gambled-only variant.

Reference: `_03037` Sanjay, `_01116` Yevgeni.

### Drawing cards

- One card: `EventFactory::createCardDrawnEvent($playerId, $reason)` then `queueEvent`.
- N cards: loop and queue N events. The framework draws one card per event. (Yes, `_03001` literally queues five draw events in a loop.)
- The `$reason` string shows in the log alongside the draw. Use `$this->getInjectCode()` so the log links back to your card.

### Passive stat modifiers

For "Your <stat> increases / decreases by N":

```php
private function lowerFinesse(Character $character, Theah $theah)
{
    $event = EventFactory::createCharacterFinesseModifedEvent(
        $this->ControllerId,
        $character->Id,
        $character->ModifiedFinesse,                    // from
        $character->ModifiedFinesse - 1,                 // to
        $this->getInjectCode()                           // reason for log
    );
    $theah->queueEvent($event);
}
```

The factories are:
- `createCharacterCombatModifiedEvent`
- `createCharacterFinesseModifedEvent` (note the typo in the framework — `Modifed`, not `Modified`)
- `createCharacterInfluenceModifiedEvent`
- `createCharacterResolveModifiedEvent`
- `createCharacterPanacheModifiedEvent` (Leader only)

When the predicate that drives the modifier changes (a character moves into/out of the affected location, a duel ends), queue the inverse event to undo it. See `_01089` Soline el Gato — `lowerFinesse` on `EventDuelStarted`, `raiseFinesse` on `EventDuelEnd` / opposite swap. Track which character was affected on `$this->AffectedCharacterId` and set `$this->IsUpdated = true` so the change persists.

### While equipped with a Weapon (count-transition, not a bool flag)

For "While <Owner> is equipped with a **Weapon**, he gains +N[Stat]" — mirror Rena `_01040` / Íñigo `_03039`. Hook `EventAttachmentEquipped` and `EventAttachmentUnequipped` with `characterId == $this->Id`. After the event, count Weapons in `$this->Attachments` (Attachments already reflects the new set). Queue `+N` only when `weaponsCount == 1` (transition into "has a Weapon"); queue `−N` only when `weaponsCount == 0` (last Weapon left).

WHY count-transition instead of a `$WeaponBonusApplied` bool: Offhand / multi-Weapon equip paths can equip a second Weapon without the first leaving — a naïve "equip Weapon → +1" would stack. Counting after the event applies the bonus exactly once while any Weapon is present.

Use `createCharacterCombatModifiedEvent` or `createCharacterFinesseModifedEvent` (framework typo `Modifed`). Do not invent a Weapon-specific helper.

### Dynamic stat bonuses tied to the dueling line

For text like "Elena has +1[Finesse] for each **Sorcery** in her dueling line" — the bonus changes round-to-round as cards enter the dueling line. There is no event fired when a card enters `LOCATION_DUELING_LINE` (`FrameworkActionsTrait::actDuelActionChooseCombatCard` and the maneuver paths call `$this->cards->moveCard(...)` directly, bypassing the `EventCardMoved` path). So we recompute at duel-round boundaries instead.

Pattern (mirror `_03004` Elena):

```php
public int $FinesseBonus = 0;   // running state — survives across reaction-loop iterations via IsUpdated

public function handleEvent(Event $event)
{
    parent::handleEvent($event);
    if ($this->ControllerId == 0) return;

    if ($event instanceof EventDuelEndOfRound)
    {
        $this->recomputeFinesseBonus($event->theah);
    }

    if ($event instanceof EventDuelEnd)
    {
        // Subtract the running bonus directly; do NOT recount.
        // EventDuelEnd fires BEFORE the dueling-line cards are discarded
        // in stDuelEnd, so a recount would still see Sorcery cards.
        $this->applyFinesseDelta(0, $event->theah);
    }
}

private function recomputeFinesseBonus(Theah $theah): void
{
    // "Her dueling line" — LOCATION_DUELING_LINE is keyed per-player_id,
    // not per character. If a different one of this player's characters is
    // the duelist, the cards in the line belong to *them*, not the owner.
    $challengerId = $theah->getDuelChallengerId();
    $defenderId   = $theah->getDuelDefenderId();
    if ($this->Id != $challengerId && $this->Id != $defenderId)
    {
        $this->applyFinesseDelta(0, $theah);
        return;
    }

    $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $this->ControllerId);
    $count = 0;
    foreach ($cards as $card)
    {
        if ($card->hasTrait("Sorcery"))  // or whatever trait the card text names
        {
            $count++;
        }
    }
    $this->applyFinesseDelta($count, $theah);
}

private function applyFinesseDelta(int $newBonus, Theah $theah): void
{
    $delta = $newBonus - $this->FinesseBonus;
    if ($delta == 0) return;

    $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
        $this->ControllerId, $this->Id,
        $this->ModifiedFinesse, $this->ModifiedFinesse + $delta,
        $this->getInjectCode()
    );
    $theah->queueEvent($finesseEvent);

    $this->FinesseBonus = $newBonus;
    $this->IsUpdated = true;
}
```

WHY recompute at `EventDuelEndOfRound` (not on every event):

- It is the cleanest boundary: both players' combat cards have resolved into the dueling line, and the *next* round's gambling hasn't fired yet. Gamble capacity is `ModifiedFinesse - gamblesCount` (see `FrameworkActionsTrait::actChooseGambleCard`) — recomputing here means the bonus is correct *before the next round's gambling*, which is when Finesse matters in a duel.
- Recomputing on a calc event (e.g. `EventDuelCalculateCombatCardStats`) is wrong because that event doesn't expose Finesse — it exposes parry/riposte/thrust on the combat card. The card text modifies *Finesse* itself; both consumers (gamble capacity and any other card reading `ModifiedFinesse`) must see the updated stat.

WHY reset at `EventDuelEnd` via `applyFinesseDelta(0, ...)` and NOT a recount:

- `StatesTrait::stDuelEnd` queues `EventDuelEnd` BEFORE queueing the `CardDiscardedFromHand` events that empty the dueling line. So at `EventDuelEnd` handling time, the dueling line still contains the round's Sorcery cards — a naive recount would re-apply the bonus instead of clearing it. Directly applying `delta = 0 - currentBonus` (the inverse-event approach) is correct.

WHY gate on the owner being a duel participant:

- `LOCATION_DUELING_LINE` is keyed per player_id in the deck table, not per character. If Elena's player has a *different* character dueling (e.g. Aja), Aja's combat cards land in the *same per-player dueling line* — a naive recount would credit Elena with cards she didn't play. Card text says "her dueling line", so gate on `$this->Id == challengerId || $this->Id == defenderId`.

Edge cases (Elena journal `2026-05-16-01-elena-agnelli-03004-implementation.md` flags these explicitly — re-read it before you implement a similar effect):

- **Card pulled from the dueling line mid-round.** The recount catches it at end-of-round; if anything pulls it earlier (rare), the bonus stays inflated for the rest of the current round. Acceptable — no event lets us hook arbitrary departures from the line.
- **Owner swapped into / out of an in-progress duel.** Not handled by the basic pattern. The next `EventDuelEndOfRound` recomputes from the player's line, which may already contain cards played by a prior duelist. Flag for QA if the text is sensitive to this; usually unimportant.
- **Owner destroyed mid-duel.** `EventDuelEnd` still fires and resets the bonus. `ModifiedFinesse` on a discarded card doesn't affect anything else, so no special handling needed.

### Location-counting passives — `EventCardMoved` fires BEFORE the DB updates

For "while you control another X at <Owner>'s location, she has +N [Stat]" (Angeline Dèmone `_03026`) or any other passive that **counts who is at a location** in response to `EventCardMoved` — the DB location field hasn't been updated yet when card->handleEvent runs. `EventCardMoved` sets `runEventHubAfterCards = true`, so the EventHub's location update runs AFTER every card's `handleEvent`. A naive `getCharactersAtLocation($this->Location)` returns the *pre-move* state: the moving card is still at `fromLocation`, not at `toLocation`.

01037 Edeline works around this by passing an explicit `$adjustment` int (`+1` for IN, `-1` for OUT) added to the count — fine for "+1 per character at this location" because every character contributes equally. **Binary "any qualifying member" bonuses can't use the adjustment shape** — you need to know the moving card's identity/trait/controller to decide whether to count it.

Pattern (mirror `_03026` Angeline):

```php
private function updateInfluence(Theah $theah, string $location, ?EventCardMoved $moveEvent = null): void
{
    $characters = $location == Game::LOCATION_PLAYER_HOME
        ? $theah->getCharactersAtHomeByPlayerId($this->ControllerId)
        : $theah->getCharactersAtLocation($location);

    $bonus = 0;
    foreach ($characters as $character)
    {
        // WHY: EventCardMoved fires before DB updates — a card moving OUT of
        // $location is still listed there. Exclude it from the count.
        if ($moveEvent !== null
            && $character->Id == $moveEvent->cardId
            && $moveEvent->fromLocation == $location
            && $moveEvent->toLocation != $location)
        {
            continue;
        }
        if ($character->Id != $this->Id
            && $character->ControllerId == $this->ControllerId
            && $character->hasTrait("Sorcerer"))
        {
            $bonus = 1;
            break;
        }
    }

    // WHY: Same stale-DB reason — a card moving IN isn't listed at $location yet.
    if ($bonus == 0
        && $moveEvent !== null
        && $moveEvent->cardId != $this->Id
        && $moveEvent->toLocation == $location
        && $moveEvent->fromLocation != $location)
    {
        $movingCard = $theah->getCardById($moveEvent->cardId);
        if ($movingCard !== null
            && $movingCard->ControllerId == $this->ControllerId
            && $movingCard->hasTrait("Sorcerer"))
        {
            $bonus = 1;
        }
    }

    $newInfluence = $this->Influence + $bonus;
    if ($newInfluence == $this->ModifiedInfluence) return;   // no-op gate

    $theah->queueEvent(EventFactory::createCharacterInfluenceModifiedEvent(
        $this->ControllerId, $this->Id,
        $this->ModifiedInfluence, $newInfluence,
        $this->getInjectCode()
    ));
}
```

In `handleEvent`, pass the event so the helper can compensate:

```php
if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
    $this->updateInfluence($event->theah, $event->toLocation, $event);

if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->toLocation == $this->Location)
    $this->updateInfluence($event->theah, $this->Location, $event);

if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->fromLocation == $this->Location)
    $this->updateInfluence($event->theah, $this->Location, $event);
```

WHY a no-op gate (`$newInfluence == $this->ModifiedInfluence` early-return): even when the bonus value doesn't change, naive code queues a same-value `*ModifiedEvent` for every triggering event (a non-Sorcerer moving in, an opponent's character entering, etc.). The framework still processes those no-op events. The gate trims log noise and event-loop work.

**Apply the same gate to `_01037`-style adjustment-int patterns** — Edeline's `updateInfluence` also benefits from skipping a same-value event. Add `if ($newInfluence == $this->ModifiedInfluence) return;` before queueing.

WHY not just hook the post-tense `EventCardMoved` differently — there's no later "after DB updates" event for moves. `runEventHubAfterCards = true` puts the EventHub's write AFTER card handleEvent, period. The choice is: read stale DB and compensate, or hook `EventCharacterMustered`/`EventApproachCharacterPlayed` (which don't have the timing problem) and forgo the move trigger entirely. Card text that says "while X is at this location" needs the move trigger.

Reference: `_03026` Angeline (binary bonus), `_01037` Edeline (per-character count via `$adjustment` int).

### Opposed by N+ wounded characters — location count + wound state

For text like "While Benci is opposed by two or more wounded characters, he gains +1[Combat]" (`_04001` Benci). Combine:

1. **Ise flag ±1** for the Combat bonus (attachments also mutate `ModifiedCombat` — do **not** set absolute `Combat + bonus`).
2. **Angeline/Edeline location recount** on `EventCardMoved` / muster / approach / destroy / recruit, counting **opposing** characters (`isNotControlledByPlayer`) with wounds.
3. **Wound/heal recount** on `EventCharacterWounded` / `EventCharacterHealed` when the event character is at Owner's location (or is Owner — usually irrelevant for opposing counts).

**Home short-circuit (load-bearing):** `Game::LOCATION_PLAYER_HOME` is one location string for every player. `getOpposingCharactersAtLocation(HOME, …)` returns enemies sitting at *their* Homes. You cannot be "opposed" at Home — if `$location == LOCATION_PLAYER_HOME`, return count `0`.

**Wound-event order:** cards handle `EventCharacterWounded` in foreach order; the wounded character may not have run yet when Owner recounts. If `$event->characterId == $character->Id && ! $event->characterHandled`, add `$event->wounds` (or subtract for heal) when testing `Wounds > 0`.

**Move stale-DB:** same Angeline exclude-out / include-in when the moving card is an opposing wounded character.

Reference: `_04001` Benci; Ise `_03016` (flag); Angeline `_03026` (location timing).

### Location Technique grant aura — "Your other characters at this location gain: Technique: …"

For text like Jean Urbain `_01067` ("Your other Musketeers … gain Technique"), Stranahan `_02022` ("Your Musketeers … gain Lethal"), or Yepikhodov `_03051` ("Your other characters … gain Technique: Engage … Copy …"). This is a **card-class `handleEvent` passive**, not a Reaction and not a Technique mounted on the aura source himself (unless the printed text also gives him the Technique).

**Lifecycle (canonical Jean shape):**

| Event | What to do |
|---|---|
| `EventCharacterRecruited` | If the recruit is another controlled character at the aura source's non-Home location → grant |
| `EventCharacterDestroyed` (`characterId == aura source`) | Strip the Technique from every other controlled character at the (still-set) location |
| `EventCardMoved` (`cardId == aura source`) | Strip at `fromLocation`; grant at `toLocation` (both skip Home) |
| `EventCardMoved` (other card `toLocation == aura source.Location`) | Grant to the arriving controlled ally |
| `EventCardMoved` (other card `fromLocation == aura source.Location`) | Strip from the departing ally |

**Grant / remove recipe:**

```php
$technique = new Technique_NNNNN();
$technique->setId("Technique_NNNNN");   // sets ClassId too — required for later lookup
$technique->setOwnerId($character->Id); // Id becomes "{charId}_Technique_NNNNN"
$character->addTechnique($technique, $game);

// remove:
$technique = $character->getTechniqueByClassId("Technique_NNNNN");
if ($technique) $character->removeTechnique($technique, $game);
```

WHY `setId` before `setOwnerId`: `setId` overwrites both `Id` and `ClassId`; `setOwnerId` then prefixes `Id` with the owner. Removing by ClassId only works if you set ClassId to the stable `"Technique_NNNNN"` token.

**Filters:**

- Trait gate only when the text names one (`hasTrait("Musketeer")`). "Your other characters" = every other controlled `IHasTechniques` character — **exclude the aura source**.
- Skip `LOCATION_PLAYER_HOME` for both grant and remove (Jean/Stranahan convention).
- Dedup: skip grant if `getTechniqueByClassId` already finds one (Yepikhodov helper) — Jean historically re-adds; prefer the dedup.

**Known hole (accept when mirroring Jean):** `EventCharacterRecruited` does **not** also emit `EventCardMoved`. If the *aura source* is recruited into a location that already has allies, those allies are not granted until a later move. Same hole exists on `_01067` / `_02022`. Do not invent an extra Recruited-self branch unless Eddie asks — stay consistent with Jean.

Reference: `_03051` Yepikhodov (no trait filter; granted Technique is interactive), `_01067` Jean (`Technique_PlusOneRiposte` with ClassId `Technique_01067`), `_02022` Stranahan (`Technique_GainLethal`).

### Location trait-stat aura — "Your <Trait>s at Home and <Owner>'s location gain +N[Stat] / +M Resolve"

For text like Danilo `_04002` ("Your **Thugs** at **Home** and Danilo's location gain +1[Finesse] and +1 Resolve"). This is **not** a Technique grant (Jean) — it buffs **stats** on other controlled characters that match a trait and location predicate.

**Shape:**

1. Track `$BuffedIds` (list of character ids currently holding the aura) on the aura source — survive via `IsUpdated`.
2. On relevant events, recompute the eligible set and apply/remove only on set transitions (same idea as Weapon count-transition / Ise flag — avoid stacking).
3. Finesse/Combat/Influence → `createCharacter<Stat>ModifiedEvent` (Finesse factory typo `Modifed`).
4. Resolve → **direct** `$thug->ModifiedResolve ±1` (no factory) **and** emit `characterResolveModified` client notif — see "Resolve client sync" under Phase-conditional Resolve below. Without the notif the chip stays flat while Finesse updates (Danilo playtest).

**Eligibility:**

- Controlled by aura source's controller, `hasTrait(<printed trait>)`, not the aura source (unless the source also has the trait and the text includes him — Danilo is not a Thug).
- Location is **Home OR aura source's current location**. When printed "at Home and X's location", Home is **always** in scope even when the aura source is in the city. When the aura source is at Home the two locations collapse (set membership — no double-apply).
- Home lookup: `getCharactersAtHomeByPlayerId($controllerId)` / effective-location `== LOCATION_PLAYER_HOME`. **Never** `getCharactersAtLocation(HOME)` — shared string across players (Benci / opposing-count lesson).

**Lifecycle hooks (same family as Jean / Angeline):**

| Event | What to do |
|---|---|
| `EventCardMoved` | Recompute with move-event compensation (effective `toLocation` for mover; include-in / exclude-out) |
| `EventCharacterMustered` / `EventApproachCharacterPlayed` / `EventCharacterRecruited` | Recompute; force-include newly entered Thug when ControllerId may still be unset |
| `EventCharacterDestroyed` (aura source) | `clearAll` — strip every buffed id |
| `EventCharacterDestroyed` (other) | Exclude the destroyed id (`runEventHubAfterCards` — still looks in-play during card `handleEvent`) |

On Resolve **remove**, run the Joern destruction check if `Wounds >= ModifiedResolve`.

Contrast Technique aura (Jean): Home usually **excluded**; grant/remove Techniques by ClassId rather than ±1 stats.

Reference: `_04002` Danilo.

### Forced muster/approach triggers — hook BOTH `EventCharacterMustered` AND `EventApproachCharacterPlayed`

For any "after X musters" / "when X musters" / "Forced after X musters" trigger, the conditional MUST hook both events:

```php
if (($event instanceof EventCharacterMustered
        || $event instanceof EventApproachCharacterPlayed)
    && $event->characterId == $this->Id)
{
    // ... effect ...
}
```

WHY both: the printed text says "musters" colloquially to cover every way a character enters play, but the engine emits a distinct `EventApproachCharacterPlayed` when an Approach card puts a character into play vs. the standard muster path (`createCharacterMusteredEvent` in the recruit / brute / muster-from-action flows). Hooking only `EventCharacterMustered` silently skips the Forced trigger when the character enters via Approach. The user has flagged this as a definitional miss — it's not a polish item.

Reference: `modules/php/cards/_7s5s/_01009.php` (Cirilo) line ~57 — the canonical OR pattern for "I added Brute to my Mercenaries when I muster or come in via Approach." `_03015` Joern uses the same pair for his self-wound Forced trigger.

If the trigger is "after **another** character musters" (not self), still hook both events; only the `characterId` filter changes.

**Approach also triggers Home-scoped passives — even when the Owner isn't the approached character.** For "while you control another X at <Owner>'s location" passives where Owner is at Home, an opponent's *teammate* X being approach-played to the player's Home should also recompute. Hook a second `EventApproachCharacterPlayed` branch:

```php
if ($event instanceof EventApproachCharacterPlayed
    && $event->characterId != $this->Id
    && $this->Location == Game::LOCATION_PLAYER_HOME
    && $event->playerId == $this->ControllerId)
{
    $this->updateInfluence($event->theah, Game::LOCATION_PLAYER_HOME);
}
```

Gate on `$event->playerId == $this->ControllerId` so the recompute only fires for Owner's controller (Home is per-player; opponent's approach doesn't change who's at *your* Home).

**For the Owner's own approach, use `$event->playerId` as the controller — not `$this->ControllerId`.** When Angeline herself is the approach character, her in-memory `ControllerId` may not be propagated yet at the moment `EventApproachCharacterPlayed` fires (EventHub handler doesn't set it; recruit/muster events do). Pass `$event->playerId` as an override so `getCharactersAtHomeByPlayerId` looks up the right home:

```php
private function updateInfluence(Theah $theah, string $location, ?EventCardMoved $moveEvent = null, ?int $controllerIdOverride = null): void
{
    $controllerId = $controllerIdOverride ?? $this->ControllerId;
    // ... use $controllerId in lookups instead of $this->ControllerId ...
}

// caller for own-approach:
if ($event instanceof EventApproachCharacterPlayed && $event->characterId == $this->Id)
    $this->updateInfluence($event->theah, Game::LOCATION_PLAYER_HOME, null, $event->playerId);
```

Reference: `_03026` Angeline.

### Phase-conditional Resolve modifier — direct `ModifiedResolve` mutation, no event factory

For text like "During Dusk, <Owner> has -N Resolve" or "At the beginning of Dawn, <Owner> has +N Resolve" — the engine does NOT have an `EventCharacterResolveModifiedEvent` factory. Resolve is not event-driven the way Combat/Finesse/Influence are. The pattern:

```php
private bool $DuskResolvePenaltyApplied = false;   // running flag — survives via IsUpdated

public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventDuskPhaseBegin
        && ! $this->DuskResolvePenaltyApplied
        && $this->isControlled())
    {
        $this->ModifiedResolve -= 3;
        $this->DuskResolvePenaltyApplied = true;
        $this->IsUpdated = true;

        $event->theah->game->notify->all("message",
            clienttranslate('${character_inject_code}: During Dusk, -3 Resolve (now ${resolve}).'),
            [
                "character_inject_code" => $this->getInjectCode(),
                "resolve"               => $this->ModifiedResolve,
            ]
        );

        // WHY: Character::handleEvent (~line 256) only triggers destruction inside
        // an EventCharacterWounded handler. If the Resolve drop crosses the
        // wounds-equal-resolve threshold with no concurrent wound event, the
        // engine won't notice. Mirror EventHub.php:251 (the unequip path):
        if ($this->Wounds >= $this->ModifiedResolve && ! $this->IsDying)
        {
            $this->IsDying = true;
            $this->unEquipAllAttachments($event->theah);
            $destroyEvent = EventFactory::createCharacterDestroyedEvent(
                $this->ControllerId, $this->Id, $this->getInjectCode()
            );
            $event->theah->queueEvent($destroyEvent);
        }
    }

    if ($event instanceof EventDuskEndOfDay && $this->DuskResolvePenaltyApplied)
    {
        $this->ModifiedResolve += 3;
        $this->DuskResolvePenaltyApplied = false;
        $this->IsUpdated = true;
    }
}
```

WHY a private bool flag (and NOT a recompute or a queued event):

- **No `createCharacterResolveModifiedEvent` factory exists.** Combat/Finesse/Influence each have `createCharacter<Stat>ModifiedEvent` factories; Resolve does not. The codebase mutates `ModifiedResolve` directly — see `Character::addAttachment` line 166 (`$this->ModifiedResolve += $attachment->ResolveModifier`).
- **Attachments mutate `ModifiedResolve` independently.** A naive `-= 3` / `+= 3` is fine if Dusk events are perfectly paired, but skipped/duplicated phase begins are not. A flag makes the apply idempotent: only one `-= 3` per Dusk, regardless of how the events fire.
- **Pattern A's "Dynamic stat bonuses" recompute approach (Elena) doesn't fit here.** Resolve has no naturally recurring "this card's snapshot of the world changes" event the way a dueling-line count does. The trigger is a phase boundary, not a stream of state changes.

WHY the manual destruction check:

- `Character::handleEvent`'s destruction check (line ~256) runs ONLY inside `EventCharacterWounded`. Lowering `ModifiedResolve` past `Wounds` outside a wound event silently leaves the character alive at `Wounds >= Resolve`.
- The card text's parenthetical reminder "(Characters are destroyed when their wounds equal their Resolve)" makes the rule explicit — the threshold check applies whenever it's crossed, not only on a wound event.
- Mirror the EventHub unequip pattern (`EventHub.php` ~251): `if ($character->Wounds >= $character->ModifiedResolve && ! $character->IsDying)` → flip `IsDying`, unequip attachments, queue `createCharacterDestroyedEvent`.

WHY restore unconditional on the flag (not `isControlled()` or `cardInCity`):

- If the character is destroyed mid-Dusk, the flag is still true and the EndOfDay restore still runs. The destroyed-character object is in the Locker; restoring its in-memory `ModifiedResolve` is harmless. Re-instantiation on re-recruit goes through the constructor + `resetCard()` which sets `ModifiedResolve = Resolve` anyway, but the unconditional restore is a defense against any hypothetical "return from Locker" path that bypasses construction.

WHY `EventDuskEndOfDay` for the restore (not `EventDuskPhaseEnd`):

- Dusk lifecycle is: `stDuskPhaseBegin` → `EventDuskPhaseBegin` → (reactions, cleanup, hand-discard, purgatory-discard) → `stDuskPhaseEnd` → `EventDuskPhaseEnd` → `stDuskEndOfDay` → `EventDuskEndOfDay`.
- "During Dusk" should cover every step in between. `EventDuskEndOfDay` is the last event of the day — restoring there guarantees nothing inside Dusk sees the restored value early.
- `EventDuskPhaseEnd` would work too (Brute discard at end-of-day doesn't read Resolve), but EndOfDay is the strict latest safe point.

Reference: `_03015` Joern Kietelsson. Note that the same pattern applies in reverse for "+N Resolve" phase-conditional buffs.

### Resolve client sync — `characterResolveModified` notif (required for live chip)

Finesse / Combat / Influence factories run through EventHub handlers that call `notify->all("characterFinesseModifed"|…)` and `Notifications.js` updates the chip. **Resolve has no such pipeline.** Mutating `ModifiedResolve` + `IsUpdated` persists to DB but the Resolve number on the card stays flat until something else redraws it (Danilo `_04002` playtest: Finesse moved, Resolve did not).

Whenever a passive/aura changes Resolve and the player must see it immediately:

```php
$oldResolve = $character->ModifiedResolve;
$character->ModifiedResolve += 1; // or -= 1
$character->IsUpdated = true;

$theah->game->notify->all(
    "characterResolveModified",
    clienttranslate('The resolve of ${character_name} went from ${oldResolve} to ${newResolve} due to: ${reason}.'),
    [
        'i18n' => ['character_name'],
        "character_name" => $character->Name,
        "characterId" => $character->Id,
        "oldResolve" => $oldResolve,
        "newResolve" => $character->ModifiedResolve,
        "reason" => $this->getInjectCode(),
    ]
);
```

Client: subscribe `['characterResolveModified', 1]` in `Notifications.js` `setupNotifications` and implement `notif_characterResolveModified` mirroring `notif_characterFinesseModifed` (set `modifiedResolve`, update `${divId}_resolve_value`, keep `_7sfs-modified-stat-value` when `modifiedResolve != resolve || wounds > 0`).

WHY not invent a factory yet: Joern-style direct mutation remains the server truth; the notif is the missing half. A future `createCharacterResolveModifiedEvent` could centralize both — until then, emit the notif at every Resolve mutation that should be visible. Joern's phase penalty historically used `message`-only (chip may lag); prefer `characterResolveModified` for new work.

Reference: `_04002` Danilo (aura); `_03015` Joern (phase Resolve — add the notif if live chip matters).

### Wound-prevention passive — `eventCheck` on `EventCharacterBeingWounded`

For text like "<Owner> ignores wounds from <X>" or "<Y>'s abilities cannot wound <Owner>" (`_03014` Kaspar, `_01069` Maxime, `_01153` Breastplate). Override `eventCheck` on the card class — NOT `handleEvent` — and zero `$event->wounds` on `EventCharacterBeingWounded`.

```php
public function eventCheck(Event $event)
{
    parent::eventCheck($event);   // propagates to your Techniques/Reactions/etc.

    if (! ($event instanceof EventCharacterBeingWounded)) return;
    if ($event->characterId != $this->Id || $event->wounds <= 0) return;

    // "(Threat is still converted to wounds.)" Threat conversion (StatesTrait
    // ~line 1500) emits with empty $abilityId; only block ability-emitted wounds.
    if ($event->abilityId == '') return;

    $source = $event->theah->getCardById($event->sourceId);
    if ($source == null || $source->ControllerId == 0
        || $source->ControllerId == $this->ControllerId) return;

    $oldWounds = $event->wounds;
    $event->wounds = 0;

    $event->theah->game->notify->all("message", clienttranslate(
        '${character_inject_code}: Opponents\' abilities cannot wound. '
        . '${oldWounds} wound(s) ignored from ${source_inject_code}.'
    ), [
        "character_inject_code" => $this->getInjectCode(),
        "source_inject_code"    => $source->getInjectCode(),
        "oldWounds"             => $oldWounds,
    ]);
}
```

WHY `eventCheck` on the *Being*-tense event (not `handleEvent` on `EventCharacterWounded`):

- `EventHub` only emits the past-tense `EventCharacterWounded` when `$event->wounds > 0` (see `EventHub.php` ~1988). Setting `wounds = 0` in `eventCheck` on `EventCharacterBeingWounded` means the past-tense event is *never created* — no other reaction/passive that listens to "when X is wounded" thinks Kaspar took a wound. Cleaner than Maxime's `handleEvent` pattern of skipping `parent::handleEvent` (which still propagates the event to other `Character::handleEvent` listeners).
- `Card::eventCheck` (Card.php ~371) is the framework's per-card check hook and runs BEFORE `handleEvent`. Override it on the *card class*, not on a Technique/Reaction — the passive is the card itself, not an ability.
- Always call `parent::eventCheck($event)` first — it dispatches to any Techniques/Reactions/Maneuvers/Actions on the card.

WHY `abilityId == ''` is the threat-conversion signal:

- The round-end threat-to-wounds conversion (`StatesTrait::stDuelEndOfRound` ~line 1500) emits `createCharacterBeingWoundedEvent($actor->Id, $adversary->Id, $wounds, $reason)` — note the missing 5th positional argument, so `abilityId` defaults to `''`.
- Every ability that emits a wound passes the ability id as the 5th argument (`Action_02010`, `Technique_03004`, all the Sorcerer Actions/Reactions). So `abilityId != ''` is a clean "this wound is from an ability" filter without needing to grep call sites.

WHY `source.ControllerId != $this->ControllerId` is "opponent's ability":

- The source card's `ControllerId` is the controlling player at the moment the wound is queued. For an opponent's Action/Reaction/Technique/Maneuver/Sorcery card causing the wound, that's a different player from Kaspar's controller.
- `source.ControllerId == 0` means uncontrolled (rare — usually a card in transit between zones). Treat that as "not an opponent" and let it through; nothing in the codebase emits an ability-typed wound from an uncontrolled source as of this writing, but the guard is cheap.
- For wound *movement* abilities (the heal+wound recipe, `Action_02010`): the wound half is queued from the action's owner with the action's id as `abilityId`. Same filter blocks it. Kaspar's text "or move wounds to Kaspar" comes free with the wound-block — don't add a special "move-wounds" handler.

Scope-matters: Maxime's text is about "abilities he performs" (own scope via `CHOSEN_PERFORMER` or Sorcery-trait source), so Maxime checks the source's identity / trait. Kaspar's text is about "opponents' abilities" (controller scope), so Kaspar checks the source's controller. Read the text literally — don't reuse the wrong helper.

For partial reduction (Breastplate `_01153` reduces by 1, not to 0), the same `eventCheck` pattern applies — just `$event->wounds--` with a floor at 0. Breastplate additionally tracks `$hasBlockedWound` to enforce "first time this duel."

### Opponent collects one fewer Renown from this location

For text like "When an opponent collects Renown from this location, they collect one fewer. *(Remaining Renown stays.)*" (`_03049` Ekaterina). This is a **passive** on the card class (no Reaction file) — Collect is automatic, not a player choice for the denier.

**Hard constraints:**

1. `EventPlayerGainsReknown` has **no location field** — you cannot reduce every opponent Gains.
2. Renown **Moves** also emit `EventRenownRemovedFromLocation` + `EventRenownAddedToLocation`. Blindly reducing every opponent Removed from her location would steal renown from Moves.
3. There are **two Collect pipelines** with different event order:

| Pipeline | Order | Location signal |
|---|---|---|
| **Plunder** (`StatesTrait::stPlunderGainRenown`) | `EventPlayerTakeReknownForControlledLocation` → Gains → Removed | Take has `location` + `playerId`; plunder's Removed often has `playerId = 0` |
| **Ability Collect** (Sanjay / pressure Collect) | Removed → Gains | Removed has `playerId` of the collector; Gains has no location |

**Plunder path** — mutate amounts at `eventCheck` (called at `queueEvent` time):

```php
// EventPlayerTakeReknownForControlledLocation:
//   location == $this->Location && playerId != ControllerId && reknown > 0
//   → reknown--; arm pendingFewerGainPlayerId + pendingFewerRemoveLocation; notify
// EventPlayerGainsReknown: if playerId matches pending → amount--; clear gain pending
// EventRenownRemovedFromLocation: if location matches pending → amount--; clear remove pending
```

**Ability Collect path** — arm on Removed, confirm on immediately next Gains, put Renown back after Removed has already been queued at full amount:

```php
// eventCheck EventRenownRemovedFromLocation:
//   location == $this->Location && playerId != 0 && playerId != ControllerId && amount > 0
//   → arm pendingCollectArmPlayerId / Location (do NOT reduce Removed yet — might be a Move)
//
// eventCheck start of every subsequent event:
//   if arm set && event is NOT the confirming Gains for that playerId → clear arm
//   (Move's next event is RenownAdded → arm dies; Collect's next is Gains → confirm)
//
// eventCheck EventPlayerGainsReknown (confirming):
//   amount--; set pendingPutBackLocation; clear arm; notify
//
// handleEvent EventPlayerGainsReknown (same event, later):
//   queue createRenownAddedToLocationEvent(…, 1, …) for pendingPutBackLocation
```

WHY put-back in `handleEvent` (not `eventCheck`): ability Collect queues Removed (full amount) *before* Gains. By Gains `eventCheck` time the Removed is already serialized in the DB queue — you can't rewrite it. Putting 1 Renown back after Removed applies leaves "remaining stays" correctly (start 5, Collect 5 → remove 5, gain 4, add 1 back → end 1).

WHY stale-arm clear: a lone discard-Remove (no following Gains) or a Move (Removed then Added) must not leave an arm that later falsely denies an unrelated Gains for that player.

Liveness: `ControllerId > 0`, `! characterIsInDiscardOrLocker`, `cardInCity($this)`.

Reference: `_03049` Ekaterina. Contrast effect-side Collect (`Reaction_03037` Sanjay) which *emits* the remove+gain pair — Ekaterina *intercepts* someone else's Collect.

### Stat bonus while a self-condition holds — flag-based recompute on wound/heal

For text like "<Owner> has +1 [Combat] while wounded" (`_03016` Ise). The condition is *on the Owner herself* (wounded / engaged / has-attachment / etc.), and the bonus should flip on/off as the condition changes.

Pattern (mirror `_03016` Ise):

```php
public bool $WoundedCombatBonusApplied = false;   // running flag — survives via IsUpdated

public function handleEvent(Event $event)
{
    parent::handleEvent($event);   // parent updates $this->Wounds BEFORE this runs

    if (($event instanceof EventCharacterWounded || $event instanceof EventCharacterHealed)
        && $event->characterId == $this->Id)
    {
        $this->recomputeWoundedCombatBonus($event->theah);
    }
}

private function recomputeWoundedCombatBonus(Theah $theah): void
{
    if ($this->ControllerId == 0) return;
    if ($theah->game->characterIsInDiscardOrLocker($this)) return;
    if ($this->IsDying) return;

    $shouldHaveBonus = $this->Wounds > 0;

    if ($shouldHaveBonus && ! $this->WoundedCombatBonusApplied)
    {
        $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
            $this->ControllerId, $this->Id,
            $this->ModifiedCombat, $this->ModifiedCombat + 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($combatEvent);
        $this->WoundedCombatBonusApplied = true;
        $this->IsUpdated = true;
    }
    else if (! $shouldHaveBonus && $this->WoundedCombatBonusApplied)
    {
        $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
            $this->ControllerId, $this->Id,
            $this->ModifiedCombat, $this->ModifiedCombat - 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($combatEvent);
        $this->WoundedCombatBonusApplied = false;
        $this->IsUpdated = true;
    }
}
```

WHY a flag + delta-event instead of recompute-from-base:

- Attachments and other cards also mutate `ModifiedCombat`. A naive "set `ModifiedCombat = Combat + (wounded ? 1 : 0)`" would clobber a Weapon attachment's +1 Combat that flowed in via its own `CombatModified` event. The delta-on-transition pattern plays nicely with the rest of the stat-modifier ecosystem: each modifier only adjusts what *it* contributed.
- Mirrors `_01089` Soline el Gato's `lowerFinesse`/`raiseFinesse` shape, which uses the same per-source-bookkeeping discipline.

WHY `parent::handleEvent` BEFORE checking `$this->Wounds`:

- `Character::handleEvent` (Character.php ~242) does `$this->Wounds += $event->wounds` (or `-=` for heal) inside its own `EventCharacterWounded`/`EventCharacterHealed` branches. Our recompute MUST run *after* that update — `parent::handleEvent($event)` first is non-negotiable.

WHY skip on `IsDying` / `characterIsInDiscardOrLocker`:

- If the wound event drove her to Wounds >= ModifiedResolve, `Character::handleEvent` sets `IsDying = true` and queues `EventCharacterDestroyed`. Queueing a combat bonus at that point is wasted work — her `ModifiedCombat` is irrelevant. When she re-instantiates (next game/recruit), `resetCard` re-derives `ModifiedCombat = Combat`, and the bonus flag is default-false on the fresh instance.

Adapting for other stats / conditions:
- `+N [Finesse]` → `createCharacterFinesseModifedEvent` (note framework typo: `Modifed`).
- `+N [Influence]` → `createCharacterInfluenceModifiedEvent`.
- `+N [Resolve]` → no factory exists. Use Joern's `$this->ModifiedResolve` direct-mutation pattern instead (Pattern A's "Phase-conditional Resolve modifier").
- `+N [Panache]` (Leader only) → `createCharacterPanacheModifiedEvent`.

For non-wound conditions (e.g., "while engaged"), swap the trigger event (`EventCardEngaged` / `EventCardEngarded`) and the `$shouldHaveBonus` predicate. Same flag discipline.

### Set one stat equal to another while a scoped condition holds — replacement flag + snapshot restore

For text like "While Térence is participating in a duel at [The Grand Bazaar], set his [Combat] as equal to his [Influence]" (`_03028` Térence). This is **not** the ±1 delta pattern from "has +N while wounded" — the printed text replaces the target stat with the current value of the source stat, and the link is **dynamic** (if Influence changes during the duel, Combat must follow).

Pattern (mirror `_03028` Térence):

```php
public bool $DuelCombatEqualsInfluenceApplied = false;
public ?int $CombatBeforeDuelOverride = null;

// Apply on condition start:
private function applyCombatEqualsInfluence(Theah $theah): void
{
    if ($this->ControllerId == 0) return;
    if ($theah->game->characterIsInDiscardOrLocker($this)) return;
    if ($this->Location != Game::LOCATION_CITY_BAZAAR) return;   // scope gate from card text

    if (! $this->DuelCombatEqualsInfluenceApplied)
    {
        $this->CombatBeforeDuelOverride = $this->ModifiedCombat;   // snapshot ONCE
        $this->DuelCombatEqualsInfluenceApplied = true;
        $this->IsUpdated = true;
    }
    $this->syncCombatToInfluence($theah);
}

private function syncCombatToInfluence(Theah $theah): void
{
    if (! $this->DuelCombatEqualsInfluenceApplied) return;
    $targetCombat = $this->ModifiedInfluence;
    if ($this->ModifiedCombat == $targetCombat) return;
    $theah->queueEvent(EventFactory::createCharacterCombatModifiedEvent(
        $this->ControllerId, $this->Id,
        $this->ModifiedCombat, $targetCombat,
        $this->getInjectCode()
    ));
}

// Clear on condition end:
private function clearCombatEqualsInfluence(Theah $theah): void
{
    if (! $this->DuelCombatEqualsInfluenceApplied) return;
    $restoreCombat = $this->CombatBeforeDuelOverride ?? $this->Combat;
    if ($this->ModifiedCombat != $restoreCombat)
    {
        $theah->queueEvent(EventFactory::createCharacterCombatModifiedEvent(
            $this->ControllerId, $this->Id,
            $this->ModifiedCombat, $restoreCombat,
            $this->getInjectCode()
        ));
    }
    $this->DuelCombatEqualsInfluenceApplied = false;
    $this->CombatBeforeDuelOverride = null;
    $this->IsUpdated = true;
}
```

Lifecycle hooks:
- **`EventDuelStarted`** — apply when `$this->Id` is `challengerId` or `defenderId` AND `$this->Location` matches the named city location (`Game::LOCATION_CITY_BAZAAR` for `[The Grand Bazaar]`).
- **`EventDuelEnd`** — clear unconditionally when the flag is set.
- **`EventDefenderSwapped` / `EventChallengerSwapped`** — apply when `$this->Id` becomes the new participant (with location gate); clear when `$this->Id` was the old participant. Same swap discipline as `_01089` Soline.
- **`EventCharacterInfluenceModified`** with `$event->CharacterId == $this->Id` — re-sync while flag is set (Influence is the source stat).
- **`EventCharacterCombatModified`** with `$event->CharacterId == $this->Id` — if override active and `$event->NewCombat != $this->ModifiedInfluence`, re-sync (external Combat buffs don't stick during the override).

WHY snapshot restore instead of recompute-from-base:

- Attachments may equip mid-duel and change the "natural" Combat independently of this override. The snapshot taken at apply-time is the correct undo target on `EventDuelEnd`. Recomputing `Combat + sum(attachment modifiers)` at clear-time risks drift if the override itself was the last thing that touched `ModifiedCombat`.

WHY NOT the Ise ±1 flag pattern:

- "Set equal to" is a **replacement**, not a fixed delta. If Influence is 2 and Combat is 0, the event sets Combat **to 2**, not **+2**. If Influence later becomes 3, Combat must become 3 — a running `$BonusApplied` ±1 counter can't express that.

WHY `EventCharacterCombatModified` re-sync uses `$event->NewCombat`:

- `EventCharacterCombatModified` has `runEventHubAfterCards = false` — EventHub applies `ModifiedCombat = NewCombat` **before** card `handleEvent` runs (`Theah::runEvents` order). By the time the card handler fires, `$this->ModifiedCombat` already reflects the external change; comparing `$event->NewCombat != $this->ModifiedInfluence` detects drift from the link.

Named city location constants (real ones in `Game.php`):
- `[The Grand Bazaar]` → `Game::LOCATION_CITY_BAZAAR`
- `[The City Docks]` → `Game::LOCATION_CITY_DOCKS`
- `[The City Forum]` / `[The Forums]` → `Game::LOCATION_CITY_FORUM`
- `[Ole's Inn]` → `Game::LOCATION_CITY_OLES_INN`
- `[The Governor's Garden]` → `Game::LOCATION_CITY_GOVERNORS_GARDEN`

Reference: `_03028` Térence, `_01089` Soline (duel boundary + swap discipline — but Soline modifies *adversaries*, Térence modifies *self*).

### "Opposing characters are considered <Trait>" — tag opposing characters, don't override hasTrait

For text like "While using your abilities, characters opposing <Owner> may be considered <Trait>" (Daniella Dietrich `_03013`): the trait must light up on *opposing* characters, not on the owner. The Uwe Zimmerman `_01043` `hasTrait` override pattern is the WRONG fit — that pattern lights up the *receiver* of `hasTrait`, so it only works when the card being considered is the card whose `hasTrait` was overridden. For the opposing-direction case, mirror the Wilhelm Dünst `Action_02013` pattern instead: **mutate the opposing characters' `ModifiedTraits` directly via `addTrait` / `removeTrait`**, keep a tracked set of the ids you tagged, and untag at the scope boundary.

Pattern (typically lives on a continuous Action; see the next subsection):

```php
private array $TaggedOpposingIds = [];  // ids we added the trait to

private function tagOpposingAs(string $trait, Theah $theah): void
{
    $owner = $this->getOwningCharacter($theah);
    if ($owner === null) return;
    $game = $theah->game;

    $opposing = array_filter(
        $theah->getCharactersAtLocation($owner->Location),
        fn($c) => $c->ControllerId !== $owner->ControllerId
            && ! in_array($c->Id, $this->TaggedOpposingIds, true)  // dedup — see WHY below
            && ! $c->hasTrait($trait)
    );
    foreach ($opposing as $c)
    {
        $c->addTrait($game, $trait);
        $this->TaggedOpposingIds[] = $c->Id;
    }
}

private function untagOpposing(string $trait, Theah $theah): void
{
    if (empty($this->TaggedOpposingIds)) return;
    $game = $theah->game;
    foreach ($this->TaggedOpposingIds as $cid)
    {
        $c = $theah->getCharacterById($cid);
        if ($c !== null) $c->removeTrait($game, $trait);
    }
    $this->TaggedOpposingIds = [];
}
```

WHY tracked-set + skip-already-tagged:

- `Card::addTrait` (in `modules/php/cards/Card.php`) appends to `$this->ModifiedTraits` **without** deduping. Two `addTrait("Sorcerer")` calls leave two `"Sorcerer"` entries in the array, and `removeTrait` removes only one (`array_search` returns the first match). Re-tagging on every ability-use event without a guard would pile up duplicates that never fully clear.
- `! $c->hasTrait($trait)` is the cheap "they already have it printed/granted" check; `! in_array($c->Id, ...)` is the cheap "we already granted it" check. Use both — a character could legitimately have the trait printed before our grant fires.

WHY "opposing" = controller-mismatch + location-match: this matches `Theah::getOpposingCharactersAtLocation` and the codebase-wide definition (see the memory note). Don't roll your own filter; just pull from the location and exclude same-controller.

Scope boundary for untagging: the scope is whatever the card text says. Daniella's "while using your abilities" reads as "for the duration of your turn" once you map ability-use to turn-scope — `EventPlayerTurnEnd` is the natural clear. Add `EventCardMoved` / `EventCharacterDestroyed` cleanups for the owner so an outstanding tag set doesn't get orphaned on a character that no longer opposes her.

### Continuous Action — passive ability that lives on an `Action` class but never appears in the UI

For passive abilities that the framework should treat as an ability but the player never directly activates (e.g., Daniella Dietrich `_03013`'s trait-tagging passive), mount the logic on a `CharacterAction` subclass attached via `IHasActions` / `ActionTrait`. Make `isAvailableToPlayer` return false so it never shows in the action menu — the Action is purely a `handleEvent` listener.

```php
class Action_NNNNN extends CharacterAction
{
    /** @var int[] running state for the passive (e.g. tagged character ids) */
    private array $TaggedOpposingIds = [];

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("(Continuous) <plain-English description of what it does>");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        // Passive — never offered from the action menu. Returning false hides
        // it but does not suppress handleEvent.
        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        // ... trait-tagging / passive work ...

        if ($event instanceof EventPlayerTurnEnd)
        {
            $this->untagOpposing("Sorcerer", $event->theah);

            // "Continuous" — clear Used at the same boundary so the parent
            // CardAction::handleEvent's EventDuskEndOfDay reset isn't the only
            // thing keeping the action alive across turns.
            $this->setUsed($event->theah, false);
        }
    }
}
```

Wiring on the card:

```php
class _NNNNN extends Leader implements IHasActions, IHasReactions, IHasTechniques
{
    use ActionTrait;
    use ReactionTrait;
    use TechniqueTrait;

    // ... constructor ...
    $this->Actions = [ new Action_NNNNN() ];
}
```

Where to place the passive's `handleEvent` — the Action or the card class? Either works mechanically, but **prefer the Action** when the passive is conceptually an ability that the card text *names* as an "Action / Forced / Maneuver / Passive / Technique / Reaction." That keeps the responsibility scoped to one file and lets the card class's `handleEvent` stay minimal (just `parent::handleEvent($event)` for the Leader-inherited renown/Panache logic). The card class's `handleEvent` is still where you put cross-ability bookkeeping that doesn't belong to any single ability.

WHY pre-commit doesn't complain about the missing `createActionResolvedEvent()`: the hook's regex matches `extends CardAction/RiskAction/RiskCityAction` literally — `CharacterAction` isn't on that list (see the Pre-Commit Hook section). A continuous Action that never goes through normal action resolution legitimately doesn't fire `createActionResolvedEvent`.

WHY `setUsed(false)` on a continuous Action: the parent `CardAction::handleEvent` already resets `Used` on `EventDuskEndOfDay`, which is fine for once-per-day actions. For a "continuous" Action that must survive multiple ability uses within the same turn, explicitly flip `Used` back to `false` at the same scope boundary you untag at (typically `EventPlayerTurnEnd`). The Reaction analogue is "do not call `setUsed(true)` at all" — see `Reaction_01196` "Continuous". Both forms work; the Action variant needs the explicit reset because `parent::handleEvent`'s once-per-day reset isn't frequent enough.

Reference: `Action_03013` (Daniella Dietrich) — Continuous Action that tags opposing characters with "Sorcerer" on ability-start events and untags at `EventPlayerTurnEnd`. `Action_01090` (Yuri Pyetrovich) — Continuous Action that pre-activates a paired Reaction; opposite shape (user-triggered, but immediately flips `Used` back to false).

### Phase / lifecycle events worth knowing

| Event | When it fires | Typical use |
|---|---|---|
| `EventNewDay` | Start of each Day | Reset per-day flags |
| `EventPhaseDawnBeginning` | Dawn begins | "At the beginning of Dawn …" |
| `EventPhaseDawnEnding` | Dawn ends (fired by `StatesTrait::stDawnEnding`) | "At the end of Dawn …" |
| `EventDuskPhaseBegin` | Dusk phase begins (fired by `StatesTrait::stDuskPhaseBegin`, BEFORE characters route home) | "At the beginning of Dusk …" / start of a phase-conditional Resolve penalty (Joern `_03015`). |
| `EventDuskPhaseEnd` | After cleanup/discard, before `EventDuskEndOfDay` | Less commonly used; `EventDuskEndOfDay` is usually the right "Dusk is over" hook |
| `EventDuskEndOfDay` | End of Day (Brute discards happen here) | Reset per-day Used flags (base classes handle this for Actions/Reactions automatically); restore phase-conditional Resolve penalties |
| `EventCharacterMustered` | A character was just mustered (recruit / brute / `Action_01024` / etc.) | "Forced after X musters …" — **always pair with `EventApproachCharacterPlayed`** (see Pattern A's "Forced muster/approach triggers" subsection) |
| `EventApproachCharacterPlayed` | A character entered play via an Approach card | Same triggers as `EventCharacterMustered`; hook the pair |
| `EventChallengeRejected` | A challenge was refused (`$event->challengerId` issued, `$event->targetId` refused) | "When <Owner>'s challenge is refused …" / "When a challenge to <Owner> is refused …". Reference: `_03015` Joern (self-heal), `_01119` Nazem (engage the refuser). |
| `EventChallengeIssued` | A challenge was just issued (`$event->challengerId`, `$event->defenderId`); queued by `StatesTrait::stIssueChallenge` BEFORE the intervention dispatcher state advances | "After a challenge is issued at this location, **before choosing to intervene** …" — `_03027` Odette (pull adjacent Duelist before intervention). Use this (NOT `EventChallengeAccepted`) when the text says "before intervene" — accept fires AFTER the intervention window resolves. |
| `EventChallengeAccepted` | A challenge was accepted (post-intervention) | "After a challenge is accepted at this location …" — existing Odette `_01062` move-adjacent-renown reaction. |
| `EventCharacterIntervened` | An intervention character was selected during a challenge | "After X intervenes …" — `Reaction_01062`. |
| `EventPressureOccuring` | A pressure is happening at a location | "When pressuring …", `_01006` Don Constanzo |
| `EventDuelStarted` / `EventDuelEnd` | Duel boundaries | Passive duel stat modifiers, `_01089`. **`EventDuelEnd` fires BEFORE the dueling line is cleared** in `stDuelEnd` (the discard events are queued AFTER it), so a recount-based dueling-line effect must reset via direct inverse-event, not via re-reading the line. |
| `EventCharacterCombatModified` / `EventCharacterInfluenceModified` | A character's modified stat changed (`$event->CharacterId`, `$event->OldCombat`/`NewCombat` or `OldInfluence`/`NewInfluence`) | Re-sync a "set [StatA] equal to [StatB]" link when the source stat changes, or re-apply the link when an external effect mutates the target stat during the override. EventHub applies the new stat **before** card `handleEvent` runs (`runEventHubAfterCards = false`). Reference: `_03028` Térence. |
| `EventAttachmentEquipped` | An attachment was equipped (`$event->characterId`, `$event->attachmentId`; `$event->asAction` distinguishes action-equip vs passive) | "After a character equips an attachment at [location] …" City Reactions. Look up equipping character via `getCharacterById($event->characterId)` and compare `.Location` to the named city constant. Skip `$attachment->FakeAttachment`. Reference: `Reaction_03028` (any character at Grand Bazaar), `Reaction_01039` (owner self-equip only). |
| `EventDuelEndOfRound` | A duel round just ended; both combat cards are in the dueling line; the next round hasn't begun | Recompute "for each X in my dueling line" running bonuses *before* the next round's gambling. `_03004` Elena. |
| `EventDuelCalculateCombatCardStats` | Combat card stats are being computed for a duel (`$event->gambled` is set from `duel_round.gambled`) | "+X to combat card stats" — `_01116` Yevgeni (every card); gambled-only — `_03037` Sanjay (`$event->gambled` gate) |
| `EventChallengerSwapped` / `EventDefenderSwapped` | A challenge had its participant changed | Re-evaluate any duel-time modifier you applied, `_01089` |
| `EventTableSetup` | Game setup | Initial decisions like "during setup, reveal X from your deck", `_01006` |
| `EventSchemeCardRevealed` | A scheme is revealed | Leaders react via the base `Leader::handleEvent`; only override if you have card-specific logic |
| `EventCharacterDestroyed` | A character is destroyed (`runEventHubAfterCards = true`, so the destroyed character's `.Location` is STILL set during `handleEvent` — the locker move runs AFTER all card handlers). Look up via `getCharacterById($event->characterId)` and compare `.Location == $owner->Location` for "another character at this location" triggers. | Leaders have built-in renown-loss logic in `Leader::handleEvent` — don't reinvent. "After another character at this location is destroyed …" — `_03027` Odette, `Reaction_01013`. |
| `EventSorcererAbilityPlayed` | A sorcerer ability resolved | "After <X> performs a Sorcerer ability …" reactions, Pattern D below |
| `EventActionResolved` | An action just resolved | "After an Action resolves …" reactions, `Reaction_01089` |
| `EventCardMoving` / `EventCardMoved` | Pre / past tense of a card-to-location move | `Moving` is cancelable (`$event->canceled = true`) — use for opt-out Reactions (Pattern D "Cancel-and-reissue"). `Moved` is the past-tense receiver — use for "after X moves to/from this location" triggers. The Dusk auto-move emits `Moving` with `$sourceId == 0`; ability-driven moves pass a non-zero sourceId. Reference: `Reaction_03016a` (cancel), `Reaction_03016b` (react to). |
| `EventLocationClaimed` | A location was just claimed (`$event->playerId` claimer, `$event->location`, optional `$event->performerId`) | "After <Owner>'s location is claimed …" — Continuous / daily Reactions. Gate `event.location == owner.Location`. Any-claimer vs opponent-only is text-dependent (`Reaction_03049` any; `Reaction_01117` opponent). |
| `EventPlayerTakeReknownForControlledLocation` | Plunder notify ahead of Gains+Removed (`$event->playerId`, `$event->location`, `$event->reknown`) | Plunder Collect location signal for "opponent collects one fewer" passives. Reference: `_03049` Ekaterina. |
| `EventPlayerGainsReknown` / `EventRenownRemovedFromLocation` | Player score + / location Renown − | Collect / Plunder / Moves. Gains has **no location**. See Pattern A "Opponent collects one fewer Renown". |

