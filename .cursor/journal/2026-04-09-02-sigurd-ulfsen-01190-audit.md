# Sigurd Ulfsen (01190) Audit

## Card Text
> Traits: Mercenary, Pirate, Vesten
> **Negotiable** (You may parley when paying for this card.)
> Sigurd's [Combat] cannot be increased and he cannot issue challenges.
> While Sigurd is en garde, he must be the target of enemy challenges at this location.

## Verdict: Three Fixes Applied

### Fix 1: `removeAttachment` phantom subtraction bug

The existing `addAttachment` override correctly caps combat at base when a positive modifier is added. But there was no `removeAttachment` override. When a weapon with CombatModifier=+1 is equipped then later removed:

1. Add: combat = 2+1=3 → cap fires → combat=2 ✓
2. Remove: parent does combat = 2-1=1 → BUG, should be 2

The parent subtracts the modifier that was never actually applied. Added `removeAttachment` with a `recalculateCappedCombat` helper that rebuilds combat from scratch: base + remaining modifiers → locked values → `min(result, base)`.

WHY recalculate from scratch instead of just capping: The running-tally approach breaks with any combination of capped positive modifiers and legitimate negative modifiers. Example: add +1 (capped to 2), add -1 (combat=1), remove the +1 → parent makes it 0 (wrong, should be 1). Recalculating from scratch is the only approach that handles all permutations correctly.

WHY two separate loops (modifiers then locks): Matches the parent's `addAttachment`+`setLockedValues` ordering — all modifiers are accumulated first, then CombatLocked values override. Combining them in one loop produces different results when both exist on remaining attachments.

### Fix 2: `handleEvent` for EventCharacterCombatModified

The addAttachment override only catches attachment-based combat increases. But `EventCharacterCombatModified` is a separate path — EventHub directly sets `ModifiedCombat = max(0, NewCombat)` without any Sigurd-specific guard. Compare to how `DashedInfluence` has a guard in EventHub that skips influence modification entirely.

Currently no cards target Sigurd specifically with this event (Rena _01040 and Guillén _01064 only target themselves), but it's a defensive gap. Added `handleEvent` that caps combat back to base after EventHub processes the event.

WHY this works: `EventCharacterCombatModified` has `runEventHubAfterCards = false` (inherited default from Event), so EventHub runs first (sets the value), then card handleEvent runs (Sigurd caps it). The ordering is correct.

### Fix 3: Deprecated `\BgaUserException`

Replaced with `Bga\GameFramework\UserException` per established convention (see 01185 journal entry).

## What checks out

- **Negotiable**: `$this->Negotiable = true` ✓
- **Cannot issue challenges**: `canChallenge()` returns `false` ✓
- **Must be target of enemy challenges when en garde**: `eventCheck` with `EventChallengeIssued` — correctly checks all four conditions (enemy challenger, Sigurd not already the target, same location, Sigurd not engaged) ✓
- **DashedInfluence**: `$this->DashedInfluence = true` with `Influence = 0` ✓
- **Stats**: Resolve=5, Combat=2, Finesse=3, WealthCost=4, CityCardNumber=14 — all set correctly ✓
