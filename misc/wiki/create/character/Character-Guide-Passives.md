# 04 — Passives (Pattern A)

← [[03 — Classify|Character Guide Classify The Printed Text]] · [[Index|Implementing a Character Card]] · Next: [[05 — Actions|Character Guide Actions]]

A **passive** has no player choice. When something happens in the game, your Character's code reacts automatically.

You almost always implement this by overriding `handleEvent` on the **card class** (`_NNNNN.php`). Some bans use `eventCheck` instead (see below).

## The three gates

Every passive body should answer three questions:

1. **Is this the right event?** — `if ($event instanceof EventCharacterWounded)`
2. **Is this about me?** — `$event->characterId == $this->Id` / `$event->actorId == $this->Id` / `$event->cardId == $this->Id` (read the event class for the real field names)
3. **Am I still in play?** — for phase / ongoing bonuses:

```php
if ($this->ControllerId == 0) {
    return;
}
if ($game->characterIsInDiscardOrLocker($this)) {
    return;
}
```

**Why not `isControlled()` alone?** A destroyed Character can still have a non-zero `ControllerId`, so `isControlled()` can stay true after they are out of play.

Also always:

```php
parent::handleEvent($event);   // first line of your override
```

## Ise: +1 Combat while wounded

Shape: private flag + only emit a modified event when the flag **flips**.

```php
public bool $WoundedCombatBonusApplied = false;

public function handleEvent(Event $event)
{
    parent::handleEvent($event);   // updates $this->Wounds first

    if (($event instanceof EventCharacterWounded || $event instanceof EventCharacterHealed)
        && $event->characterId == $this->Id)
    {
        $this->recomputeWoundedCombatBonus($event->theah);
    }
}
```

Inside the helper:

1. Skip if discard/locker or `IsDying`.
2. `$shouldHaveBonus = $this->Wounds > 0`.
3. If should apply and flag is false → queue `createCharacterCombatModifiedEvent(... + 1)` and set flag true.
4. If should not apply and flag is true → queue `... - 1` and set flag false.

Full file: `modules/php/cards/faf/_03016.php`.

## Aldo: Influence tracks location Renown

Aldo listens for moves and Renown changes, then queues `createCharacterInfluenceModifiedEvent` so Influence equals base Influence + current location Renown (0 at Home).

Full file: `modules/php/cards/_7s5s/_01007.php`.

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

For Combat / Finesse / Influence, queue the matching factory:

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

## Preventing wounds (`eventCheck`)

Some passives say opponents' abilities cannot wound this character. Prefer overriding `eventCheck` on `EventCharacterBeingWounded` and setting `$event->wounds = 0` when the gate matches — not skipping `parent::handleEvent` on the past-tense wound event.

Reference: Kaspar `_03014`.

## Muster / Approach triggers

"When this character musters" / "When Owner is played" must listen to **both**:

- `EventCharacterMustered`
- `EventApproachCharacterPlayed`

Hooking only one silently misses the other path. Reference: Joern `_03015`, Kaj Relic Raider `Reaction_04042`.

## "While equipped with a Weapon, +N Stat"

Do **not** invent a bool that ignores Offhand / multi-Weapon edge cases. Count Weapons in `$this->Attachments` after equip/unequip events, apply +N when count transitions to `1`, undo when count transitions to `0`.

Reference: Rena `_01040`, Íñigo `_03039`.

## Location-counting passives

`EventCardMoved` fires **before** the DB location update (`runEventHubAfterCards = true`). Helpers that call `getCharactersAtLocation` see the *pre-move* board. Established cards either:

- pass a `+1`/`-1` adjustment (Edeline `_01037`), or
- thread the event into the helper to exclude/include the mover (Angeline `_03026`).

Also: **never** count opposing characters at `LOCATION_PLAYER_HOME` with `getOpposingCharactersAtLocation` — Home is a shared location string across players.

## What passives are *not*

| Situation | Use instead |
|---|---|
| Player chooses yes/no or a target after a trigger | [Reaction](Character-Guide-Reactions) |
| Player activates from the High Drama menu | [Action](Character-Guide-Actions) |
| Optional duel ability the player picks | [Technique](Character-Guide-Techniques-And-Maneuvers) |

## Next

If your Character has an Action → [[05 — Actions|Character Guide Actions]]  
If only a Reaction → [[06 — Reactions|Character Guide Reactions]]
