# 04 — Equip restrictions

← [[03 — Classify|FactionAttachment Guide Classify The Printed Text]] · [[Index|Implementing a FactionAttachment Card]] · Next: [[05 — Passives and Forced|FactionAttachment Guide Passives And Forced]]

Many attachments say **"May only equip to your …"** (Duelist, Strega, character with a Weapon, …). That restriction is **not** a separate Action class — it lives on the attachment card class.

## The dual-gate rule (memorize this)

Implement **both**:

| Method | When it runs | What it does |
|---|---|---|
| `canAttachTo(Character)` | Before / while choosing a target | Greys out illegal characters in the UI |
| `eventCheck(EventAttachmentEquipping)` | When the equip event actually fires | Throws `UserException` if illegal |

If you only write `canAttachTo`, a crafted client request can still equip illegally.  
If you only write `eventCheck`, the UI offers illegal targets and players get a confusing error.

Always call `parent::…` first in both methods.

## Basic template (trait gate)

```php
public function eventCheck(Event $event)
{
    parent::eventCheck($event);

    if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        if (! $character->hasTrait('Duelist'))
        {
            throw new \Bga\GameFramework\UserException(
                $event->theah->game->translate('This attachment can only be equipped to a Duelist.')
            );
        }
    }
}

public function canAttachTo(Character $character): bool
{
    if (! parent::canAttachTo($character))
    {
        return false;
    }

    return $character->hasTrait('Duelist');
}
```

Mirror: Cavalier Hat `_01073` (Duelist), Matushka's Shears `_03007` (Strega).

**Throw `\Bga\GameFramework\UserException`**, not the older `\BgaUserException`. Some old files still use the old name — do not copy that.

## Inverted restrictions ("non-Diplomat")

Tabard `_01075` equips only if the character does **not** have Diplomat:

```php
return ! $character->hasTrait('Diplomat');
```

Same dual-gate shape — only the boolean flips.

## "Must already have a Weapon"

Unsavory Salve `_01050` checks an existing attachment via a character helper:

```php
if (! $character->hasWeaponEquipped($event->theah))
{
    throw new \Bga\GameFramework\UserException(/* … */);
}
```

Grep for helpers (`hasWeaponEquipped`, `hasOffHand`, …) before writing your own loop.

When the text also says **(If they lose their Weapon, destroy this card.)**, that is a **separate** Forced/passive clause — see [[05|FactionAttachment Guide Passives And Forced]].

## Opponent-equip (`CanEquipToOpponents`)

Rare. Printed text equips onto an **opposing** character (Legion's Caress `_01021`, Shackles `_03066`).

1. Set `$this->CanEquipToOpponents = true` in the constructor.
2. Still implement the dual gate for any other printed limits (non-Leader, City only, Finesse compare, …).

### Framework quirks beginners miss

- High Drama Equip stores the equip **target** in `Game::CHOSEN_PERFORMER`. With opponent-equip, that target can be an enemy. There is **no** separate "your performer" picker in the normal Equip flow.
- After equip, `EventHub` sets `$attachment->ControllerId` to the **equipping player**, not the victim. Effects that say "your attachment" still use the equipper. Effects about the victim's character use `$attachedCharacter->ControllerId`.
- **"Opposing"** = different controller **and** same location.

### "Less [Stat] than your performer"

Printed text may still say "your performer" even though BGA collapsed performer → target. Canonical fix (Shackles `_03066`):

- Target must be opposing.
- You must control a character **at the target's location** with greater `ModifiedFinesse` (that ally is the implicit performer).

Do **not** invent a two-step Equip UI unless a maintainer asks for it.

`canAttachTo` can often only check "is opposing"; the Finesse comparison that needs Theah belongs in `eventCheck`.

## Equip-discount footgun

Abilities that tax "when an opponent equips" must key off **`$attachment->ControllerId`**, not `$performer->ControllerId`.

**Why:** with `CanEquipToOpponents`, `performer` / `CHOSEN_PERFORMER` is the **target** (often the opponent). A performer-based check falsely taxes *your* Shackles onto them.

Fixed shape: Smuggling Run `_03063`. Makepeace `_01092` has different printed wording — do not copy blindly.

## Checklist for this pattern

- [ ] Both `canAttachTo` and `eventCheck` implemented
- [ ] `parent::` called first
- [ ] `UserException` uses the framework class
- [ ] Opponent-equip sets `CanEquipToOpponents = true` when required
- [ ] Any "destroy if prerequisite lost" clause planned for [[05|FactionAttachment Guide Passives And Forced]]

## Next

Passive grants, while-equipped locks, and Forced effects → [[05 — Passives and Forced|FactionAttachment Guide Passives And Forced]]
