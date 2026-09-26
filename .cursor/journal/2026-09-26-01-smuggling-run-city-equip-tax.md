# Smuggling Run city equip tax (Object of Wonder / Let's Haggle)

## Report (clarified)
Eddie used **Let's Haggle** (`Action_01147`) to equip Object of Wonder from the Bazaar. Performer was at the Bazaar opposing Damiya (Smuggling Run owner's Scoundrel). Expected:

- Base WealthCost 2
- Let's Haggle −1 (performer at Bazaar via `_01147::getEquipDiscount`)
- Smuggling Run +1 (opponent equipping onto character opposing Scoundrel → `discount -= 1`)
- **Net cost 2** (tax offsets the Haggle discount)

Got the Haggle discount without the Smuggling Run tax (cost 1).

## Root cause
`_03063::getEquipDiscount` (2026-07-20 CanEquipToOpponents fix) gated "opponent equips" on:

```php
if ($attachment->ControllerId == $this->ControllerId || $attachment->ControllerId == 0)
    return $discount;
```

City-deck / Bazaar attachments have `ControllerId == 0` until `EventAttachmentEquipped` sets it. Let's Haggle calls `getEquipDiscount($performer, $attachment)` in `Action_01147` *before* equip and stores `Game::DISCOUNT` — so Object of Wonder hit the early return and never taxed. Haggle's −1 still applied → net −1 instead of 0.

Object of Wonder's Leader redirect is a red herring — `getRequiredAttachTargetId` runs in `actHighDramaEquipAttachment` *after* payment. Discount uses `CHOSEN_PERFORMER` (city character opposing Damiya).

## Fix
```php
$equippingPlayerId = $attachment->ControllerId > 0
    ? $attachment->ControllerId
    : $performer->ControllerId;
```

WHY hybrid:
- ControllerId > 0: hand / owned — equipper is on the attachment (required for CanEquipToOpponents where performer = target)
- ControllerId == 0: city / Bazaar available — equipper is the performer (Let's Haggle + normal city equip; CityAttachment has no CanEquipToOpponents)

With fix: Haggle +1 discount and Smuggling −1 discount cancel → pay 2.

## Not changed
Makepeace `_01092` never checked attachment ControllerId — city equips already taxed there.

## Docs debt
Skill/wiki "equip-discount footgun" still say "key off `$attachment->ControllerId`" without the city-0 fallback. Update if touching those guides.
