> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern B — Passive Grant via Equip/Unequip pair

For "the equipped character gains <Trait>" or analogous static buffs, you need the *pair* of events. Forgetting `EventAttachmentUnequipped` leaks the trait when the attachment moves off:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        $character->addTrait($event->theah->game, "Duelist");
    }

    if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        $character->removeTrait($event->theah->game, "Duelist");
    }
}
```

References:
- `_01198` (Guild Triskelion → Duelist) — CityAttachment
- `bas/_04cd15` (Syrneth Puzzle Box → Sorcerer) — CityAttachment + City Action combo
- `tac/_02047` (Temnota → Sorcerer) — FactionAttachment sibling; same equip/unequip pair
