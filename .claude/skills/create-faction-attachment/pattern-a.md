> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern A — Equip Restriction ("May only equip to your X")

The dual-gate pattern: `eventCheck` enforces at the framework level when equip is actually attempted; `canAttachTo` gates the UI/discoverability before the player tries. **Implement both.**

```php
public function eventCheck(Event $event)
{
    parent::eventCheck($event);

    if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        if (! $character->hasTrait("Strega"))
        {
            throw new UserException($event->theah->game->translate("Matushka's Shears can only be equipped to a Strega."));
        }
    }
}

public function canAttachTo(Character $character): bool
{
    if (! parent::canAttachTo($character))
    {
        return false;
    }

    return $character->hasTrait("Strega");
}
```

References: `_01073` (Duelist), `_01075` (non-Diplomat — note the inversion), `_03007` (Strega).

**Use `\Bga\GameFramework\UserException`, not `\BgaUserException`.** Older code uses `BgaUserException`; new code should use the framework path. (Memory feedback.) Older files (`_01050`, `_01073`, `_01075`, `_02006`, …) still throw `\BgaUserException` for historical reasons — don't follow that example, follow memory.

### Compound restriction ("must have a Weapon")

When the restriction is "the target already has another attachment," check via a character helper rather than re-iterating equipment in your card. `_01050` (Unsavory Salve) calls `$character->hasWeaponEquipped($event->theah)`:

```php
if (! $character->hasWeaponEquipped($event->theah))
{
    throw new UserException(/* "must have a weapon equipped" */);
}
```

Grep for the helper before writing one — `hasWeaponEquipped`, `hasOffHand`, etc. are already there.

### Auto-destroy when prerequisite is lost

Parenthetical "(If they lose their Weapon, destroy this card.)" on `_01050`: watch `EventAttachmentUnequipped` and check whether the equipped character STILL satisfies the constraint. If not, queue an unequip-self + discard-self:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventAttachmentUnequipped && $this->isAttached())
    {
        $owner = $this->attachedTo($event->theah);
        if ($owner instanceof Character && ! $owner->hasWeaponEquipped($event->theah))
        {
            $event->theah->game->notify->all("message", clienttranslate('${attachment_inject_code} will be discarded from ${character_inject_code} because they no longer have a weapon equipped.'), [
                "attachment_inject_code" => $this->getInjectCode(),
                "character_inject_code" => $owner->getInjectCode(),
            ]);

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($this->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($unequipEvent);

            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($this->ControllerId, $this->Id, $this->Location, $this->Id, $asEffect = true);
            $event->theah->queueEvent($discardEvent);
        }
    }
}
```

Reference: `_01050` (Unsavory Salve).
