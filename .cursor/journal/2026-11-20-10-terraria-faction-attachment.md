# _05Terraria Cloud Bottle

Finishing CAD FactionAttachment via create-faction-attachment skill. User: no clienttranslate / translate / _().

## Orientation
Prior today: Pocketknife Covert Technique + Gambling Maneuver. Terraria Text:
1. This card has -1 cost while you control an Academic.
2. Covert Reaction: When the equipped character is targeted, destroy this card • Cancel the effects.

Art (05Terraria.v5.jpg): WealthCost 1, Riposte 0 / Parry 2 / Thrust +1, traits Alquimia/Invention. CardNumber stub 25.

## Text → shapes
1. Self equip discount while controlling Academic → override `getEquipDiscount` on the attachment (hand cards are in `$theah->cards`). Gate `$attachment->Id == $this->Id` + `!$this->isAttached()` so Character.php's shadowed-attachment loop cannot false-discount other equips while Terraria is already equipped.
2. Covert Reaction cancel-on-targeted → Pattern D + Covert uncontrolled gate. Mirror UL `Reaction_01032` cancel-first on `EventCharacterTargeted` (+ ChallengeIssued defender fallback for hub-exit challenges that skip CharacterTargeted). Cost = destroy this card (unequip + `createAttachmentDiscardedFromPlayEvent` asEffect) — not wealth / ICancelReaction. No GameState.

## WHY decisions
- Academic discount is NOT Risk Pattern E.2 (`getActionFromHandDiscount`) — this is an attachment equip cost paid via `getEquipDiscount`.
- `!$this->isAttached()` is mandatory: Character::getEquipDiscount shadows `$attachment` with each equipped attachment and would call Terraria.getEquipDiscount(…, Terraria) on every later equip.
- Opposing initiator gate like UL — printed text omits "opponent" but canceling your own targeting is not the intent.
- Destroy-before-cost-sweep order: Hexenjagd `deleteEventsTargetingCard` then unequip/discard so the cost events are not swept.
- `CHALLENGE_CANCELLED` only for in-flight challenge target pick (UL clearEvents) — not every cancel.
- Invention missing from TraitNames — added alphabetically after Invigorant.
- ChallengeIssued path kept even though primary tests use Torvo CharacterTargeted — hub-exit challenges still need it (FrameworkActionsTrait WHY).

## Shipped
- `_05Terraria` + `Reaction_05Terraria`
- TraitNames `Invention`
- Skill: shape rows + pattern-d cancel-on-targeted + references/compose/checklist/helpers
- `Card_05Terraria_Test` — 9 pass

## Unfinished / watch
- Did not unit-test ChallengeIssued fallback (needs BasicChallenge registered in FakeGame or CHOSEN_ACTION technique overwrite). Live path mirrors UL; add if a hub-exit challenge misses Terraria in play.
- Character.php getEquipDiscount shadow bug is pre-existing — our gate works around it; fixing Character would help future self-discount attachments.

## Follow-up (same day)
Bleed Out miss: Action_01160 never emitted CharacterTargeted — fixed in `2026-11-20-13-bleedout-terraria-targeted.md`. Terraria reaction itself unchanged.
