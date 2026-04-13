# Soline el Gato (01089) Audit

## Card Text
> Traits: Leader, Pirate, Scoundrel, Castille
> Your adversaries at Soline's location have -1 [Finesse].
> City Reaction: After an Action resolves • Move Soline to an adjacent City location.

## Key Clarification from Eddie
"Adversary" means **duel opponent only**. This is a general game term, not specific to this card — anywhere "adversary" appears in card text, it refers to the duel opponent. So the -1 Finesse applying exclusively during duels is correct — it does not need to be a continuous location-based aura outside duels.

## What checks out

### Passive -1 Finesse (duel-scoped)
- **EventDuelStarted**: If a friendly character (same controller as Soline) is at Soline's location, debuffs the opponent. Since both duelists are at the same location, checking either one for being at Soline's location is sufficient. ✓
- **EventDuelEnd**: Restores finesse on AffectedCharacterId. Skips restore if character is in discard/locker (destroyed during duel). Clears AffectedCharacterId either way. ✓
- **EventDefenderSwapped**: When challenger is friendly at Soline's location, swaps debuff from old defender to new defender. When defender is friendly (challenger was debuffed), correctly leaves debuff on adversary challenger since they're still the opponent. ✓
- **EventChallengerSwapped**: Mirror of defender swap logic. When defender is friendly, swaps debuff from old to new challenger. ✓
- **Soline herself dueling**: Code checks `$this->Location` and `$this->ControllerId` — Soline is always at her own location and controlled by herself, so her duel opponents correctly get debuffed. ✓

### City Reaction (move after action)
- Triggers on **any** `EventActionResolved` (not filtered by player) — matches "After an Action resolves" ✓
- `cardInCity($owner)` gate — "City Reaction" only fires when Soline is in the city ✓
- `getAdjacentCityLocations($owner->Location, $includeHome = false)` — offers only city locations, no home ✓
- `createCardMovingEvent` with `engage=false` — doesn't force engagement on move ✓
- `setUsed` after performing — standard once-per-cycle reaction usage ✓
- Pass option available ✓

## Fix Applied
- Typo in Reaction_01089 name: "Adjecent" → "Adjacent"

## No functional bugs found
