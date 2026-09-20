# 05 — Passives and Forced

← [[04 — Equip restrictions|FactionAttachment Guide Equip Restrictions]] · [[Index|Implementing a FactionAttachment Card]] · Next: [[06 — Actions|FactionAttachment Guide Actions]]

These clauses live on the **attachment card class** (`handleEvent` / sometimes `eventCheck` on `Character`). No Action or Reaction file.

Always call `parent::handleEvent($event)` first.

## Passive trait grant ("gains Musketeer")

When text says the equipped character **gains a Trait**, implement **both** halves:

| Event | What to do |
|---|---|
| `EventAttachmentEquipped` (this attachment) | `$character->addTrait($game, 'Musketeer')` |
| `EventAttachmentUnequipped` (this attachment) | `$character->removeTrait($game, 'Musketeer')` |

Forgetting unequip **leaks** the trait when the attachment leaves (destroyed, sunk, character dies, …).

Mirror: Tabard `_01075`, Guild Triskelion `_01198`, El Gato's Mask `_03043` (Scoundrel).

## Conditional auto-destroy ("If they lose their Weapon…")

Watch `EventAttachmentUnequipped` on *other* attachments. If the host no longer satisfies the prerequisite, notify, then queue unequip-self + discard-self:

```php
$unequipEvent = EventFactory::createAttachmentUnequippedEvent(
    $this->ControllerId, $owner->Id, $this->Id
);
$discardEvent = EventFactory::createAttachmentDiscardedFromPlayEvent(
    $this, $this->Id, $asEffect = true
);
```

Mirror: Unsavory Salve `_01050`.

## While-equipped lasting restrictions (conditions)

When text is a **lasting restriction while equipped** (not a trait grant, not "for the remainder of the duel"):

1. On equip → stamp a `Game::*_CONDITION` on the character.
2. On unequip → clear that condition.
3. Enforce the rule in **`Character::eventCheck`** (not only on the attachment).

**Why a condition?** If the attachment leaves `$theah->cards` mid-resolve (including during its own "Sink this card" Action), an Attachment-only gate disappears. The condition on the character stays until unequip clears it. Tooltips also show the condition string for free.

| Printed idea | Canonical card | Clears on |
|---|---|---|
| Opponents' abilities cannot move this character **Home** | Lodestone `_03065` / `LODESTONE_CONDITION` | Unequip |
| Equipped character **cannot move** (any destination) | Shackles `_03066` / `SHACKLES_CONDITION` | Unequip |
| −1 Finesse, cannot move/swap **for the remainder of the duel** | Harpoon `_03064` — see [[08|FactionAttachment Guide Techniques And Maneuvers]] | **Duel end**, not unequip |

### Opponent Home-block (Lodestone)

Detect "opponent's ability" via the move's **`sourceId`** → source card's `ControllerId`.

Do **not** use `EventCardMoving::$initiatingPlayerId` — some Maneuvers set that to the **victim**, which would wrongly treat enemy moves as friendly (or the reverse).

Own abilities (including Lodestone's own City Action) pass the owner's card as `sourceId` and must still be allowed.

### All-moves block (Shackles)

Gate `EventCardMoving` when the character has the condition and `! $event->unstoppable`. No swap gate unless the text also bans swaps.

## Forced effects (no player choice)

Forced text has **no** Action/Reaction class. Override `handleEvent` on the attachment and react to the matching event.

Examples:

| Text | Event | Mirror |
|---|---|---|
| Wound when something En Gardes | (see card) | Legion's Caress `_01021` |
| At the end of High Drama • Destroy this attachment | `EventHighDramaPhaseEnd` | Shackles `_03066` (also `_01025_Burden` for the trigger style) |

### Forced destroy at end of High Drama

```php
if ($event instanceof EventHighDramaPhaseEnd && $this->isAttached())
{
    $owner = $this->attachedTo($event->theah);
    if ($owner instanceof Character)
    {
        // notify why, then:
        $event->theah->queueEvent(EventFactory::createAttachmentUnequippedEvent(
            $this->ControllerId, $owner->Id, $this->Id
        ));
        $event->theah->queueEvent(EventFactory::createAttachmentDiscardedFromPlayEvent(
            $this, $this->Id, $asEffect = true
        ));
    }
}
```

Use **`EventHighDramaPhaseEnd`**, not dusk / phase-start. Unequip first so while-equipped conditions clear.

## JS for conditions

If you add a new `Game::*_CONDITION`:

1. Constant string in PHP and the matching JS constant must be **identical**.
2. Register `*ConditionStarted` / `*ConditionEnded` notification handlers that push/filter `card.conditions` and `refreshTooltipForCard`.
3. Mirror Soline / Lodestone / Harpoon notification shape.

## Checklist for this pattern

- [ ] Trait grants have both equip **and** unequip halves
- [ ] While-equipped locks use conditions + `Character::eventCheck`
- [ ] Lodestone-style opponent detection uses `sourceId`, not `initiatingPlayerId`
- [ ] Forced destroy uses `EventHighDramaPhaseEnd` + unequip + discard
- [ ] Duel-only lasting effects are **not** implemented here — see Techniques

## Next

Player-chosen Actions → [[06 — Actions|FactionAttachment Guide Actions]]
