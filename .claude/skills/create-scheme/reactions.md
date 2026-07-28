> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Reactions on Schemes

Schemes can carry Reactions (use `IHasReactions` + `ReactionTrait` + `reactions/Reaction_NNNNN.php`). The reaction shape is identical to character-borne `CardReaction`s — see `create-character`'s Pattern D for the full recipe.

### Lifecycle: scheme reactions DO fire during High Drama

This was a worry during `_03005` implementation. Verified: `Theah::buildCity()` populates `$this->cards` from every persistent location including each player's Home. Chosen schemes sit at **`LOCATION_PLAYER_HOME` for the whole day** (not discard — see lifecycle above), so `handleEvent` still reaches the scheme and its reactions during High Drama claims/challenges/pressures. Don't add liveness guards based on "the scheme is no longer in play."

`CardReaction::handleEvent` resets `Used` to `false` on `EventDuskEndOfDay`, so a scheme reaction is once-per-day, same as a character reaction.

### Identity gates inside the reaction's `handleEvent`

`$this->getOwningCard($event->theah)` returns the *scheme* (which is the owner of this reaction). The scheme's `ControllerId` is set to the player who chose it for this turn. Standard idiom:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventChallengeRejected && $this->isAvailable())
    {
        $owner = $this->getOwningCard($event->theah);
        if ($owner == null) return;

        $challenger = $event->theah->getCharacterById($event->challengerId);
        if ($challenger == null) return;
        if ($challenger->ControllerId != $owner->ControllerId) return;
        if (! $challenger->hasTrait("Red Hand")) return;

        // Capture event-time context onto the reaction object so performReaction can use it.
        $this->location = $challenger->Location;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }
}
```

Reference: `Reaction_03005` (claim a location after Red Hand's challenge refused), `Reaction_02004` (move adjacent performer when opponent initiates pressure at scheme controller's location).

### Capturing context onto the reaction

The triggering event has only a snapshot of args (`$event->challengerId`, etc.). If the reaction needs context that isn't on the event (the location of the challenge, the destroyed character's name, etc.), capture it into a `private` property on the reaction at trigger time, **then clear it** in `performReaction` (or `resetStage` for multi-stage reactions). `$owner->IsUpdated = true` persists the property to DB. See `Reaction_02004::$location` and `Reaction_03005::$location` for the pattern.

**Surface captured context in the prompt.** The reaction-button screen is the player's first chance to see *why* they're being prompted — bake the relevant context into `getReactionDescription` so they can make an informed pass/play call. Resolve the captured id to a name and `sprintf` it in:

```php
public function getReactionDescription(Theah $theah): string
{
    $base = parent::getReactionDescription($theah);
    $target = $this->targetCharacterId > 0
        ? $theah->getCharacterById($this->targetCharacterId)
        : null;
    $name = $target ? $target->Name : $theah->game->translate('your character');
    return $base . sprintf($theah->game->translate(
        '${you} may force the opponent to sink two cards after they targeted %s: '
    ), $name);
}
```

Always defensively null-check (`$target ? ... : translate('your character')`) — the captured id might point at a character that's since been destroyed/recruited away by the time the prompt renders. Reference: `Reaction_03006::$targetCharacterId` in the `'offer'` description.

### Bundled-effect reactions ("Reaction: … • A. B. C.")

When the bullet text has multiple sentences but no internal "may", the decision point is whether to use the reaction at all — once confirmed, all sub-effects fire atomically. Render a single **Resolve** + **Pass** button pair; don't gate sub-effects behind separate clicks. Resolve-branch queries (e.g. `getCharactersAtLocation($this->location)`) happen at *resolve time*, not at trigger time, so the deictic "that location" reflects the current set of characters there rather than a snapshot from when the trigger fired. Conditional clauses (e.g. "If the destroyed character was a Zealot, draw a card") gate on the *captured* trait snapshot, since the character is gone from play by then. Reference: `Reaction_03017`.

### Pre-commit hook compliance

`CardReaction` subclasses must include the literal strings `$this->setUsed(` and `$this->isAvailable(` somewhere in the file. The `handleEvent` `isAvailable` check + the `setUsed(true)` in `performReaction`'s success branch satisfy both. Decline/Pass branches deliberately skip `setUsed` — the reaction stays available for the next trigger that day. Mirror `Reaction_03005` / `Reaction_02004` / `Reaction_03017` for this discipline.

### `EventCharacterDestroyed` — destroy-time location is readable

`EventCharacterDestroyed` is declared with `runEventHubAfterCards = true`. Card `handleEvent` calls run **before** the hub moves the character to the locker, so `$destroyed->Location` still reports the destroy-time city slot inside your reaction's handler. Capture it into a `private string $location` field (with `$owner->IsUpdated = true`) for use in `performReaction`, because by the time the player clicks the button, the character has been moved out and `$destroyed->Location` no longer matches the city. Also capture any trait/name snapshots the resolve branch needs (`$destroyedWasZealot`, `$destroyedName`) — same reason.

### Schemes that target city locations / can't always claim

`createLocationClaimedEvent($playerId, ?int $performerId, string $location)` — `performerId` is `null` when the claim isn't tied to a specific performer (e.g., scheme-driven claims). Compare `Action_03cd13.php` which passes the performer for an Action-driven claim. Don't invent a "fake performer" — `null` is correct.

### "After you claim a location • Move a Renown …"

City Reaction on `EventLocationClaimed`:

1. Gate: `$this->isAvailable()`, `$event->playerId == $owner->ControllerId`, claimed `getCityLocation(...)->Renown > 0`, and at least one other city location exists as a destination.
2. Capture `$this->location = $event->location` + `$owner->IsUpdated = true`.
3. Queue `createReactionTransitionEvent`.
4. Buttons: one per other city location (`moveTo-{Name}`) + Pass. No GameState / no JS.
5. On confirm: batch `createRenownMovingBetweenLocationsEvent` + `createRenownRemovedFromLocationEvent` + `createRenownAddedToLocationEvent(..., $isMove = true)` with a shared `batchId`. Re-validate source still has Renown and destination ≠ source. `setUsed` only on success.
6. Pass clears `$this->location` without `setUsed`.
7. Bake the claimed location name into `getReactionDescription` (defensive fallback if empty).

Reference: `Reaction_03041`. Button-from-location move-Renown idiom: `Reaction_01118` (Elina — sources with Renown; Proper Study flips it: fixed source, destinations).

### Multi-stage reactions (button-driven, no sub-state)

Use this when the Reaction needs several player clicks in sequence (e.g. offer → pick target → confirm), or when the *player who clicks* changes between steps. Pattern source: `Reaction_03cd10` (Julius Caligari), `Reaction_03006` (Premonition).

Anatomy:

- A `private string $stage` field (e.g. `''` idle, `'offer'`, `'pick1'`, `'pick2'`, etc.) plus any per-stage context (`$opponentId`, `$performerId`, …). Persist with `$owner->IsUpdated = true`.
- `getReactionDescription` switches on `$stage` to return the right prompt text.
- `getReactionButtonProperties` switches on `$stage` to render different button sets (e.g. **Force Sink** / **Pass** for `'offer'`; one `card-{id}` button per hand card for `'pick1'`/`'pick2'`).
- `performReaction` parses the click via `str_starts_with($reactionId, 'card-')` etc., applies the effect, advances `$stage`, then **queues another `createReactionTransitionEvent` for the player whose turn comes next** and calls `nextState("done")`. The framework re-enters `playerReaction` with the updated active player + button set.
- `setUsed($theah, true)` only fires when the multi-stage flow is fully resolved (in `finalize` / the last stage).

Example skeleton:

```php
public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
{
    parent::performReaction($game, $state, $internalId, $reactionId);
    $owner = $this->getOwningCard($game->theah);

    if ($this->stage === 'offer') {
        if ($reactionId === 'sink') {
            // advance to pick1, queue reaction transition (often for a different player)
            $this->stage = 'pick1';
            $owner->IsUpdated = true;
            $transition = EventFactory::createReactionTransitionEvent($this->opponentId, $owner->Id, $this->Id);
            $game->theah->queueEvent($transition);
            $game->gamestate->nextState("done");
            return;
        }
        $this->resetStage(); $owner->IsUpdated = true;
        $game->gamestate->nextState("done");
        return;
    }

    if ($this->stage === 'pick1' || $this->stage === 'pick2') {
        if (str_starts_with($reactionId, 'card-')) {
            $cardId = (int)substr($reactionId, strlen('card-'));
            $this->doOneSink($game, $owner, $cardId);
            $this->cardsSunk++;
            if ($this->cardsSunk < 2 && $this->hasMoreToPick($game)) {
                $this->stage = 'pick2';
                $owner->IsUpdated = true;
                $transition = EventFactory::createReactionTransitionEvent($this->opponentId, $owner->Id, $this->Id);
                $game->theah->queueEvent($transition);
                $game->gamestate->nextState("done");
                return;
            }
            $this->finalize($game, $owner);  // sets Used, resets stage
        }
    }
    $game->gamestate->nextState("done");
}
```

### Cross-player Reactions (opponent performs part of the resolution)

When a Reaction's effect requires the **opposing** player to make a choice (e.g. "they must sink two cards from their hand"), **do not** route through a dedicated GameState sub-state. Reactions can fire from any phase (Planning, High Drama, Dawn, Duels), and a sub-state mapped under one phase's `*_EVENTS` transitions table only works in that one phase.

Instead, queue a `createReactionTransitionEvent($opponentId, $owner->Id, $this->Id)` with the opponent's playerId. The framework makes them the active player in the `playerReaction` state, where the reaction's `getReactionButtonProperties` (driven by `$stage`) renders the appropriate hand-picker buttons for them. `playerReaction` exists alongside every events state, so this works phase-independently.

Why not `createTransitionEvent($opponentId, ...)` (the `_02025` "Tea and Cakes" pattern)? That works only when the reaction fires from a single, predictable events state (`_02025` only resolves during `PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS`). For reactions with broad firing surfaces, the reaction-button pattern is the portable choice.

Reference: `_03006` (Premonition — scheme owner clicks Force Sink, then opponent gets buttons for each hand card to sink in `pick1`/`pick2`).

### Listening for "ability that targets a character"

If the trigger is "when your character is targeted by an opposing ability …", you need to react to any ability that `instanceof IAbilityThatTargetsCharacters`. There is no single event for this; abilities propagate via several effect events. Listen to the **full set**:

- `EventSorcererAbilityPlayed`, `EventRangedAbilityPlayed` (both expose `$targetId`/`$targetLocation` directly)
- `EventCardEngaged`, `EventCardEngarded` (target via `$cardId`)
- `EventCardMoving` (target via `$cardId`)
- `EventCharacterBeingWounded`, `EventCharacterBeingHealed` (target via `$characterId`)
- `EventChallengeIssued` (target via `$defenderId`)

Inside each branch, look up the source ability:

```php
private function sourceAbilityTargetsCharacters(Theah $theah, int $sourceId, string $abilityId): bool
{
    if ($abilityId === '') return false;

    $source = $theah->getCardById($sourceId);
    if ($source !== null) {
        $ability = $source->getAbilityById($abilityId);
        if ($ability instanceof IAbilityThatTargetsCharacters) return true;
    }

    // Fallback for BasicChallengeAction which fires with sourceId = 0.
    $action = $theah->getInPlayActionById($abilityId);
    return $action instanceof IAbilityThatTargetsCharacters;
}
```

Both the `getCardById->getAbilityById` AND `getInPlayActionById` lookups are needed — the basic challenge action fires with `sourceId = 0`, so the card lookup returns null and you need the action-by-id fallback. See `Reaction_01014` (Vittoria), `Reaction_01032` (Unyielding Loyalty), `Reaction_03006` for the full pattern.

Wrap the whole `handleEvent` body with an `if (! $this->isAvailable()) return;` near the top. The once-per-day reset handles "one ability fires multiple effect events" — the reaction only triggers on the first event; after the player resolves, `setUsed` blocks further events from the same ability.

### "When an opposing character is destroyed • …"

**"Opposing" is not "any enemy."** Helpers.md: different controller **and same location**. A gate of only `$destroyed->ControllerId != $owner->ControllerId` is wrong — it fires for destroys across the board.

For a **trait-prefixed** scheme Reaction with no named performer in the trigger (e.g. **"Duelist Reaction: When an opposing character is destroyed • Draw a card"**):

1. On `EventCharacterDestroyed` + `isAvailable()`.
2. Reject if `$destroyed->ControllerId == $owner->ControllerId`.
3. Require a controlled traited character **at `$destroyed->Location`** (`getCharactersAtLocation` + `hasTrait("Duelist")`). That is both the trait gate and the opposing-location rule.
4. Destroy-time Location is still readable during `handleEvent` (`runEventHubAfterCards = true`) — no need to capture location unless the resolve branch needs it later.
5. Buttons: Resolve/Draw + Pass. `setUsed` only on success. Surface destroyed name in `getReactionDescription` if useful.

**Regression:** First Blood Money draft gated "any Duelist in play anywhere" + any enemy destroy. Eddie corrected: opposing = same location as your Duelist.

Reference: `Reaction_04004`. Contrast friendly-destroy-at-city: `Reaction_03017`. Contrast "any opposing sent to Locker" with no same-location requirement when the printed text does not say opposing-at-location (re-read the card — `_03007` uses Locker, not "opposing" in the same sense).

### "Your performer's location" on a scheme

Schemes don't have a fixed performer like character actions do. When the printed text says "your performer's location" (e.g. Premonition's "your character at your performer's location"), interpret it as: **the scheme controller picks/identifies a character to act as the performer**, and that character's location is "your performer's location".

For a trait-prefixed reaction ("Strega Reaction" etc.), the performer must have the gating trait. Pattern:

```php
private function findStregaPerformerAtLocation(Theah $theah, int $controllerId, string $location): ?Character
{
    foreach ($theah->getCharactersAtLocation($location) as $character) {
        if ($character->ControllerId == $controllerId && $character->hasTrait("Strega")) {
            return $character;
        }
    }
    return null;
}
```

Capture the performer's id onto the reaction at trigger time (e.g. `$this->performerId = $performer->Id`) so `performReaction` can attribute events to that character. Reference: Cross-of-Martyrs audit (`2026-03-17-04`) — Eddie's correction: "the 'performer' is the character the player CHOOSES to perform the reaction."

### "If able" loop termination

When the effect demands N items from a finite pool ("sink two cards", "discard three", etc.), structure the loop so it terminates gracefully when the pool is exhausted — the rules implicitly read "if able". Pattern:

```php
private function advanceToNextPick(Game $game, Card $owner): bool
{
    $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
    if (count($hand) == 0) return false;          // pool exhausted — finalize
    $this->stage = ($this->cardsSunk == 0) ? 'pick1' : 'pick2';
    $owner->IsUpdated = true;
    $game->theah->queueEvent(EventFactory::createReactionTransitionEvent(
        $this->opponentId, $owner->Id, $this->Id));
    return true;
}
```

Caller treats `false` as "finalize early" and skips remaining picks. Reference: `Reaction_03006::advanceToNextPick`.
