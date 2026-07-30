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

## Pattern B''' — Duel-scoped conditional stat / gamble reveal

When printed text is **passive** (no Forced / Action / Reaction / Technique keyword) and scoped to a duel condition — e.g. Assassin's Garb `_04006`:

> During a duel, while their adversary is wounded, the equipped character gains +1[Finesse] and reveals an additional card when they gamble.

Implement on the **attachment card class** only. Do **not** invent an Action/Reaction/Technique file.

### Do not use constructor `*Modifier`

`FinesseModifier` / `CombatModifier` / etc. are **always-on while equipped**. Conditional "during a duel / while X" bonuses must be applied and cleared dynamically.

### Stat half — Benci-style applied flag

Mirror character `_04001` (Benci) / Elena `_03004`: keep a `public bool $…BonusApplied` (or int delta like Elena's `$FinesseBonus`) on the attachment. Apply/clear via `createCharacterFinesseModifedEvent` / `createCharacterCombatModifiedEvent` (note factory typo `Modifed`).

**Recompute when the condition can change:**

| Event | Why |
|---|---|
| `EventDuelStarted` | Challenge wounds may already be on the adversary before the duel; IN_DUEL is set in `stDuelStarted` before this event queues |
| `EventCharacterWounded` / `EventCharacterHealed` | Adversary wound state flips mid-duel |
| `EventChallengerSwapped` / `EventDefenderSwapped` | Equipped character left the duel, or adversary identity changed |
| `EventAttachmentEquipped` | Mid-duel equip edge case |
| `EventDuelEnd` | Clear if still applied |
| `EventAttachmentUnequipped` | Clear if still applied |

**Gate for "should have bonus":** `isAttached()` + `Game::IN_DUEL` + equipped character is challenger or defender (`getDuelChallengerId` / `getDuelDefenderId`) + condition (e.g. adversary `Wounds > 0` via `getDuelOpponentId`).

**Stale wounds (WHY):** `EventCharacterWounded` / `EventCharacterHealed` run card `handleEvent` without guaranteed order. If the wounded character has not handled yet, `$character->Wounds` is stale — apply the event delta when `! $event->characterHandled` (same helper shape as Benci).

**Unequip AttachedToId footgun (WHY):** `EventAttachmentUnequipped` does **not** set `runEventHubAfterCards`, so EventHub runs **first** and zeroes `$this->AttachedToId` before the attachment's `handleEvent`. Undo the bonus using `$event->characterId`, not `$this->AttachedToId`. Skip restore/undo when `characterIsInDiscardOrLocker`.

**No Game condition / JS by default:** Benci does not stamp a `Game::*_CONDITION` for the combat bonus — the `*Modified` event updates the stat UI. Add a Soline/Harpoon condition only if Eddie wants a tooltip string.

**Not challenge-phase:** "During a duel" starts at `EventDuelStarted`. Do not hook only `EventGenerateChallengeThreat` — that misses mid-duel Finesse uses (gamble cap via `ModifiedFinesse - gamblesCount`, comparisons, UI).

### Gamble +1 half — `getNumberOfGambleCardsToReveal`

Base reveal count is **2** + sum of every card's `getNumberOfGambleCardsToReveal` (`Theah::getNumberOfGambleCardsToReveal`). Override on the attachment:

```php
public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
{
    $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);

    if ($this->isAttached()
        && $actor->Id == $this->AttachedToId
        && /* same duel condition, e.g. adversary wounded */)
    {
        $count += 1;
        $explanations[] = sprintf($theah->game->translate("%s: +1 …"), $this->getInjectCode());
    }

    return $count;
}
```

No applied-flag needed — evaluated at gamble time. Unconditional "when equipped character gambles" exemplars: Gallegos Blade `_01101`, Devil Jonah `_03cd05`, Ivy `_02042`, Sarafina `_01010`.

**Both halves can stack on one card** (`_04006`): +1 Finesse (more gambles allowed) **and** +1 card revealed per gamble — they are separate systems; implementing only one is wrong when both are printed.

References: `bas/_04006.php` (canonical attachment B'''), `_04001` (flag + wound delta — character host), `_01101` (always-on gamble +1), `_03004` (duel Finesse recomputed from dueling line).
