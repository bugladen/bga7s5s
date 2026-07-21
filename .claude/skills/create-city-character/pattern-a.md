> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern A — Hard ban via `canIntervene` / `canChallenge` + `eventCheck`

For text like "Penya cannot intervene." or "X cannot be challenged" — there are two layers, both of which Penya implements:

1. **Override the predicate** — `canIntervene()`, `canChallenge()`, `canPressure()`, etc. Return `false`. The engine reads this when offering options to the player. Matches the base `Character::canIntervene` pattern (`Character.php:78-81`). `Theah::interventionCheck` calls it.
2. **Override `eventCheck(Event)`** — throw a `\Bga\GameFramework\UserException` when the engine *processes* the banned event. This is a belt-and-suspenders backstop for code paths that bypass the predicate (forced retargeting, copied effects, future card interactions). Call `parent::eventCheck($event)` first.

Penya's intervention ban:

```php
public function canIntervene(): bool
{
    return false;
}

public function eventCheck(Event $event)
{
    parent::eventCheck($event);

    if ($event instanceof EventCharacterIntervened && $event->newTargetId == $this->Id)
    {
        throw new UserException($event->theah->game->translate("Penya cannot intervene."));
    }
}
```

The field you check on the event (`newTargetId`, `characterId`, `actorId`, etc.) depends on the event — read the event class. Most "this character is being …" events use `characterId`; intervention specifically tracks `newTargetId` because intervention re-targets an in-flight effect.

**Use `UserException` from `Bga\GameFramework\UserException`** — `BgaUserException` is deprecated.

**Why a separate `eventCheck` if `canIntervene` already returns `false`?** Predicates filter the UI; `eventCheck` filters the engine. Many edge cases (zombie passes, copied actions, AI driving things) skip the UI predicate. The thrown exception is the last line of defense.
