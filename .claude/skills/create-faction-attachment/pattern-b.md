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

## Pattern B'' — While-equipped condition restriction

When printed text is a **lasting restriction while equipped** (not a trait grant, not duel-scoped) — stamp a **`Game::*_CONDITION`** on the equipped character. Do **not** rely on Attachment-only `eventCheck` alone: if the attachment leaves `$theah->cards` mid-resolve (including during its own "Sink this card" City Action or Forced destroy), the gate disappears.

**WHY condition (same rationale as Harpoon / Soline):** tooltip shows the string for free; `Character::eventCheck` is the source of truth; clear on unequip restores cleanly.

### Pick the right while-equipped scope

| Printed text | Canonical | Gate |
|---|---|---|
| Opponent's abilities cannot move the equipped character **Home** | `_03065` Lodestone / `LODESTONE_CONDITION` | `EventCardMoving` → Home **and** source card `ControllerId !=` character controller |
| **Equipped character cannot move** (any destination) | `_03066` Shackles / `SHACKLES_CONDITION` | `EventCardMoving` on this card, respect `unstoppable` (Harpoon-shaped move gate) — **no** swap gate unless text says so |
| Remainder-of-duel cannot move / swap | Pattern E / `_03064` Harpoon | Clear on **DuelEnd**, not unequip — do not use B'' |

**Apply / clear** (on the attachment card class — same Equipped/Unequipped pair as Pattern B):

```php
if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
{
    $character = $event->theah->getCharacterById($event->characterId);
    $character->addCondition(Game::SHACKLES_CONDITION); // or LODESTONE_CONDITION
    $event->theah->game->updateCardObjectInDb($character);
    $event->theah->game->notify->all("shacklesConditionStarted", /* … */, [
        "character_inject_code" => $character->getInjectCode(),
        "cardId" => $character->Id,
    ]);
}

if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
{
    $character = $event->theah->getCharacterById($event->characterId);
    if ($character !== null && $character->hasCondition(Game::SHACKLES_CONDITION))
    {
        $character->removeCondition(Game::SHACKLES_CONDITION);
        $event->theah->game->updateCardObjectInDb($character);
        $event->theah->game->notify->all("shacklesConditionEnded", /* … */, [
            "character_inject_code" => $character->getInjectCode(),
            "cardId" => $character->Id,
        ]);
    }
}
```

**Enforce** in `Character::eventCheck` on the relevant event.

### Opponent-ability detection (Lodestone / ability-scoped gates)

Do **not** use `EventCardMoving::$initiatingPlayerId` to decide "opponent." It is unreliable — `Maneuver_01033` (Move Adversary Home) sets `initiatingPlayerId` to the **victim** (adversary controller), not the ability's controller.

Use the **source card**:

```php
if ($this->hasCondition(Game::LODESTONE_CONDITION)
    && $event instanceof EventCardMoving
    && $event->cardId == $this->Id
    && $event->toLocation == Game::LOCATION_PLAYER_HOME
    && $event->sourceId > 0)
{
    $source = $event->theah->getCardById($event->sourceId);
    if ($source !== null && $source->ControllerId != $this->ControllerId)
    {
        throw new UserException(/* … */);
    }
}
```

Own abilities (including Lodestone's City Action, which passes the attachment as `sourceId`) keep `source->ControllerId == character->ControllerId` and are allowed.

### All-moves while-equipped (Shackles)

```php
if ($this->hasCondition(Game::SHACKLES_CONDITION)
    && $event instanceof EventCardMoving
    && $event->cardId == $this->Id
    && ! $event->unstoppable)
{
    throw new UserException(/* Shackled and cannot move */);
}
```

Same shape as Harpoon's move gate, but the condition clears on **unequip** (including Forced end-of-HD destroy → unequip → discard), not on `EventDuelEnd`.

**Activate-time siblings:** when a deferred ability always does the blocked thing, add `EventManeuverActivated` / `EventTechniqueActivated` `eventCheck` on the conditioned character — same WHY as Harpoon (`Theah::queueEvent` swallows `UserException` into a log message and drops the event).

Move-only text → movers only (`Technique_01036`, `Maneuver_01059`, `Maneuver_01164` actor; `Maneuver_01033` adversary). **Do not** add swap activate-time checks (`Technique_01063Swap`, `Technique_03013`) unless printed text also bans swaps.

**Do not conflate with Pattern E remainder-of-duel:** while-equipped conditions clear on **unequip**, not on `EventDuelEnd`.

**JS:** same Soline/Harpoon shape — constant string must match `Game::*_CONDITION` exactly; register `*ConditionStarted` / `*ConditionEnded` handlers that push/filter `card.conditions` + `refreshTooltipForCard`.

### Forced destroy at end of High Drama

When text is **`<b>Forced:</b> At the end of High Drama • Destroy this attachment`** (often paired with B'' on the same card — `_03066`):

```php
if ($event instanceof EventHighDramaPhaseEnd && $this->isAttached())
{
    $owner = $this->attachedTo($event->theah);
    if ($owner instanceof Character)
    {
        // notify why, then:
        $event->theah->queueEvent(EventFactory::createAttachmentUnequippedEvent($this->ControllerId, $owner->Id, $this->Id));
        $event->theah->queueEvent(EventFactory::createCardDiscardedFromPlayEvent($this->ControllerId, $this->Id, $this->Location, $this->Id, $asEffect = true));
    }
}
```

- Trigger: `EventHighDramaPhaseEnd` (not `EventDuskEndOfDay` / phase-start). Mirror `_01025_Burden`.
- Destroy chain: unequip then discard (`_01153` / `_01050`). Unequip clears the while-equipped condition.
- Use `$this->ControllerId` (equipper) for both events — correct even when attached to an opponent.
