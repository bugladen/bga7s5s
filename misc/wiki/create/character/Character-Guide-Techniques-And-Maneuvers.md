# 07 — Techniques and Maneuvers (Pattern E)

← [[06 — Reactions|Character Guide Reactions]] · [[Index|Implementing a Character Card]] · Next: [[08 — Challenges|Character Guide Challenge Actions]]

**Techniques** and **Maneuvers** are abilities used during duels (and sometimes during challenge setup).

| Kind | Folder | Card class wiring |
|---|---|---|
| Technique | `cards/<expansion>/techniques/Technique_NNNNN.php` | `$this->Techniques = [ new Technique_NNNNN() ];` — Character already has `TechniqueTrait` |
| Maneuver | `cards/<expansion>/maneuvers/Maneuver_NNNNN.php` | `implements IHasManeuvers` + `use ManeuverTrait` + `$this->Maneuvers = [...]` |

## Basic Technique availability

Most Character Techniques are "during a duel, while I am the actor":

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

Reference: Aja `Technique_03002`, Íñigo `Technique_03039`.

## Simple stat Technique (+1 Riposte, etc.)

Handle `EventDuelCalculateTechniqueValues` when `$event->techniqueId == $this->Id`, mutate `$event->parry` / `$event->thrust` / `$event->riposte`, and call `$this->setUsed($event->theah, true)`.

Many +1 helpers already exist (`Technique_PlusOneThrust`, `Technique_PlusOneRiposte`, …). Prefer subclassing them when the text is that simple.

**En Garde Technique: +1 Thrust** → subclass `Technique_PlusOneThrust` and gate `!$owner->Engaged`. Keep an `// EventTechniqueCanceled handler not needed` comment so pre-commit stays happy when extending those generics. Reference: Aimée `Technique_04021b`.

## Techniques that need a picker

Examples: choose a city location, destroy an attachment, look at top cards and sink/reorder, adversary discards a card.

1. On `EventResolveTechnique`, queue `EventFactory::createTechniqueTransitionEvent(...)` (prefer this over older `createTransitionEvent` + priority hacks).
2. Add a duel state under `DUEL_CHOOSE_TECHNIQUE_EVENTS`.
3. Wire JS — see [[09 — Wiring|Character Guide Wiring States And JavaScript]].

References:

- City location picker: Angeline `Technique_03025b`
- Look / sink / reorder own deck: Benci `Technique_04001`
- Adversary discards a card: Maya `Technique_01093`, Íñigo `Technique_03039`
- Private look at adversary hand: Yevgeni `Technique_03052` (Leader, but Pattern E is identical)

## Gain Lethal

In-duel Lethal usually queues `EventFactory::createGainLethalEvent($actorId, $theah)` from `EventDuelCalculateTechniqueValues`.  
Only also handle `EventGenerateChallengeThreat` if the Technique should work outside duels too.

Combat-card trait → Lethal on the **card class** (not a Technique) is Pattern A — Jak-Sen `_04041`, Rosa `_02033`.

## Do not re-declare TechniqueTrait

`Character` already `implements IHasTechniques` and uses `TechniqueTrait`. On the card class you only assign `$this->Techniques = [...]`. Older base-game files sometimes re-declare both — do not copy that habit on new cards.

## Next

Challenge Actions → [[08|Character Guide Challenge Actions]] · Wiring → [[09|Character Guide Wiring States And JavaScript]]
