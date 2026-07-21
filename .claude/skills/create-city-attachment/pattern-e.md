> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern E — Steady-State Override (NOT event mutation)

**For properties that are read at fixed points in the game flow, override the corresponding `Card::get*` method. Do NOT mutate globals from `handleEvent`.**

`_03cd05`'s `+1 gamble reveal` was initially drafted as `handleEvent(EventGambleSetup)` mutating a count global. Rejected on review. The right pattern:

```php
public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
{
    $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);

    if ($this->isAttached() && $actor->Id == $this->AttachedToId)
    {
        $count += 1;
        $explanations[] = sprintf(
            $theah->game->translate("%s reveals +1 card when Gambling."),
            $this->getInjectCode()
        );
    }

    return $count;
}
```

Why: `Theah::getNumberOfGambleCardsToReveal` iterates every card in play and sums their contributions. The count is a *steady-state property* of the play area — it should be recomputed from scratch each gamble setup, not stored transiently. Established by Sarafina (`_01010`), Ivy (`_02042`), Roll the Bones (`_01114`).

The same principle applies anywhere `Card` exposes a `get*` hook (pressure tallies via `UtilitiesTrait::pressureLocation`, cost discounts, etc.). When in doubt, grep for whether `Theah` iterates cards summing a `get*` call — if so, override; don't mutate.
