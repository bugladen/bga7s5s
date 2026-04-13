# Smuggled Item (01187) Audit

## Card Text
> Traits: Artifact
> **Action:** Destroy this card • Equip an attachment from your hand or a City location to this character, ignoring all costs. (It must be available.)

## Verdict: No Issues Found

### How the flow works

1. `Action_01187.handleEvent` on `EventActionTriggered`:
   - Sets `CHOSEN_PERFORMER` to owning character (enforces "to this character")
   - Sets `EQUIP_TYPE` to `SMUGGLED_ITEM_EQUIP_TYPE`
   - Stores Smuggled Item's ID in `SMUGGLED_ITEM_ATTACHMENT_ID`
   - Transitions to `HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_LOCATION`

2. Player chooses Hand or City Location source, then selects an attachment

3. `EnteringPayState` event fires, which calls `getEquipDiscount` in Theah.php — for `SMUGGLED_ITEM_EQUIP_TYPE`, returns full WealthCost as discount → effective cost = 0

4. Player confirms (0 cards selected), `actHighDramaEquipAttachment` in FrameworkActionsTrait runs:
   - Validates attachment location and equip restrictions
   - Verifies 0-wealth payment matches 0 cost
   - Unequips and discards Smuggled Item (events queued in correct order)
   - Equips new attachment

### What checks out

- **Action setup**: `CityAttachment` + `IHasActions` + `ActionTrait` + `Action_01187` extends `AttachmentAction` ✓
- **Destroy this card**: Unequip + discard-to-city-pile pattern matches other "Destroy" cards (e.g., Duckfoot Pistol 01191) ✓
- **From your hand**: `getAttachmentsInHand` checked in both availability and args ✓
- **Or a City location**: `getAvailableAttachmentsAtLocation` checked in both availability and args; correctly returns only unattached attachments ✓
- **To this character**: `CHOSEN_PERFORMER` hardcoded to owning character, no performer selection step exposed to player ✓
- **Ignoring all costs**: `getEquipDiscount` returns full WealthCost for `SMUGGLED_ITEM_EQUIP_TYPE`, making cost 0 ✓
- **It must be available**: `getAvailableAttachmentsAtLocation` filters `!$card->isAttached()`, hand cards inherently available ✓
- **Event ordering**: Destroy (unequip → discard) queued before equip → correct "cost then effect" ordering ✓
- **Back transition**: `backSmuggledItem` → action selection, not performer selection ✓
- **JS side**: `onPaymentConfirmed` handles empty card array (0 cost) correctly, both from-hand and from-play paths work ✓
- **`setUsed` not called**: Correct — card is destroyed, marking used is unnecessary ✓
- **Stats**: InfluenceModifier=1, WealthCost=1, CityCardNumber=11 ✓
- **Equip restrictions**: Checked before equip; Smuggled Item is Artifact trait, doesn't conflict with Weapon/Armor/Attire restrictions ✓
- **Self-exclusion**: Smuggled Item is attached (`isAttached()` true), so it correctly doesn't appear in the "available to equip" lists ✓

### Edge case considered: restriction timing

`hasEquipRestrictions` runs while Smuggled Item is still equipped (line 610, before destruction on line 649). This doesn't cause false positives because Smuggled Item has trait "Artifact", not Weapon/Armor/Attire — it won't conflict with the new attachment's type restrictions. The card says "ignoring all costs", not "ignoring all restrictions", so blocking duplicate Weapon/Armor/Attire is correct behavior.
