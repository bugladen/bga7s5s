> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern F — Continuous location-scoped passive (`handleEvent` recompute)

For printed text with **no player choice** and a **while-condition** tied to who shares the owner's location:

| Card phrase | What changes |
|---|---|
| **"While you control [trait/type] at \<Name\>'s location, [he/she] has +N [Stat]"** | Stat bonus (Influence / Combat / …) |
| **"While you control a Diplomat, Duelist, … at \<Name\>'s location, he gains that trait"** | Conditional trait grant |

This is **not** City Forced (`cardInCity`), **not** an Action, **not** a Reaction. No Action/Reaction/State/JS files. Override `handleEvent` on the card class and recompute whenever the location roster changes.

**Canonical exemplars:**
- Stat bonus: `modules/php/cards/faf/_03026.php` (Angeline — faction Character, same shape)
- Trait copy: `modules/php/cards/bas/_04cd02.php` (Jack Trades — CityCharacter)

create-character's Pattern A "Location-counting passives" covers the same EventCardMoved stale-DB mechanics in more depth — read that if the recompute shape is non-obvious. This file adds CityCharacter-specific gates and the trait-copy variant learned from Jack.

### Scope gate: `isControlled()`, not `cardInCity`

City characters sit at a city location **before** muster with `ControllerId == 0`. The printed "While **you** control …" only applies once the character is in play.

```php
if (! $this->isControlled()
    && ! ($event instanceof EventCharacterMustered && $event->characterId == $this->Id)
    && ! ($event instanceof EventCharacterRecruited && $event->characterId == $this->Id))
{
    return;
}
```

WHY allow muster/recruit of self through the gate: the hub sets `ControllerId` before card handlers for those events, but the early `isControlled()` check would otherwise skip Jack's own entry into play. Pass `$event->playerId` as a controller override on self-muster/recruit for safety (Angeline's approach pattern).

### Event set to hook

| Event | When |
|---|---|
| `EventCardMoved` (`cardId == $this->Id`) | Owner moves — recompute at `$event->toLocation` |
| `EventCardMoved` (other, `toLocation` / `fromLocation == $this->Location`) | Ally arrives / leaves |
| `EventCharacterMustered` | Owner or someone at owner's location musters |
| `EventCharacterRecruited` | Same for recruit (city Mercenaries) |
| `EventCharacterDestroyed` (other) | Ally dies at location |
| `EventApproachCharacterPlayed` | Only if the passive can apply at Home (Angeline); city Mercenaries rarely need this |

### `EventCardMoved` stale-location compensation (mandatory)

`EventCardMoved.runEventHubAfterCards = true` → card `handleEvent` runs **before** the hub updates `Location`. Naive `getCharactersAtLocation($location)` is wrong:

1. **Moving out** — still listed at `$location` → exclude `$moveEvent->cardId` when `fromLocation == $location && toLocation != $location`.
2. **Moving in** — not listed yet → if no qualifying ally found, peek `$theah->getCardById($moveEvent->cardId)` when `toLocation == $location`.

Thread `?EventCardMoved $moveEvent` into the recompute helper. Mirror `_03026` / `_04cd02`.

### `EventCharacterDestroyed` exclude (mandatory for correct drop)

Same `runEventHubAfterCards = true` ordering: when card handlers run, the dying character is **still** at the location with traits intact. A naive recompute keeps the bonus/trait forever.

Pass `$excludeCardId = $event->characterId` into the helper and skip that id. Also skip `characterIsInDiscardOrLocker` defensively.

WHY Angeline `_03026` still has this latent gap — do **not** copy that omission. Jack `_04cd02` is the corrected form.

### Trait-copy specifics (Jack)

When the passive **grants traits** rather than a stat:

1. **Track grants in a public array** (e.g. `$CopiedTraits = []`), cleared in `resetCard()`. Persistable like other card fields.
2. **Only add/remove traits present in that array.** WHY: naive `removeTrait("Duelist")` when the ally leaves also strips Duelist granted by an attachment (Guild Triskelion, Cross of Martyrs → Zealot). `$CopiedTraits` is the source-of-truth for *this* ability only.
3. **Exclude `$this->Id` from the source scan.** WHY: once Jack gains Diplomat he'd latch it permanently by counting himself.
4. **Do not hook `EventAttachmentEquipped` / `Unequipped` by default.** Attachment trait-grants fire in the attachment's own `handleEvent` during the same card pass — ordering vs the CityCharacter is nondeterministic. Printed-trait allies cover the intended play pattern. Document as a known gap in the journal if relevant.

```php
/** @var string[] Traits currently granted by this passive. */
public array $CopiedTraits = [];

private const COPYABLE_TRAITS = ['Diplomat', 'Duelist', 'Pirate', 'Zealot'];

public function resetCard()
{
    parent::resetCard();
    $this->CopiedTraits = [];
}

// In recompute, for each COPYABLE_TRAITS entry:
//   shouldHave && !in CopiedTraits → addTrait + push
//   !shouldHave && in CopiedTraits → removeTrait + filter out
```

### Stat-bonus specifics (Angeline)

Queue `createCharacterInfluenceModifiedEvent` (or Combat/Finesse) from **base stat + bonus**, with a no-op gate:

```php
if ($newInfluence == $this->ModifiedInfluence) return;
```

Recompute absolute value; do not accumulate ±1 without a tracked flag unless you are sure every edge fires exactly once.

### Constructor gotchas for CityCharacter stubs

- Always set **`WealthCost`** from the printed coin — stubs often omit it.
- Verify **Combat / Finesse / Influence / Resolve** against the card image; scaffolding numbers can be wrong.
- **`Negotiable`** is independent of the passive — set from the keyword / reminder text.
- New Traits go in `TraitNames.php` alphabetically; Factotum and the four role traits already exist.

### Home location

`LOCATION_PLAYER_HOME` is a shared string across players. Use `getCharactersAtHomeByPlayerId($controllerId)`, not `getCharactersAtLocation(HOME)`. When another card moves to/from Home, gate on that card's `ControllerId == $this->ControllerId` (Angeline's home branches).
