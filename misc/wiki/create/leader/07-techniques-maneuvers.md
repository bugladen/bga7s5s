# 07 — Techniques and Maneuvers (Pattern E)

← [06 — Reactions](06-reactions.md) · [Index](README.md) · Next: [08 — Challenges](08-challenges.md)

**Techniques** and **Maneuvers** are abilities used during duels (and sometimes during challenge setup).

| Kind | Folder | Card class wiring |
|---|---|---|
| Technique | `cards/<expansion>/techniques/Technique_NNNNN.php` | `$this->Techniques = [ new Technique_NNNNN() ];` — Character already has `TechniqueTrait` |
| Maneuver | `cards/<expansion>/maneuvers/Maneuver_NNNNN.php` | `implements IHasManeuvers` + `use ManeuverTrait` + `$this->Maneuvers = [...]` |

## Basic Technique availability

Most Leader Techniques are "during a duel, while I am the actor":

```php
public function isAvailableToPlayer(int $playerId, Theah $theah): bool
{
    if (! parent::isAvailableToPlayer($playerId, $theah)) {
        return false;
    }
    if (! $theah->game->globals->get(Game::IN_DUEL, false)) {
        return false;
    }

    $owner = $this->getOwningCharacter($theah);
    $actor = $theah->getDuelRoundActor();
    if ($actor === null || $actor->Id !== $owner->Id) {
        return false;
    }

    return true;
}
```

### When "adversary" forces `IN_DUEL`

| Where "adversary" appears | Gate `IN_DUEL`? |
|---|---|
| Cost / condition **before** the `•` ("If the adversary is wounded • …") | Yes |
| Effect **after** the `•` only ("Wound the adversary") | Not for that word alone |

## Gambling Technique

Printed **Gambling Technique** must gate **both**:

```php
if (! $theah->game->globals->get(Game::IN_DUEL, false)) return false;
if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false)) return false;
```

Reference: Aja `Technique_03002`.

## Simple stat Technique (+1 Riposte, etc.)

Handle `EventDuelCalculateTechniqueValues` when `$event->techniqueId == $this->Id`, mutate `$event->parry` / `$event->thrust` / `$event->riposte`, and call `$this->setUsed($event->theah, true)`.

Many +1 helpers already exist (`Technique_PlusOneThrust`, `Technique_PlusOneRiposte`, …). Prefer subclassing them when the text is that simple.

**En Garde Technique: +1 Thrust** → subclass `Technique_PlusOneThrust` and gate `!$owner->Engaged`. Keep an `// EventTechniqueCanceled handler not needed` comment so pre-commit stays happy when extending those generics.

## Techniques that need a picker

Examples: choose a city location, destroy an attachment, look at top cards and sink/reorder.

1. On `EventResolveTechnique`, queue `EventFactory::createTechniqueTransitionEvent(...)` (prefer this over older `createTransitionEvent` + priority hacks).
2. Add a duel state under `DUEL_CHOOSE_TECHNIQUE_EVENTS`.
3. Wire JS — see [09 — Wiring](09-wiring.md).

References:

- City location picker: Angeline `Technique_03025b`
- Look / sink / reorder own deck: Benci `Technique_04001`
- Private look at adversary hand: Yevgeni `Technique_03052`

## Gain Lethal

In-duel Lethal usually queues `EventFactory::createGainLethalEvent($actorId, $theah)` from `EventDuelCalculateTechniqueValues`.  
Only also handle `EventGenerateChallengeThreat` if the Technique should work outside duels too.

## Maneuvers

Same general idea as Techniques: availability during the duel, effect on maneuver resolve / calculate events. Mirror an existing Maneuver with similar text rather than inventing structure.

## Next

Challenge-issuing Actions → [08](08-challenges.md) · Wiring → [09](09-wiring.md)
