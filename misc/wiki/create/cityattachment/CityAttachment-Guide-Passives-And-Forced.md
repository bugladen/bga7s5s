# 04 — Passives and Forced

← [[03 — Classify|CityAttachment Guide Classify The Printed Text]] · [[Index|Implementing a CityAttachment Card]] · Next: [[05 — Actions|CityAttachment Guide Actions]]

These clauses live on the **attachment card class** (`handleEvent`). No Action or Reaction file.

Always call `parent::handleEvent($event)` first — except Pattern G cancel branches that set `$event->canceled = true` and `return` before parent (mirror Silver Spine).

## Three gates for "equipped character" Forced effects

When you write a Forced that cares about the host character, check:

1. **Event type** — `EventAttachmentEquipped`, `EventCharacterBeingWounded`, …
2. **This attachment** — `$event->attachmentId == $this->Id` for equip/unequip on *this* card
3. **Equipped character involvement** — `$this->isAttached() && $event-><actorId> == $this->AttachedToId`

Without `isAttached()`, `$this->AttachedToId == 0` can accidentally match bogus targets.

## Forced on equip ("When a character equips this card • Wound them")

Mirror: Devil Jonah's Bones `_03cd05`.

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
    {
        $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
            $event->characterId,
            $this->Id,                  // source: the attachment
            1,
            $this->getInjectCode()
        );
        $event->theah->queueEvent($woundEvent);
    }
}
```

**Why Forced in `handleEvent`, not a Reaction?** Forced is mandatory and needs no player choice. A Reaction would require `setUsed` / `isAvailable` plumbing that does not fit. Precedent: Tabard `_01075`, Penya `_03cd01`, Bones `_03cd05`.

**Wound source = `$this->Id`.** Provenance stays on the attachment.

## Passive trait grant ("gains Duelist")

When text says the equipped character **gains a Trait**, implement **both** halves:

| Event | What to do |
|---|---|
| `EventAttachmentEquipped` (this attachment) | `$character->addTrait($game, 'Duelist')` |
| `EventAttachmentUnequipped` (this attachment) | `$character->removeTrait($game, 'Duelist')` |

Forgetting unequip **leaks** the trait when the attachment leaves.

Mirror: Guild Triskelion `_01198`. (Faction twin: Temnota `tac/_02047`.)

## Equip restrictions ("May only equip to …")

Override `canAttachTo(Character $c): bool` for the UI filter. For server enforcement, also block illegal equips in `eventCheck` on `EventAttachmentEquipping` (throw `\Bga\GameFramework\UserException`).

City Attachments use the same dual-gate idea as Faction Attachments — see [[FactionAttachment Equip Restrictions|FactionAttachment Guide Equip Restrictions]] if your text has a strict "may only equip to" clause. Many city artifacts equip freely and skip this.

## Forced once-per-Day cancel of opponent Risks (Pattern G)

Card text shape: "**Forced:** Each Day, the first time an opponent's Risk targets the equipped character • Cancel the effects."

Canonical: Silver Spine `_03cd21` (modeled on Maryam `_01186`, which is a CityCharacter).

A Risk that targets a character can fire several event types. You must intercept **all** of them that your card needs. Silver Spine covers move, engage, challenge, wound, character-targeted, and equipping — open `_03cd21.php` and copy the branches, do not invent a shorter list.

Key adapters when porting a *character* cancel to an *attachment*:

1. Target field = `$this->AttachedToId`, not `$this->Id`.
2. Gate every branch with `$this->isAttached()`.
3. "Opponent's Risk" → source is a `Risk` + `IRiskThatTargetsCharacters` and `$source->ControllerId != $this->ControllerId`.
4. Once-per-Day condition lives on the **attachment**, not the character. Clear at `EventDuskEndOfDay`.
5. Canceling `EventAttachmentEquipping` needs a **manual discard** of the would-be attachment (otherwise it sits in limbo). Copy Silver Spine's discard branch.

### Chip UI (once-per-Day spent marker)

If you add a `<CARD>_ABILITY_USED` condition, you also need PHP constant + JS constant + Notifications handlers + a chip render block in `Utilities.js` **inside `createAttachmentCard`** (attachments have no generic conditions loop) + CSS. Use `card.divId` for **both** chip placement and removal — Maryam/Carmella have a known id-mismatch bug; do not copy their removal handler. Anchor chips at `left: 0; top: 0` so they stay visible when attachments are splayed.

Full checklist lives in the skill companion `pattern-g.md` and in `_03cd21` / journal notes around Silver Spine.

## Checklist for this pattern

- [ ] Forced uses card `handleEvent`, not a Reaction class
- [ ] `isAttached()` gates "equipped character" effects
- [ ] Trait grants have both equip **and** unequip halves
- [ ] Pattern G: all needed Risk-target event types + dusk clear + chip plumbing if used
- [ ] Equip-restriction cards gate both UI and server

## Next

Player-chosen Actions → [[05 — Actions|CityAttachment Guide Actions]]
