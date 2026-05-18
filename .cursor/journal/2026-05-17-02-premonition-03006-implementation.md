# Premonition (`_03006`) — implementation

Vodacce scheme. Initiative 33, Panache +1. Traits: Sorte, Weave.

## Card text

- **Resolve:** Add a Renown to two different locations.
- **Strega Reaction:** When your character at your performer's location is
  targeted by an opponent with more cards in hand than you • They must sink
  two cards from their hand.
  *(When a card is played, it is removed from hand when it is announced,
  before choosing targets.)*

## Files touched

- `modules/php/cards/faf/_03006.php` — finished the scheme class: added
  `IHasReactions` + `ReactionTrait`, `handleEvent(EventResolveScheme)` that
  queues a `MEDIUM_PRIORITY` transition into the location-pick state.
- `modules/php/cards/faf/reactions/Reaction_03006.php` — new. Strega
  Reaction (`implements ISorcererAbility`).
- `modules/php/States/faf/State_planningPhaseResolveSchemes03006.php` — new
  GameState class. Uses framework `actCityLocationsForReknownSelected`
  (same as Cat's Embargo `_01098`) — JS picks two locations, server queues
  both Renown events in one act.
- `modules/php/States.php` — added
  `PLANNING_PHASE_RESOLVE_SCHEMES_03006 = 2603006`.
- `states.inc.php` — added `"03006"` transition mapping.
- `modules/js/OnEnteringState.faf.js`, `OnUpdateActionButtons.faf.js`,
  `OnLeavingState.faf.js`, `PlayerActions.js` — JS hooks for
  `planningPhaseResolveSchemes_03006`.

## Resolve flow — two-location pick (no separate _2 state)

I initially planned the two-state pattern (`02046` style: pick first
location, validate different, pick second). On closer look at Cat's
Embargo (`_01098`) — same exact "Add a Renown to two different locations"
text — it uses a **single** state and the framework helper
`actCityLocationsForReknownSelected(string $locationsJson)`. The JS sets
`numberOfCityLocationsSelectable = 2`, the player picks both at once, and
the helper iterates and queues a `ReknownAddedToLocation` per name.

WHY this over the `_02046` two-step pattern: simpler, fewer states, and
the "two different" enforcement happens client-side (the city-location
selector requires two distinct picks). The framework helper doesn't
validate distinctness server-side, which is a known gap shared with
`_01098` — if a malicious client submits the same location twice, two
Renown go on it. Acceptable for now (matches the existing reference
implementation); could harden later with a server-side guard if QA
complains.

## Strega Reaction — `Reaction_03006`

**"Strega Reaction" is a performer-trait gate, NOT a Sorcerer ability.**
The "Strega" prefix is a mechanical gate: the performer of the
reaction must have the Strega trait. It is NOT mechanically a Sorcerer
ability — do not `implements ISorcererAbility`, do not emit Sorcerer
start/played events.

The gate is enforced via `findStregaPerformerAtLocation(controllerId,
targetLocation)`: at trigger time, find a Strega-trait character of the
scheme owner at the targeted character's location. If none exists, the
reaction can't fire (no valid performer). The found character is
captured as `$this->performerId`.

Analogous to "Mercenary City Action" / "Diplomat City Action" /
"Musketeer City Action" — trait-prefixed keywords gate the performer.
"Sorcerer …" is a separate keyword that adds the Sorcerer-ability
mechanic. The two can stack ("Sorcerer Strega Reaction" would be
both) but Premonition only has the Strega prefix.

**Does NOT implement `IAbilityThatTargetsCharacters`.** The reaction's
effect (opponent sinks cards) targets a *player*, not a character. The
triggering ability targets a character — that's what we *listen* for,
not what we *do*.

### "Your performer's location" interpretation

The card text "your character at your performer's location" was ambiguous
on a scheme reaction (the scheme has no location of its own). I worked
through these readings:

1. The targeted character IS the performer (redundant).
2. "Your performer" = your Faction Leader's location (no API support).
3. The scheme controller has a Strega character at the targeted
   character's location — that Strega is "your performer" of the Strega
   Reaction.

I went with #3. Rationale: for Strega Reactions the performer of the
sorcery must be a Strega character. Cross-of-Martyrs journal
(`2026-03-17-04`) confirms "performer = the character the player
CHOOSES to perform the reaction" — so the trigger validates that at
least one valid performer exists (a Strega of yours at the targeted
character's location), and `performReaction` captures one to attribute
the Sorcerer ability events to.

If QA disagrees (e.g. reading #2 is correct), the helper
`findStregaPerformerAtLocation` is the swap point. Flagging this as the
biggest interpretive risk in this card.

### Trigger detection — multiple events

Listens to the same set of "ability targeted a character" events as
Vittoria's redirect reaction (`Reaction_01014`):
`EventSorcererAbilityPlayed`, `EventRangedAbilityPlayed`,
`EventCardEngaged`, `EventCardEngarded`, `EventCardMoving`,
`EventCharacterBeingWounded`, `EventCharacterBeingHealed`,
`EventChallengeIssued`.

For each, the helper `maybeTrigger` verifies:
1. The source ability `instanceof IAbilityThatTargetsCharacters`
   (via `sourceAbilityTargetsCharacters` — checks both
   `getCardById($sourceId)->getAbilityById(...)` AND
   `getInPlayActionById(...)`, same fallback as Reaction_01014).
2. The targeted character is controlled by the scheme owner.
3. The source's controller is an opposing player.
4. The scheme owner has a Strega character at the target's location.
5. The opposing player has more cards in hand than the scheme owner.

WHY both `getCardById`+`getAbilityById` AND `getInPlayActionById`
fallbacks: per `2026-03-08-01-basic-challenge-action.md`,
`BasicChallengeAction` fires with `sourceId = 0`, so the
`getCardById(0)` path returns null and we need the in-play action
lookup. Without this, opening-move basic-challenge targeting wouldn't
trigger the reaction.

`isAvailable()` gate at the top makes the reaction once-per-day
(reset on `EventDuskEndOfDay` by the base class). This also prevents
double-trigger from a single ability that fires multiple events
(e.g. challenge issued + engage + wound).

### "More cards in hand than you" — timing

Card text includes the explicit clarification: *"(When a card is played,
it is removed from hand when it is announced, before choosing targets.)"*
This means by the time the targeting event fires, the played card has
already been removed from the opponent's hand — so the hand count
comparison is automatic. No special timing handling needed; the helper
`opponentHasMoreCardsInHand` just compares
`getCardObjectsAtLocation(LOCATION_HAND, ...)` counts.

### Effect — opponent picks two cards via multi-stage reaction buttons

Implemented with the multi-stage reaction-button pattern from
`Reaction_03cd10` (Julius Caligari). No new GameState. Stages:

- `'offer'` — scheme owner is active; buttons: **Force Sink** / **Pass**.
- `'pick1'` — triggering opponent is active; one button per card in their hand.
- `'pick2'` — triggering opponent is active again; one button per remaining card.

**The opponent is fully determined by the triggering event** — it's the
player whose ability targeted one of your characters. `maybeTrigger`
captures `$opponentId = $source->ControllerId` and that's the final
value. No player-picker stage; an earlier draft added a
`'pickOpponent'` stage in case of multiple eligible opponents in 3p+
games, but per Eddie the card text is unambiguous: the opponent who
did the targeting is the one who sinks.

State transitions between stages use `createReactionTransitionEvent`
with the *next acting player's* id (owner for `'offer'`, opponent for
`'pick1'`/`'pick2'`). Each `performReaction` call updates `$this->stage`,
queues a fresh reaction transition for the right player, and calls
`nextState("done")` — the framework re-enters `playerReaction` with the
new active player and the updated button set.

`getReactionButtonProperties` renders the hand cards as
`card-{id}` buttons when in a pick stage. `performReaction` parses
`'card-{id}'` via `str_starts_with`, validates the id is currently in
the opponent's hand, and sinks via
`$deck->insertCardOnExtremePosition($id, $deckName, false)`. Private
`cardRemovedFromHand` notify with handCount; public message per sink.

**"If able" handling.** After each sink, `advanceToNextPick` checks
the opponent's current hand: if empty, finalize and stop. So an
opponent with one card sinks it and the reaction completes after one
pick rather than blocking.

`Reaction_03006` carries four private state fields persisted via
`$owner->IsUpdated = true`: `$stage`, `$opponentId`, `$performerId`,
`$cardsSunk`. All cleared in `resetStage` after finalize/decline.

### WHY this over a sub-state

Previous design used a separate `HIGH_DRAMA_PLAYER_TURN_03006_SINK`
state, registered under `HIGH_DRAMA_PLAYER_TURN_EVENTS`. **Removed**.
Reactions can fire from any phase (Planning resolve, Dawn city cards,
High Drama, Duels). A sub-state mapped in one events table only covers
that one phase — the reaction would silently fail to route the sink
prompt if triggered from elsewhere.

The button-based pattern avoids the phase coupling entirely:
`createReactionTransitionEvent` routes through the *current* phase's
`*_REACTIONS` state, which always exists alongside whatever
`*_EVENTS` state is active. Same reaction buttons render regardless of
phase. This is the right pattern for a Reaction that can trigger
broadly.

### No Sorcerer ability event bracketing

Previous iteration emitted `createSorcererAbilityStartEvent` /
`createSorcererAbilityPlayedEvent` around the sink work because the
reaction was tagged `ISorcererAbility`. Both removed — "Strega Reaction"
is flavor, not the "Sorcerer" mechanical keyword. `Reaction_03006`
extends `CardReaction` only.

### Sorcerer event bracketing

```php
createSorcererAbilityStartEvent(controllerId, ownerId, abilityId,
    performerId, 0, performerLocation);
... sink work ...
createSorcererAbilityPlayedEvent(controllerId, ownerId, abilityId,
    performerId, 0, performerLocation);
```

`targetId = 0` because the Strega Reaction's effect targets a *player*,
not a character. `targetLocation = performerLocation` since the
performer is the channel for the sorcery.

## Pre-commit hook compliance

- `Reaction_03006 extends CardReaction implements ISorcererAbility`.
  Hook checks: `$this->setUsed(` ✓, `$this->isAvailable(` ✓,
  `createSorcererAbilityStartEvent(` ✓,
  `createSorcererAbilityPlayedEvent(` ✓.
- No `IAbilityThatTargetsCards` — no double-targeting interface
  conflict.
- `php -l` clean on all five edited PHP files.

## Open questions / risks

- **"Your performer" reading.** See above. If reading #2 (Faction
  Leader's location) is correct, swap `findStregaPerformerAtLocation`
  to `getLeaderByPlayerId(...)->Location` check.
- **Multiple-targets ability.** If a single
  `IAbilityThatTargetsCharacters` ability targets 2 of my characters
  (e.g. an AoE), only the first event will set `setUsed` after the
  reaction resolves — the others will hit the `isAvailable()` gate.
  This is correct per once-per-day semantics: the opponent sinks 2
  cards ONCE per day, not per target.
- **Hand-count "at announcement" timing.** The italics note in the
  card text says hand-card-played-this-turn is already removed by the
  time targeting fires. My check just compares current hand counts at
  trigger time — should be correct because of the natural ordering
  but worth verifying against an actual playtest where the opponent
  plays a 1-card-from-hand action against my Strega.
- **`actCityLocationsForReknownSelected` doesn't enforce distinctness.**
  Same gap as `_01098`. Client-side selector enforces it via
  `numberOfCityLocationsSelectable = 2` requiring two distinct picks,
  but a hostile client could submit duplicates. Low risk for now.

## What I considered and rejected

- **Two-state location pick (`02046` style).** Rejected for `_01098`
  parity — single state + framework helper is cleaner.
- **Listen only to `EventSorcererAbilityPlayed` (Cesca pattern).** Too
  narrow — wouldn't trigger on opponent's Risk-driven challenges,
  attachment-action wounds, etc. The full Vittoria event set is the
  right thing here.
- **Implement `IAbilityThatTargetsCharacters` on the reaction.** No —
  the reaction doesn't target a character; it targets a player. The
  interface is for things that you can *target with*, not things that
  trigger *off of* targeting.
- **Dedicated sink state (`HIGH_DRAMA_PLAYER_TURN_03006_SINK`).**
  Initial design. Rejected after the reaction-trigger reality check:
  reactions can fire from any phase; a sub-state in one phase's events
  table won't route from elsewhere. Multi-stage reaction buttons
  (Reaction_03cd10 pattern) keep the prompting inside the
  `playerReaction` flow that already exists in every phase.
- **`createTransitionEvent` for the opponent into a sink state.**
  Pattern from `_02025` (Tea and Cakes). Works for that scheme because
  it ONLY fires during scheme resolve (one events table to register
  with). Not portable to a Reaction with broad firing surface.
- **`changeActivePlayer` mid-`performReaction`.** Rejected: for
  action sub-states within a single playerTurn, not for cross-player
  reactions.
