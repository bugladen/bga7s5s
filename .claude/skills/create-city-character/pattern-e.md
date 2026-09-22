> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern E — Techniques and Maneuvers

These follow the standard Character patterns — nothing CityCharacter-specific for the trait plumbing:

- **Technique** — `IHasTechniques` is already on `Character` via `TechniqueTrait`. You do **not** need to re-declare `implements IHasTechniques` / `use TechniqueTrait` on the CityCharacter subclass (unlike some older faction Characters that restate it). Push into `$this->Techniques` **after** `resetCard()`.
- **Maneuver** — Implement `IHasManeuvers`, `use ManeuverTrait`, push into `$this->Maneuvers`.

### Prefer generic techniques when the text is exactly "+1 [Thrust]" (etc.)

Do **not** mint a custom `techniques/Technique_04cdNN.php` for a bare +1 Thrust / +1 Riposte / +1 Parry. Reuse:

| Printed text | Class |
|---|---|
| `+1[Thrust]` / `+1 [Thrust]` | `cards\techniques\Technique_PlusOneThrust` |
| (other generics live under `cards/techniques/` — check before inventing) | |

Constructor pattern (after `resetCard()`):

```php
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;

$technique = new Technique_PlusOneThrust();
$technique->setId("Technique_04cd14_1");
$this->Techniques[] = $technique;
```

### Dual / repeated identical techniques — distinct `setId` is mandatory

When the card prints **two** identical Technique lines (Millstone `_04cd14`, Langschwert `_01048`):

```php
$technique = new Technique_PlusOneThrust();
$technique->setId("Technique_04cd14_1");
$this->Techniques[] = $technique;

$technique = new Technique_PlusOneThrust();
$technique->setId("Technique_04cd14_2");
$this->Techniques[] = $technique;
```

**WHY:** `CardAbilityTrait::initializeAbility()` sets both `Id` and `ClassId` to the unqualified class name (`Technique_PlusOneThrust`). `setOwnerId` then builds `Id` as `{ownerId}_{ClassId}`. Without unique `setId` first, **both** instances collapse to the same `{ownerId}_Technique_PlusOneThrust` and overwrite each other in lookups / UI.

`setId("Technique_04cdNN_N")` sets **both** `Id` and `ClassId` to that string; later `setOwnerId` yields `{ownerId}_Technique_04cdNN_N` — unique per copy.

Single-copy generics still call `setId("Technique_04cdNN")` (or `Technique_01042`-style) so the ClassId is card-scoped rather than the shared generic class name — see Terrell `_01042`.

Custom (non-generic) techniques live under `cards/<expansion>/techniques/Technique_04cdNN.php`. Still call `setId("Technique_04cdNN")` after `new` before pushing onto `$this->Techniques`.

### Gambling Technique

"**Gambling Technique:** …" is a **duel-round gate**, not a Trait. On top of the usual in-duel actor check (`IN_DUEL` + `getDuelRoundActor()->Id == owner.Id`):

```php
if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
{
    return false;
}
```

WHY the global (not `duel_round.gambled` SQL): set in `actChooseGambleCard`, cleared in `stDoneRound`; `isAvailableToPlayer` is a hot path. Fuller notes live in the sibling skill `create-character` [pattern-e.md](../create-character/pattern-e.md) "Gambling Technique gate".

### Gambling Technique: If adversary has lower [Finesse] • Wound them

Printed (Tijani `_04cd29`): gate availability on `adversary->ModifiedFinesse < owner->ModifiedFinesse` (strictly lower — equal fails). On `EventDuelCalculateTechniqueValues` when `$event->techniqueId == $this->Id`, queue `createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id)` and append an explanation. Optional explicit `$this->setUsed($event->theah, true)` (idempotent with base Technique activation).

No new state / JS — pure resolve wound. Mirror `Technique_03004` / `Technique_03014` wound-on-calc shape; Finesse "If" gate mirrors `Technique_03043`'s greater-Finesse check (flipped inequality).

Reference: `Technique_04cd29`; Gambling Lethal sibling `Technique_03002`.
