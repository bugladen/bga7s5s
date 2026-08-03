# Attachment Type Limit Reaction

## Context
Eddie: equipping a second Weapon/Armor/Attire threw. Want game-level reaction like CrewCapLimit — after equip, choose which to discard. Initially OffHand left as hard exception; Eddie then asked for the same discard-choice treatment for OffHand.

## Approach
1. New `Reaction_AttachmentTypeLimit` (GameReaction), registered in `Theah::buildCity` alongside CrewCapLimit/NameGate.
2. Trigger on `EventAttachmentEquipped` (after Hub has attached the card — Equipped has `runEventHubAfterCards=false`, so Hub runs before reactions).
3. Two independent limit groups:
   - Non-OffHand Weapon / Armor / Attire: count > 1 → offer discard among that type
   - OffHand (any): count > 1 → offer discard among OffHands
4. Buttons: Discard {name} for each attachment in an over-limit group (deduped by id). Discard = unequip + city/faction discard.
5. `hasEquipRestrictions`: always `[false, ""]` for these limits now — reaction handles all four cases.

## WHY separate OffHand from type counts
Card text: OffHands do not count against one Armor/Weapon limit; separate "limit one Offhand". So an OffHand Weapon + normal Weapon is legal (no reaction). Two OffHands is not (reaction).

## WHY reaction not pre-equip block
Player should complete the equip (pay, resolve equip events), then choose which of the two to keep. Blocking before equip prevented legal "replace" play.

## WHY ControllerId not event.playerId
Character owner manages their attachment loadout. Usually same as equipping player; ControllerId is correct if they ever diverge.

## Gotcha
reactionId must be `discard_{id}` not `discardAttachment_{id}` — explode on `_` would split the latter wrong (same pitfall as any camelCase_id pattern with embedded underscores).

## Unfinished / watch
- If somehow over by >1 on same type (3 weapons), only one discard per reaction fire; equipping one-at-a-time normally only goes to 2. No re-queue after discard (CrewCapLimit also doesn't re-queue). If both a type limit AND OffHand are over from one equip (nearly impossible), one discard may leave the other over — no re-queue; extremely edge.
- UI availability filters that called hasEquipRestrictions will now allow same-type and second-OffHand equips (desired).
- `hasEquipRestrictions` is now a stub; kept for call sites / future restrictions.
