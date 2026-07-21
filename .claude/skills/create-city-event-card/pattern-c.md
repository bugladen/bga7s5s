> Part of **create-city-event-card**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern C — City Reaction (and "Reaction while in Home")

1. On the card class:

```php
class _03cdNN extends CityEventCard implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        // ... fields ...
        $this->resetCard();

        $this->Reactions = [ new Reaction_03cdNN() ];
    }
}
```

2. Create `modules/php/cards/<expansion>/reactions/Reaction_03cdNN.php` extending `CardReaction`. Implement `getReactionDescription`, `getReactionButtonProperties`, `handleEvent` (detect the trigger, queue a `ReactionTransitionEvent`), and `performReaction` (apply effect, call `$this->setUsed($game->theah, true)`, then `$game->gamestate->nextState("done")`).
3. Pre-commit hook requires `$this->setUsed(...)` AND `$this->isAvailable()` to appear somewhere in the reaction class.
4. `CardReaction`'s base `setUsed` is reset at dusk automatically.

### Pick the right reaction shape

- **Single-stage opt-in** (one decision, then resolve): `_7s5s/reactions/Reaction_01184.php` (Claude — opt in to a pressure-type flag) is the simplest template.
- **Multi-stage button-based** (sequential picks: target, then option, then sub-target): use a private `$stage` field (string enum) plus per-stage state fields (e.g. `$chosenLocation`, `$chosenCharacterId`). Each `performReaction` advances the stage and re-queues `createReactionTransitionEvent($playerId, $cardId, $this->Id)` so the framework re-enters `playerReaction` with a fresh `getReactionButtonProperties` payload. Mark `$owner->IsUpdated = true` after each stage flip so the private fields persist across action handlers. References: `_03cd10` (Julius — letter → trait → target), `_03cd18` (Kalla — option A vs B branches), `_03cd20` (Early Morning Arrangements — character → adjacent city). Provide a `< Back` button on intermediate stages and a `Decline` button on every stage (Decline always wins — set used and exit).
- **In-Home reaction** (text reads `<b>Reaction:</b>` without "City"): same shape as a City Reaction; only the `handleEvent` guard differs (`$owner->Location == Game::LOCATION_PLAYER_HOME` instead of `cardInCity($owner)`). See `_03cd20` and the "CityEventCard living in a player's Home" sub-pattern below.

A multi-stage CardReaction does NOT need new GameState classes, `states.inc.php` edits, or per-state JS files — the framework's built-in `playerReaction` state hosts all stages. Only promote to dedicated State classes if a stage needs richer UI (board highlighting, multi-select, dragging) that can't be expressed as a flat button list.
