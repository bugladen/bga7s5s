# OffHand Property Audit (triggered by _02026 Croc de Lion)

## Bug Found and Fixed

`characterHasAttachmentOfType()` in `UtilitiesTrait.php` was counting OffHand attachments in the normal type-limit `$count`. This meant if a character had an OffHand Weapon (e.g., Croc de Lion), equipping a normal Weapon was blocked — even though the rule explicitly says "Offhand attachments do not count against the limit of one Armor and one Weapon per character."

**WHY the bug existed:** The `$count` variable counted ALL attachments matching the trait type, regardless of OffHand status. The OffHand logic only kicked in when `$newAttachmentIsOffHand = true` (checking `$offHandCount`), but when equipping a non-OffHand attachment, the code returned `$count > 0` which included OffHand attachments in the count.

**The fix:** Added `&& !$attachment->OffHand` to the `$count` increment condition. Now `$count` only tallies non-OffHand attachments of the type, so OffHand attachments truly occupy their own separate slot.

### Scenario verification:
1. Has normal Weapon, equipping OffHand Weapon → allowed (offHandCount=0) ✓
2. Has OffHand Weapon, equipping normal Weapon → allowed (count=0, OffHand excluded) ✓
3. Has normal Weapon, equipping another normal Weapon → blocked (count=1) ✓
4. Has OffHand Weapon, equipping another OffHand attachment → blocked (offHandCount=1) ✓
5. Has normal Weapon AND OffHand Weapon, equipping another normal Weapon → blocked (count=1) ✓

## Existing OffHand cards
- `_01047` Kaspar's Panzerhand (Armor, Eisenfaust, Unique)
- `_02017` Panzerhand (Armor, Eisenfaust)
- `_02026` Croc de Lion (Weapon, Melee, Dagger, Unique)

## No JS-side issues
The `offHand` property is passed via `getPropertyArray()` but never referenced in JavaScript for equip validation — all restriction logic runs server-side in `hasEquipRestrictions()`. Clean.
