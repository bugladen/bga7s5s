# 06 — Maneuvers

← [[05 — Actions|Risk Guide Actions]] · [[Index|Implementing a Risk Card]] · Next: [[07 — Reactions|Risk Guide Reactions]]

Use this page when the printed text has **`<b>Maneuver:</b>`**, **`<b>Gambling Maneuver:</b>`**, **`<b>Duelist Maneuver:</b>`**, **`<b>Scoundrel Maneuver:</b>`**, etc.

A Maneuver activates when this Risk is played as the **combat card** in a duel round.

## Base class

```php
class Maneuver_NNNNN extends Maneuver
```

File: `modules/php/cards/<expansion>/maneuvers/Maneuver_NNNNN.php`

## Two event channels (memorize this)

| Event | When | Put here |
|---|---|---|
| `EventDuelCalculateManeuverValues` | May fire **multiple** times per round | Additive Riposte / Parry / Thrust + explanations |
| `EventResolveManeuver` | Fires **once** | One-shot effects: wound, draw, transitions, arm next-round locks |

```php
if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id) {
    $event->riposte += 1;
    $event->explanations[] = sprintf(
        $event->theah->game->translate('%s adds 1 Riposte.'),
        $this->getOwningCard($event->theah)->getInjectCode()
    );
}

if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id) {
    // wound / draw / queue chooser …
}
```

Pure-calc Maneuvers skip resolve. Pure-resolve Maneuvers (wound only) skip calc.

## Trait / Gambling gates

```php
public function isAvailableToPlayer(int $playerId, Theah $theah): bool
{
    if (! parent::isAvailableToPlayer($playerId, $theah)) {
        return false;
    }

    $actor = $theah->getDuelRoundActor();
    if (! $actor || ! $actor->hasTrait('Duelist')) {
        return false;
    }

    // Gambling Maneuver:
    // if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false)) return false;

    return true;
}
```

These are **mechanical gates**, not Sorcerer abilities.

## Stat comparisons

Use **Modified** stats. Parse the operator literally (`>` vs `>=`). When the payoff wounds the adversary, also hide the Maneuver if the adversary is already discarded / locker.

## Pre-commit: cancel handler

Every Maneuver file must contain **either**:

- A real `EventManeuverCanceled` handler that clears sticky state, **or**
- The exact comment: `// EventManeuverCanceled handler not needed`

Use the comment when there is nothing to undo (pure additive stats / simple queued effects the framework rolls back).

## Wound target

| Printed phrase | Wound |
|---|---|
| Wound **the adversary** | `getDuelRoundOpponent()` |
| Wound **your participant** | `getDuelRoundActor()` |

## Common Maneuver families

| Shape | What to copy |
|---|---|
| Pure +Riposte / +Parry / +Thrust | Many Gambling Maneuvers (`Maneuver_03008`) |
| Calc + resolve wound/draw | `Maneuver_03009`, `Maneuver_03045` |
| Choice at activation (+A or +B) | Pattern C.3 — `Maneuver_01135`, `Maneuver_03024` |
| +N per other card in your dueling line | Pattern C.4 — `Maneuver_01166`, `Maneuver_03036` |
| Next-round cannot gamble / you choose their card | Pattern C.5 — `Maneuver_03047a` / `b` |
| Move / remove / discard-excess threat | Pattern C.6 — `Maneuver_03048`, `Maneuver_03070` |
| +N per opposing character at duel location | Pattern C.7 — `Maneuver_03058` |
| Peek adversary deck → reveal for Parry/Thrust | Pattern C.8 — `Maneuver_03059` |
| Swap participant with other character here | Pattern C.9 — `Maneuver_03069` |
| Final Strike (after death) | `_01082`, `_03022` |

### Choice-at-activation (Pattern C.3) — timing trap

When the player must pick **before** calc ("+2 Parry or +2 Thrust"):

- Transition from `EventManeuverActivated` with **`stackEvent`** (not after Resolve)
- Wire under `DUEL_RESOLVE_MANEUVER_EVENTS`
- Multi-step choosers: `stackEvent` **every** intermediate step, or pending calc races ahead of the choice
- Do **not** re-emit calc after the choice — fix ordering instead

### Combat-card cost discounts

"This card has −1 cost when …" during duel pay lives on the Maneuver:

```php
public function getManeuverFromCombatCardDiscount(...): int
```

Gate on `$owner->Id == $combatCard->Id` plus the printed condition (engaged adversary, Finesse comparison, `DUEL_GAMBLED`, …). See [[08|Risk Guide Passives Forced And Discounts]].

### Dual a/b Maneuvers

Two distinct trait-prefixed Maneuvers → `Maneuver_NNNNNa` and `Maneuver_NNNNNb`. If Gambling only adds calc on the same resolve effect, `b extends a` is fine (`_03069`) — still satisfy the cancel comment in **both** files.

### Neutral / Ussura + Miyato/Ota

If your Maneuver queues a card-specific transition and the Risk is Neutral or Ussura, also mirror the transition under `DUEL_CHOOSE_TECHNIQUE_EVENTS` (Miyato/Ota clone path). See [[09 — Wiring|Risk Guide Wiring States And JavaScript]].

## Next

Reaction text → [[07 — Reactions|Risk Guide Reactions]]
