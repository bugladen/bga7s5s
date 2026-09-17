# Attachment discard helper + call-site sweep

## Decision
Yes — added `EventFactory::createAttachmentDiscardedFromPlayEvent(Attachment, sourceId, asEffect, ?fromLocation, cityDiscardPlayerId)`.

WHY a helper (not more copy-paste branches):
- Damya 03038b bug was caused by teaching/copying a faction-only destroy recipe.
- Same bug lived in many "destroy any attachment" sites (01174, 01142, 02026b, 03cd18, 01079, 03072, etc.).
- Even sites that already branched (Character::unEquipAllAttachments, AttachmentTypeLimit, Maryam, Silver Spine, Blood Like Winter) duplicated the logic.

## API choices
- Takes `Attachment` object, not ids — needs instanceof CityAttachment + OwnerId/ControllerId/Location.
- City → `createCardAddedToCityDiscardPileEvent`; else → `createCardDiscardedFromPlayEvent(OwnerId, ...)`.
- WHY OwnerId for faction: Shackles/opponent-equip; Character::unEquipAllAttachments already used OwnerId. (ControllerId on equip stays the equipping player per EventHub, so OwnerId ≈ ControllerId for Shackles, but OwnerId is the durable rule.)
- Optional `$fromLocation` for call sites that pass character location (01079, 01142, Duckfoot).
- Optional `$cityDiscardPlayerId` for cancel-before-equip (Maryam / Silver Spine / Blood Like Winter) where ControllerId may be unset.

## Updated call sites
Destroy/discard attachments: Action_03038b, Character::unEquipAllAttachments, Reaction_AttachmentTypeLimit, _03cd21, _01186, Reaction_02048, Action_01174, Maneuver_01142, Technique_02026b, Reaction_03cd18, Maneuver_01079, Maneuver_03072, Action_03072, _03066, _01050, _01153, Technique_DestroyPlusOneThrust, RiskAttachmentTrait, _01195, Action_01191, Action_01194, FrameworkActionsTrait (Smuggled Item).

Also updated skill docs that taught the old recipe (create-character checklist/SKILL/references, create-risk pattern-a, create-faction-attachment SKILL, create-city-attachment pattern-g).

## Intentionally NOT updated
**Maneuver_01113** — unequip + player-discard is a staging hack for steal-to-hand, then `createCardRemovedFromPlayerDiscardPileEvent` + add to hand. Routing through city discard would break the remove-from-player-discard step and would wrongly fire Eager Blade → Bazaar mid-steal. Needs a separate "move equipped attachment to hand without discard-pile transit" cleanup if Eddie wants it.

Non-attachment city discards (events, characters, city deck sinks) correctly keep the raw city/player discard factories.

## Feelings
This is the right fix for the Eager Blade class of bugs. Slightly nervous about 01113 still being a landmine for city attachments mid-steal — journaled so we don't "helpfully" convert it later without fixing the hand path.
