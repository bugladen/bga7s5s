> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern B — Passive Trait Grant via Equip/Unequip Pair

For "the equipped character gains Trait," you need *both* halves of the pair. Forgetting `EventAttachmentUnequipped` leaks the trait when the attachment moves off (gets unequipped, attachment destroyed, character destroyed taking the attachment with them).

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        $character->addTrait($event->theah->game, "Musketeer");
    }

    if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        $character->removeTrait($event->theah->game, "Musketeer");
    }
}
```

References: `_01075` (Tabard → Musketeer), `_01198` (Guild Triskelion → Duelist), `tac/_02047` (Temnota → Sorcerer; technically a CityAttachment but same pattern).
