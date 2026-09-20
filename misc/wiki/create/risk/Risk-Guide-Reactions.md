# 07 — Reactions

← [[06 — Maneuvers|Risk Guide Maneuvers]] · [[Index|Implementing a Risk Card]] · Next: [[08 — Passives, Forced, and discounts|Risk Guide Passives Forced And Discounts]]

Use this page when the printed text has **`<b>Reaction:</b>`** or **`<b>City Reaction:</b>`**.

## Base class

```php
class Reaction_NNNNN extends RiskReaction
```

File: `modules/php/cards/<expansion>/reactions/Reaction_NNNNN.php`

There is **no** `RiskCityReaction` class. City Reactions are still `RiskReaction` plus a city-presence gate.

## Pre-commit literals (required)

Somewhere in the Reaction file you must have all three:

1. Hand guard using **`==`** (not `!=`):

```php
if (! ($owner->Location == Game::LOCATION_HAND)) {
    return;
}
```

2. Literal `$this->setUsed(`
3. Literal `$this->isAvailable(`

The hook greps exact substrings. `Location != Game::LOCATION_HAND` does **not** satisfy it.

## Minimal single-stage shape (Pattern D.2)

1. `handleEvent` on the trigger → gate + store ids + `createReactionTransitionEvent` to the owner
2. `performReaction('use')` → queue pay (`EnteringPayState` + `ReactionPayTransition`) — **do not** apply the effect yet
3. `handleEvent` on `EventRiskReactionTriggered` → apply the effect, notify, `setUsed`

**Why defer the effect?** Cancel-reactions can still cancel between pay start and trigger. Side effects applied in `performReaction('use')` would stick even if the play is canceled.

Reference: `Reaction_03012` (Subtle — flips `CHALLENGE_STAT`), `Reaction_03046a` / `b` (Passionate — engarde after intervene).

## City Reaction gate

Before offering:

```php
$inCity = $theah->getCharactersInCityByPlayerId($owner->ControllerId);
if (count($inCity) === 0) {
    return;
}
```

The Risk itself stays in hand until paid. "City" means you have presence in the city.

Reference: `Reaction_03068` (Confusion).

## Intervene ≠ Challenge Accepted

`actHighDramaChallengeActionIntervene` sets `CHALLENGE_ACCEPTED = true` but does **not** fire `EventChallengeAccepted`. For "if their adversary intervened" text, listen to **`EventCharacterIntervened` only**.

## Dual a/b Reactions

Two distinct trait-prefixed Reactions on one Risk → `Reaction_NNNNNa` / `Reaction_NNNNNb`. Do not one class with a mode field. See `_03046`.

## Multi-stage / opponent chooses (Pattern D.1)

When after pay another player must choose something:

- Keep a `$stage` field (prefer **public** across serialize round-trips)
- Drive buttons via `getReactionButtonProperties()` / `getReactionDescription()`
- Prefer **reaction buttons** over new GameStates when the chooser is a small fixed pool
- Do not `setUsed` until `finalize()` at the end
- After pay, `$owner->Location` is discard — do not re-apply the hand guard on post-pay stages

Reference: `Reaction_03010` (Manipulative).

### Pass → opponent must move Home→City (Pattern D.1.1)

Trigger: `EventHighDramaPhasePlayerPassed`. Hide the Reaction when the passer has no en garde character at Home ("must" would be impossible). Opponent picks character then city location via buttons. Move with `engage: false`.

Works on both normal pass (turn events) and final pass (`HIGH_DRAMA_END_EVENTS`). Reference: `_03068`.

## Pressure +1 (Pattern D.2.1)

Trigger `EventPressureOccuring`. After pay, set a **new** `PRESSURE_TYPE` flag + player-id global. Apply +1 inside `pressureLocation()` outside the per-stat loop.

Do **not** reuse `PRESSURE_BONUS` (Pack Tactics / Influence-only). Reference: `Reaction_03035`.

## Effect redirect (Pattern D.4)

"When an opponent's ability would wound / move / engage your character" (no "target" wording):

- Gate on the effect event + opponent source — **not** on `IAbilityThatTargetsCharacters` at trigger time
- Defer re-emit until after pay (`EventRiskReactionTriggered`)

Reference: `Reaction_03031` (Altruistic); structural cousin `Reaction_02016` on attachments (do not copy wound-on-redirect unless printed).

## Cancel Reactions (`ICancelReaction`)

When your effect must run **before** high-priority events already queued from the same batch (e.g. cancel a Renown move), `implements ICancelReaction` and use `stackEvent` for the post-pay path. Reference: `Reaction_03020` / Commanding.

## Next

Forced / discounts → [[08 — Passives, Forced, and discounts|Risk Guide Passives Forced And Discounts]]
