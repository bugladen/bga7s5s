# Ravenna Destine (01201) Audit

## Card Text
> Traits: Mercenary, Sorcerer, Strega, Vodacce
> Negotiable (You may parley when paying for this card.)
> **Sorcerer City Action:** Wound Ravenna • Draw a card.

## Verdict: Minor Fix Applied

### Fix: Duplicate `setUsed` call
`$this->setUsed($event->theah, true)` was called twice in `handleEvent` — once before any events were queued (line 53) and again after (line 67). Removed the second call. Functionally harmless but redundant.

### Everything else checks out

**Availability (`isAvailableToPlayer`):**
- Parent chain: CardAction checks owner belongs to player, checks `!Used` ✓
- `isControlled()` — Ravenna must be in play ✓
- `hasTrait("Sorcerer")` — required for sorcerer action (can be removed by other cards) ✓
- `cardInCity($ravenna)` — city action requirement ✓

**No engagement required:** The card text says "Wound Ravenna" not "Engage Ravenna". The wound IS the cost. Confirmed by comparing with other CharacterAction + ISorcererAbility cards (e.g., Action_01008) which also skip engagement when the card text doesn't call for it.

**Execution order:**
1. Notify → setUsed → SorceryStart → Wound(1) → DrawCard → resetPassCount → ActionResolved → SorceryPlayed ✓

**Double `eventCheck` on wound:** The explicit `eventCheck` before `queueEvent` results in it running twice (since `queueEvent` calls `eventCheck` internally). This is a widespread codebase pattern seen in Maneuver_01055, Action_01046a, Action_01194, Maneuver_01079, Action_01068 — not specific to this card. Leaving as-is.

**`Negotiable = true`** properly set in constructor ✓
