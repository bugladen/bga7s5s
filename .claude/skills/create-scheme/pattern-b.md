> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern B — When-Revealed effect

Override `hasWhenRevealedEffect()` so the framework knows to fire `EventCardWhenRevealedEffect` against this scheme *before* any scheme's resolve.

```php
public function hasWhenRevealedEffect(): bool
{
    return true;
}

public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventCardWhenRevealedEffect && $event->cardId == $this->Id)
    {
        // pre-resolve cleanup or setup work
    }

    if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
    {
        // normal resolve
    }
}
```

Reference: `_01151` (Shifting Tides) — When Revealed discards all city cards from city locations *before* any other scheme resolves.
