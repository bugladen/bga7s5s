> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern G — Pressure-count Influence bonus (`getInfluencePressureValue`)

For printed text that grants **+N Influence during pressures** (not a continuous on-card ModifiedInfluence):

| Card phrase | Gates |
|---|---|
| **"During pressures, \<Name\> gains +N[Influence]"** | Always on while counting (Claude) |
| **"<i>En Garde</i> — … gains +N[Influence] during pressures …"** | Italic En Garde = precondition: `!$this->Engaged` |
| **"... during pressures initiated by an opponent"** | `PRESSURING_PLAYER != 0 && PRESSURING_PLAYER != $this->ControllerId` |

**Canonical exemplars:**
- Unconditional: `modules/php/cards/_7s5s/_01184.php` (Claude)
- En Garde + opponent-initiated: `modules/php/cards/bas/_04cd04.php` (Astrid)

### Use `getInfluencePressureValue`, not ModifiedInfluence

```php
public function getInfluencePressureValue(Theah $theah, string $location): int
{
    $value = parent::getInfluencePressureValue($theah, $location);

    $pressuringPlayerId = (int) $theah->game->globals->get(Game::PRESSURING_PLAYER, 0);
    if (
        ! $this->Engaged
        && $pressuringPlayerId != 0
        && $pressuringPlayerId != $this->ControllerId
    )
    {
        $value += 1;
    }

    return $value;
}
```

WHY not Pavel/Angeline `createCharacterInfluenceModifiedEvent`:
- "During pressures" is a **pressure-tally** bonus. Permanently bumping `ModifiedInfluence` would show on the card outside pressures and needs add/remove lifecycle events.
- `pressureLocation` only calls `getInfluencePressureValue` when `STAT_INFLUENCE` is among the pressure stats — so Influence-only is inherent. Do **not** also override `getCombatPressureValue` / `getFinessePressureValue`.

### Italic *En Garde* is a precondition

In this codebase vocabulary:
- `Engaged = true` → committed / has acted
- `Engaged = false` → en garde / ready

When the printed ability leads with italic *En Garde* (or says "while en garde"), gate with `!$this->Engaged`. It is **not** flavor text. Contrast Éventail `_02027`, which uses ModifiedInfluence + engage/engarde events for a continuous on-card bonus — different shape because that card grants Influence on the character while en garde, not only during pressure totals.

### Opponent-initiated pressures

`Game::PRESSURING_PLAYER` is set on every pressure path (basic Claim, card Actions, etc.) **before** `pressureLocation` runs. Require it non-zero and unequal to `$this->ControllerId`. If unset, do not grant the bonus (safe default for non-pressure callers of the hook, if any).

### No Action / Reaction / State / JS

Pure override on the card class. Same finish surface as Pattern F for wiring (none), but a different ability shape — do not route "during pressures" text into Pattern F location recompute.
