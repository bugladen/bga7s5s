# Does Reaction_05Cooper react to Stranahan (_02022) challenge wound?

## Answer (before)
**No.** `_02022` omitted `abilityId`; Cooper/Cascade/Spaulders early-return on empty.

## Fix (fill the gap)
1. **`_02022`**: pass `abilityId = (string) $this->Id` — Joern `_03015` convention for card-level Forced/passives.
2. **`Reaction_05Cooper` / `Reaction_02059` / `Reaction_04053`**: when `getAbilityById` misses but `abilityId !== ''`, fall back to `source.ControllerId` opponent check (Kaspar shape). WHY: `getAbilityById` only finds Action/Reaction/Technique/Maneuver composites; Forced text on the card class is not one of those. Prefer composite when present (ability owner can differ from source in edge cases); fallback for card Id tagging.

## WHY not invent a dummy Ability class on Stranahan
Heavier than the established Joern tag + one gate fallback shared by the whole Ignore-wound family. Kaspar already treated non-empty abilityId + source ControllerId as "opponent's ability" without getAbilityById.

## Other emitters tagged (same sweep)
Card-class Forced wounds that still omitted abilityId — all now `(string) $this->Id`:
- `_01021` Legion's Caress (important: CanEquipToOpponents)
- `_01085` Porté Travel Forced sorcerer wound
- `_01169` Not Today
- `_03021` Cornered intervene wound (lives on card handleEvent, not Action_03021)
- `_04cd19` Blood in the Water (ControllerId 0 → Cascade/Cooper still skip opponent gate; tag is for abilityId != '' hygiene)
- `_03cd05` Devil Jonah's Bones equip Forced

Already correct before this: `_03015`, `_03061`, `_04050`, `_04cd14`, `_02022`.

## Docs
pattern-d / checklist 104 / SKILL table updated so future agents don't reintroduce the getAbilityById-only gate.

## Left alone
`Action_01012` performer cost wound still omits abilityId — it's a cost half next to a tagged target wound; own-controller gates already skip Cascade/Cooper even if tagged. Not part of the card-level Forced sweep.

## Safety: do card-Id abilityIds break getAbilityById callers?
**No.** `getAbilityById(cardId)` returns null — same as Joern `_03015` already did. Wound/ability consumers either:
- null-check / `instanceof` (UL 01014, Angeline 03006, Cross 02016, Cesca 01008, Hexenjagd 01053) → fail closed, skip (correct: Forced ≠ targeting composite)
- Cooper/Cascade/Spaulders → composite first, ControllerId fallback we added
- Kaspar → never calls getAbilityById; `abilityId != ''` + source ControllerId (benefits from tags)
- Action_01071 → Technique/Maneuver lookup then source-controller fallback

Existing Action/Technique/Maneuver emitters still pass composite Ids; Prefer-composite path unchanged.
