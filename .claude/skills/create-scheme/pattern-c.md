> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern C — Multi-player sequential loop

When the text says "Then, each opponent does X", queue one transition per opponent (in turn order) after your own resolve completes. Each opponent's state runs, calls `nextState("")`, and the next opponent's transition fires.

```php
if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_NNNNN_PLAYER1)
{
    $sql = "SELECT player_id FROM player ORDER BY turn_order";
    $list = $game->getCollectionFromDB($sql);
    foreach ($list as $playerId => $player)
    {
        if ($player['player_id'] == $this->ControllerId)
        {
            continue;
        }

        $transition = EventFactory::createTransitionEvent($playerId, $this->Id, "NNNNN_2");
        $transition->priority = Event::HIGH_PRIORITY;   // higher than the MEDIUM owner-resolve transition
        $game->theah->queueEvent($transition);
    }
}
```

Reference: `_01151` — first state is the owner's pick, second state (`"01151_2"`) is each opponent's pick, queued per-opponent in turn order with `HIGH_PRIORITY` so they fire in order.
