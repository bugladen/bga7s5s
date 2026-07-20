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

When printed text is a **lasting restriction while equipped** (not a trait grant, not duel-scoped) — e.g. **"Opponent's abilities cannot move the equipped character Home"** — stamp a **`Game::*_CONDITION`** on the equipped character. Do **not** rely on Attachment-only `eventCheck` alone: if the attachment leaves `$theah->cards` mid-resolve (including during its own "Sink this card" City Action), the gate disappears.

**WHY condition (same rationale as Harpoon / Soline):** tooltip shows the string for free; `Character::eventCheck` is the source of truth; clear on unequip restores cleanly.

Canonical: **`_03065` (Lodestone)** + `Game::LODESTONE_CONDITION`.

**Apply / clear** (on the attachment card class — same Equipped/Unequipped pair as Pattern B):

```php
if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
{
    $character = $event->theah->getCharacterById($event->characterId);
    $character->addCondition(Game::LODESTONE_CONDITION);
    $event->theah->game->updateCardObjectInDb($character);
    $event->theah->game->notify->all("lodestoneConditionStarted", /* … */, [
        "character_inject_code" => $character->getInjectCode(),
        "cardId" => $character->Id,
    ]);
}

if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
{
    $character = $event->theah->getCharacterById($event->characterId);
    if ($character !== null && $character->hasCondition(Game::LODESTONE_CONDITION))
    {
        $character->removeCondition(Game::LODESTONE_CONDITION);
        $event->theah->game->updateCardObjectInDb($character);
        $event->theah->game->notify->all("lodestoneConditionEnded", /* … */, [
            "character_inject_code" => $character->getInjectCode(),
            "cardId" => $character->Id,
        ]);
    }
}
```

**Enforce** in `Character::eventCheck` on the relevant event (for Lodestone: `EventCardMoving` to `LOCATION_PLAYER_HOME`).

### Opponent-ability detection (critical)

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

**Activate-time siblings:** when a deferred ability always does the blocked thing (e.g. `Maneuver_01033` always moves the adversary Home), add `EventManeuverActivated` / `EventTechniqueActivated` `eventCheck` on the conditioned character — same WHY as Harpoon activate-time checks (`Theah::queueEvent` swallows `UserException` into a log message and drops the event, so a queued-only failure soft-fails mid-flow).

**Do not conflate with Pattern E remainder-of-duel:** while-equipped conditions clear on **unequip**, not on `EventDuelEnd`. No swap gate unless printed text says so.

**JS:** same Soline/Harpoon shape — constant string must match `Game::*_CONDITION` exactly; register `*ConditionStarted` / `*ConditionEnded` handlers that push/filter `card.conditions` + `refreshTooltipForCard`.
