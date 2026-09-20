# 04 — Passives (Pattern A)

← [03 — Classify](03-classify-text.md) · [Index](README.md) · Next: [05 — Actions](05-actions.md)

A **passive** has no player choice. When something happens in the game, your Leader's code reacts automatically.

You almost always implement this by overriding `handleEvent` on the **card class** (`_NNNNN.php`).

## The three gates

Every passive body should answer three questions:

1. **Is this the right event?** — `if ($event instanceof EventPhaseDawnEnding)`
2. **Is this about me?** — `$event->characterId == $this->Id` / `$event->actorId == $this->Id` / `$event->playerId == $this->ControllerId` (read the event class for the real field names)
3. **Am I still in play?** — for Leaders on phase events, use:

```php
if ($game->characterIsInDiscardOrLocker($this)) {
    return;
}
```

**Why not `isControlled()` alone?** A destroyed Leader still has a non-zero `ControllerId`, so `isControlled()` can stay true after they are out of play.

Also always:

```php
parent::handleEvent($event);   // first line of your override
```

## Cesca: end-of-Dawn draw

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventPhaseDawnEnding && $this->ControllerId > 0)
    {
        $game = $event->theah->game;
        if ($game->characterIsInDiscardOrLocker($this))
        {
            return;
        }

        $game->notify->all("message", clienttranslate('${leader_inject_code}: ${player_name} draws five cards at the end of Dawn.'), [
            "leader_inject_code" => $this->getInjectCode(),
            "player_name" => $game->getPlayerNameById($this->ControllerId),
        ]);

        for ($i = 0; $i < 5; $i++)
        {
            $drawEvent = EventFactory::createCardDrawnEvent($this->ControllerId, $this->getInjectCode());
            $event->theah->queueEvent($drawEvent);
        }
    }
}
```

Notes:

- Drawing N cards = queue N `createCardDrawnEvent` calls (the framework draws one per event).
- Use `$this->getInjectCode()` so the game log links back to the Leader.

## Common phase events

| Printed timing | Event class |
|---|---|
| Beginning of Dawn | `EventPhaseDawnBeginning` |
| End of Dawn | `EventPhaseDawnEnding` |
| Beginning of Dusk | `EventDuskPhaseBegin` |
| End of Dusk / end of day | `EventDuskPhaseEnd` / `EventDuskEndOfDay` |
| New day | `EventNewDay` |
| Duel started / ended | `EventDuelStarted` / `EventDuelEnd` |

## Changing stats

For Combat / Finesse / Influence / Panache, queue the matching factory:

```php
$mod = EventFactory::createCharacterFinesseModifedEvent(
    $this->ControllerId,
    $character->Id,
    $character->ModifiedFinesse,      // from
    $character->ModifiedFinesse - 1,  // to
    $this->getInjectCode()
);
$theah->queueEvent($mod);
```

Note the historic typo in the Finesse factory name: `Modifed` (one "i").

**Resolve is special.** There is no Resolve modified event factory. Mutate `$this->ModifiedResolve` directly, keep a private bool flag so attachments do not desync you, and emit a `characterResolveModified` client notification if the Resolve chip must update on screen. See Joern `_03015` and Danilo `_04002`.

## "While wounded, +1 Combat" shape

Use a private flag (`$WoundedCombatBonusApplied`). On `EventCharacterWounded` / `EventCharacterHealed` for `$this->Id`:

1. Call `parent::handleEvent` first (so `$this->Wounds` is current).
2. Recompute whether the bonus should apply.
3. Queue `±1` Combat modified **only when the flag flips**.

Reference: Schwester Ise `_03016`.

## Preventing wounds (`eventCheck`)

Some passives say opponents' abilities cannot wound this character. Prefer overriding `eventCheck` on `EventCharacterBeingWounded` and setting `$event->wounds = 0` when the gate matches — not skipping `parent::handleEvent` on the past-tense wound event.

Reference: Kaspar `_03014`.

## Muster / Approach triggers

"When this character musters" must listen to **both**:

- `EventCharacterMustered`
- `EventApproachCharacterPlayed`

Hooking only one silently misses the other path. Reference: Joern `_03015`.

## What passives are *not*

| Situation | Use instead |
|---|---|
| Player chooses yes/no or a target after a trigger | [Reaction](06-reactions.md) |
| Player activates from the High Drama menu | [Action](05-actions.md) |
| Optional duel ability the player picks | [Technique](07-techniques-maneuvers.md) |

## Next

If your Leader has an Action → [05 — Actions](05-actions.md)  
If only a Reaction → [06 — Reactions](06-reactions.md)
